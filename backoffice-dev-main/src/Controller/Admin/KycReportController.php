<?php

namespace App\Controller\Admin;

use App\Entity\Enum\TaskStatus;
use App\Entity\KycReport;
use App\Entity\User;
use App\Event\Kyc\KycReportCreatedEvent;
use App\Form\Type\KycReportFormType;
use App\Form\Type\QueryKycReportType;
use App\Repository\KycReportRepository;
use App\Service\KycReportService;
use App\Service\MangopayKycService;
use Doctrine\ORM\EntityManagerInterface;
use MangoPay\PersonType;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/kyc-reports')]
class KycReportController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private EventDispatcherInterface $eventDispatcher,
        private KycReportRepository $kycReportRepository,
        private KycReportService $kycReportService,
        private MangopayKycService $mangopayKycService,
    ) {}

    #[Route(path: '', name: 'admin_kyc_report_index')]
    public function indexAction(Request $request)
    {
        // $this->logger->debug('Showing KYC reports');
        $form = $this->createForm(QueryKycReportType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();

            // $this->logger->debug('filters', $filters);
        }
        $results = $this->kycReportRepository->findByWithAssociations(
            $filters ?? [],
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/kyc/reports/index.html.twig', [
            'objects' => $results,
            'form' => $form,
        ]);
    }

    #[Route('/create', name: 'admin_kyc_report_create', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function create(Request $request): Response
    {
        $form = $this->createForm(KycReportFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Shouldn't normally be adding kyc reports in manually
            // Hence this route is locked dowmn to superadmin which nobody should use in prod
            // This route is more useful for development and debugging
            $this->logger->warning('Manual kyc report added from CMS');
            $kycReport = $form->getData();
            $this->entityManager->persist($kycReport);
            $this->entityManager->flush();
            $this->addFlash('success', 'Successfully created Kyc report registration');
            return $this->redirectToRoute('admin_kyc_report_view', [
                'id' => $kycReport->id,
            ]);
        }
        return $this->render('admin/pages/kyc/reports/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        '/review-check/mangopay',
        name: 'admin_kyc_report_review_check_mangopay',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_TECH_OPS')]
    public function reviewCheckMangopay(Request $request): Response
    {
        $this->logger->debug(
            'Kyc Reports - KYC review needed checker for Mangopay downgrades',
        );

        $taskTracker = $this->kycReportService->getTaskTracker();
        if (is_null($taskTracker->getId())) {
            $this->entityManager->persist($taskTracker);
            $this->entityManager->flush();
        }
        $filters = [
            'providerName' => MangopayKycService::PROVIDER_NAME,
            'checkType' => [PersonType::Natural, PersonType::Legal],
            'result' => 'LIGHT',
        ];
        if ($taskTracker->getMetadata()['lastReportId']) {
            $lastCheckedReport = $this->kycReportRepository->find(
                $taskTracker->getMetadata()['lastReportId'],
            );
            if ($lastCheckedReport) {
                $filters['createdAt_gte'] = $lastCheckedReport->createdAt->modify(
                    '+1 second',
                );
            }
        }

        $preview = $this->kycReportRepository->findByWithAssociations(
            $filters,
            [
                'checkedAt' => 'ASC',
            ],
            10,
        );
        $form = $this
            ->createFormBuilder([
                'batchSize' => 1,
            ])
            ->add('batchSize', IntegerType::class)
            ->add('skipToReportId', IntegerType::class, [
                'required' => false,
                'help' => 'Use this to skip to a particular user. Leave this empty to actually run checker. Non-empty will skip all checks until the id given.',
            ])
            ->add('submit', SubmitType::class)
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->getData()['skipToReportId'] !== null) {
                $this->kycReportService->updateTaskTrackerProgress(
                    $taskTracker,
                    $form->getData()['skipToReportId'],
                );
                $this->entityManager->flush();
                $this->addFlash(
                    'success',
                    'Updating last report id to: ' . $form->getData()['skipToReportId'],
                );
                return $this->redirectToRoute('admin_kyc_report_review_check_mangopay');
            }
            /**
             * @var KycReport[] $matches
             */
            $matches = $this->kycReportRepository->findByWithAssociations(
                $filters,
                [
                    'checkedAt' => 'ASC',
                ],
                $form->getData()['batchSize'],
            );
            $this->kycReportService->updateTaskStatusInTracker(
                $taskTracker,
                TaskStatus::Started,
            );
            $currentId = null;
            foreach ($matches as $kycReport) {
                try {
                    // Get the current Mangopay KYC status
                    $latestReport = $this->mangopayKycService->viewReport(
                        $kycReport->subject,
                        $kycReport->subject->getMangoPayUserId(),
                    );
                    if ($latestReport->result == 'LIGHT') {
                        $this->logger->debug('Submitting report event', [
                            'userId' => $kycReport->subject->getId(),
                            'mangopayUserId' =>
                                $kycReport->subject->getMangoPayUserId(),
                        ]);
                        $this->eventDispatcher->dispatch(
                            new KycReportCreatedEvent($kycReport),
                        );
                    } else {
                        $this->logger->debug('Not light, skipping');
                    }
                    $currentId = $kycReport->id;
                } catch (\Throwable $th) {
                    $this->addFlash(
                        'error',
                        'Failed to process kyc report: ' . $th->getMessage(),
                    );
                    $this->logger->error('Unable to retrieve Mangopay user', [
                        'userId' => $kycReport->subject->getId(),
                        'mangopayUserId' => $kycReport->subject->getMangoPayUserId(),
                    ]);
                }
            }
            if ($currentId) {
                $this->kycReportService->updateTaskTrackerProgress(
                    $taskTracker,
                    $currentId,
                );
            }
            $this->entityManager->flush();
            $this->addFlash(
                'success',
                'Successfully checked reports for required reviews. Most recent report: '
                . $currentId,
            );
            return $this->redirectToRoute('admin_kyc_report_review_check_mangopay');
        }
        return $this->render('admin/pages/kyc/reports/review_check_mangopay.html.twig', [
            'form' => $form,
            'preview' => $preview,
            'taskTracker' => $taskTracker,
        ]);
    }

    #[Route(
        '/sync/mangopay/{id}',
        name: 'admin_kyc_report_sync_mangopay',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function syncMangopay(
        Request $request,
        #[MapEntity(id: 'id')] User $user,
    ): Response {
        /**
         * Functionally similar to what the webhook controller for USER_KYC_LIGHT and USER_KYC_REGULAR will trigger
         */
        $redirectToRoute = null;
        $redirectToId = null;
        if (in_array($request->query->get('redirectRoute'), [
            'admin_user_dashboard_kyc',
            'admin_kyc_review_view',
            'admin_kyc_onboarding_review',
            'admin_kyc_vip_review',
            'admin_kyc_recurring_review',
        ]) && $request->query->get('redirectId')) {
            $redirectToRoute = $request->query->get('redirectRoute');
            $redirectToId = $request->query->get('redirectId');
        }
        if ($user->getMangoPayUserId()) {
            try {
                // Get the current Mangopay KYC status
                $kycReport = $this->mangopayKycService->viewReport(
                    $user,
                    $user->getMangoPayUserId(),
                );
                // Check if the most recent similar kyc report exists
                $recentKycReports = $this->kycReportRepository
                    ->buildQueryWithAssociations([
                        'subjectId' => $kycReport->subject->getId(),
                        'providerName' => $kycReport->providerName,
                        'providerReference' => $kycReport->providerReferenceId,
                        'checkType' => $kycReport->checkType,
                    ], ['createdAt' => 'DESC'])
                    ->setMaxResults(1)
                    ->getResult();
                $this->logger->notice('similar reports', $recentKycReports);
                if (
                    empty($recentKycReports)
                    || !$this->kycReportService->isSimilarReport(
                        $recentKycReports[0],
                        $kycReport,
                    )
                ) {
                    $this->entityManager->persist($kycReport);
                    $this->entityManager->flush();
                    $this->addFlash(
                        'success',
                        "Successfully synced Mangopay KYC reports. KycReport created ID#{$kycReport->id}",
                    );
                    $this->logger->debug('Created new KycReport record', [
                        'id' => $kycReport->id,
                    ]);
                    $this->eventDispatcher->dispatch(
                        new KycReportCreatedEvent($kycReport),
                    );
                    if ($redirectToRoute === null && $redirectToId === null) {
                        $redirectToRoute = 'admin_kyc_report_view';
                        $redirectToId = $kycReport->id;
                    }
                } else {
                    $this->addFlash(
                        'success',
                        'Successfully synced Mangopay KYC reports. No new KycReport was needed.',
                    );
                    $this->logger->debug('Mangopay KYC reports synced, no report created', [
                        'userId' => $user->getId(),
                    ]);
                }
            } catch (\Throwable $th) {
                $this->addFlash(
                    'error',
                    'Failed to sync Mangopay KYC reports: ' . $th->getMessage(),
                );
                $this->logger->error('Unable to retrieve Mangopay user', [
                    'userId' => $user->getId(),
                    'mangopayUserId' => $user->getMangoPayUserId(),
                ]);
            }
        } else {
            $this->addFlash('warning', 'User must have a Mangopay account to sync.');
        }
        if ($redirectToRoute && $redirectToId) {
            return $this->redirectToRoute($redirectToRoute, [
                'id' => $redirectToId,
            ]);
        }
        return $this->redirectToRoute('admin_kyc_report_index');
    }

    #[Route('/{id}', name: 'admin_kyc_report_view', methods: ['GET', 'POST'])]
    public function edit(#[MapEntity(id: 'id')] KycReport $kycReport): Response
    {
        return $this->render('admin/pages/kyc/reports/view.html.twig', [
            'kycReport' => $kycReport,
        ]);
    }
}
