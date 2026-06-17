<?php

namespace App\Controller\Admin;

use App\Entity\AbstractOrder;
use App\Entity\Asset;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Investment;
use App\Entity\Lifecycle\InvestmentLifecycle;
use App\Entity\Offering;
use App\Entity\PaymentOrder;
use App\Repository\InvestmentRepository;
use App\Repository\OfferingDocumentRepository;
use App\Repository\OfferingRepository;
use App\Repository\PaymentOrderRepository;
use App\Repository\PayoutRepository;
use App\Repository\ShareTradeRepository;
use App\Repository\TradeOrderRepository;
use App\Service\PaymentService;
use App\Service\Porting\DivestmentPorter;
use App\Service\Porting\DivestmentScanner;
use App\Service\Porting\InvestmentPorter;
use App\Service\Porting\OfferingPorter;
use App\Service\Porting\RepaymentPorter;
use App\Service\Porting\RepaymentScanner;
use App\Service\Porting\SettlementPorter;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/maintenance/trade-system-porting')]
class TradeSystemPortingController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private OfferingRepository $offeringRepository,
        private OfferingDocumentRepository $offeringDocumentRepository,
        private InvestmentRepository $investmentRepository,
        private TradeOrderRepository $tradeOrderRepository,
        private ShareTradeRepository $shareTradeRepository,
        private PayoutRepository $payoutRepository,
        private PaymentOrderRepository $paymentOrderRepository,
        private OfferingPorter $offeringPorter,
        private InvestmentPorter $investmentPorter,
        private DivestmentScanner $divestmentScanner,
        private RepaymentScanner $repaymentScanner,
        private DivestmentPorter $divestmentPorter,
        private RepaymentPorter $repaymentPorter,
        private SettlementPorter $settlementPorter,
    ) {}

    #[Route('', name: 'admin_trade_system_porting_index', methods: ['GET'])]
    #[IsGranted('ROLE_TECH_OPS')]
    public function hub(): Response
    {
        $this->logger->notice('Trade system - porting hub');
        return $this->render('admin/pages/maintenance/porting/hub.html.twig');
    }

    #[Route(
        '/offerings',
        name: 'admin_trade_system_port_offerings',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_TECH_OPS')]
    public function portOfferings(Request $request): Response
    {
        $this->logger->notice('Trade system - porting offerings');
        $batchSize = $request->query->get('batchSize', 10);
        $batchSize = min(max($batchSize, 1), 200);
        $form = $this
            ->createFormBuilder()
            ->add('port', SubmitType::class, [])
            ->getForm();
        $form->handleRequest($request);
        $offerings = $this->offeringRepository
            ->buildQueryWithAssociations([
                'tradeOrder' => false,
            ])
            ->setMaxResults($batchSize)
            ->getResult();
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                foreach ($offerings as $offering) {
                    $tradeOrder = $this->offeringPorter->portOffering($offering);
                    $this->entityManager->persist($tradeOrder);
                    // Sync the trade order relation
                    $this->entityManager->persist($offering);
                }
                $this->entityManager->flush();
                $count = count($offerings);
                $this->addFlash('success', "Ported {$count} offerings");
                return $this->redirectToRoute('admin_trade_system_port_offerings');
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Unable to port offering. ' . $e->getMessage(),
                );
                $this->logger->error('Unable to port offering. ', [$e->getMessage()]);
            }
        }

        return $this->render('admin/pages/maintenance/porting/offerings.html.twig', [
            'form' => $form,
            'offerings' => $offerings,
        ]);
    }

    #[Route(
        '/offerings/{id}',
        name: 'admin_trade_system_port_offering_single',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_TECH_OPS')]
    public function portOfferingSingle(
        Request $request,
        #[MapEntity(id: 'id')] Offering $offering,
    ): Response {
        $this->logger->notice("Trade system - porting offering #{$offering->getId()}");

        if ($offering->getTradeOrder() !== null) {
            $this->addFlash('notice', 'Already ported to trade order');
        }
        $form = $this
            ->createFormBuilder()
            ->add('port', SubmitType::class, [])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $tradeOrder = $this->offeringPorter->portOffering($offering);
                $this->entityManager->persist($tradeOrder);
                // Sync the trade order relation
                $this->entityManager->persist($offering);
                $this->entityManager->flush();
                return $this->redirectToRoute('admin_offering_edit', ['id' => $offering->getId()]);
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Unable to port offering. ' . $e->getMessage(),
                );
                $this->logger->error('Unable to port offering. ', [$e->getMessage()]);
            }
        }

        return $this->render('admin/pages/maintenance/porting/offering_single.html.twig', [
            'form' => $form,
            'offering' => $offering,
        ]);
    }

    #[Route(
        '/investments',
        name: 'admin_trade_system_port_investments',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_TECH_OPS')]
    public function portInvestments(Request $request): Response
    {
        $this->logger->notice('Trade system - porting investments');
        $batchSize = $request->query->get('batchSize', 10);
        $batchSize = min(max($batchSize, 1), 1000);
        $form = $this
            ->createFormBuilder()
            ->add('port', SubmitType::class, [])
            ->getForm();
        $form->handleRequest($request);
        $investments = $this->investmentRepository
            ->buildQueryWithAssociations([
                'tradeOrder' => false,
                'shareTrade' => false,
            ])
            ->setMaxResults($batchSize)
            ->getResult();
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                foreach ($investments as $investment) {
                    $tradeOrder =
                        $this->investmentPorter->portInvestmentOrder($investment);
                    $shareTrade = $this->investmentPorter->portInvestmentTrade(
                        $investment,
                        $tradeOrder,
                    );
                    $this->entityManager->persist($tradeOrder);
                    $this->entityManager->persist($shareTrade);
                    // Sync the relations
                    $this->entityManager->persist($investment);
                }
                $this->entityManager->flush();
                $count = count($investments);
                $this->addFlash('success', "Ported {$count} investments");
                return $this->redirectToRoute('admin_trade_system_port_investments');
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Unable to port investment. ' . $e->getMessage(),
                );
                $this->logger->error('Unable to port investment. ', [$e->getMessage()]);
            }
        }

        return $this->render('admin/pages/maintenance/porting/investments.html.twig', [
            'form' => $form,
            'investments' => $investments,
        ]);
    }

    #[Route(
        '/investments/{id}',
        name: 'admin_trade_system_port_investment_single',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_TECH_OPS')]
    public function portInvestmentSingle(
        Request $request,
        #[MapEntity(id: 'id')] Investment $investment,
    ): Response {
        $this->logger->notice(
            "Trade system - porting investment #{$investment->getId()}",
        );

        if ($investment->getTradeOrder() !== null) {
            $this->addFlash('notice', 'Already ported to trade order');
        }
        if ($investment->getShareTrade() !== null) {
            $this->addFlash('notice', 'Already ported to share trade');
        }
        $form = $this
            ->createFormBuilder()
            ->add('port', SubmitType::class, [])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $tradeOrder = $this->investmentPorter->portInvestmentOrder($investment);
                $shareTrade = $this->investmentPorter->portInvestmentTrade(
                    $investment,
                    $tradeOrder,
                );
                $this->entityManager->persist($tradeOrder);
                $this->entityManager->persist($shareTrade);
                // Sync the relations
                $this->entityManager->persist($investment);
                $this->entityManager->flush();
                return $this->redirectToRoute('admin_investment_edit', ['id' => $investment->getId()]);
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Unable to port investment. ' . $e->getMessage(),
                );
                $this->logger->error('Unable to port investment. ', [$e->getMessage()]);
            }
        }

        return $this->render('admin/pages/maintenance/porting/investment_single.html.twig', [
            'form' => $form,
            'investment' => $investment,
        ]);
    }

    #[Route(
        '/divestments',
        name: 'admin_trade_system_port_divestments',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_TECH_OPS')]
    public function scanDivestments(Request $request): Response
    {
        $this->logger->notice('Trade system - scan divestments');

        $tradeHoldings = $this->shareTradeRepository->aggregateSharesInCirculation();

        $tradeHoldings = array_combine(
            array_column($tradeHoldings, 'assetid'),
            $tradeHoldings,
        );
        $divestmentsPaid = $this->divestmentScanner->scanAssetsDivested();
        $divestmentBuyBacks = $this->divestmentScanner->scanTradeBuyBacks();
        $divestmentBuyBacks = array_combine(
            array_column($divestmentBuyBacks, 'assetid'),
            $divestmentBuyBacks,
        );

        return $this->render('admin/pages/maintenance/porting/divestments_query.html.twig', [
            'tradeHoldings' => $tradeHoldings,
            'divestmentsPaid' => $divestmentsPaid,
            'divestmentBuyBacks' => $divestmentBuyBacks,
        ]);
    }

    #[Route(
        '/divestments/{id}',
        name: 'admin_trade_system_port_divestments_asset',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_TECH_OPS')]
    public function portDivestments(
        Request $request,
        #[MapEntity(id: 'id')] Asset $asset,
    ): Response {
        $this->logger->notice('Trade system - port divestments');

        // Core scanning data
        $tradeHoldings = $this->shareTradeRepository->aggregateAssetShareholdingsByUser($asset->getId());
        $tradeHoldings = array_combine(
            array_column($tradeHoldings, 'userid'),
            $tradeHoldings,
        );
        $sharesCirculating = array_sum(array_filter(
            array_column($tradeHoldings, 'shares'),
            fn(int|string $item): bool => $item >= 0,
        ));
        $this->logger->debug('Shares Circulating', [
            'sum' => $sharesCirculating,
        ]);
        $divestmentsPaid = $this->divestmentScanner->scanAssetsDivested($asset->getId());
        $divestmentBuyBacks = $this->divestmentScanner->scanTradeBuyBacks($asset->getId());
        $divestmentBuyBacks = array_combine(
            array_column($divestmentBuyBacks, 'userid'),
            $divestmentBuyBacks,
        );

        // Determining the generation
        $divestmentOrders = $this->paymentOrderRepository->buildQueryWithAssociations([
            'assetId' => $asset->getId(),
            'paymentType' => [
                PaymentService::TYPE_DIVESTMENT,
                PaymentService::TYPE_INVESTMENT_EXIT,
            ],
            'status' => [
                AbstractOrder::STATE_COMPLETED,
                AbstractOrder::STATE_IN_PROGRESS,
            ],
        ])->getResult();
        if (empty($divestmentOrders)) {
            $generation = 1; // Exclusively use payouts
        } else {
            $generation = 2; // Use payment orders
        }
        $buyOrders = $this->tradeOrderRepository->buildQueryWithAssociations([
            'assetId' => $asset->getId(),
            'status' => [TradeOrderStatus::Active, TradeOrderStatus::Completed],
            'type' => TradeOrderType::BuyBack,
            'direction' => TradeDirection::Buy,
        ])->getResult();

        $sellOrders = $this->tradeOrderRepository->buildQueryWithAssociations([
            'assetId' => $asset->getId(),
            'status' => [TradeOrderStatus::Active, TradeOrderStatus::Completed],
            'type' => TradeOrderType::BuyBack,
            'direction' => TradeDirection::Sell,
        ])->getResult();

        $form = $this
            ->createFormBuilder()
            ->add('port', SubmitType::class, [])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($sharesCirculating <= 0) {
                $this->addFlash(
                    'warning',
                    'Nothing to port, no shares circulating in asset.',
                );
                return $this->redirectToRoute('admin_trade_system_port_divestments_asset', ['id' => $asset->getId()]);
            }
            // Get any of the first party orders
            $initialOrder = $this->tradeOrderRepository->buildQueryWithAssociations([
                'assetId' => $asset->getId(),
                'status' => TradeOrderStatus::Completed,
                'type' => TradeOrderType::Initial,
                'direction' => TradeDirection::Sell,
            ])->getResult()[0];
            try {
                if ($generation == 1) {
                    $payouts = $this->payoutRepository->buildQueryWithAssociations([
                        'assetId' => $asset->getId(),
                        'payoutType' => 1,
                    ])->getResult();
                    if (empty($buyOrders)) {
                        $buyOrder = $this->divestmentPorter->createBuyBackOrder(
                            $initialOrder,
                            array_sum(array_column(
                                $divestmentsPaid,
                                'investmentShares',
                            )),
                            array_sum(array_column($divestmentsPaid, 'value')),
                            $payouts,
                        );
                        $this->entityManager->persist($buyOrder);
                    } else {
                        $buyOrder = $buyOrders[0];
                    }

                    foreach ($payouts as $payout) {
                        $sellOrder = $this->divestmentPorter->portPayoutOrder($payout);
                        $shareTrade = $this->divestmentPorter->portPayoutTrade(
                            $payout,
                            $sellOrder,
                            $buyOrder,
                        );
                        $this->entityManager->persist($sellOrder);
                        $this->entityManager->persist($shareTrade);
                    }
                    $count = count($payouts);
                    $this->addFlash('success', "Ported {$count} payouts");
                } else {
                    if (empty($buyOrders)) {
                        $buyOrder = $this->divestmentPorter->createBuyBackOrderFromPaymentOrder(
                            $initialOrder,
                            $divestmentOrders[0],
                        );
                        $this->entityManager->persist($buyOrder);
                    } else {
                        $buyOrder = $buyOrders[0];
                    }

                    /**
                     * @var PaymentOrder[] $divestmentOrders
                     */
                    foreach ($divestmentOrders[0]->getPayments() as $paymentRequest) {
                        $sellOrder =
                            $this->divestmentPorter->portPaymentRequestOrder(
                                $paymentRequest,
                            );
                        $shareTrade = $this->divestmentPorter->portPayoutTrade(
                            $paymentRequest->getPayout(),
                            $sellOrder,
                            $buyOrder,
                        );
                        $paymentRequest->setShareTrade($shareTrade);
                        $this->entityManager->persist($sellOrder);
                        $this->entityManager->persist($shareTrade);
                        // Sync the relation
                        $this->entityManager->persist($paymentRequest);
                    }
                    $count = count($divestmentOrders[0]->getPayments());
                    $this->addFlash('success', "Ported {$count} payment requests");
                }
                $this->entityManager->flush();
                return $this->redirectToRoute('admin_trade_system_port_divestments_asset', ['id' => $asset->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Unable to port payouts. ' . $e->getMessage());
                $this->logger->error('Unable to port payouts. ', [$e->getMessage()]);
            }
        }
        return $this->render('admin/pages/maintenance/porting/divestments_single.html.twig', [
            'tradeHoldings' => $tradeHoldings,
            'divestmentsPaid' => $divestmentsPaid,
            'divestmentBuyBacks' => $divestmentBuyBacks,
            'buyOrders' => $buyOrders,
            'sellOrders' => $sellOrders,
            'generation' => $generation,
            'sharesCirculating' => $sharesCirculating,
            'asset' => $asset,
            'form' => $form,
        ]);
    }

    #[Route(
        '/repayments',
        name: 'admin_trade_system_port_repayments',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_TECH_OPS')]
    public function scanRepayments(Request $request): Response
    {
        $this->logger->notice('Trade system - scan repayments');

        $tradeHoldings = $this->shareTradeRepository->aggregateSharesInCirculation();

        $tradeHoldings = array_combine(
            array_column($tradeHoldings, 'assetid'),
            $tradeHoldings,
        );
        $repaymentsMade = $this->repaymentScanner->scanAssetRepayments();

        return $this->render('admin/pages/maintenance/porting/repayments_query.html.twig', [
            'tradeHoldings' => $tradeHoldings,
            'repaymentsMade' => $repaymentsMade,
        ]);
    }

    #[Route(
        '/repayments/{id}',
        name: 'admin_trade_system_port_repayments_asset',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_TECH_OPS')]
    public function portRepayments(
        Request $request,
        #[MapEntity(id: 'id')] Asset $asset,
    ): Response {
        $this->logger->notice('Trade system - port repayments');

        $tradeHoldings = $this->shareTradeRepository->aggregateAssetShareholdingsByUser($asset->getId());
        $tradeHoldings = array_combine(
            array_column($tradeHoldings, 'userid'),
            $tradeHoldings,
        );
        $sharesCirculating = array_sum(array_filter(
            array_column($tradeHoldings, 'shares'),
            fn(int|string $item): bool => $item >= 0,
        ));

        $repaymentsMade = $this->repaymentScanner->scanAssetRepayments($asset->getId());

        // Determining the generation
        $repaymentOrders = $this->paymentOrderRepository
            ->buildQueryWithAssociations([
                'assetId' => $asset->getId(),
                'paymentType' => [
                    PaymentService::TYPE_REPAYMENT,
                ],
                'status' => [
                    AbstractOrder::STATE_COMPLETED,
                    AbstractOrder::STATE_IN_PROGRESS,
                ],
            ], ['scheduledFor' => 'DESC'])
            ->getResult();

        $sellOrders = $this->tradeOrderRepository->buildQueryWithAssociations([
            'assetId' => $asset->getId(),
            'status' => [TradeOrderStatus::Active, TradeOrderStatus::Completed],
            'type' => TradeOrderType::Prefunding,
            'direction' => TradeDirection::Sell,
        ])->getResult();

        $buyOrders = $this->tradeOrderRepository->buildQueryWithAssociations([
            'assetId' => $asset->getId(),
            'status' => [TradeOrderStatus::Active, TradeOrderStatus::Completed],
            'type' => TradeOrderType::Proxy,
            'direction' => TradeDirection::Buy,
        ])->getResult();

        /**
         * @var Investment[] $investments
         */
        $investments = $this->investmentRepository->buildQueryWithAssociations([
            'type' => 'prefunding',
            'lifecycleStatus' => InvestmentLifecycle::STATE_SETTLED,
            'assetId' => $asset->getId(),
        ])->getResult();

        /**
         * @var Investment[] $lastRetailInvestments
         */
        $lastRetailInvestments = $this->investmentRepository
            ->buildQueryWithAssociations([
                'type' => 'normal',
                'lifecycleStatus' => InvestmentLifecycle::STATE_SETTLED,
                'offeringId' => $investments[0]->getOffering()->getId(),
            ], ['createdAt' => 'DESC'])
            ->setMaxResults(1)
            ->getResult();

        $repaymentBuyBacks = $this->repaymentScanner->scanTradeBuyBacks($asset->getId());
        $repaymentBuyBacks = array_combine(
            array_column($repaymentBuyBacks, 'userid'),
            $repaymentBuyBacks,
        );

        $form = $this
            ->createFormBuilder()
            ->add('port', SubmitType::class, [])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // This go inside the form processing as the first guard clause to prevent further changes
            if ($sharesCirculating <= $asset->getAmountOfShares()) {
                $this->addFlash(
                    'warning',
                    'No excess shares in asset. No prefunder repayments expected.',
                );

                return $this->redirectToRoute('admin_trade_system_port_repayments_asset', ['id' => $asset->getId()]);
            }
            // Get any of the first party orders
            $initialOrder = $this->tradeOrderRepository->buildQueryWithAssociations([
                'assetId' => $asset->getId(),
                'status' => [TradeOrderStatus::Active, TradeOrderStatus::Completed],
                'type' => TradeOrderType::Initial,
                'direction' => TradeDirection::Sell,
            ])->getResult()[0];

            try {
                $lastRepayment = $lastRetailInvestments[0]->getCreatedAt();
                if (count($repaymentOrders) > 0) {
                    $lastRepaymentOrder = array_first($repaymentOrders);
                    $lastRepayment = max(
                        $lastRetailInvestments[0]->getCreatedAt(),
                        $lastRepaymentOrder->getScheduledFor(),
                        $lastRepaymentOrder->getCreatedAt(),
                    );
                }
                if (empty($buyOrders)) {
                    $buyOrder = $this->repaymentPorter->createBuyBackOrder(
                        $initialOrder,
                        array_sum(array_column($repaymentsMade, 'sharesCombined')),
                        $lastRepayment,
                        $lastRepayment == $lastRetailInvestments[0]->getCreatedAt(),
                    );
                    $this->entityManager->persist($buyOrder);
                } else {
                    $buyOrder = $buyOrders[0];
                }

                foreach ($investments as $investment) {
                    if ($investment->getShareAmount() > 0) {
                        $sellOrder = $this->repaymentPorter->portInvestmentSellOrder(
                            $investment,
                            $lastRepayment,
                        );
                        $this->entityManager->persist($sellOrder);
                        if ($investment->getDivestedShares() > 0) {
                            $shareTrade = $this->repaymentPorter->portRepaymentTrade(
                                $investment->getDivestedShares(),
                                $sellOrder,
                                $buyOrder,
                            );
                            $this->entityManager->persist($shareTrade);
                        }
                    }
                }
                $count = count($investments);
                $this->addFlash('success', "Ported {$count} investments");
                $this->entityManager->flush();
                return $this->redirectToRoute('admin_trade_system_port_repayments_asset', ['id' => $asset->getId()]);
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Unable to port repayments. ' . $e->getMessage(),
                );
                $this->logger->error('Unable to port repayments. ', [$e->getMessage()]);
            }
        }

        return $this->render('admin/pages/maintenance/porting/repayments_single.html.twig', [
            'tradeHoldings' => $tradeHoldings,
            'repaymentsMade' => $repaymentsMade,
            'buyOrders' => $buyOrders,
            'sellOrders' => $sellOrders,
            'repaymentBuyBacks' => $repaymentBuyBacks ?? [],
            'sharesCirculating' => $sharesCirculating,
            'repaymentOrders' => $repaymentOrders,
            'investments' => $investments,
            'lastRetailInvestments' => $lastRetailInvestments,
            'asset' => $asset,
            'form' => $form,
        ]);
    }

    #[Route(
        '/offering-documents',
        name: 'admin_trade_system_port_offering_documents',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_TECH_OPS')]
    public function portOfferingDocuments(Request $request): Response
    {
        $this->logger->notice('Trade system - porting offering documents');
        $batchSize = $request->query->get('batchSize', 10);
        $batchSize = min(max($batchSize, 1), 200);
        $form = $this
            ->createFormBuilder()
            ->add('port', SubmitType::class, [])
            ->getForm();
        $form->handleRequest($request);
        $offeringDocs = $this->offeringDocumentRepository
            ->buildQueryWithAssociations([
                'hasCreatedById' => false,
                'hasTag' => true,
                'hasDocumentUrl' => true,
                // firsty party offerings only
                'hasSell_investment' => false,
                // misnomer, as all legitimate offers have isSecondaryMarket checked
                // So we are filtering out "broken" offerings
                'offeringIsSecondaryMrkt' => true,
            ])
            ->setMaxResults($batchSize)
            ->getResult();
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                foreach ($offeringDocs as $offeringDoc) {
                    $assetDoc =
                        $this->offeringPorter->portOfferingDocument($offeringDoc);
                    $this->entityManager->persist($assetDoc);
                }
                $this->entityManager->flush();
                $count = count($offeringDocs);
                $this->addFlash('success', "Ported {$count} offering documents");
                return $this->redirectToRoute(
                    'admin_trade_system_port_offering_documents',
                );
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Unable to port offering documents. ' . $e->getMessage(),
                );
                $this->logger->error('Unable to port offering documents. ', [$e->getMessage()]);
            }
        }

        return $this->render('admin/pages/maintenance/porting/offering_documents.html.twig', [
            'form' => $form,
            'offeringDocs' => $offeringDocs,
        ]);
    }

    #[Route(
        '/settlements',
        name: 'admin_trade_system_port_settlements',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_TECH_OPS')]
    public function portSettlements(Request $request): Response
    {
        $this->logger->notice('Trade system - porting settlement share-trade relation');
        $settlementOrderCounts = $this->settlementPorter->scanSettlementsOrders();
        $form = $this
            ->createFormBuilder()
            ->add('port', SubmitType::class, [])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $result = $this->settlementPorter->portSettlements();
                $this->addFlash(
                    'success',
                    "Ported {$result} settlement request share trade relations",
                );
                return $this->redirectToRoute('admin_trade_system_port_settlements');
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Unable to port offering. ' . $e->getMessage(),
                );
                $this->logger->error('Unable to port offering. ', [$e->getMessage()]);
            }
        }

        return $this->render('admin/pages/maintenance/porting/settlements.html.twig', [
            'form' => $form,
            'settlementOrderCounts' => $settlementOrderCounts,
        ]);
    }
}
