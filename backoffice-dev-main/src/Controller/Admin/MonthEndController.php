<?php

namespace App\Controller\Admin;

use App\Entity\AbstractOrder;
use App\Entity\Asset;
use App\Entity\Enum\AssetStatus;
use App\Entity\Enum\GenericOrderType;
use App\Entity\Enum\OrderingDirection;
use App\Entity\Enum\OrderStatus;
use App\Entity\Enum\PaymentType;
use App\Entity\Enum\QueryGrouping;
use App\Entity\Enum\TaskStatus;
use App\Entity\Enum\TaskTrackerType;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TradeStatus;
use App\Entity\Enum\TransferOrderPreset;
use App\Entity\Enum\TransferType;
use App\Entity\Lifecycle\InvestmentLifecycle;
use App\Entity\ShareTrade;
use App\Entity\TransferOrder;
use App\Form\QueryShareTransferOrderForm;
use App\Form\Type\QueryAssetType;
use App\Form\Type\QueryDateRangeType;
use App\Form\Type\QueryMonthendActivityType;
use App\Form\Type\QueryPaymentOrderType;
use App\Form\Type\QueryProductType;
use App\Form\Type\QueryTransferOrderType;
use App\Repository\AssetRepository;
use App\Repository\InvestmentRepository;
use App\Repository\OfferingRepository;
use App\Repository\PaymentOrderRepository;
use App\Repository\ShareTradeRepository;
use App\Repository\ShareTransferOrderRepository;
use App\Repository\TaskTrackerRepository;
use App\Repository\TradeOrderRepository;
use App\Repository\TransferOrderRepository;
use App\Service\DivestmentService;
use App\Service\Manager\InvestmentManagerV2;
use App\Service\MangopayWalletService;
use App\Service\MonthEndActivityService;
use App\Service\MonthEndService;
use App\Service\MonthEndTaskTrackerService;
use App\Service\ProductService;
use App\Service\SettlementService;
use App\Service\Util\Helper;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/monthend')]
class MonthEndController extends AbstractController
{
    // List of routes that other controllers should be allowed to redirect to
    public const REDIRECT_ROUTES = [
        'admin_monthend_income_transfer_manage',
        'admin_monthend_dividend_manage',
        'admin_monthend_repayment_manage',
        'admin_monthend_divestment_manage',
        'admin_monthend_settlement_manage',
        'admin_monthend_fee_collection_manage',
        'admin_monthend_income_disaggregation_manage',
        'admin_monthend_income_transfer_builder_expenses',
        'admin_monthend_income_transfer_builder_tax',
        'admin_monthend_income_transfer_builder_treasury',
        'admin_monthend_income_transfer_builder_distribution',
    ];

    public const SETUP_FLOW = [
        'admin_payment_order_edit_date' => 'admin_payment_order_edit_description',
    ];

