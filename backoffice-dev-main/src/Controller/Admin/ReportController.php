<?php

namespace App\Controller\Admin;

use App\Entity\Enum\ReportStatus;
use App\Entity\Report;
use App\Form\Type\DeleteType;
use App\Form\Type\MangopayReportRequestType;
use App\Repository\ReportRepository;
use App\Service\MangopayReportService;
use App\Service\MangopayWalletService;
use App\Service\ReportStorageService;
use App\Service\Util\ExportHelper;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/reports')]
#[IsGranted('ROLE_OPERATIONS')]
class ReportController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private ReportRepository $reportRepository,
        private MangopayWalletService $mangopayWalletService,
        private MangopayReportService $mangopayReportService,
        private ReportStorageService $reportStorageService,
    ) {}

    #[Route('', name: 'admin_reports_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // $this->logger->debug('Showing reports');
        $filters = [
            'perPage' => 10,
            'page' => $request->query->get('page', 1),
        ];
        $queryBuilder = $this->reportRepository->createQueryBuilder('r')->addOrderBy(
            'r.createdAt',
            'DESC',
        );
        $adapter = new QueryAdapter($queryBuilder);
        $results = new Pagerfanta($adapter);
        $results->setMaxPerPage($filters['perPage'] ?? 10);
        $results->setCurrentPage(min($filters['page'] ?? 1, $results->getNbPages()));
        return $this->render('admin/pages/reports/index.html.twig', [
            'results' => $results ?? [],
        ]);
    }

    #[Route(
        '/create/mangopay',
        name: 'admin_reports_create_mangopay',
        methods: ['GET', 'POST'],
    )]
    public function createMangopayReport(Request $request): Response
    {
        $callbackUrl = $this->generateUrl(
            'webhooks_mangopay_report',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
        $reportRequest =
            $this->mangopayReportService->createReportRequest($callbackUrl);
        $form = $this->createForm(MangopayReportRequestType::class, $reportRequest);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->logger->debug('Creating new Mangopay report request');
                // Ensure array is non-associative (i.e. a list, not a dict) so Mangopay can process properly
                $reportRequest->Columns = array_values($reportRequest->Columns);
                if ($form->get('disableAutoSave')->getData()) {
                    // Clear the default callback URL if autosave has been explicitly disabled
                    $this->logger->info(
                        'Removing report callback url to disable autosave',
                    );
                    $reportRequest->CallbackURL = null;
                }
                $reportRequest = $this->mangopayWalletService
                    ->mangopayApi
                    ->Reports
                    ->create($reportRequest);
                $this->addFlash(
                    'success',
                    'Mangopay report request successfully submitted.',
                );
                $report =
                    $this->mangopayReportService->createReportRecord($reportRequest);
                $this->entityManager->persist($report);
                $this->entityManager->flush();
                return $this->redirectToRoute(
                    'admin_reports_view',
                    ['id' => $report->getId()],
                    Response::HTTP_SEE_OTHER,
                );
            } catch (\MangoPay\Libraries\ResponseException $e) {
                $this->logger->error('Error creating Mangopay reports', [
                    $e->GetCode(),
                    $e->getMessage(),
                    $e->GetErrorDetails(),
                ]);
                $this->addFlash(
                    'error',
                    'Error creating Mangopay report ' . $e->getMessage() . '. '
                        . $e->GetErrorDetails(),
                );
            } catch (\Exception $e) {
                $this->logger->error('Error creating Mangopay report', [$e->getMessage()]);
                $this->addFlash(
                    'error',
                    'Error creating Mangopay report ' . $e->getMessage(),
                );
            }
        }
        return $this->render('admin/pages/reports/mangopay/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        '/{id}',
        name: 'admin_reports_view',
        methods: ['GET'],
        requirements: ['id' => '\d+'],
    )]
    public function view(Report $report): Response
    {
        // $this->logger->debug('Showing report');
        $form = $this->createForm(DeleteType::class, null, [
            'objectName' => 'Report',
            'action' => $this->generateUrl('admin_reports_delete', ['id' => $report->getId()]),
        ]);
        return $this->render('admin/pages/reports/view.html.twig', [
            'form' => $form,
            'report' => $report,
        ]);
    }

    #[Route(
        '/{id}/download',
        name: 'admin_reports_download',
        methods: ['GET'],
        requirements: ['id' => '\d+'],
    )]
    public function download(Report $report): Response
    {
        try {
            $response = new Response($this->reportStorageService->download($report));
            $response->headers->set('Content-Encoding', 'none');
            $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');

            $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, ExportHelper::generateFileName(
                "report_{$report->getId()}_{$report->getResourceId()}_",
                'csv',
            ));

            $response->headers->set('Content-Disposition', $disposition);
            $response->headers->set('Content-Description', 'File Transfer');
            return $response;
        } catch (\Exception $e) {
            $this->logger->error('Unable to download report', [$e->getMessage()]);
            $this->addFlash('error', "Unable to download report: {$e->getMessage()}");
        }
        return $this->redirectToRoute(
            'admin_reports_view',
            ['id' => $report->getId()],
            Response::HTTP_SEE_OTHER,
        );
    }

    #[Route(
        '/{id}/check-for-updates',
        name: 'admin_reports_check_update',
        methods: ['GET'],
        requirements: ['id' => '\d+'],
    )]
    public function checkUpdate(Request $request, Report $report): Response
    {
        if (empty($report->getUrl()) || $request->query->get('force', false)) {
            // If we are force refreshing, clear out the current url for regeneration
            if ($request->query->get('force', false)) {
                $report->setUrl(null);
            }
            try {
                if (Report::ORIGIN_MANGOPAY == $report->getOrigin()) {
                    if ($report->getReferenceId()) {
                        $mangopayReport = $this->mangopayWalletService->getReport($report->getReferenceId());
                        if (
                            $mangopayReport->Status
                            == \MangoPay\ReportStatus::ReadyForDownload
                        ) {
                            $this->mangopayReportService->storeReport(
                                $report,
                                $mangopayReport,
                            );
                            $report->setStatus(ReportStatus::Available);
                            $this->entityManager->flush();
                        } elseif (
                            $mangopayReport->Status == \MangoPay\ReportStatus::Failed
                        ) {
                            $this->addFlash(
                                'warning',
                                "Mangopay origin report {$mangopayReport->Status}. Try again or check Mangopay for any ongoing issues.",
                            );
                            $report->setStatus(ReportStatus::Cancelled);
                            $this->entityManager->flush();
                        } else {
                            $this->addFlash(
                                'notice',
                                "Report not yet ready. Current status: {$mangopayReport->Status}",
                            );
                        }
                    } else {
                        $this->addFlash(
                            'warning',
                            'Mangopay origin report is missing reference id to check for updates',
                        );
                    }
                }
            } catch (\MangoPay\Libraries\ResponseException $e) {
                $this->logger->error('Error retrieving Mangopay report', [
                    $e->GetCode(),
                    $e->getMessage(),
                    $e->GetErrorDetails(),
                ]);
                $this->addFlash(
                    'error',
                    'Error retrieving Mangopay report ' . $e->getMessage() . '. '
                        . $e->GetErrorDetails(),
                );
            } catch (\Exception $e) {
                $this->logger->error('Unable to store report', [$e->getMessage()]);
                $this->addFlash('error', 'Unable to store report ' . $e->getMessage());
            }
        } else {
            $this->addFlash(
                'warning',
                'Report already has a download url. No changes made.',
            );
        }
        return $this->redirectToRoute(
            'admin_reports_view',
            ['id' => $report->getId()],
            Response::HTTP_SEE_OTHER,
        );
    }

    #[Route(
        '/{id}/delete',
        name: 'admin_reports_delete',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function delete(Report $report): Response
    {
        try {
            if ($report->getUrl()) {
                $this->reportStorageService->delete($report);
            }
        } catch (\Exception $e) {
            $this->logger->error('Unable to delete report from storage', [$e->getMessage()]);
            $this->addFlash(
                'error',
                "Unable to delete report from storage: {$e->getMessage()}",
            );
        }
        $this->entityManager->remove($report);
        $this->entityManager->flush();
        $this->addFlash('success', 'Successfully deleted report');
        return $this->redirectToRoute(
            'admin_reports_index',
            [],
            Response::HTTP_SEE_OTHER,
        );
    }
}
