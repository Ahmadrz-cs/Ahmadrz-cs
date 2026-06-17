<?php

namespace App\Controller\Admin;

use App\Entity\Asset;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TradeStatus;
use App\Entity\Enum\TransferOrderPreset;
use App\Entity\ShareTrade;
use App\Entity\TransferOrder;
use App\Entity\TransferRequest;
use App\Entity\User;
use App\Form\Type\AssetRelationType;
use App\Form\Type\AssetTransferRequestType;
use App\Form\Type\MonthendOrderEditType;
use App\Form\Type\SettlementSearchGenerateType;
use App\Repository\AssetRepository;
use App\Repository\InvestmentRepository;
use App\Repository\OfferingRepository;
use App\Repository\ShareTradeRepository;
use App\Repository\TransferOrderRepository;
use App\Repository\UserRepository;
use App\Service\Manager\AssetManagerV2;
use App\Service\MonthEndService;
use App\Service\SettlementService;
use App\Service\TransferOrderService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/monthend/settlements')]
class SettlementController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private MonthEndService $monthEndService,
        private TransferOrderService $transferOrderService,
        private SettlementService $settlementService,
        private AssetRepository $assetRepository,
        private InvestmentRepository $investmentRepository,
        private OfferingRepository $offeringRepository,
        private UserRepository $userRepository,
        private ShareTradeRepository $shareTradeRepository,
        private TransferOrderRepository $transferOrderRepository,
        private AssetManagerV2 $assetManager,
    ) {}

    #[Route(
        '/create',
        name: 'admin_monthend_settlement_create',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function create(Request $request): Response
    {
        $transferOrder = $this->monthEndService->createTransferOrderByPreset(TransferOrderPreset::InvestmentSettlement);
        if ((int) $request->query->get('assetId')) {
            $asset = $this->assetRepository->find($request->query->get('assetId'));
            if (!is_null($asset)) {
                $transferOrder->setAsset($asset);
            }
        }
        $form = $this->createForm(AssetRelationType::class, $transferOrder, [
            'data_class' => TransferOrder::class,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->persist($transferOrder);
            $this->doctrine->getManager()->flush();
            $this->logger->debug(
                "Created new asset investment settlement order #{$transferOrder->getId()}",
            );
            $this->addFlash('success', 'Settlement order successfully created');
            // return $this->redirectToRoute('admin_monthend_settlement_generate', [
            //     'id' => $transferOrder->getId(),
            // ]);
            return $this->redirectToRoute('admin_monthend_settlement_manage', [
                'id' => $transferOrder->getId(),
            ]);
        }
        return $this->render('admin/pages/monthend/settlements/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        '/create/{id}',
        name: 'admin_monthend_settlement_create_monthend',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function createForMonthend(Asset $asset): Response
    {
        $transferOrder = $this->monthEndService->createTransferOrderByPreset(TransferOrderPreset::InvestmentSettlement);
        $transferOrder->setAsset($asset);
        $this->doctrine->getManager()->persist($transferOrder);
        $this->doctrine->getManager()->flush();
        $this->logger->debug(
            "Created new asset investment settlement order #{$transferOrder->getId()}",
        );
        $this->addFlash('success', 'Settlement order successfully created');
        return $this->redirectToRoute('admin_monthend_settlement_generate', [
            'id' => $transferOrder->getId(),
        ]);
    }

    #[Route('/{id}', name: 'admin_monthend_settlement_manage', methods: ['GET'])]
    #[IsGranted('ROLE_ANALYST')]
    public function manage(TransferOrder $transferOrder): Response
    {
        $stampDutyUser = $this->userRepository->findByEmail(
            (string) $transferOrder->getAsset()?->getStampDutyUser(),
        );
        if (!$this->monthEndService->isSettlementOrder(
            $transferOrder,
            $stampDutyUser?->getMangoPayWalletId(),
        )) {
            $this->addFlash(
                'warning',
                "Transfer order #{$transferOrder->getId()} is not a settlement order"
                . ' or the asset is not ready',
            );
            return $this->redirectToRoute('admin_transfer_order_manage', ['id' => $transferOrder->getId()]);
        }
        try {
            $holdBalance = $this->assetManager->getAssetWalletByType(
                $transferOrder->getAsset(),
                'hold',
            )['balance'];

            // $settlementBalance = $this->assetManager->getAssetWalletByType(
            //     $transferOrder->getAsset(),
            //     'settlement'
            // )['balance'];
        } catch (\Exception $e) {
            $this->addFlash(
                'warning',
                'Unable to retrieve wallet balance. ' . $e->getMessage(),
            );
            $this->logger->error('Unable to retreive wallet balance', [
                'asset #' . $transferOrder->getAsset()->getId(),
                $e->getMessage(),
            ]);
        }
        $walletChoices = $this->monthEndService->getAssetWalletChoices(
            $transferOrder->getAsset(),
        );
        $walletChoices['Stamp Duty'] = $stampDutyUser->getMangoPayWalletId();
        $settlementSummary =
            $this->settlementService->getTradeSettlementOrderSummary($transferOrder);
        return $this->render('admin/pages/monthend/settlements/manage.html.twig', [
            'transferOrder' => $transferOrder,
            'offering' => $this->offeringRepository->findFirstPartyByAssetId(
                $transferOrder->getAsset()->getId(),
            ),
            'holdWalletBalance' => $holdBalance ?? 0,
            // 'settlementWalletBalance' => $settlementBalance ?? 0,
            'stampDutyUser' => $stampDutyUser,
            'walletIdMap' => array_flip($walletChoices),
            'settlementSummary' => $settlementSummary,
        ]);
    }

    #[Route(
        '/{id}/edit',
        name: 'admin_monthend_settlement_edit',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function edit(Request $request, TransferOrder $transferOrder): Response
    {
        // Should restrict monthend edits if any transfers exist
        // Need to prevent monthend and investments being settled being for different months
        $form = $this->createForm(MonthendOrderEditType::class, $transferOrder, [
            'data_class' => TransferOrder::class,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();
            $this->logger->debug(
                "Updated asset investment settlement order #{$transferOrder->getId()}",
            );
            $this->addFlash('success', 'Settlement order successfully updated');
            return $this->redirectToRoute('admin_monthend_settlement_manage', [
                'id' => $transferOrder->getId(),
            ]);
        }
        return $this->render('admin/pages/monthend/order_edit.html.twig', [
            'form' => $form->createView(),
            'order' => $transferOrder,
            'exitRoute' => 'admin_monthend_settlement_manage',
        ]);
    }

    #[Route(
        '/{id}/generate',
        name: 'admin_monthend_settlement_generate',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function generate(Request $request, TransferOrder $transferOrder): Response
    {
        $stampDutyUser = $this->userRepository->findByEmail(
            (string) $transferOrder->getAsset()?->getStampDutyUser(),
        );
        if (!$this->monthEndService->isSettlementOrder(
            $transferOrder,
            $stampDutyUser?->getMangoPayWalletId(),
        )) {
            $this->addFlash(
                'warning',
                "Transfer order #{$transferOrder->getId()} is not a settlement order",
            );
            return $this->redirectToRoute('admin_transfer_order_manage', ['id' => $transferOrder->getId()]);
        }

        // Compile list of trades that already have transfers
        $existingTradeIds = [];
        foreach ($transferOrder->getTransfers() as $existingTransfers) {
            if ($existingTransfers->getShareTrade()?->getId()) {
                $existingTradeIds[] = $existingTransfers->getShareTrade()?->getId();
            }
        }
        $existingTradeIds = array_unique($existingTradeIds);

        $walletChoices = $this->monthEndService->getAssetWalletChoices(
            $transferOrder->getAsset(),
        );
        $monthendDateRange = $this->monthEndService->getMonthEndDateRangeFromDateTime(
            $transferOrder->getScheduledFor(),
            -1,
        );
        $form = $this->createForm(SettlementSearchGenerateType::class, [
            'createdAt_gte' => $monthendDateRange['start'],
            'createdAt_lt' => $monthendDateRange['end'],
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ShareTrade[] $shareTrades */
            $shareTrades = $this->shareTradeRepository
                ->buildQueryWithAssociations([
                    'assetId' => $transferOrder->getAsset()->getId(),
                    'status' => TradeStatus::Unsettled,
                    'buyOrderType' => TradeOrderType::tradingBuyTypes(),
                    ...$form->getData(),
                ], ['id' => 'DESC'])
                ->getResult();
            // Filter our any trades that are already in the current settlement order
            foreach ($shareTrades as $index => $matchingTrades) {
                if (in_array($matchingTrades->getId(), $existingTradeIds)) {
                    unset($shareTrades[$index]);
                }
            }

            /** @var ClickableInterface $generateButton */
            $generateButton = $form->get('generate');
            if ($generateButton->isClicked()) {
                try {
                    $this->settlementService->generateSettlementTransfers(
                        $transferOrder,
                        $shareTrades,
                    );
                    $this->doctrine->getManager()->flush();
                    $this->addFlash('success', 'Settlements successfully generated');
                } catch (\Exception $e) {
                    $this->addFlash(
                        'warning',
                        'Could not generate settlements: ' . $e->getMessage(),
                    );
                    $this->logger->warning(
                        'Could not generate settlements for share trades: '
                            . $e->getMessage(),
                    );
                }
                $this->doctrine->getManager()->flush();
                return $this->redirectToRoute(
                    'admin_monthend_settlement_generate_stamp_duty',
                    ['id' => $transferOrder->getId()],
                    Response::HTTP_SEE_OTHER,
                );
            }
        } else {
            /** @var ShareTrade[] $shareTrades */
            $shareTrades = $this->shareTradeRepository
                ->buildQueryWithAssociations([
                    'assetId' => $transferOrder->getAsset()->getId(),
                    'status' => TradeStatus::Unsettled,
                    'buyOrderType' => TradeOrderType::tradingBuyTypes(),
                    'createdAt_gte' => $monthendDateRange['start'],
                    'createdAt_lt' => $monthendDateRange['end'],
                ], ['id' => 'DESC'])
                ->getResult();
            foreach ($shareTrades as $index => $matchingTrades) {
                if (in_array($matchingTrades->getId(), $existingTradeIds)) {
                    unset($shareTrades[$index]);
                }
            }
        }
        $settlementSummary =
            $this->settlementService->getTradeSettlementSummary($shareTrades);
        return $this->render('admin/pages/monthend/settlements/generate.html.twig', [
            'form' => $form->createView(),
            'transferOrder' => $transferOrder,
            'walletIdMap' => array_flip($walletChoices),
            'monthendDateRange' => $monthendDateRange,
            'tradesToSettle' => $shareTrades,
            'settlementSummary' => $settlementSummary,
        ]);
    }

    #[Route(
        '/{id}/generate-stamp-duty',
        name: 'admin_monthend_settlement_generate_stamp_duty',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function generateStampDuty(
        Request $request,
        TransferOrder $transferOrder,
    ): Response {
        $stampDutyUser = $this->userRepository->findByEmail(
            (string) $transferOrder->getAsset()?->getStampDutyUser(),
        );
        if (!$this->monthEndService->isSettlementOrder(
            $transferOrder,
            $stampDutyUser?->getMangoPayWalletId(),
        )) {
            $this->addFlash(
                'warning',
                "Transfer order #{$transferOrder->getId()} is not a settlement order",
            );
            return $this->redirectToRoute('admin_transfer_order_manage', ['id' => $transferOrder->getId()]);
        }

        $groupedTrades =
            $this->settlementService->groupTradeSettlementsByUser($transferOrder);
        $stampDutySummary =
            $this->settlementService->getTradeStampDutyOverview($groupedTrades);
        $walletChoices = $this->monthEndService->getAssetWalletChoices(
            $transferOrder->getAsset(),
        );
        $form = $this
            ->createFormBuilder()
            ->add('generate', SubmitType::class, [
                'label' => 'Generate All Transfers',
            ])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->settlementService->generateStampDutyTransfers(
                    $transferOrder,
                    $groupedTrades,
                );
                $this->doctrine->getManager()->flush();
                $this->addFlash('success', 'Settlements successfully generated');
            } catch (\Exception $e) {
                $this->addFlash(
                    'warning',
                    'Could not generate settlements: ' . $e->getMessage(),
                );
                $this->logger->warning(
                    'Could not generate settlements for share trades: '
                        . $e->getMessage(),
                );
            }
            $this->doctrine->getManager()->flush();
            return $this->redirectToRoute(
                'admin_monthend_settlement_manage',
                ['id' => $transferOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        $settlementSummary =
            $this->settlementService->getTradeSettlementOrderSummary($transferOrder);
        return $this->render('admin/pages/monthend/settlements/generate_stamp_duty.html.twig', [
            'form' => $form->createView(),
            'transferOrder' => $transferOrder,
            'walletIdMap' => array_flip($walletChoices),
            'settlementSummary' => $settlementSummary,
            'groupedSettlements' => $groupedTrades,
            'stampDutySummary' => $stampDutySummary,
            'stampDutyWalletId' => $stampDutyUser?->getMangoPayWalletId(),
        ]);
    }

    #[Route(
        '/{transferOrder}/generate/{id}',
        name: 'admin_monthend_settlement_generate_single',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function generateSingle(
        #[MapEntity(id: 'transferOrder')]
        TransferOrder $transferOrder,
        #[MapEntity(id: 'id')]
        ShareTrade $shareTrade,
    ): Response {
        $stampDutyUser = $this->userRepository->findByEmail(
            (string) $transferOrder->getAsset()?->getStampDutyUser(),
        );
        if (!$this->monthEndService->isSettlementOrder(
            $transferOrder,
            $stampDutyUser?->getMangoPayWalletId(),
        )) {
            $this->addFlash(
                'warning',
                "Transfer order #{$transferOrder->getId()} is not a settlement order",
            );
            return $this->redirectToRoute('admin_transfer_order_manage', ['id' => $transferOrder->getId()]);
        }
        $existingTransfersCount = $transferOrder->getTransfers()->count();
        try {
            $this->settlementService->generateSettlementTransfers($transferOrder, [
                $shareTrade,
            ]);
            $newTransfersCount = $transferOrder->getTransfers()->count();
            if ($newTransfersCount > $existingTransfersCount) {
                $this->doctrine->getManager()->flush();
                $this->addFlash(
                    'success',
                    "Settlements successfully generated for share trade #{$shareTrade->getId()}",
                );
            } else {
                $this->addFlash('warning', 'Could not generate settlements for share trade.
                    Share trade is not in the unsettled state or is an share trade in a different asset');
            }
        } catch (\Exception $e) {
            $this->addFlash(
                'warning',
                'Could not generate settlements for share trade: ' . $e->getMessage(),
            );
            $this->logger->warning(
                'Could not generate settlements for share trade: ' . $e->getMessage(),
            );
        }
        return $this->redirectToRoute(
            'admin_monthend_settlement_manage',
            ['id' => $transferOrder->getId()],
            Response::HTTP_SEE_OTHER,
        );
    }

    #[Route(
        '/{transferOrder}/generate-stamp-duty/{user}',
        name: 'admin_monthend_settlement_generate_stamp_duty_single',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function generateStampDutySingle(
        TransferOrder $transferOrder,
        User $user,
    ): Response {
        $stampDutyUser = $this->userRepository->findByEmail(
            (string) $transferOrder->getAsset()?->getStampDutyUser(),
        );
        if (!$this->monthEndService->isSettlementOrder(
            $transferOrder,
            $stampDutyUser?->getMangoPayWalletId(),
        )) {
            $this->addFlash(
                'warning',
                "Transfer order #{$transferOrder->getId()} is not a settlement order",
            );
            return $this->redirectToRoute('admin_transfer_order_manage', ['id' => $transferOrder->getId()]);
        }
        $existingTransfersCount = $transferOrder->getTransfers()->count();
        $groupedTrades =
            $this->settlementService->groupTradeSettlementsByUser($transferOrder);
        if (array_key_exists($user->getId(), $groupedTrades)) {
            $groupedTrades = [
                $user->getId() => $groupedTrades[$user->getId()],
            ];
            try {
                $this->settlementService->generateStampDutyTransfers(
                    $transferOrder,
                    $groupedTrades,
                );
                $newTransfersCount = $transferOrder->getTransfers()->count();
                if ($newTransfersCount > $existingTransfersCount) {
                    $this->doctrine->getManager()->flush();
                    $this->addFlash(
                        'success',
                        "Stamp duty transfer successfully generated for user #{$user->getId()}",
                    );
                } else {
                    $this->addFlash('warning', 'Could not generate stamp duty transfer for user.
                        Share trade is not in the unsettled state or is an share trade in a different asset');
                }
            } catch (\Exception $e) {
                $this->addFlash(
                    'warning',
                    'Could not generate stamp duty transfer for user: '
                        . $e->getMessage(),
                );
                $this->logger->warning(
                    'Could not generate stamp duty transfer for user: '
                        . $e->getMessage(),
                );
            }
        } else {
            $this->addFlash('warning', "#{$user->getId()} has no settlements");
        }

        return $this->redirectToRoute(
            'admin_monthend_settlement_generate_stamp_duty',
            ['id' => $transferOrder->getId()],
            Response::HTTP_SEE_OTHER,
        );
    }

    #[Route(
        '/edit-transfer/{id}',
        name: 'admin_monthend_edit_settlement',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function editTransfer(
        Request $request,
        TransferRequest $transferRequest,
    ): Response {
        $transferOrder = $transferRequest->getTransferOrder();
        $stampDutyUser = $this->userRepository->findByEmail(
            (string) $transferOrder->getAsset()?->getStampDutyUser(),
        );
        if (!$this->monthEndService->isSettlementOrder(
            $transferOrder,
            $stampDutyUser?->getMangoPayWalletId(),
        )) {
            $this->addFlash(
                'warning',
                "Transfer order #{$transferOrder->getId()} is not a settlement order"
                . ' or the asset is not ready',
            );
            return $this->redirectToRoute('admin_transfer_order_manage', ['id' => $transferOrder->getId()]);
        }
        $walletChoices = $this->monthEndService->getAssetWalletChoices(
            $transferOrder->getAsset(),
        );
        $creditWalletChoices = ['stamp duty' => $stampDutyUser->getMangoPayWalletId()];
        if (
            $transferRequest->getCreditWalletId() != $stampDutyUser->getMangoPayWalletId()
        ) {
            $creditWalletChoices['settlement'] = $transferRequest->getCreditWalletId();
        }
        $form = $this->createForm(AssetTransferRequestType::class, $transferRequest, [
            'debitWalletChoices' => ['hold' => $walletChoices['hold']],
            'creditWalletChoices' => $creditWalletChoices,
            'lockDebitWallet' => true,
            'lockCreditWallet' => true,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();
            $this->logger->debug(
                "Saved changes to transfer request #{$transferRequest->getId()}",
            );
            $this->addFlash('success', 'Transfer request successfully updated');
            return $this->redirectToRoute('admin_monthend_settlement_manage', [
                'id' => $transferOrder->getId(),
            ]);
        }
        return $this->render('admin/pages/monthend/settlements/edit_transfer.html.twig', [
            'form' => $form->createView(),
            'offering' => $this->offeringRepository->findFirstPartyByAssetId(
                $transferOrder->getAsset()->getId(),
            ),
            'transferOrder' => $transferOrder,
            'transferRequest' => $transferRequest,
            'stampDutyUser' => $stampDutyUser,
        ]);
    }
}
