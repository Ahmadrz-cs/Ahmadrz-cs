<?php

namespace App\Controller\Admin;

use App\Entity\Enum\ReportSetType;
use App\Entity\ReportSet;
use App\Form\Type\MangopayReportRequestType;
use App\Form\Type\MangopayReportSetConfigType;
use App\Form\Type\ReportMergeConfigType;
use App\Repository\ReportRepository;
use App\Repository\ReportSetRepository;
use App\Service\MangopayReportService;
use App\Service\MangopayWalletService;
use App\Service\MonthEndService;
use App\Service\ReportStorageService;
use App\Service\Util\ExportHelper;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/reports/mangopay')]
#[IsGranted('ROLE_OPERATIONS')]
class MangopayReportController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private ReportRepository $reportRepository,
        private ReportSetRepository $reportSetRepository,
        private MonthEndService $monthEndService,
        private MangopayWalletService $mangopayWalletService,
        private MangopayReportService $mangopayReportService,
        private ReportStorageService $reportStorageService,
    ) {}

    #[Route('', name: 'admin_reports_mangopay_index', methods: ['GET'])]
    public function mangopayReportList(Request $request): Response
    {
        // $this->logger->debug('Showing Mangopay report requests');
        try {
            $pagination = new \MangoPay\Pagination($request->query->get('page', 1), 10);
            $sorting = new \MangoPay\Sorting();
            $sorting->AddField('CreationDate', 'DESC');
            $results = $this->mangopayWalletService->mangopayApi->Reports->GetAll(
                $pagination,
                null,
                $sorting,
            );
        } catch (\MangoPay\Libraries\ResponseException $e) {
            $this->logger->error('Error retrieving Mangopay reports', [
                $e->GetCode(),
                $e->getMessage(),
                $e->GetErrorDetails(),
            ]);
            $this->addFlash(
                'error',
                'Error loading Mangopay reports ' . $e->getMessage() . '. '
                    . $e->GetErrorDetails(),
            );
        } catch (\Exception $e) {
            $this->logger->error('Error retrieving Mangopay reports', [$e->getMessage()]);
            $this->addFlash(
                'error',
                'Error loading Mangopay reports ' . $e->getMessage(),
            );
        }
        // Clamp pagination for rendering
        $pagination->Page = min($pagination->Page ?? 1, $pagination->TotalPages);
        return $this->render('admin/pages/reports/mangopay/list.html.twig', [
            'results' => $results ?? [],
            'pagination' => $pagination,
        ]);
    }

    #[Route('/sets', name: 'admin_report_mangopay_sets', methods: ['GET'])]
    public function reportSets(Request $request): Response
    {
        // $this->logger->debug('Showing report sets');
        $filters = [
            'perPage' => 10,
            'page' => $request->query->get('page', 1),
        ];
        $queryBuilder = $this->reportSetRepository
            ->createQueryBuilder('rs')
            ->andWhere('rs.reportSetType = :reportSetType')
            ->addOrderBy('rs.createdAt', 'DESC')
            ->setParameter('reportSetType', ReportSetType::WalletTransaction->value);
        $adapter = new QueryAdapter($queryBuilder);
        $results = new Pagerfanta($adapter);
        $results->setMaxPerPage($filters['perPage'] ?? 10);
        $results->setCurrentPage(min($filters['page'] ?? 1, $results->getNbPages()));
        return $this->render('admin/pages/reports/mangopay/list_sets.html.twig', [
            'results' => $results ?? [],
        ]);
    }

    #[Route(
        '/transaction-report/create',
        name: 'admin_reports_mangopay_set_create',
        methods: ['GET', 'POST'],
    )]
    #[Route(
        '/transaction-report/{id}/edit',
        name: 'admin_reports_mangopay_set_edit',
        methods: ['GET', 'POST'],
    )]
    public function transactionReportCreate(
        Request $request,
        ?ReportSet $reportSet = null,
    ): Response {
        $isSetup = false;
        if (is_null($reportSet)) {
            $reportSet = new ReportSet();
            $reportSet->setReportSetType(ReportSetType::WalletTransaction);
            $isSetup = true;
        }
        $form = $this->createForm(MangopayReportSetConfigType::class, $reportSet);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($reportSet);
            $this->entityManager->flush();
            $this->addFlash(
                'success',
                'Transaction report builder configuration successfully saved.',
            );
            $redirectToRoute = $isSetup
                ? 'admin_reports_mangopay_transactions_wallet'
                : 'admin_reports_mangopay_set_view';
            return $this->redirectToRoute(
                $redirectToRoute,
                ['id' => $reportSet->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        return $this->render('admin/pages/reports/mangopay/report_set_config.html.twig', [
            'form' => $form,
            'reportSet' => $reportSet,
        ]);
    }

    #[Route(
        '/transaction-report/{id}',
        name: 'admin_reports_mangopay_set_view',
        methods: ['GET'],
    )]
    public function transactionReport(Request $request, ReportSet $reportSet): Response
    {
        if (
            is_null($reportSet->getPeriodStart()) || is_null($reportSet->getPeriodEnd())
        ) {
            $this->addFlash(
                'warning',
                'The start and end periods must be configured to use the builder.',
            );
            $dateCheckpoints = [];
        } else {
            $dateCheckpoints = $this->mangopayReportService->chunkDateRange(
                $reportSet->getPeriodStart(),
                $reportSet->getPeriodEnd(),
            );
        }
        $mergedReports = $this->mangopayReportService->getMergedReports($reportSet);

        return $this->render('admin/pages/reports/mangopay/report_set.html.twig', [
            'dateCheckpoints' => $dateCheckpoints,
            'reportSet' => $reportSet,
            'mergedReport' => array_shift($mergedReports),
        ]);
    }

    #[Route(
        '/transaction-report/{id}/wallet',
        name: 'admin_reports_mangopay_transactions_wallet',
        methods: ['GET', 'POST'],
    )]
    public function configWallet(Request $request, ReportSet $reportSet): Response
    {
        if (is_null($reportSet->getAsset())) {
            $this->addFlash(
                'warning',
                'No asset configured. Link an asset before choosing a wallet',
            );
            return $this->redirectToRoute(
                'admin_reports_mangopay_set_view',
                ['id' => $reportSet->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        if (
            is_null($reportSet->getPeriodStart()) || is_null($reportSet->getPeriodEnd())
        ) {
            $this->addFlash(
                'warning',
                'The start and end periods must be configured to use the builder.',
            );
            return $this->redirectToRoute(
                'admin_reports_mangopay_set_view',
                ['id' => $reportSet->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        $dateCheckpoints = $this->mangopayReportService->chunkDateRange(
            $reportSet->getPeriodStart(),
            $reportSet->getPeriodEnd(),
        );
        $walletChoices = $this->monthEndService->getAssetWalletChoices($reportSet->getAsset());
        $defaultChoice = ['walletId' => $walletChoices['settlement'] ?? null];
        $form = $this
            ->createFormBuilder($defaultChoice)
            ->add('walletId', ChoiceType::class, [
                'choices' => $walletChoices,
                'choice_label' => function ($choice, $key, $value) {
                    return ucfirst($key) . " ({$value})";
                },
                'help' => 'Select the wallet to create a transaction report for. This defaults to the main/settlement wallet if available.',
            ])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            return $this->redirectToRoute(
                'admin_reports_mangopay_transactions_report_config',
                [
                    'id' => $reportSet->getId(),
                    'walletId' => $form->getData()['walletId'],
                ],
                Response::HTTP_SEE_OTHER,
            );
        }
        return $this->render('admin/pages/reports/mangopay/report_set_wallet.html.twig', [
            'dateCheckpoints' => $dateCheckpoints,
            'form' => $form,
            'reportSet' => $reportSet,
            'walletChoices' => $walletChoices,
        ]);
    }

    #[Route(
        '/transaction-report/{id}/report-config',
        name: 'admin_reports_mangopay_transactions_report_config',
        methods: ['GET', 'POST'],
    )]
    public function configReport(Request $request, ReportSet $reportSet): Response
    {
        if (is_null($reportSet->getAsset())) {
            $this->addFlash(
                'warning',
                'No asset configured. Link an asset before choosing a wallet',
            );
            return $this->redirectToRoute(
                'admin_reports_mangopay_set_view',
                ['id' => $reportSet->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        if (
            is_null($reportSet->getPeriodStart()) || is_null($reportSet->getPeriodEnd())
        ) {
            $this->addFlash(
                'warning',
                'The start and end periods must be configured to use the builder.',
            );
            return $this->redirectToRoute(
                'admin_reports_mangopay_set_view',
                ['id' => $reportSet->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        $dateCheckpoints = $this->mangopayReportService->chunkDateRange(
            $reportSet->getPeriodStart(),
            $reportSet->getPeriodEnd(),
        );
        $callbackUrl = $this->generateUrl(
            'webhooks_mangopay_report',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
        $reportRequestTemplate =
            $this->mangopayReportService->createReportRequest($callbackUrl);
        $reportRequestTemplate->Filters->WalletId = $request->query->get('walletId');
        $form = $this->createForm(
            MangopayReportRequestType::class,
            $reportRequestTemplate,
            ['showAllFields' => false],
        );
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if ($form->get('disableAutoSave')->getData()) {
                    // Clear the default callback URL if autosave has been explicitly disabled
                    $this->logger->info(
                        'Removing report callback url to disable autosave',
                    );
                    $reportRequestTemplate->CallbackURL = null;
                }
                $this->logger->debug('Creating multiple Mangopay report requests');
                $reportRequestsToSend = $this->mangopayReportService->generateReportRequestsWithDateCheckpoints(
                    $this->mangopayReportService->prepareMergeSafeTemplate(
                        $reportRequestTemplate,
                    ),
                    $dateCheckpoints,
                );
                foreach ($reportRequestsToSend as $reportRequest) {
                    $reportRequest = $this->mangopayWalletService
                        ->mangopayApi
                        ->Reports
                        ->create($reportRequest);
                    $this->logger->debug(
                        "Report id {$reportRequest->Id} columns:",
                        $reportRequest->Columns,
                    );
                    $report =
                        $this->mangopayReportService->createReportRecord(
                            $reportRequest,
                        );
                    $reportSet->addReport($report);
                    $this->entityManager->persist($report);
                }
                $this->addFlash(
                    'success',
                    'Mangopay report requests successfully submitted.',
                );
                $reportSet->setProgress(MangopayReportService::PROGRESS_REPORTS_STORED);
                $this->entityManager->flush();

                return $this->redirectToRoute(
                    'admin_reports_mangopay_set_view',
                    ['id' => $reportSet->getId()],
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
        return $this->render('admin/pages/reports/mangopay/report_set_report_config.html.twig', [
            'dateCheckpoints' => $dateCheckpoints,
            'form' => $form,
            'reportSet' => $reportSet,
            'walletChoices' => $this->monthEndService->getAssetWalletChoices($reportSet->getAsset()),
        ]);
    }

    #[Route(
        '/transaction-report/{id}/merge',
        name: 'admin_reports_mangopay_transactions_merge',
        methods: ['GET', 'POST'],
    )]
    public function mergeReportSet(Request $request, ReportSet $reportSet): Response
    {
        if ($reportSet->getReports()->isEmpty()) {
            $this->addFlash('warning', 'No reports to merge or process');
            return $this->redirectToRoute(
                'admin_reports_mangopay_set_view',
                ['id' => $reportSet->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        $defaultConfig = [
            'directionalAmount' => true,
            'runningBalance' => true,
        ];
        $form = $this->createForm(ReportMergeConfigType::class, $defaultConfig);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->logger->debug('Merging and processing report set', [$reportSet->getId()]);
                $reportsToMerge =
                    $this->mangopayReportService->getMergeableReports($reportSet);
                if (empty($reportsToMerge)) {
                    $this->addFlash('warning', 'No available reports to merge');
                    return $this->redirectToRoute(
                        'admin_reports_mangopay_set_view',
                        ['id' => $reportSet->getId()],
                        Response::HTTP_SEE_OTHER,
                    );
                }
                $report = $this->mangopayReportService->createMergedReportRecord(
                    $reportSet,
                    $reportsToMerge[0]->getResourceId(),
                );
                // Store this placeholder record in the database to generate its id
                $reportSet->addReport($report);
                $this->entityManager->persist($report);
                $this->entityManager->flush();

                // Do the actual merge and processing
                $report = $this->mangopayReportService->mergeAndProcessReports(
                    $report,
                    $reportsToMerge,
                    $form->getData()['directionalAmount'],
                    $form->getData()['runningBalance'],
                );

                // $reportSet->setProgress(MangopayReportService::PROGRESS_MERGED);
                $reportSet->setProgress(ReportSet::PROGRESS_END);
                $this->entityManager->flush();
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Could not merge reports. ' . $e->getMessage(),
                );
                $this->logger->error('Could not merge reports', [$e->getMessage()]);
            }

            return $this->redirectToRoute(
                'admin_reports_mangopay_set_view',
                ['id' => $reportSet->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        return $this->render('admin/pages/reports/mangopay/report_set_merge.html.twig', [
            'form' => $form,
            'reportSet' => $reportSet,
        ]);
    }

    #[Route('/merger', name: 'admin_reports_mangopay_merger', methods: ['GET', 'POST'])]
    public function merger(Request $request): Response
    {
        $this->logger->debug('Merger tool for Mangopay reports');
        $form = $this
            ->createFormBuilder()
            ->add('reportUrl1', UrlType::class)
            ->add('reportUrl2', UrlType::class)
            ->add('submit', SubmitType::class, ['label' => 'Merge Reports'])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $csv = $this->mangopayReportService->mergeCsvFromUrls(
                    $form->getData()['reportUrl1'],
                    $form->getData()['reportUrl2'],
                );
                $this->addFlash('success', 'Reports successfully merged');

                // Chunk and stream file response
                // https://csv.thephpleague.com/9.0/connections/output/#using-a-response-object-symfony-laravel-psr-7-etc
                $flush_threshold = 1000; //the flush value should depend on your CSV size.
                $content_callback = function () use ($csv, $flush_threshold) {
                    foreach ($csv->chunk(1024) as $offset => $chunk) {
                        echo $chunk;
                        if (($offset % $flush_threshold) === 0) {
                            flush();
                        }
                    }
                };
                $response = new StreamedResponse();
                $response->headers->set('Content-Encoding', 'none');
                $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');

                $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, ExportHelper::generateFileName(
                    'merged_mangopay_report',
                    'csv',
                ));

                $response->headers->set('Content-Disposition', $disposition);
                $response->headers->set('Content-Description', 'File Transfer');
                $response->setCallback($content_callback);
                return $response;
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Could not merge reports. ' . $e->getMessage(),
                );
                $this->logger->error('Could not merge reports', [$e->getMessage()]);
            }
        }
        return $this->render('admin/pages/reports/mangopay/merger.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_reports_mangopay_view', methods: ['GET'])]
    public function mangopayReportView(Request $request, string $id): Response
    {
        // $this->logger->debug('Showing Mangopay report request');
        try {
            $report = $this->mangopayWalletService->mangopayApi->Reports->Get($id);
        } catch (\MangoPay\Libraries\ResponseException $e) {
            $this->logger->error('Error retrieving Mangopay reports', [
                $e->GetCode(),
                $e->getMessage(),
                $e->GetErrorDetails(),
            ]);
            $this->addFlash(
                'error',
                'Error loading Mangopay report ' . $e->getMessage() . '. '
                    . $e->GetErrorDetails(),
            );
        } catch (\Exception $e) {
            $this->logger->error('Error retrieving Mangopay report', [$e->getMessage()]);
            $this->addFlash(
                'error',
                'Error loading Mangopay report ' . $e->getMessage(),
            );
        }
        return $this->render('admin/pages/reports/mangopay/view.html.twig', [
            'report' => $report ?? null,
        ]);
    }

    #[Route('/{id}/store', name: 'admin_reports_mangopay_store', methods: ['GET'])]
    public function mangopayReportStore(Request $request, string $id): Response
    {
        // $this->logger->debug('Showing Mangopay report request');
        // Do we already have a record for this mangopay report
        $report = $this->reportRepository->findOneBy(['referenceId' => $id]);
        if (is_null($report)) {
            try {
                $reportRequest =
                    $this->mangopayWalletService->mangopayApi->Reports->Get($id);
                $report =
                    $this->mangopayReportService->createReportRecord($reportRequest);
                $this->entityManager->persist($report);
                $this->entityManager->flush();
                return $this->redirectToRoute(
                    'admin_reports_check_update',
                    ['id' => $report->getId()],
                    Response::HTTP_SEE_OTHER,
                );
            } catch (\MangoPay\Libraries\ResponseException $e) {
                $this->logger->error('Error retrieving Mangopay reports', [
                    $e->GetCode(),
                    $e->getMessage(),
                    $e->GetErrorDetails(),
                ]);
                $this->addFlash(
                    'error',
                    'Error storing Mangopay report ' . $e->getMessage() . '. '
                        . $e->GetErrorDetails(),
                );
            } catch (\Exception $e) {
                $this->logger->error('Error storing Mangopay report', [$e->getMessage()]);
                $this->addFlash(
                    'error',
                    'Error storing Mangopay report ' . $e->getMessage(),
                );
            }
        } else {
            $this->addFlash('notice', 'This report has already been saved.');
        }
        return $this->redirectToRoute(
            'admin_reports_view',
            ['id' => $report->getId()],
            Response::HTTP_SEE_OTHER,
        );
    }
}