    public const AUTOSYNC_DELAY = 5; // in seconds

    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private MonthEndService $monthEndService,
        private MonthEndActivityService $monthEndActivityService,
        private MonthEndTaskTrackerService $monthEndTaskTrackerService,
        private ProductService $productService,
        private PaymentOrderRepository $paymentOrderRepository,
        private ShareTransferOrderRepository $shareTransferOrderRepository,
        private TransferOrderRepository $transferOrderRepository,
        private TradeOrderRepository $tradeOrderRepository,
        private AssetRepository $assetRepository,
        private OfferingRepository $offeringRepository,
        private InvestmentRepository $investmentRepository,
        private ShareTradeRepository $shareTradeRepository,
        private TaskTrackerRepository $taskTrackerRepository,
        private MangopayWalletService $walletService,
        private SettlementService $settlementService,
        private DivestmentService $divestmentService,
        private InvestmentManagerV2 $investmentManager,
    ) {}

    #[Route('', name: 'admin_monthend_index', methods: ['GET'])]
    public function checklist(Request $request): Response
    {
        // $this->logger->debug('Monthend checklist');
        $taskTracker = $this->taskTrackerRepository->findOneBy([
            'taskTrackerType' => TaskTrackerType::Monthend,
        ], ['createdAt' => 'DESC']);
        if (is_null($taskTracker)) {
            $taskTracker = $this->monthEndTaskTrackerService->createMonthendTaskTracker(TaskTrackerType::Monthend);
            $this->entityManager->persist($taskTracker);
            $this->entityManager->flush();
        } else {
            $taskTracker =
                $this->monthEndTaskTrackerService->validateTaskTracker($taskTracker);
        }

        $currentMonthend = $this->monthEndService->getMonthEndDateRangeFromDateTime(
            new \DateTime(),
        );
        $activity = $this->monthEndActivityService->getMonthendActivitySummary(
            [
                TransferType::IncomeDisaggregation,
                PaymentType::Dividend,
                TransferType::InvestmentSettlement,
                PaymentType::Repayment,
                TransferType::FeeCollection,
            ],
            $currentMonthend['start'],
            $currentMonthend['end'],
        );
        $lastSynced = $taskTracker->getMetadata()['syncedAt'];
        if ((time() - $lastSynced) > self::AUTOSYNC_DELAY) {
            $this->monthEndTaskTrackerService->syncMonthendTaskTracker($taskTracker);
            $this->entityManager->flush();
        }

        return $this->render('admin/pages/monthend/overview.html.twig', [
            'taskTracker' => $taskTracker,
            'activity' => $activity,
            'settlementsBreakdown' => $this->monthEndActivityService->separateSettlements(
                $activity['settlements'],
            ),
        ]);
    }

    #[Route('/review', name: 'admin_monthend_review', methods: ['GET'])]
    public function review(Request $request): Response
    {
        // $this->logger->debug('Monthend activity review');

        $defaultFilters =
            $filters = [
                'startMonth' => new \DateTime(),
                'endMonth' => new \DateTime(),
                'status' => [AbstractOrder::STATE_COMPLETED],
            ];
        $form = $this->createForm(QueryMonthendActivityType::class, $filters);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Don't allow null values
            $filters = array_merge($defaultFilters, array_filter($form->getData()));
            $this->logger->debug('filters', $filters);
        }

        $firstMonthend = $this->monthEndService->getMonthEndDateRangeFromDateTime(
            $filters['startMonth'],
        );
        $lastMonthend = $this->monthEndService->getMonthEndDateRangeFromDateTime(
            $filters['endMonth'],
        );
        $activity = $this->monthEndActivityService->getMonthendActivitySummary(
            [
                TransferType::IncomeDisaggregation,
                PaymentType::Dividend,
                TransferType::InvestmentSettlement,
                PaymentType::Repayment,
                TransferType::FeeCollection,
                PaymentType::Divestment,
                PaymentType::InvestmentExit,
                GenericOrderType::ShareTransfer,
            ],
            $firstMonthend['start'],
            $lastMonthend['end'],
            $filters['status'],
            true,
        );

        // further process the settlements to separate the settlement and stamp duty
        $activity['settlementsBreakdown'] = [];
        foreach ($activity['settlements'] as $month => $monthlySettlements) {
            $activity['settlementsBreakdown'][$month] =
                $this->monthEndActivityService->separateSettlements(
                    $monthlySettlements,
                );
        }
        $searchInterval = $lastMonthend['end']->diff($firstMonthend['start']);
        $searchInterval = ($searchInterval->y * 12) + $searchInterval->m;
        return $this->render('admin/pages/monthend/review.html.twig', [
            'form' => $form,
            'activity' => $activity,
            'settlementsBreakdown' => $activity['settlementsBreakdown'],
            'searchInterval' => max($searchInterval, 1),
        ]);
    }

    #[Route('/assets', name: 'admin_monthend_assets', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // $this->logger->debug('Monthend overview');
        $defaultFilters = [
            'status' => AssetStatus::typicalCases(),
        ];
        $form = $this->createForm(QueryAssetType::class, $defaultFilters);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();

            // $this->logger->debug('filters', $filters);
        }
        $results = $this->offeringRepository->findByWithAssociations(
            $filters ?? $defaultFilters,
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
            ['assetId'],
        );
        $results = $this->assetRepository->findByWithAssociations(
            array_merge($defaultFilters, $filters ?? []),
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/monthend/assets.html.twig', [
            'objects' => $results,
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        '/update-checklist',
        name: 'admin_monthend_update_checklist',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function checklistTaskUpdate(Request $request): Response
    {
        $taskTracker = $this->taskTrackerRepository->findOneBy([
            'taskTrackerType' => TaskTrackerType::Monthend,
        ], ['createdAt' => 'DESC']);
        if (is_null($taskTracker)) {
            $taskTracker = $this->monthEndTaskTrackerService->createMonthendTaskTracker(TaskTrackerType::Monthend);
            $this->entityManager->persist($taskTracker);
        } else {
            $taskTracker =
                $this->monthEndTaskTrackerService->validateTaskTracker($taskTracker);
        }

        $status = $request->query->get('status');
        if (!is_null(TaskStatus::tryFrom($status))) {
            $this->monthEndTaskTrackerService->updateTaskStatusInTracker(
                $taskTracker,
                $request->query->get('task'),
                TaskStatus::from($status),
            );
            $this->entityManager->flush();
        }
        return $this->redirectToRoute(
            'admin_monthend_index',
            [],
            Response::HTTP_SEE_OTHER,
        );
    }

    #[Route('/dividends', name: 'admin_monthend_overview_dividends', methods: ['GET'])]
    public function dividendsOverview(Request $request): Response
    {
        // $this->logger->debug('Monthend dividend overview');
        $form = $this->createForm(QueryPaymentOrderType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $defaultDateRange = $this->monthEndService->getMonthEndDateRangeFromDateTime(
            new \DateTime(),
        );
        $results = $this->paymentOrderRepository->findByWithAssociations(
            array_merge($filters ?? [], [
                'paymentType' => [PaymentType::Dividend->value],
            ]),
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/monthend/payments/overview_dividends.html.twig', [
            'objects' => $results,
            'defaultDateRange' => $defaultDateRange,
        ]);
    }

    #[Route(
        '/settlements',
        name: 'admin_monthend_overview_settlements',
        methods: ['GET'],
    )]
    public function settlementsOverview(Request $request): Response
    {
        // $this->logger->debug('Monthend settlement overview');
        $defaultDateRange = $this->monthEndService->getMonthEndDateRangeFromDateTime(
            new \DateTime(),
            -1,
        );
        $filters = [
            'dateStart' => \DateTime::createFromImmutable($defaultDateRange['start']),
            'dateEnd' => \DateTime::createFromImmutable($defaultDateRange['end']),
        ];
        $form = $this->createForm(QueryDateRangeType::class, $filters, [
            'dateFieldName' => 'Investments Made',
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        /** @var ShareTrade[] $shareTrades */
        $shareTrades = $this->shareTradeRepository
            ->buildQueryWithAssociations([
                'status' => TradeStatus::Unsettled,
                'buyOrderType' => TradeOrderType::tradingBuyTypes(),
                'createdAt_gte' => $filters['dateStart'],
                'createdAt_lt' => $filters['dateEnd'],
            ], ['id' => 'DESC'])
            ->getResult();
        $summary = $this->settlementService->getSettlementOverview($shareTrades);
        $this->logger->debug('settlement summary', [
            'shareTrades' => count($shareTrades),
            $summary,
        ]);
        return $this->render('admin/pages/monthend/settlements/overview_settlements.html.twig', [
            'form' => $form->createView(),
            'summary' => $summary,
        ]);
    }

    #[Route(
        '/settlements/list',
        name: 'admin_monthend_overview_settlements_list',
        methods: ['GET'],
    )]
    public function settlementsList(Request $request): Response
    {
        $form = $this->createForm(QueryTransferOrderType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->transferOrderRepository->findByWithAssociations(
            array_merge($filters ?? [], [
                'transferType' => TransferType::InvestmentSettlement,
            ]),
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/monthend/settlements/overview_settlements_list.html.twig', [
            'objects' => $results,
        ]);
    }

    #[Route(
        '/repayments',
        name: 'admin_monthend_overview_repayments',
        methods: ['GET'],
    )]
    public function repaymentsOverview(Request $request): Response
    {
        // $this->logger->debug('Monthend prefunder repayment overview');

        // Get asset shares in circulation

        $shareholdings = $this->shareTradeRepository->aggregateSharesInCirculation();
        $shareholdings = array_combine(
            array_column($shareholdings, 'assetid'),
            $shareholdings,
        );

        // Get asset prefunder shares to repay - order by asset id descending
        $prefunderSellOrders = $this->tradeOrderRepository
            ->buildQueryWithAssociations([
                'status' => [TradeOrderStatus::Active, TradeOrderStatus::Completed],
                'type' => TradeOrderType::Prefunding,
                'direction' => TradeDirection::Sell,
            ], ['asset.id' => 'ASC'])
            ->getResult();
        $repaymentSummary = $this->divestmentService->compileRepaymentProgress(
            $prefunderSellOrders,
            QueryGrouping::Asset,
        );

        // Get asset objects if they have prefunders to repay
        $assetIds = array_keys($repaymentSummary);
        $assetRepository = $this->entityManager->getRepository(Asset::class);
        $assets = Helper::convertArrayKeysAsIds($assetRepository->findBy([
            'id' => $assetIds,
        ]));

        return $this->render('admin/pages/monthend/payments/overview_repayments.html.twig', [
            'assets' => $assets,
            'shareholdings' => $shareholdings,
            'repaymentSummary' => $repaymentSummary,
        ]);
    }

    #[Route(
        '/repayments/list',
        name: 'admin_monthend_overview_repayments_list',
        methods: ['GET'],
    )]
    public function repaymentsList(Request $request): Response
    {
        $form = $this->createForm(QueryPaymentOrderType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->paymentOrderRepository->findByWithAssociations(
            array_merge($filters ?? [], [
                'paymentType' => [
                    PaymentType::Repayment->value,
                ],
            ]),
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );

        return $this->render('admin/pages/monthend/payments/overview_repayments_list.html.twig', [
            'objects' => $results,
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        '/share-transfers',
        name: 'admin_monthend_overview_share_transfers',
        methods: ['GET'],
    )]
    public function shareTransfersOverview(Request $request): Response
    {
        // Get asset with shares in circulation
        $shareholdings = $this->shareTradeRepository->aggregateSharesInCirculation();
        $assets = Helper::convertArrayKeysAsIds(
            $this->assetRepository->buildQueryWithAssociations([
                'id' => array_column($shareholdings, 'assetid'),
            ])->getResult(),
        );
        $assetOrders = array_fill_keys(array_column($shareholdings, 'assetid'), [
            'settlements' => 0,
            'repayments' => 0,
            'shareTransfers' => 0,
        ]);
        $shareholdings = array_combine(
            array_column($shareholdings, 'assetid'),
            $shareholdings,
        );

        $defaultDateRange = $this->monthEndService->getMonthEndDateRangeFromDateTime(
            new \DateTime(),
        );
        $filters = [
            'dateStart' => \DateTime::createFromImmutable($defaultDateRange['start']),
            'dateEnd' => \DateTime::createFromImmutable($defaultDateRange['end']),
        ];
        $form = $this->createForm(QueryDateRangeType::class, $filters, [
            'dateFieldName' => 'Monthend Periods',
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }

        $settlements = $this->transferOrderRepository->buildQueryWithAssociations([
            'transferType' => TransferType::InvestmentSettlement,
            'scheduledFor_gte' => $filters['dateStart'],
            'scheduledFor_lt' => $filters['dateEnd'],
            'status' => [
                AbstractOrder::STATE_DRAFT,
                AbstractOrder::STATE_APPROVED,
                AbstractOrder::STATE_IN_PROGRESS,
                AbstractOrder::STATE_COMPLETED,
            ],
        ])->getResult();
        foreach ($settlements as $settlementOrder) {
            if ($settlementOrder->getAsset()) {
                $assetOrders[$settlementOrder->getAsset()->getId()]['settlements'] += 1;
            }
        }
        $repayments = $this->paymentOrderRepository->buildQueryWithAssociations([
            'paymentType' => PaymentType::Repayment->value,
            'scheduledFor_gte' => $filters['dateStart'],
            'scheduledFor_lt' => $filters['dateEnd'],
            'status' => [
                AbstractOrder::STATE_DRAFT,
                AbstractOrder::STATE_APPROVED,
                AbstractOrder::STATE_IN_PROGRESS,
                AbstractOrder::STATE_COMPLETED,
            ],
        ])->getResult();
        foreach ($repayments as $repaymentOrder) {
            if ($repaymentOrder->getAsset()) {
                $assetOrders[$repaymentOrder->getAsset()->getId()]['repayments'] += 1;
            }
        }
        $shareTransfers = $this->shareTransferOrderRepository->buildQueryWithAssociations([
            'scheduledFor_gte' => $filters['dateStart'],
            'scheduledFor_lt' => $filters['dateEnd'],
            'status' => [
                OrderStatus::Draft,
                OrderStatus::Approved,
                OrderStatus::InProgress,
                OrderStatus::Completed,
            ],
        ])->getResult();
        foreach ($shareTransfers as $shareTransfer) {
            if ($shareTransfer->getAsset()) {
                $assetOrders[$shareTransfer
                    ->getAsset()
                    ->getId()]['shareTransfers'] += 1;
            }
        }
        return $this->render('admin/pages/monthend/share_transfers/overview.html.twig', [
            'assetOrderSummary' => array_reverse($assetOrders, true),
            'shareholdings' => $shareholdings,
            'assets' => $assets,
            'form' => $form,
        ]);
    }

    #[Route(
        '/divestments',
        name: 'admin_monthend_overview_divestments',
        methods: ['GET'],
    )]
    public function divestmentsOverview(Request $request): Response
    {
        $form = $this->createForm(QueryPaymentOrderType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->paymentOrderRepository->findByWithAssociations(
            array_merge($filters ?? [], [
                'paymentType' => [
                    PaymentType::Divestment->value,
                    PaymentType::InvestmentExit->value,
                ],
            ]),
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );

        return $this->render('admin/pages/monthend/payments/overview_divestments.html.twig', [
            'objects' => $results,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_monthend_asset_dashboard', methods: ['GET'])]
    public function dashboard(Asset $asset): Response
    {
        // $this->logger->debug('Asset monthend dashboard');
        // $offering = $this->offeringRepository->findFirstPartyByAssetId($asset->getId());
        // if (is_null($offering)) {
        //     // Future: redirect to a recovery page to create the offering and continue setup
        //     $this->addFlash(
        //         'error',
        //         'No first party offering associated with this asset',
        //     );
        //     return $this->redirectToRoute('admin_monthend_assets');
        // }
        $shareholders = $this->shareTradeRepository->aggregateAssetShareholdingsByUser(
            assetId: $asset->getId(),
            nonZero: true,
            shareholderOrdering: OrderingDirection::Descending,
        );
        $currentIncomeTransfer = $this->transferOrderRepository->findForCurrentMonthend(
            $asset,
            TransferOrderPreset::IncomeTransfer,
        );
        $currentDividend = $this->paymentOrderRepository->findForCurrentMonthend(
            $asset,
            PaymentType::Dividend,
        );
        $currentSettlement = $this->transferOrderRepository->findForCurrentMonthend(
            $asset,
            TransferOrderPreset::InvestmentSettlement,
        );
        $currentRepayment = $this->paymentOrderRepository->findForCurrentMonthend(
            $asset,
            PaymentType::Repayment,
        );
        $currentShareTransfer =
            $this->shareTransferOrderRepository->findForCurrentMonthend($asset);

        // Sync checklist or create if not exists
        $taskTracker = $asset->getTaskTracker();
        if (is_null($taskTracker)) {
            $taskTracker = $this->monthEndTaskTrackerService->createMonthendTaskTracker(TaskTrackerType::AssetMonthend);
            $asset->setTaskTracker($taskTracker);
        } else {
            $taskTracker =
                $this->monthEndTaskTrackerService->validateTaskTracker($taskTracker);
        }
        $taskTracker = $this->monthEndTaskTrackerService->syncMonthendAssetChecklist(
            $taskTracker,
            $currentIncomeTransfer,
            $currentDividend,
            $currentSettlement,
            $currentRepayment,
            $currentShareTransfer,
        );
        $this->entityManager->flush();

        return $this->render('admin/pages/monthend/dashboard/overview.html.twig', [
            'asset' => $asset,
            'taskTracker' => $taskTracker,
            'currentIncomeTransfer' => $currentIncomeTransfer,
            'currentDividend' => $currentDividend,
            'currentSettlement' => $currentSettlement,
            'currentRepayment' => $currentRepayment,
            'currentShareTransfer' => $currentShareTransfer,
            'currentShareholders' => $shareholders,
            'launchTodo' => $this->productService->identifyDataMissingForLaunch($asset),
            // 'offering' => $offering,
        ]);
    }

    #[Route(
        '/{id}/update-checklist',
        name: 'admin_monthend_update_asset_checklist',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function assetChecklistTaskUpdate(Request $request, Asset $asset): Response
    {
        $taskTracker = $asset->getTaskTracker();
        if (is_null($taskTracker)) {
            $taskTracker = $this->monthEndTaskTrackerService->createMonthendTaskTracker(TaskTrackerType::AssetMonthend);
            $asset->setTaskTracker($taskTracker);
        } else {
            $taskTracker =
                $this->monthEndTaskTrackerService->validateTaskTracker($taskTracker);
        }

        $status = $request->query->get('status');
        if (!is_null(TaskStatus::tryFrom($status))) {
            $this->monthEndTaskTrackerService->updateTaskStatusInTracker(
                $taskTracker,
                $request->query->get('task'),
                TaskStatus::from($status),
            );
            $this->entityManager->flush();
        }

        return $this->redirectToRoute(
            'admin_monthend_asset_dashboard',
            ['id' => $asset->getId()],
            Response::HTTP_SEE_OTHER,
        );
    }

    #[Route(
        '/{id}/toggle-auto-checklist',
        name: 'admin_monthend_toggle_auto_checklist',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function toggleAutoChecklist(Asset $asset): Response
    {
        $taskTracker = $asset->getTaskTracker();
        if (is_null($taskTracker)) {
            $taskTracker = $this->monthEndTaskTrackerService->createMonthendTaskTracker(TaskTrackerType::AssetMonthend);
            $asset->setTaskTracker($taskTracker);
        } else {
            $taskTracker =
                $this->monthEndTaskTrackerService->validateTaskTracker($taskTracker);
        }
        $metadata = $taskTracker->getMetadata();
        $metadata['autoUpdate'] = !$metadata['autoUpdate'];
        $taskTracker->setMetadata($metadata);
        $this->entityManager->flush();

        return $this->redirectToRoute(
            'admin_monthend_asset_dashboard',
            ['id' => $asset->getId()],
            Response::HTTP_SEE_OTHER,
        );
    }

    #[Route(
        '/{id}/income-transfers',
        name: 'admin_monthend_income_transfers',
        methods: ['GET'],
    )]
    public function incomeTransfers(Request $request, Asset $asset): Response
    {
        // $this->logger->debug('Asset monthend dashboard - income transfers');
        $form = $this->createForm(QueryTransferOrderType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->transferOrderRepository->findByWithAssociations(
            array_merge($filters ?? [], [
                'assetId' => $asset->getId(),
                'transferType' => TransferType::AssetIncomeProcessing,
            ]),
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        $currentIncomeTransfer = $this->transferOrderRepository->findForCurrentMonthend(
            $asset,
            TransferOrderPreset::IncomeTransfer,
        );
        return $this->render('admin/pages/monthend/dashboard/income_transfers.html.twig', [
            'asset' => $asset,
            'currentIncomeTransfer' => $currentIncomeTransfer,
            'transferOrders' => $results,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/dividends', name: 'admin_monthend_dividends', methods: ['GET'])]
    public function dividends(Request $request, Asset $asset): Response
    {
        // $this->logger->debug('Asset monthend dashboard - dividends');
        $form = $this->createForm(QueryPaymentOrderType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->paymentOrderRepository->findByWithAssociations(
            array_merge($filters ?? [], [
                'assetId' => $asset->getId(),
                'paymentType' => PaymentType::Dividend->value,
            ]),
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        $currentDividend = $this->paymentOrderRepository->findForCurrentMonthend(
            $asset,
            PaymentType::Dividend,
        );
        return $this->render('admin/pages/monthend/dashboard/dividends.html.twig', [
            'asset' => $asset,
            'currentDividend' => $currentDividend,
            'paymentOrders' => $results,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/settlements', name: 'admin_monthend_settlements', methods: ['GET'])]
    public function settlements(Request $request, Asset $asset): Response
    {
        // $this->logger->debug('Asset monthend dashboard - settlements');
        $form = $this->createForm(QueryTransferOrderType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->transferOrderRepository->findByWithAssociations(
            array_merge($filters ?? [], [
                'assetId' => $asset->getId(),
                'transferType' => TransferType::InvestmentSettlement,
            ]),
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        $currentSettlement = $this->transferOrderRepository->findForCurrentMonthend(
            $asset,
            TransferOrderPreset::InvestmentSettlement,
        );
        $investmentsToSettle = $this->investmentRepository->buildQueryWithAssociations([
            'lifecycleStatus' => [InvestmentLifecycle::STATE_APPROVED],
            'assetId' => $asset->getId(),
        ])->getResult();
        return $this->render('admin/pages/monthend/dashboard/settlements.html.twig', [
            'asset' => $asset,
            'currentSettlement' => $currentSettlement,
            'investmentsToSettle' => $investmentsToSettle,
            'transferOrders' => $results,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/repayments', name: 'admin_monthend_repayments', methods: ['GET'])]
    public function repayments(Request $request, Asset $asset): Response
    {
        // $this->logger->debug('Asset monthend dashboard - repayments');
        $form = $this->createForm(QueryPaymentOrderType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->paymentOrderRepository->findByWithAssociations(
            array_merge($filters ?? [], [
                'assetId' => $asset->getId(),
                'paymentType' => PaymentType::Repayment->value,
            ]),
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        $currentRepayment = $this->paymentOrderRepository->findForCurrentMonthend(
            $asset,
            PaymentType::Repayment,
        );
        $prefunderSellOrders = $this->tradeOrderRepository
            ->buildQueryWithAssociations([
                'assetId' => $asset->getId(),
                'status' => [TradeOrderStatus::Active, TradeOrderStatus::Completed],
                'type' => TradeOrderType::Prefunding,
                'direction' => TradeDirection::Sell,
            ], ['numberOfShares' => 'ASC'])
            ->getResult();
        $repaymentSummary =
            $this->divestmentService->compileRepaymentProgress($prefunderSellOrders);
        return $this->render('admin/pages/monthend/dashboard/repayments.html.twig', [
            'asset' => $asset,
            'currentRepayment' => $currentRepayment,
            'form' => $form->createView(),
            'paymentOrders' => $results,
            'prefunderSellOrders' => $prefunderSellOrders,
            'repaymentSummary' => $repaymentSummary,
        ]);
    }

    #[Route('/{id}/divestments', name: 'admin_monthend_divestments', methods: ['GET'])]
    public function divestments(Request $request, Asset $asset): Response
    {
        // $this->logger->debug('Asset monthend dashboard - divestments');
        $form = $this->createForm(QueryPaymentOrderType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->paymentOrderRepository->findByWithAssociations(
            array_merge($filters ?? [], [
                'assetId' => $asset->getId(),
                'paymentType' => [
                    PaymentType::Divestment->value,
                    PaymentType::InvestmentExit->value,
                ],
            ]),
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/monthend/dashboard/divestments.html.twig', [
            'asset' => $asset,
            'paymentOrders' => $results,
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        '/{id}/share-transfers',
        name: 'admin_monthend_share_transfers',
        methods: ['GET'],
    )]
    public function shareTransfers(Request $request, Asset $asset): Response
    {
        $form = $this->createForm(QueryShareTransferOrderForm::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->shareTransferOrderRepository->findByWithAssociations(
            array_merge($filters ?? [], [
                'assetId' => $asset->getId(),
            ]),
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/monthend/dashboard/share_transfers.html.twig', [
            'asset' => $asset,
            'results' => $results,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/review', name: 'admin_monthend_dashboard_review', methods: ['GET'])]
    public function dashboardReview(Request $request, Asset $asset): Response
    {
        // $this->logger->debug("Asset #{$asset->getId()} monthend activity review");

        $defaultFilters =
            $filters = [
                'startMonth' => new \DateTime(),
                'endMonth' => new \DateTime(),
                'status' => [AbstractOrder::STATE_COMPLETED],
            ];
        $form = $this->createForm(QueryMonthendActivityType::class, $filters);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Don't allow null values
            $filters = array_merge($defaultFilters, array_filter($form->getData()));
            $this->logger->debug('filters', $filters);
        }

        $firstMonthend = $this->monthEndService->getMonthEndDateRangeFromDateTime(
            $filters['startMonth'],
        );
        $lastMonthend = $this->monthEndService->getMonthEndDateRangeFromDateTime(
            $filters['endMonth'],
        );
        $activity = $this->monthEndActivityService->getMonthendActivitySummary(
            [
                PaymentType::Dividend,
                TransferType::InvestmentSettlement,
                PaymentType::Repayment,
                PaymentType::Divestment,
                PaymentType::InvestmentExit,
                GenericOrderType::ShareTransfer,
            ],
            $firstMonthend['start'],
            $lastMonthend['end'],
            $filters['status'],
            true,
            assetIds: [$asset->getId()],
        );

        // further process the settlements to separate the settlement and stamp duty
        $activity['settlementsBreakdown'] = [];
        foreach ($activity['settlements'] as $month => $monthlySettlements) {
            $activity['settlementsBreakdown'][$month] =
                $this->monthEndActivityService->separateSettlements(
                    $monthlySettlements,
                );
        }
        $searchInterval = $lastMonthend['end']->diff($firstMonthend['start']);
        $searchInterval = ($searchInterval->y * 12) + $searchInterval->m;
        return $this->render('admin/pages/monthend/dashboard/review.html.twig', [
            'asset' => $asset,
            'offering' => $this->offeringRepository->findFirstPartyByAssetId(
                $asset->getId(),
            ),
            'form' => $form,
            'activity' => $activity,
            'settlementsBreakdown' => $activity['settlementsBreakdown'],
            'searchInterval' => max($searchInterval, 1),
        ]);
    }

    #[Route(
        '/{id}/wallet-checker',
        name: 'admin_monthend_wallet_checker',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_ANALYST')]
    public function walletChecker(
        Request $request,
        TransferOrder $transferOrder,
    ): Response {
        // Prevent other transfer orders from using this tool, unless an override is used
        if (
            !$request->query->get('override', false)
            && !in_array($transferOrder->getTransferType(), [
                TransferType::IncomeDisaggregation,
                TransferType::FeeCollection,
                TransferType::AssetIncomeProcessing,
            ])
        ) {
            $this->addFlash('warning', 'Wallet checker only available for orders involving
                superadmin or asset wallets unless override used');
            return $this->redirectToRoute('admin_monthend_assets');
        }
        $amountPerWallet =
            $this->monthEndService->transferOrderAmountPerWallet($transferOrder);
        $walletIds = array_unique(array_merge(
            array_keys($amountPerWallet['debit']),
            array_keys($amountPerWallet['credit']),
        ));
        /**
         * Wallet querying will need a refactor
         * Refactor will also impact AssetManagerV2:getAssetWalletByType
         * Related to https://gitlab.com/yielders2/backoffice-dev/-/issues/2055#note_1020499076
         */
        $walletBalances = array_fill_keys($walletIds, []);
        foreach ($walletIds as $walletId) {
            try {
                $providerWallet = $this->walletService->getWallet(
                    $walletId,
                    'USER_NOT_PRESENT',
                );
                $walletBalances[$walletId] = [
                    'walletId' => $walletId,
                    'balance' => (string) round(
                        $providerWallet->Balance->Amount / 100,
                        2,
                    ),
                    'currency' => (string) $providerWallet->Currency,
                    'description' => (string) $providerWallet->Description,
                    // Mangopay provide an array of owners, we only want the first one
                    'owner' => (string) reset($providerWallet->Owners) ?: 'Not found',
                ];
            } catch (\Exception $e) {
                $this->logger->error(
                    "Wallet with id {$walletId} could not be retrieved",
                    [$e->getMessage(), $e->getCode()],
                );
                $this->addFlash(
                    'warning',
                    "Wallet with id {$walletId} could not be retrieved",
                );
                $walletBalances[$walletId] = [
                    'walletId' => $walletId,
                    'balance' => null,
                    'currency' => null,
                    'description' => null,
                    'owner' => null,
                ];
            }
        }
        // Provide per-tool back-button links for supported transfer types
        $exitRoute = match ($transferOrder->getTransferType()) {
            TransferType::IncomeDisaggregation
                => 'admin_monthend_income_disaggregation_manage',
            TransferType::FeeCollection => 'admin_monthend_fee_collection_manage',
            TransferType::AssetIncomeProcessing
                => 'admin_monthend_income_transfer_manage',
            default => 'admin_transfer_order_manage',
        };
        return $this->render('admin/pages/monthend/wallet_checker.html.twig', [
            'transferOrder' => $transferOrder,
            'amountPerWallet' => $amountPerWallet,
            'walletBalances' => $walletBalances,
            'exitRoute' => $exitRoute,
        ]);
    }
}
