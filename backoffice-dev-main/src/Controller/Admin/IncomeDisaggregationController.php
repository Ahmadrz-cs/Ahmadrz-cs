<?php

namespace App\Controller\Admin;

use App\Entity\AbstractOrder;
use App\Entity\Asset;
use App\Entity\Enum\TransferOrderPreset;
use App\Entity\Enum\TransferType;
use App\Entity\TransferOrder;
use App\Entity\TransferRequest;
use App\Form\Type\AssetTransferRequestType;
use App\Form\Type\MonthendOrderEditType;
use App\Form\Type\QueryTransferOrderType;
use App\Form\Type\TransferOrderDateType;
use App\Repository\AssetRepository;
use App\Repository\ShareTradeRepository;
use App\Repository\TransferOrderRepository;
use App\Service\Manager\UserManagerV2;
use App\Service\MangopayWalletService;
use App\Service\MonthEndService;
use App\Service\TransferOrderService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/monthend/income-disaggregations')]
class IncomeDisaggregationController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private MonthEndService $monthEndService,
        private ShareTradeRepository $shareTradeRepository,
        private TransferOrderService $transferOrderService,
        private TransferOrderRepository $transferOrderRepository,
        private AssetRepository $assetRepository,
        private UserManagerV2 $userManager,
        private MangopayWalletService $walletService,
    ) {}

    #[Route('', name: 'admin_monthend_income_disaggregation_index', methods: ['GET'])]
    #[IsGranted('ROLE_ANALYST')]
    public function index(Request $request): Response
    {
        // $this->logger->debug('income disaggregation overview');
        $form = $this->createForm(QueryTransferOrderType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->transferOrderRepository->findByWithAssociations(
            array_merge($filters ?? [], [
                'transferType' => TransferType::IncomeDisaggregation,
            ]),
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        // Ideally we have a separate wallet for aggregated income
        // Similar to how a separate fee wallet is used
        return $this->render('admin/pages/monthend/income_disaggregations/index.html.twig', [
            'objects' => $results,
            // 'aggregationWallet' => $this->superadminWallet
            // 'form' => $form->createView(),
        ]);
    }

    #[Route(
        '/create',
        name: 'admin_monthend_income_disaggregation_create',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function create(Request $request): Response
    {
        $transferOrder = $this->monthEndService->createTransferOrderByPreset(TransferOrderPreset::IncomeDisaggregation);
        $form = $this->createForm(TransferOrderDateType::class, $transferOrder, [
            'data_class' => TransferOrder::class,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->persist($transferOrder);
            $this->doctrine->getManager()->flush();
            $this->logger->debug(
                "Created new income disaggregation order #{$transferOrder->getId()}",
            );
            $this->addFlash('success', 'Transfer order successfully created');
            return $this->redirectToRoute('admin_monthend_income_disaggregation_manage', [
                'id' => $transferOrder->getId(),
            ]);
        }
        return $this->render('admin/pages/monthend/income_disaggregations/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        '/{id}',
        name: 'admin_monthend_income_disaggregation_manage',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_ANALYST')]
    public function manage(TransferOrder $transferOrder): Response
    {
        $monthendDateRange = $this->monthEndService->getMonthEndDateRangeFromDateTime(
            $transferOrder->getScheduledFor(),
        );
        $otherMonthendOrders = $this->transferOrderRepository->buildQueryWithAssociations([
            'description' => TransferOrderPreset::IncomeDisaggregation->value,
            'scheduledFor_gte' => $monthendDateRange['start'],
            'scheduledFor_lt' => $monthendDateRange['end'],
        ])->getResult();
        // Remove self from the list
        $otherMonthendOrders = array_filter(
            $otherMonthendOrders,
            fn(TransferOrder $item): bool => $item->getId() !== $transferOrder->getId(),
        );
        return $this->render('admin/pages/monthend/income_disaggregations/manage.html.twig', [
            'transferOrder' => $transferOrder,
            'otherMonthendOrders' => $otherMonthendOrders,
            'issues' => $this->monthEndService->validateTransferOrder($transferOrder),
        ]);
    }

    #[Route(
        '/{id}/edit',
        name: 'admin_monthend_income_disaggregation_edit',
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
            return $this->redirectToRoute('admin_monthend_income_disaggregation_manage', [
                'id' => $transferOrder->getId(),
            ]);
        }
        return $this->render('admin/pages/monthend/order_edit.html.twig', [
            'form' => $form->createView(),
            'order' => $transferOrder,
            'exitRoute' => 'admin_monthend_income_disaggregation_manage',
        ]);
    }

    #[Route(
        '/{id}/add-transfer',
        name: 'admin_monthend_income_disaggregation_search',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function search(Request $request, TransferOrder $transferOrder): Response
    {
        if (!$this->monthEndService->isTransferOrderValid($transferOrder)) {
            $this->addFlash(
                'warning',
                "Transfer order #{$transferOrder->getId()} is not a income disaggregation order",
            );
            return $this->redirectToRoute('admin_monthend_income_disaggregation_manage', ['id' => $transferOrder->getId()]);
        }
        if (TransferOrder::STATE_DRAFT != $transferOrder->getStatus()) {
            $this->addFlash(
                'warning',
                'Transfers can only be added when the order is in draft mode',
            );
            return $this->redirectToRoute(
                'admin_monthend_income_disaggregation_manage',
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
        return $this->render('admin/pages/monthend/income_disaggregations/search.html.twig', [
            'transferOrder' => $transferOrder,
            'matchingAssets' => $matchingAssets,
            'shareholdings' => $assetShareholdings,
        ]);
    }

    #[Route(
        '/{transferOrder}/add-transfer/{asset}',
        name: 'admin_monthend_income_disaggregation_add_transfer',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function addTransfer(
        Request $request,
        TransferOrder $transferOrder,
        Asset $asset,
    ): Response {
        if (!$this->monthEndService->isTransferOrderValid($transferOrder)) {
            $this->addFlash(
                'warning',
                "Transfer order #{$transferOrder->getId()} is not a income disaggregation order",
            );
            return $this->redirectToRoute('admin_monthend_income_disaggregation_manage', ['id' => $transferOrder->getId()]);
        }
        if (TransferOrder::STATE_DRAFT != $transferOrder->getStatus()) {
            $this->addFlash(
                'warning',
                'Transfers can only be added when the order is in draft mode',
            );
            return $this->redirectToRoute(
                'admin_monthend_income_disaggregation_manage',
                ['id' => $transferOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }

        // Currently only support super admin as an aggregate wallet
        // May add support for more in future
        $superAdminWallet = $this->userManager->getSuperAdmin()->getMangoPayWalletId();
        $aggregateWalletChoices = [
            'superadmin' => $superAdminWallet,
        ];
        $walletChoices = $this->monthEndService->getAssetWalletChoices($asset);

        if (!array_key_exists('deposit', $walletChoices)) {
            $this->addFlash('warning', "{$asset->getName()} is missing the deposit wallet.
                Configure missing wallets for this asset in the product hub.");
            return $this->redirectToRoute(
                'admin_monthend_income_disaggregation_search',
                ['id' => $transferOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }

        $transferRequest = new TransferRequest();
        $transferRequest->setAsset($asset);
        $transferRequest->setDescription($this->monthEndService->createStructuredDescription(
            'Deposit rental income',
            $asset,
            $transferOrder->getScheduledFor(),
        ));
        $transferRequest->setDebitWalletId($aggregateWalletChoices['superadmin']);
        $transferRequest->setCreditWalletId($walletChoices['settlement']);
        $transferOrder->addTransfer($transferRequest);

        $walletIds = [
            'superadmin' => $aggregateWalletChoices['superadmin'],
            // 'processing wallet' => $aggregateWalletChoices['processingWallet'],
            'asset deposit' => $walletChoices['deposit'],
            'asset settlement/actual' => $walletChoices['settlement'],
        ];
        $walletBalances = [];
        foreach ($walletIds as $name => $walletId) {
            try {
                $providerWallet = $this->walletService->getWallet(
                    $walletId,
                    'USER_NOT_PRESENT',
                );
                $walletBalances[$name] = [
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
                $walletBalances[$name] = [
                    'walletId' => $walletId,
                    'balance' => null,
                    'currency' => null,
                    'description' => null,
                    'owner' => null,
                ];
            }
        }

        $form = $this->createForm(AssetTransferRequestType::class, $transferRequest, [
            'debitWalletChoices' => $aggregateWalletChoices,
            'creditWalletChoices' => [
                'deposit' => $walletChoices['deposit'],
                'settlement/actual' => $walletChoices['settlement'],
            ],
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->doctrine->getManager()->flush();
                return $this->redirectToRoute(
                    'admin_monthend_income_disaggregation_manage',
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

        return $this->render('admin/pages/monthend/income_disaggregations/add_transfer.html.twig', [
            'form' => $form->createView(),
            'asset' => $asset,
            'transferOrder' => $transferOrder,
            'transferRequest' => $transferRequest,
            'walletBalances' => $walletBalances,
        ]);
    }

    #[Route(
        '/{id}/copy-existing',
        name: 'admin_monthend_income_disaggregation_copy_existing',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function listExisting(TransferOrder $transferOrder): Response
    {
        $monthendDateRangeStart = $this->monthEndService->getMonthEndDateRangeFromDateTime(
            $transferOrder->getScheduledFor(),
            -12,
        );
        $monthendDateRange = $this->monthEndService->getMonthEndDateRangeFromDateTime(
            $transferOrder->getScheduledFor(),
        );
        $results = $this->transferOrderRepository
            ->buildQueryWithAssociations([
                'transferType' => TransferType::IncomeDisaggregation,
                'scheduledFor_gte' => $monthendDateRangeStart['start'],
                'scheduledFor_lt' => $monthendDateRange['end'],
                'status' => AbstractOrder::STATE_COMPLETED,
            ], ['id' => 'DESC'])
            ->getResult();
        return $this->render('admin/pages/monthend/income_disaggregations/list_existing.html.twig', [
            'transferOrder' => $transferOrder,
            'suitableOrders' => $results,
            'startDate' => $monthendDateRangeStart['start'],
            'endDate' => $monthendDateRange['end'],
        ]);
    }

    #[Route(
        '/{id}/copy-existing/{copyFromOrder}',
        name: 'admin_monthend_income_disaggregation_copy_existing_preview',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function copyExisting(
        Request $request,
        TransferOrder $transferOrder,
        TransferOrder $copyFromOrder,
    ): Response {
        $form = $this
            ->createFormBuilder()
            // ->add('confirmation', CheckboxType::class, [
            //     'label' => 'I confirm that the user has added our email to their allowlist/whitelist',
            // ])
            ->add('submit', SubmitType::class, ['label' => 'Generate Transfers'])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->transferOrderService->copyRequestsFromExisting(
                    $transferOrder,
                    $copyFromOrder,
                );
                foreach ($transferOrder->getTransfers() as $transferRequest) {
                    if (!is_null($transferRequest->getAsset())) {
                        $transferRequest->setDescription($this->monthEndService->createStructuredDescription(
                            'Deposit rental income',
                            $transferRequest->getAsset(),
                            $transferOrder->getScheduledFor(),
                        ));
                    }
                }
                $this->doctrine->getManager()->flush();
                $this->addFlash('success', 'Transfers successfully copied');
                return $this->redirectToRoute(
                    'admin_monthend_income_disaggregation_manage',
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
        return $this->render('admin/pages/monthend/income_disaggregations/copy_existing.html.twig', [
            'form' => $form,
            'transferOrder' => $transferOrder,
            'copyFromOrder' => $copyFromOrder,
        ]);
    }
}
