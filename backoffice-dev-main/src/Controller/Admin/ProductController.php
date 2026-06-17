<?php

namespace App\Controller\Admin;

use App\Entity\Asset;
use App\Entity\Enum\AssetStatus;
use App\Entity\Enum\QueryGrouping;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Lifecycle\InvestmentLifecycle;
use App\Form\Type\ProductCreateType;
use App\Form\Type\QueryAssetType;
use App\Form\Type\QueryInvestmentType;
use App\Form\Type\QueryOfferingType;
use App\Form\Type\QueryPaymentOrderType;
use App\Form\Type\QueryPayoutType;
use App\Form\Type\QueryProductReviewListingsType;
use App\Form\Type\QueryTradeOrderType;
use App\Form\Type\QueryTradeType;
use App\Form\Type\QueryTransferOrderType;
use App\Repository\AssetRepository;
use App\Repository\HoldingRepository;
use App\Repository\InvestmentRepository;
use App\Repository\OfferingRepository;
use App\Repository\PaymentOrderRepository;
use App\Repository\PayoutRepository;
use App\Repository\ShareTradeRepository;
use App\Repository\TradeOrderRepository;
use App\Repository\TransferOrderRepository;
use App\Repository\UserRepository;
use App\Service\DivestmentService;
use App\Service\ProductReviewService;
use App\Service\ProductService;
use App\Service\ShareholdingService;
use App\Service\Util\ExportHelper;
use App\Service\Util\Helper;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Sonata\Exporter\Exporter;
use Sonata\Exporter\Source\ArraySourceIterator;
use Sonata\Exporter\Source\IteratorCallbackSourceIterator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/products')]
#[IsGranted('ROLE_ANALYST')]
class ProductController extends AbstractController
{
    // List of routes that other controllers should be allowed to redirect to
    public const REDIRECT_ROUTES = [
        'admin_product_documents',
        'admin_product_listings',
    ];

    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private ProductService $productService,
        private AssetRepository $assetRepository,
        private OfferingRepository $offeringRepository,
        private InvestmentRepository $investmentRepository,
        private PayoutRepository $payoutRepository,
        private PaymentOrderRepository $paymentOrderRepository,
        private ShareTradeRepository $shareTradeRepository,
        private TradeOrderRepository $tradeOrderRepository,
        private TransferOrderRepository $transferOrderRepository,
        private HoldingRepository $holdingRepository,
        private ProductReviewService $productReviewService,
        private ShareholdingService $shareholdingService,
        private DivestmentService $divestmentService,
        private SluggerInterface $slugger,
        private Exporter $exporter,
    ) {}

    #[Route('', name: 'admin_products_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->logger->debug('Asset product overview');
        $form = $this->createForm(QueryAssetType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
            $this->logger->debug('filters', $filters);
        }
        $results = $this->assetRepository->findByWithAssociations(
            $filters ?? [],
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/products/index.html.twig', [
            'objects' => $results,
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        '/review/listings',
        name: 'admin_products_review_listings',
        methods: ['GET'],
    )]
    public function review(Request $request): Response
    {
        // $this->logger->debug('Asset product review');
        $summary = $this->productReviewService->getAssetListingSummary();

        $form = $this->createForm(QueryProductReviewListingsType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $summary = $this->productReviewService->filterAssetListingSummary(
                $summary,
                $form->getData(),
            );
        }

        $assets = $this->assetRepository
            ->buildQueryWithAssociations(['id' => array_keys($summary)], [
                'id' => 'DESC',
            ])
            ->getResult();

        return $this->render('admin/pages/products/review/listings.html.twig', [
            'form' => $form,
            'assets' => $assets,
            'summary' => $summary,
        ]);
    }

    #[Route('/create', name: 'admin_product_create', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_OPERATIONS')]
    public function create(Request $request): Response
    {
        $this->logger->debug('Create new asset product');
        $asset = new Asset();
        $form = $this->createForm(ProductCreateType::class, $asset);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->productService->fillDefaults($asset);
            $this->productService->setCommonFields($asset);
            $this->doctrine->getManager()->persist($asset);
            $this->doctrine->getManager()->flush();
            $this->addFlash('success', 'New asset product created');
            return $this->redirectToRoute('admin_product_edit_about', [
                'id' => $asset->getId(),
                'setup' => 1,
            ]);
        }
        return $this->render('admin/pages/products/editor/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_product_dashboard', methods: ['GET'])]
    public function dashboard(Asset $asset): Response
    {
        $this->logger->debug('Asset product dashboard');

        $initialTradeOrders = $this->tradeOrderRepository->findWithAssociations([
            'direction' => TradeDirection::Sell,
            'type' => TradeOrderType::Initial,
            'assetId' => $asset->getId(),
        ], ['createdAt' => 'DESC']);
        $prefunderSellOrders = $this->tradeOrderRepository
            ->buildQueryWithAssociations([
                'assetId' => $asset->getId(),
                'status' => [TradeOrderStatus::Active],
                'type' => TradeOrderType::Prefunding,
                'direction' => TradeDirection::Sell,
            ], ['numberOfShares' => 'ASC'])
            ->getResult();
        $repaymentSummary =
            $this->divestmentService->compileRepaymentProgress($prefunderSellOrders);
        return $this->render('admin/pages/products/dashboard/overview.html.twig', [
            'asset' => $asset,
            // 'offering' => $offering,
            'launchTodo' => $this->productService->identifyDataMissingForLaunch($asset),
            'alreadyLaunched' => $this->productService->isAlreadyLaunched($asset),
            'sortedDocs' => $this->productService->sortDocuments($asset),
            'initialTradeOrders' => $initialTradeOrders,
            'repaymentSummary' => $repaymentSummary,
        ]);
    }

    #[Route('/{id}/shareholders', name: 'admin_product_shareholders', methods: ['GET'])]
    public function shareholders(
        Request $request,
        Asset $asset,
        UserRepository $userRepository,
    ): Response {
        $this->logger->debug('Asset product dashboard - shareholders');
        if ($request->query->get('export')) {
            $format = ExportHelper::validateExportFormat($request->query->get(
                'format',
                'csv',
            ));
            $qb = $this->shareTradeRepository->buildAggregateShareholdingQuery(
                QueryGrouping::User,
                $asset->getId(),
                // nonZero: true,
            );
            $tradeBasedHoldings = $this->shareTradeRepository
                ->extendAssetTradeShareholdingsQuery($qb)
                ->executeQuery()
                ->fetchAllAssociative();
            $source =
                $this->shareholdingService->annotateAggregateShareholdings(
                    $tradeBasedHoldings,
                );
            return $this->exporter->getResponse(
                $format,
                ExportHelper::generateFileName(
                    $this->slugger->slug($asset->getCompanyNumber(), '_')
                        . '_trade_shareholdings_',
                    $format,
                ),
                new ArraySourceIterator($source),
            );
        }
        $shareholders = $this->holdingRepository->getShareHoldings(['assetId' =>
            $asset->getId()]);

        $tradeBasedHoldings = $this->shareTradeRepository->aggregateAssetShareholdingsByUser(
            assetId: $asset->getId(),
            // nonZero: true,
        );

        $tradeBasedHoldings =
            $this->shareholdingService->annotateAggregateShareholdings(
                $tradeBasedHoldings,
            );
        // $this->logger->debug('tradeshareholders', $tradeBasedHoldings);
        $users = $userRepository->findBy(['id' => array_column(
            $tradeBasedHoldings,
            'userid',
        )]);
        $shareholders = array_combine(
            array_column($shareholders, 'userId'),
            $shareholders,
        );
        return $this->render('admin/pages/products/dashboard/shareholders.html.twig', [
            'asset' => $asset,
            'users' => Helper::convertArrayKeysAsIds($users),
            'shareholders' => $shareholders,
            'tradeHoldings' => $tradeBasedHoldings,
        ]);
    }

    #[Route('/{id}/share-trades', name: 'admin_product_share_trades', methods: ['GET'])]
    public function shareTrades(Request $request, Asset $asset): Response
    {
        $this->logger->debug('Asset product dashboard - share trades');
        $form = $this->createForm(QueryTradeType::class, null, [
            'asset_filters' => false,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->shareTradeRepository->findByWithAssociations(
            array_merge($filters ?? [], ['assetId' => $asset->getId()]),
            [
                $filters['orderBy'] ?? 'createdAt' =>
                    $filters['orderDirection'] ?? 'DESC',
            ],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/products/dashboard/share_trades.html.twig', [
            'asset' => $asset,
            'objects' => $results,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/trade-orders', name: 'admin_product_trade_orders', methods: ['GET'])]
    public function tradeOrders(Request $request, Asset $asset): Response
    {
        $this->logger->debug('Asset product dashboard - share trades');
        $form = $this->createForm(QueryTradeOrderType::class, null, [
            'asset_filters' => false,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->tradeOrderRepository->findByWithAssociations(
            array_merge($filters ?? [], ['assetId' => $asset->getId()]),
            [
                $filters['orderBy'] ?? 'createdAt' =>
                    $filters['orderDirection'] ?? 'DESC',
            ],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );

        return $this->render('admin/pages/products/dashboard/trade_orders.html.twig', [
            'asset' => $asset,
            'objects' => $results,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/investments', name: 'admin_product_investments', methods: ['GET'])]
    public function investments(Request $request, Asset $asset): Response
    {
        $this->logger->debug('Asset product dashboard - investments');
        $form = $this->createForm(QueryInvestmentType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->investmentRepository->findByWithAssociations(
            array_merge($filters ?? [], [
                'assetId' => $asset->getId(),
                'lifecycleStatus' => [
                    InvestmentLifecycle::STATE_APPROVED,
                    InvestmentLifecycle::STATE_SETTLED,
                ],
            ]),
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/products/dashboard/investments.html.twig', [
            'asset' => $asset,
            'investments' => $results,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/listings', name: 'admin_product_listings', methods: ['GET'])]
    public function listings(Request $request, Asset $asset): Response
    {
        $this->logger->debug('Asset product dashboard - relistings');
        $form = $this->createForm(QueryOfferingType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->offeringRepository->findByWithAssociations(
            array_merge($filters ?? [], [
                'assetId' => $asset->getId(),
            ]),
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/products/dashboard/listings.html.twig', [
            'asset' => $asset,
            'offerings' => $results,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/payments', name: 'admin_product_payments', methods: ['GET'])]
    public function payments(Request $request, Asset $asset): Response
    {
        $this->logger->debug('Asset product dashboard - payments');
        $form = $this->createForm(QueryPayoutType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->payoutRepository->findByWithAssociations(
            array_merge($filters ?? [], [
                'assetId' => $asset->getId(),
            ]),
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        try {
            $payoutSummary = array_filter(
                $this->payoutRepository->getDividendSummaryByAsset(),
                function ($k) use ($asset) {
                    return $asset->getId() == $k['assetId'];
                },
            );
            if (!empty($payoutSummary)) {
                $payoutSummary = array_values($payoutSummary)[0];
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this->render('admin/pages/products/dashboard/payments.html.twig', [
            'asset' => $asset,
            'payments' => $results,
            'payoutSummary' => $payoutSummary ?? [],
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        '/{id}/payment-orders',
        name: 'admin_product_payment_orders',
        methods: ['GET'],
    )]
    public function paymentOrders(Request $request, Asset $asset): Response
    {
        $this->logger->debug('Asset product dashboard - payment orders');
        $form = $this->createForm(QueryPaymentOrderType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->paymentOrderRepository->findByWithAssociations(
            array_merge($filters ?? [], [
                'assetId' => $asset->getId(),
            ]),
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/products/dashboard/payment_orders.html.twig', [
            'asset' => $asset,
            'paymentOrders' => $results,
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        '/{id}/transfer-orders',
        name: 'admin_product_transfer_orders',
        methods: ['GET'],
    )]
    public function transferOrders(Request $request, Asset $asset): Response
    {
        $this->logger->debug('Asset product dashboard - transfer orders');
        $form = $this->createForm(QueryTransferOrderType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->transferOrderRepository->findByWithAssociations(
            array_merge($filters ?? [], [
                'assetId' => $asset->getId(),
            ]),
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/products/dashboard/transfer_orders.html.twig', [
            'asset' => $asset,
            'transferOrders' => $results,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/documents', name: 'admin_product_documents', methods: ['GET'])]
    public function documents(Asset $asset): Response
    {
        $this->logger->debug('Asset product dashboard - documents');
        // $offering = $this->offeringRepository->findFirstPartyByAssetId($asset->getId());
        // if (is_null($offering)) {
        //     // Future: redirect to a recovery page to create the offering and continue setup
        //     $this->addFlash(
        //         'error',
        //         'No first party offering associated with this asset',
        //     );
        //     return $this->redirectToRoute('admin_products_index');
        // }
        return $this->render('admin/pages/products/dashboard/documents.html.twig', [
            'asset' => $asset,
            // 'offering' => $offering,
            'sortedDocs' => $this->productService->sortDocuments($asset),
        ]);
    }

    #[Route('/{id}/status-logs', name: 'admin_product_status_logs', methods: ['GET'])]
    public function statusLogs(Asset $asset): Response
    {
        $this->logger->debug('Asset product dashboard - status logs');
        return $this->render('admin/pages/products/dashboard/status_logs.html.twig', [
            'asset' => $asset,
        ]);
    }
}
