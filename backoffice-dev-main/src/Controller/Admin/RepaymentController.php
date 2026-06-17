<?php

namespace App\Controller\Admin;

use App\Entity\Asset;
use App\Entity\Enum\OrderingDirection;
use App\Entity\Enum\PaymentType;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TradeStatus;
use App\Entity\Enum\TransferOrderPreset;
use App\Entity\Investment;
use App\Entity\Lifecycle\InvestmentLifecycle;
use App\Entity\PaymentOrder;
use App\Entity\TransferOrder;
use App\Entity\TransferRequest;
use App\Form\Type\AssetRelationType;
use App\Form\Type\AssetTransferRequestType;
use App\Form\Type\PaymentOrderGenerateType;
use App\Repository\AssetRepository;
use App\Repository\HoldingRepository;
use App\Repository\InvestmentRepository;
use App\Repository\OfferingRepository;
use App\Repository\ShareTradeRepository;
use App\Repository\TradeOrderRepository;
use App\Service\DivestmentService;
use App\Service\Manager\AssetManagerV2;
use App\Service\Manager\InvestmentManagerV2;
use App\Service\MonthEndService;
use App\Service\PaymentGeneratorService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/monthend/repayments')]
class RepaymentController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private MonthEndService $monthEndService,
        private AssetRepository $assetRepository,
        private OfferingRepository $offeringRepository,
        private InvestmentRepository $investmentRepository,
        private TradeOrderRepository $tradeOrderRepository,
        private ShareTradeRepository $shareTradeRepository,
        private HoldingRepository $holdingRepository,
        private PaymentGeneratorService $paymentGeneratorService,
        private DivestmentService $divestmentService,
        private AssetManagerV2 $assetManager,
        private InvestmentManagerV2 $investmentManager,
    ) {}

    #[Route(
        '/create',
        name: 'admin_monthend_repayment_create',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function create(Request $request): Response
    {
        $paymentOrder = $this->monthEndService->createPaymentOrderByType(PaymentType::Repayment);
        if ((int) $request->query->get('assetId')) {
            $asset = $this->assetRepository->find($request->query->get('assetId'));
            if (!is_null($asset)) {
                $paymentOrder->setAsset($asset);
            }
        }
        $form = $this->createForm(AssetRelationType::class, $paymentOrder, [
            'data_class' => PaymentOrder::class,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->persist($paymentOrder);
            $this->doctrine->getManager()->flush();
            $this->logger->debug("Created new repayment #{$paymentOrder->getId()}");
            $this->addFlash('success', 'Payment order successfully created');
            return $this->redirectToRoute('admin_payment_order_date', [
                'id' => $paymentOrder->getId(),
                'setup' => 1,
                'redirectRoute' => 'admin_monthend_repayment_manage',
            ]);
        }
        return $this->render('admin/pages/monthend/payments/create.html.twig', [
            'form' => $form->createView(),
            'paymentType' => PaymentType::Repayment->value,
        ]);
    }

    #[Route(
        '/create/{id}',
        name: 'admin_monthend_repayment_create_monthend',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function createForMonthend(Asset $asset): Response
    {
        $paymentOrder = $this->monthEndService->createPaymentOrderByType(PaymentType::Repayment);
        $paymentOrder->setAsset($asset);
        $this->doctrine->getManager()->persist($paymentOrder);
        $this->doctrine->getManager()->flush();
        $this->logger->debug("Created new repayment #{$paymentOrder->getId()}");
        $this->addFlash('success', 'Payment order successfully created');
        return $this->redirectToRoute('admin_monthend_repayment_generate', [
            'id' => $paymentOrder->getId(),
        ]);
    }

    #[Route('/{id}', name: 'admin_monthend_repayment_manage', methods: ['GET'])]
    #[IsGranted('ROLE_ANALYST')]
    public function manage(PaymentOrder $paymentOrder): Response
    {
        if (!(PaymentType::Repayment->value === $paymentOrder->getPaymentType())) {
            $this->addFlash(
                'warning',
                "Payment order #{$paymentOrder->getId()} is not for repayments",
            );
            return $this->redirectToRoute('admin_payment_order_manage', ['id' => $paymentOrder->getId()]);
        }
        try {
            $balance = $this->assetManager->getAssetWalletByType(
                $paymentOrder->getAsset(),
                $paymentOrder->getDebitWallet() == 'distribution'
                    ? $paymentOrder->getDebitWallet()
                    : 'settlement',
            )['balance'];
        } catch (\Exception $e) {
            $this->addFlash(
                'warning',
                'Unable to retrieve wallet balance. ' . $e->getMessage(),
            );
            $this->logger->error('Unable to retreive wallet balance', [
                'asset #' . $paymentOrder->getAsset()->getId(),
                $e->getMessage(),
            ]);
        }
        $shareholders = $this->shareTradeRepository->aggregateAssetShareholdingsByUser(
            assetId: $paymentOrder->getAsset()->getId(),
            nonZero: true,
            shareholderOrdering: OrderingDirection::Descending,
        );
        $monthendTrades = $this->shareTradeRepository
            ->buildQueryWithAssociations([
                'assetId' => $paymentOrder->getAsset()->getId(),
                'status' => TradeStatus::Settled,
                'buyOrderType' => TradeOrderType::tradingBuyTypes(),
                'createdAt_gte' => new \DateTime('first day of last month')->setTime(
                    0,
                    0,
                ),
                'createdAt_lt' => new \DateTime('first day of this month')->setTime(
                    0,
                    0,
                ),
            ], ['id' => 'DESC'])
            ->getResult();
        $prefunderSellOrders = $this->tradeOrderRepository
            ->buildQueryWithAssociations([
                'assetId' => $paymentOrder->getAsset()->getId(),
                'status' => [TradeOrderStatus::Active, TradeOrderStatus::Completed],
                'type' => TradeOrderType::Prefunding,
                'direction' => TradeDirection::Sell,
            ], ['numberOfShares' => 'ASC'])
            ->getResult();
        $repaymentSummary =
            $this->divestmentService->compileRepaymentProgress($prefunderSellOrders);
        return $this->render('admin/pages/monthend/payments/manage_repayment.html.twig', [
            'paymentOrder' => $paymentOrder,
            'monthendTrades' => $monthendTrades,
            'prefunderSellOrders' => $prefunderSellOrders,
            'walletBalance' => $balance ?? 0,
            'currentShareholders' => $shareholders,
            'repaymentSummary' => $repaymentSummary,
        ]);
    }

    #[Route(
        '/{id}/generate',
        name: 'admin_monthend_repayment_generate',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function generate(Request $request, PaymentOrder $paymentOrder): Response
    {
        if (!(PaymentType::Repayment->value === $paymentOrder->getPaymentType())) {
            $this->addFlash(
                'warning',
                "Payment order #{$paymentOrder->getId()} is not for repayments",
            );
            return $this->redirectToRoute('admin_payment_order_manage', ['id' => $paymentOrder->getId()]);
        }
        if (PaymentOrder::STATE_DRAFT != $paymentOrder->getStatus()) {
            $this->addFlash(
                'warning',
                'Payments can only be added when the order is in draft mode',
            );
            return $this->redirectToRoute(
                'admin_monthend_repayment_manage',
                ['id' => $paymentOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        // try {
        //     $balance = $this->assetManager->getAssetWalletByType(
        //         $paymentOrder->getAsset(),
        //         $paymentOrder->getDebitWallet() == 'distribution' ? $paymentOrder->getDebitWallet() : 'settlement',
        //     )['balance'];
        // } catch (\Exception $e) {
        //     $this->addFlash('warning', 'Unable to retrieve wallet balance. ' . $e->getMessage());
        //     $this->logger->error('Unable to retreive wallet balance', [
        //         'asset #' . $paymentOrder->getAsset()->getId(),
        //         $e->getMessage()
        //     ]);
        // }
        $shareholders = $this->shareTradeRepository->aggregateAssetShareholdingsByUser(
            assetId: $paymentOrder->getAsset()->getId(),
            nonZero: true,
            shareholderOrdering: OrderingDirection::Descending,
        );
        if (empty($shareholders)) {
            $this->addFlash(
                'warning',
                'Unable to run generate payments. There are no shareholders in this asset!',
            );
            return $this->redirectToRoute(
                'admin_payment_order_manage',
                ['id' => $paymentOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        $sharesInCirculation = array_sum(array_column($shareholders, 'shares'));
        if ($sharesInCirculation > $paymentOrder->getAsset()->getAmountOfShares()) {
            $surplusShares =
                $sharesInCirculation - $paymentOrder->getAsset()->getAmountOfShares();
        }
        $monthendTrades = $this->shareTradeRepository
            ->buildQueryWithAssociations([
                'assetId' => $paymentOrder->getAsset()->getId(),
                'status' => TradeStatus::Settled,
                'buyOrderType' => TradeOrderType::tradingBuyTypes(),
                'createdAt_gte' => new \DateTime('first day of last month')->setTime(
                    0,
                    0,
                ),
                'createdAt_lt' => new \DateTime('first day of this month')->setTime(
                    0,
                    0,
                ),
            ], ['id' => 'DESC'])
            ->getResult();
        $prefunderSellOrders = $this->tradeOrderRepository
            ->buildQueryWithAssociations([
                'assetId' => $paymentOrder->getAsset()->getId(),
                'status' => [TradeOrderStatus::Active, TradeOrderStatus::Completed],
                'type' => TradeOrderType::Prefunding,
                'direction' => TradeDirection::Sell,
            ], ['numberOfShares' => 'ASC'])
            ->getResult();
        $repaymentSummary =
            $this->divestmentService->compileRepaymentProgress($prefunderSellOrders);
        $form = $this->createForm(
            PaymentOrderGenerateType::class,
            ['shares' => $surplusShares ?? null],
            ['paymentType' => PaymentType::Repayment],
        );
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->logger->info('Generate payments for Payment Order', [$paymentOrder->getId()]);
            try {
                $this->paymentGeneratorService->generateRepayments(
                    $paymentOrder,
                    $repaymentSummary,
                    $form->getData()['shares'],
                );
                $this->doctrine->getManager()->flush();
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Unable to run generate payments. ' . $e->getMessage(),
                );
                $this->logger->error('Unable to run generate payments. ', [$e->getMessage()]);
            }
            return $this->redirectToRoute(
                'admin_monthend_repayment_manage',
                ['id' => $paymentOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        return $this->render('admin/pages/monthend/payments/generate_repayment.html.twig', [
            'currentShareholders' => $shareholders,
            'form' => $form->createView(),
            'monthendTrades' => $monthendTrades,
            'paymentOrder' => $paymentOrder,
            'repaymentSummary' => $repaymentSummary,
            'sharesInCirculation' => $sharesInCirculation,
            'prefunderSellOrders' => $prefunderSellOrders,
            // 'walletBalance' => $balance ?? 0,
        ]);
    }

    #[Route(
        '/transfer/{id}/create',
        name: 'admin_monthend_repayment_transfer_create',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function transferCreate(Asset $asset): Response
    {
        $transferOrder = $this->monthEndService->createTransferOrderByPreset(TransferOrderPreset::PrefunderRepaymentTransfer);
        $transferOrder->setAsset($asset);
        $this->doctrine->getManager()->persist($transferOrder);
        $this->doctrine->getManager()->flush();
        $this->addFlash('success', 'Transfer order successfully created');
        return $this->redirectToRoute('admin_monthend_repayment_transfer_generate', [
            'id' => $transferOrder->getId(),
        ]);
    }

    #[Route(
        '/transfer/{id}/generate',
        name: 'admin_monthend_repayment_transfer_generate',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function transferGenerate(
        Request $request,
        TransferOrder $transferOrder,
    ): Response {
        if (is_null($transferOrder->getAsset())) {
            $this->addFlash(
                'warning',
                'Transfer order must be linked to an asset to use the repayment transfer generator',
            );
        }
        try {
            $settlementWalletBalance = $this->assetManager->getAssetWalletByType(
                $transferOrder->getAsset(),
                'settlement',
            )['balance'];
            $distributionWalletBalance = $this->assetManager->getAssetWalletByType(
                $transferOrder->getAsset(),
                'distribution',
            )['balance'];
        } catch (\Exception $e) {
            $this->addFlash(
                'warning',
                'Unable to retrieve wallet balance. ' . $e->getMessage(),
            );
            $this->logger->error('Unable to retreive wallet balance', [
                'asset #' . $transferOrder->getAsset()->getId(),
                $e->getMessage(),
            ]);
            return $this->redirectToRoute('admin_transfer_order_manage', [
                'id' => $transferOrder->getId(),
            ]);
        }
        $shareholders = $this->holdingRepository->getShareHoldings([
            'assetId' => $transferOrder->getAsset()->getId(),
            'currentHolding' => 1,
        ]);
        $sharesInCirculation = (int) array_sum(array_column(
            $shareholders,
            'currentHolding',
        ));
        $surplusShares =
            $sharesInCirculation - $transferOrder->getAsset()->getAmountOfShares();

        $walletChoices = $this->monthEndService->getAssetWalletChoices(
            $transferOrder->getAsset(),
        );
        $lastMonthDateRange = $this->monthEndService->getMonthEndDateRangeFromDateTime(
            $transferOrder->getScheduledFor(),
            -1,
        );
        // Query for first party retail investments from last month that are now settled
        // Note that any "off-season" settlements will not be picked up
        // Warn bizops about this in GUI
        $offering = $this->offeringRepository->findFirstPartyByAssetId(
            $transferOrder->getAsset()->getId(),
        );
        /** @var Investment[] $monthendInvestments */
        $monthendInvestments = $this->investmentRepository->buildQueryWithAssociations([
            'offeringId' => $offering->getId(),
            'lifecycleStatus' => InvestmentLifecycle::STATE_SETTLED,
            'type' => 'normal',
            'createdAt_gte' => $lastMonthDateRange['start'],
            'createdAt_le' => $lastMonthDateRange['end'],
        ])->getResult();

        // Create prefilled transfer request
        $transferRequest = new TransferRequest();
        $transferOrder->addTransfer($transferRequest);
        $transferRequest->setDescription('Move settled funds to repay prefunders');
        $transferRequest->setDebitWalletId($walletChoices['settlement']);
        $transferRequest->setCreditWalletId($walletChoices['distribution']);
        $transferRequest->setAmount((string) round(
            $surplusShares * $transferOrder->getAsset()->getPricePerShare(),
            2,
        ));
        $form = $this->createForm(AssetTransferRequestType::class, $transferRequest, [
            'creditWalletChoices' => $walletChoices,
            'lockDebitWallet' => true,
            'lockCreditWallet' => true,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Clear any existing transfers
            $transferOrder->getTransfers()->clear();
            // Reattach the transfer request to the order
            $transferOrder->addTransfer($transferRequest);
            $this->doctrine->getManager()->flush();
            $this->addFlash(
                'success',
                'Prefunder repayment transfer successfully created',
            );
            return $this->redirectToRoute('admin_transfer_order_manage', [
                'id' => $transferOrder->getId(),
            ]);
        }
        return $this->render('admin/pages/monthend/payments/generate_repayment_transfer.html.twig', [
            'form' => $form->createView(),
            'monthendInvestments' => $monthendInvestments,
            'transferOrder' => $transferOrder,
            'settlementWalletBalance' => $settlementWalletBalance ?? 0,
            'distributionWalletBalance' => $distributionWalletBalance ?? 0,
            'sharesInCirculation' => $sharesInCirculation,
        ]);
    }
}
