<?php

namespace App\Controller\Admin;

use App\Entity\AbstractOrder;
use App\Entity\Asset;
use App\Entity\Enum\TransferOrderPreset;
use App\Entity\Enum\TransferType;
use App\Entity\TransferOrder;
use App\Entity\TransferRequest;
use App\Form\Type\AssetTransferRequestType;
use App\Form\Type\FeeDeriveGenerateType;
use App\Form\Type\FeeSearchGenerateType;
use App\Form\Type\MonthendOrderEditType;
use App\Form\Type\QueryTransferOrderType;
use App\Form\Type\TransferOrderDateType;
use App\Repository\AssetRepository;
use App\Repository\ShareTradeRepository;
use App\Repository\TransferOrderRepository;
use App\Repository\TransferRequestRepository;
use App\Service\FeeCollectionService;
use App\Service\Manager\AssetManagerV2;
use App\Service\Manager\UserManagerV2;
use App\Service\MangopayWalletService;
use App\Service\MonthEndService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/monthend/fee-collections')]
class FeeCollectionController extends AbstractController
{
    public const REDIRECT_ROUTES = [
        'admin_monthend_fee_collection_manage',
        'admin_monthend_fee_collection_generate',
        'admin_monthend_fee_collection_generate_relisting',
        'admin_monthend_fee_collection_add_transfer',
    ];

    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private MonthEndService $monthEndService,
        private ShareTradeRepository $shareTradeRepository,
        private TransferOrderRepository $transferOrderRepository,
        private TransferRequestRepository $transferRequestRepository,
        private AssetRepository $assetRepository,
        private AssetManagerV2 $assetManager,
        private UserManagerV2 $userManager,
        private MangopayWalletService $walletService,
        private FeeCollectionService $feeCollectionService,
    ) {}

    #[Route('', name: 'admin_monthend_fee_collection_index', methods: ['GET'])]
    #[IsGranted('ROLE_ANALYST')]
    public function index(Request $request): Response
    {
        // $this->logger->debug('Fee collection overview');
        try {
            $superAdminWallet = $this->userManager
                ->getSuperAdmin()
                ?->getMangoPayWalletId();
            $additionalFeeWallets = ['Superadmin' => $superAdminWallet];
        } catch (\Throwable $th) {
            $this->addFlash('error', $th->getMessage());
            $this->logger->error($th->getMessage());
        }
        $feeWallets = $this->feeCollectionService->getFeeWallets(
            $additionalFeeWallets ?? [],
        );
        $form = $this->createForm(QueryTransferOrderType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->transferOrderRepository->findByWithAssociations(
            array_merge($filters ?? [], [
                'transferType' => TransferType::FeeCollection,
            ]),
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/monthend/fee_collections/index.html.twig', [
            'objects' => $results,
            'feeWallets' => $feeWallets,
            // 'form' => $form->createView(),
        ]);
    }

    #[Route(
        '/create',
        name: 'admin_monthend_fee_collection_create',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function create(Request $request): Response
    {
        $transferOrder = $this->monthEndService->createTransferOrderByPreset(TransferOrderPreset::YieldersFees);
        $form = $this->createForm(TransferOrderDateType::class, $transferOrder, [
            'data_class' => TransferOrder::class,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->persist($transferOrder);
            $this->doctrine->getManager()->flush();
            $this->logger->debug(
                "Created new fee collection order #{$transferOrder->getId()}",
            );
            $this->addFlash('success', 'Transfer order successfully created');
            return $this->redirectToRoute('admin_monthend_fee_collection_setup', [
                'id' => $transferOrder->getId(),
            ]);
        }
        return $this->render('admin/pages/monthend/fee_collections/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_monthend_fee_collection_manage', methods: ['GET'])]
    #[IsGranted('ROLE_ANALYST')]
    public function manage(TransferOrder $transferOrder): Response
    {
        $monthendDateRange = $this->monthEndService->getMonthEndDateRangeFromDateTime(
            $transferOrder->getScheduledFor(),
        );
        $otherMonthendOrders = $this->transferOrderRepository->buildQueryWithAssociations([
            'transferType' => TransferType::FeeCollection->value,
            'scheduledFor_gte' => $monthendDateRange['start'],
            'scheduledFor_lt' => $monthendDateRange['end'],
            'description' => $transferOrder->getDescription(),
        ])->getResult();
        // Remove self from the list
        $otherMonthendOrders = array_filter(
            $otherMonthendOrders,
            fn(TransferOrder $item): bool => $item->getId() !== $transferOrder->getId(),
        );
        $superAdminWallet = $this->userManager->getSuperAdmin()->getMangoPayWalletId();
        $feeWallets = $this->feeCollectionService->getFeeWallets([
            'Superadmin' => $superAdminWallet,
        ]);
        return $this->render('admin/pages/monthend/fee_collections/manage.html.twig', [
            'transferOrder' => $transferOrder,
            'feeWallets' => $feeWallets,
            'otherMonthendOrders' => $otherMonthendOrders,
            'issues' => $this->monthEndService->validateTransferOrder($transferOrder),
            'feeGuess' =>
                $this->feeCollectionService->guessFeeBeingCollected($transferOrder),
        ]);
    }

    #[Route(
        '/{id}/edit',
        name: 'admin_monthend_fee_collection_edit',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function edit(Request $request, TransferOrder $transferOrder): Response
    {
        $form = $this->createForm(MonthendOrderEditType::class, $transferOrder, [
            'data_class' => TransferOrder::class,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();
            $this->logger->debug(
                "Updated asset income transfer #{$transferOrder->getId()}",
            );
            $this->addFlash('success', 'Transfer order successfully updated');
            return $this->redirectToRoute('admin_monthend_fee_collection_manage', [
                'id' => $transferOrder->getId(),
            ]);
        }
        return $this->render('admin/pages/monthend/order_edit.html.twig', [
            'form' => $form->createView(),
            'order' => $transferOrder,
            'exitRoute' => 'admin_monthend_fee_collection_manage',
        ]);
    }

    #[Route(
        '/{id}/generate-description',
        name: 'admin_monthend_fee_collection_generate_description',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function generateDescription(TransferOrder $transferOrder): Response
    {
        $feeGuess = $this->feeCollectionService->guessFeeBeingCollected($transferOrder);
        if (!is_null($feeGuess)) {
            $transferOrder->setDescription($feeGuess);
            $this->doctrine->getManager()->flush();
        }
        return $this->redirectToRoute(
            'admin_monthend_fee_collection_manage',
            ['id' => $transferOrder->getId()],
            Response::HTTP_SEE_OTHER,
        );
    }

    #[Route(
        '/{id}/generate',
        name: 'admin_monthend_fee_collection_generate',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function generate(Request $request, TransferOrder $transferOrder): Response
    {
        if (!$this->isOrderEditable($transferOrder)) {
            return $this->redirectToRoute(
                'admin_monthend_fee_collection_manage',
                ['id' => $transferOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        $monthendDateRange = $this->monthEndService->getMonthEndDateRangeFromDateTime(
            $transferOrder->getScheduledFor(),
        );
        $superAdminWallet = $this->userManager->getSuperAdmin()->getMangoPayWalletId();
        $feeWallets = $this->feeCollectionService->getFeeWallets([
            'Superadmin' => $superAdminWallet,
        ]);
        $defaultCriteria = [
            'scheduledFor_gte' => $monthendDateRange['start'],
            'scheduledFor_lt' => $monthendDateRange['end'],
            'description' => MonthEndService::DESCRIPTION_PRESETS['management'],
            'status' => TransferRequest::STATE_COMPLETE,
            'feeWalletId' =>
                $feeWallets[FeeCollectionService::PREFERRED_FEE_WALLET]
                    ?? $superAdminWallet,
        ];
        $form = $this->createForm(FeeSearchGenerateType::class, $defaultCriteria, [
            'feeWalletChoices' => $feeWallets,
            'filterByDescription' => true,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // $this->logger->debug('Search criteria', $form->getData());
                $matchingTransferRequests = $this->transferRequestRepository
                    ->buildQueryWithAssociations($form->getData())
                    ->getResult();
                /** @var ClickableInterface $generateButton */
                $generateButton = $form->get('generate');
                if ($generateButton->isClicked()) {
                    $this->logger->info('Generate transfers for Transfer Order', [$transferOrder->getId()]);
                    $this->monthEndService->generateRelayTransfers(
                        $transferOrder,
                        $matchingTransferRequests,
                        $form->getData()['feeWalletId'],
                    );
                    $this->doctrine->getManager()->flush();
                    return $this->redirectToRoute(
                        'admin_monthend_fee_collection_manage',
                        ['id' => $transferOrder->getId()],
                        Response::HTTP_SEE_OTHER,
                    );
                }
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Unable to run generate transfers. ' . $e->getMessage(),
                );
                $this->logger->error('Unable to run generate transfers. ', [$e->getMessage()]);
            }
        } else {
            $matchingTransferRequests = $this->transferRequestRepository
                ->buildQueryWithAssociations($defaultCriteria)
                ->getResult();
        }

        return $this->render('admin/pages/monthend/fee_collections/generate.html.twig', [
            'form' => $form->createView(),
            'transferOrder' => $transferOrder,
            'matchingRequests' => $matchingTransferRequests,
        ]);
    }

    #[Route(
        '/{id}/generate/relisting',
        name: 'admin_monthend_fee_collection_generate_relisting',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function generateRelistingFees(
        Request $request,
        TransferOrder $transferOrder,
    ): Response {
        $monthendDateRange = $this->monthEndService->getMonthEndDateRangeFromDateTime(
            $transferOrder->getScheduledFor(),
            -1,
        );
        $superAdminWallet = $this->userManager->getSuperAdmin()->getMangoPayWalletId();
        $feeWallets = $this->feeCollectionService->getFeeWallets([
            'Superadmin' => $superAdminWallet,
        ]);
        $defaultCriteria = [
            'scheduledFor_gte' => $monthendDateRange['start'],
            'scheduledFor_lt' => $monthendDateRange['end'],
            'feeWalletId' =>
                $feeWallets[FeeCollectionService::PREFERRED_FEE_WALLET]
                    ?? $superAdminWallet,
        ];
        $form = $this->createForm(FeeSearchGenerateType::class, $defaultCriteria, [
            'feeWalletChoices' => $feeWallets,
            'dateDescription' => 'Relisting Created',
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $monthlyRelistings = $this->feeCollectionService->findMonthlyRelistings(
                $form->getData()['scheduledFor_gte'],
                $form->getData()['scheduledFor_lt'],
            );
            $relistingFeeSummary =
                $this->feeCollectionService->estimateRelistingFees($monthlyRelistings);

            /** @var ClickableInterface $generateButton */
            $generateButton = $form->get('generate');
            if ($generateButton->isClicked()) {
                $this->logger->info('Generate transfers for Transfer Order', [$transferOrder->getId()]);
                $this->feeCollectionService->generateRelistingFeeTransfers(
                    $transferOrder,
                    $relistingFeeSummary,
                    $form->getData()['feeWalletId'],
                );
                $this->doctrine->getManager()->flush();
                return $this->redirectToRoute(
                    'admin_monthend_fee_collection_manage',
                    ['id' => $transferOrder->getId()],
                    Response::HTTP_SEE_OTHER,
                );
            }
        } else {
            $monthlyRelistings = $this->feeCollectionService->findMonthlyRelistings(
                $defaultCriteria['scheduledFor_gte'],
                $defaultCriteria['scheduledFor_lt'],
            );
            $relistingFeeSummary =
                $this->feeCollectionService->estimateRelistingFees($monthlyRelistings);
        }
        return $this->render('admin/pages/monthend/fee_collections/generate_relisting_fees.html.twig', [
            'form' => $form->createView(),
            'transferOrder' => $transferOrder,
            'monthlyRelistings' => $monthlyRelistings,
            'relistingFeeSummary' => $relistingFeeSummary,
            'feeWallets' => $feeWallets,
        ]);
    }

    #[Route(
        '/{id}/income-deposits',
        name: 'admin_monthend_fee_collection_income_deposit_list',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function incomeDepositList(TransferOrder $transferOrder): Response
    {
        $monthendDateRange = $this->monthEndService->getMonthEndDateRangeFromDateTime(
            $transferOrder->getScheduledFor(),
        );
        $results = $this->transferOrderRepository
            ->buildQueryWithAssociations([
                'transferType' => TransferType::IncomeDisaggregation,
                'scheduledFor_gte' => $monthendDateRange['start'],
                'scheduledFor_lt' => $monthendDateRange['end'],
                'status' => AbstractOrder::STATE_COMPLETED,
            ], ['id' => 'DESC'])
            ->getResult();
        return $this->render('admin/pages/monthend/fee_collections/income_deposits.html.twig', [
            'transferOrder' => $transferOrder,
            'suitableOrders' => $results,
        ]);
    }

    #[Route(
        '/{id}/income-deposits/{collectFromOrder}',
        name: 'admin_monthend_fee_collection_income_deposit_collect',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function incomeDepositCollect(
        Request $request,
        TransferOrder $transferOrder,
        TransferOrder $collectFromOrder,
    ): Response {
        $superAdminWallet = $this->userManager->getSuperAdmin()->getMangoPayWalletId();
        $feeWallets = $this->feeCollectionService->getFeeWallets([
            'Superadmin' => $superAdminWallet,
        ]);
        $defaultConfig = [
            'feeWalletId' =>
                $feeWallets[FeeCollectionService::PREFERRED_FEE_WALLET]
                    ?? $superAdminWallet,
            'percentageCut' => '0.1',
            'feeDescription' => MonthEndService::DESCRIPTION_PRESETS['management'],
        ];
        $form = $this->createForm(FeeDeriveGenerateType::class, $defaultConfig, [
            'feeWalletChoices' => $feeWallets,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ClickableInterface $generateButton */
            $generateButton = $form->get('generate');
            if ($generateButton->isClicked()) {
                try {
                    $transferOrder = $this->feeCollectionService->collectFeesFromTransferOrder(
                        $transferOrder,
                        $collectFromOrder,
                        $form->getData()['feeWalletId'],
                        $form->getData()['percentageCut'],
                        $form->getData()['feeDescription'],
                    );
                    $this->doctrine->getManager()->flush();
                    $this->addFlash('success', 'Transfer generated transfers');
                    return $this->redirectToRoute(
                        'admin_monthend_fee_collection_manage',
                        ['id' => $transferOrder->getId()],
                        Response::HTTP_SEE_OTHER,
                    );
                } catch (\Exception $e) {
                    $this->addFlash(
                        'error',
                        'Unable to generate transfer. ' . $e->getMessage(),
                    );
                    $this->logger->error('Unable to generate transfers. ', [$e->getMessage()]);
                }
            }
        }
        return $this->render('admin/pages/monthend/fee_collections/collect_from_order.html.twig', [
            'form' => $form,
            'transferOrder' => $transferOrder,
            'collectFromOrder' => $collectFromOrder,
        ]);
    }

    #[Route(
        '/{id}/add-transfer',
        name: 'admin_monthend_fee_collection_search',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function search(Request $request, TransferOrder $transferOrder): Response
    {
        if (!$this->isOrderEditable($transferOrder)) {
            return $this->redirectToRoute(
                'admin_monthend_fee_collection_manage',
                ['id' => $transferOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }

        // Only get assets that have shareholders
        $assetShareholdings =
            $this->shareTradeRepository->aggregateSharesInCirculation(nonZero: true);
        $assetIds = array_column($assetShareholdings, 'assetid');
        $matchingAssets = $this->assetRepository
            ->buildQueryWithAssociations(['id' => $assetIds], ['id' => 'DESC'])
            ->getResult();
        return $this->render('admin/pages/monthend/fee_collections/search.html.twig', [
            'transferOrder' => $transferOrder,
            'matchingAssets' => $matchingAssets,
            'shareholdings' => $assetShareholdings,
        ]);
    }

    #[Route(
        '/{transferOrder}/add-transfer/{asset}',
        name: 'admin_monthend_fee_collection_add_transfer',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function addTransfer(
        Request $request,
        TransferOrder $transferOrder,
        Asset $asset,
    ): Response {
        if (!$this->isOrderEditable($transferOrder)) {
            return $this->redirectToRoute(
                'admin_monthend_fee_collection_manage',
                ['id' => $transferOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        $redirectToRoute = 'admin_monthend_fee_collection_search';
        if (in_array($request->query->get('redirectRoute'), self::REDIRECT_ROUTES)) {
            $redirectToRoute = $request->query->get('redirectRoute');
        }

        $superAdminWallet = $this->userManager->getSuperAdmin()->getMangoPayWalletId();
        $feeWallets = $this->feeCollectionService->getFeeWallets([
            'Superadmin' => $superAdminWallet,
        ]);

        // Get available asset expenses wallets
        $walletChoices = $this->monthEndService->getAssetWalletChoices($asset);
        $assetWalletChoices = [
            'asset settlement/actual' => $walletChoices['settlement'],
            'asset hold' => $walletChoices['hold'],
        ];
        // Multi-wallet enabled assets will have a dedicated expenses wallet
        if (array_key_exists('expenses', $walletChoices)) {
            $assetWalletChoices['asset expenses'] = $walletChoices['expenses'];
        } else {
            $this->addFlash(
                'warning',
                'Asset does not have an expenses wallet configured',
            );
        }

        $feeDescription = match ($request->query->get('feeType')) {
            'relisting' => 'Relisting fees',
            'management' => 'Yielders management fees',
            'ypml' => 'Yielders Property Management Ltd fees',
            default => null,
        };
        $defaultDebitWallet = match ($request->query->get('feeType')) {
            'relisting' => 'hold',
            default => 'settlement',
        };
        // Prefer the main yielders fee wallet if set, otherwise use superadmin
        $defaultFeeWallet =
            $feeWallets[FeeCollectionService::PREFERRED_FEE_WALLET]
            ?? $superAdminWallet;
        // For special fee types, use relevant wallet if set, otherwise use default
        $defaultCreditWalletId = match ($request->query->get('feeType')) {
            'ypml' => $feeWallets['ypmlFeeWallet'] ?? $defaultFeeWallet,
            default => $defaultFeeWallet,
        };
        $transferRequest = new TransferRequest();
        $transferRequest->setAsset($asset);
        if (!is_null($feeDescription)) {
            $transferRequest->setDescription($this->monthEndService->createStructuredDescription(
                $feeDescription,
                $asset,
                $transferOrder->getScheduledFor(),
            ));
        }
        $transferRequest->setDebitWalletId($walletChoices[$defaultDebitWallet]);
        $transferRequest->setCreditWalletId($defaultCreditWalletId);
        if (!is_null($request->query->get('amount'))) {
            $transferRequest->setAmount($request->query->get('amount'));
        }
        $transferOrder->addTransfer($transferRequest);

        $form = $this->createForm(AssetTransferRequestType::class, $transferRequest, [
            'debitWalletChoices' => $assetWalletChoices,
            'creditWalletChoices' => $feeWallets,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->addFlash('success', 'Transfer successfully added');
                $this->doctrine->getManager()->flush();
                return $this->redirectToRoute(
                    'admin_monthend_fee_collection_search',
                    ['id' => $transferOrder->getId()],
                    Response::HTTP_SEE_OTHER,
                );
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Unable to run add transfer. ' . $e->getMessage(),
                );
                $this->logger->error('Unable to run add transfer. ', [$e->getMessage()]);
            }
        }

        return $this->render('admin/pages/monthend/fee_collections/add_transfer.html.twig', [
            'form' => $form->createView(),
            'asset' => $asset,
            'transferOrder' => $transferOrder,
            'transferRequest' => $transferRequest,
            'exitRoute' => $redirectToRoute,
            'feeWallets' => $feeWallets,
        ]);
    }

    #[Route(
        '/{id}/setup',
        name: 'admin_monthend_fee_collection_setup',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function setup(Request $request, TransferOrder $transferOrder): Response
    {
        if (!$this->isOrderEditable($transferOrder)) {
            return $this->redirectToRoute(
                'admin_monthend_fee_collection_manage',
                ['id' => $transferOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        return $this->render('admin/pages/monthend/fee_collections/setup.html.twig', [
            'transferOrder' => $transferOrder,
        ]);
    }

    private function isOrderEditable(TransferOrder $transferOrder): bool
    {
        if (!$this->monthEndService->isTransferOrderValid($transferOrder)) {
            $this->addFlash(
                'warning',
                "Transfer order #{$transferOrder->getId()} is not a fee collection order",
            );
            return false;
        }
        if (TransferOrder::STATE_DRAFT != $transferOrder->getStatus()) {
            $this->addFlash(
                'warning',
                'Transfers can only be added when the order is in draft mode',
            );
            return false;
        }
        return true;
    }
}
