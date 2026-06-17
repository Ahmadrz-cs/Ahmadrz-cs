<?php

namespace App\Controller\Admin;

use App\Entity\AbstractOrder;
use App\Entity\TransferOrder;
use App\Entity\TransferRequest;
use App\Entity\User;
use App\Form\Type\ActionConfirmationType;
use App\Form\Type\AssetTransferRequestType;
use App\Form\Type\QueryTransferOrderType;
use App\Form\Type\TransferOrderType;
use App\Form\Type\TransferRequestType;
use App\Message\OrderBatchRun;
use App\Repository\HoldingRepository;
use App\Repository\OfferingRepository;
use App\Repository\TransferOrderRepository;
use App\Service\Manager\UserManagerV2;
use App\Service\MonthEndService;
use App\Service\TransferOrderService;
use App\Service\Util\ExportHelper;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Sonata\Exporter\Exporter;
use Sonata\Exporter\Source\IteratorCallbackSourceIterator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/transfer-orders')]
class TransferOrderController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private TransferOrderRepository $transferOrderRepository,
        private TransferOrderService $transferOrderService,
        private MonthEndService $monthEndService,
        private HoldingRepository $holdingRepository,
        private OfferingRepository $offeringRepository,
        private UserManagerV2 $userManager,
        private Exporter $exporter,
    ) {}

    #[Route('', name: 'admin_transfer_order_index', methods: ['GET'])]
    #[IsGranted('ROLE_ANALYST')]
    public function index(Request $request): Response
    {
        $this->logger->debug('List transfer orders');
        $form = $this->createForm(QueryTransferOrderType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->transferOrderRepository->findByWithAssociations(
            $filters ?? [],
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/transfer_order/list.html.twig', [
            'objects' => $results,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/create', name: 'admin_transfer_order_create', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_OPERATIONS')]
    public function create(Request $request): Response
    {
        $transferOrder = new TransferOrder();
        $transferOrder->setStatus('draft');
        $transferOrder->setScheduledFor(new \DateTime('first day of this month'));
        $assetIds = array_map(function ($x) {
            return $x['assetId'];
        }, $this->holdingRepository->getShareHoldingsAggregate());
        $form = $this->createForm(TransferOrderType::class, $transferOrder, [
            'assetIds' => $assetIds,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->persist($transferOrder);
            $this->doctrine->getManager()->flush();
            return $this->redirectToRoute(
                'admin_transfer_order_manage',
                ['id' => $transferOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }

        return $this->render('admin/pages/transfer_order/new.html.twig', [
            'transfer_order' => $transferOrder,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/manage', name: 'admin_transfer_order_manage', methods: ['GET'])]
    #[IsGranted('ROLE_ANALYST')]
    public function manage(TransferOrder $transferOrder): Response
    {
        return $this->render('admin/pages/transfer_order/manage.html.twig', [
            'transferOrder' => $transferOrder,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_transfer_order_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_OPERATIONS')]
    public function edit(Request $request, TransferOrder $transferOrder): Response
    {
        $this->logger->info('Edit Transfer Order', [$transferOrder->getId()]);
        $assetIds = array_map(function ($x) {
            return $x['assetId'];
        }, $this->holdingRepository->getShareHoldingsAggregate());
        $form = $this->createForm(TransferOrderType::class, $transferOrder, [
            'assetIds' => $assetIds,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();
            return $this->redirectToRoute(
                'admin_transfer_order_manage',
                ['id' => $transferOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        return $this->render('admin/pages/transfer_order/edit.html.twig', [
            'transferOrder' => $transferOrder,
            'form' => $form->createView(),
        ]);
    }

    /**
     * https://symfony.com/blog/new-in-symfony-6-1-improved-routing-requirements-and-utf-8-parameters
     * Replace requirements with BackedEnums
     */
    #[Route(
        '/{id}/{transition}',
        name: 'admin_transfer_order_transition_edit',
        methods: ['GET'],
        requirements: [
            'transition' =>
                AbstractOrder::TRANSITION_APPROVE
                . '|'
                . AbstractOrder::TRANSITION_REQUEST_CHANGE
                . '|'
                . AbstractOrder::TRANSITION_REOPEN,
        ],
    )]
    #[IsGranted('ROLE_FINANCIAL_OPS')]
    public function editorialTransition(
        Request $request,
        TransferOrder $transferOrder,
        string $transition,
    ): Response {
        $this->logger->info('Request Changes for Transfer Order', [$transferOrder->getId()]);
        try {
            $this->transferOrderService->transitionTransferOrder(
                $transferOrder,
                $transition,
            );
            if (AbstractOrder::TRANSITION_APPROVE === $transition) {
                $transferOrder->setApprovedBy($this->getUser());
            }
            if (AbstractOrder::TRANSITION_REQUEST_CHANGE === $transition) {
                $transferOrder->setApprovedBy(null);
            }
            $this->doctrine->getManager()->flush();
            $this->addFlash(
                'success',
                'Transfer order successfully updated to ' . $transferOrder->getStatus(),
            );
        } catch (\Exception $e) {
            $this->addFlash(
                'error',
                'Could not apply state transition. ' . $e->getMessage(),
            );
            $this->logger->error('Unable to transition transfer order', [$e->getMessage()]);
        }
        if (in_array(
            $request->query->get('redirectRoute'),
            MonthEndController::REDIRECT_ROUTES,
        )) {
            $redirectToRoute = $request->query->get('redirectRoute');
        }
        return $this->redirectToRoute(
            $redirectToRoute ?? 'admin_transfer_order_manage',
            ['id' => $transferOrder->getId()],
            Response::HTTP_SEE_OTHER,
        );
    }

    /**
     * https://symfony.com/blog/new-in-symfony-6-1-improved-routing-requirements-and-utf-8-parameters
     * Replace requirements with BackedEnums
     */
    #[Route(
        '/{id}/{transition}',
        name: 'admin_transfer_order_transition_confirm',
        methods: ['GET', 'POST'],
        requirements: [
            'transition' =>
                AbstractOrder::TRANSITION_REJECT
                . '|'
                . AbstractOrder::TRANSITION_ABANDON,
        ],
    )]
    #[IsGranted('ROLE_FINANCIAL_OPS')]
    public function confirmationTransition(
        Request $request,
        TransferOrder $transferOrder,
        string $transition,
    ): Response {
        $options = [
            'reasonPlaceholder' => 'e.g. Contract repairs cancelled',
            'reasonHelpText' => 'Provide a reason for closing this transfer order. This will be added to the description',
        ];
        $form = $this->createForm(ActionConfirmationType::class, null, $options);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $reason = $form->getData()['reason'] ?? '';
            if ($reason) {
                $transferOrder->setDescription(
                    "[$reason] " . $transferOrder->getDescription(),
                );
            }
            try {
                $this->logger->info(
                    "Transfer Order {$transition}",
                    [$transferOrder->getId()],
                );
                $this->transferOrderService->transitionTransferOrder(
                    $transferOrder,
                    $transition,
                );
                $transferOrder->setApprovedBy(null);
                $this->doctrine->getManager()->flush();
                $this->addFlash(
                    'success',
                    'Transfer order successfully updated to '
                        . $transferOrder->getStatus(),
                );
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Could not apply state transition. ' . $e->getMessage(),
                );
                $this->logger->error('Unable to transition transfer order', [$e->getMessage()]);
            }
            if (in_array(
                $request->query->get('redirectRoute'),
                MonthEndController::REDIRECT_ROUTES,
            )) {
                $redirectToRoute = $request->query->get('redirectRoute');
            }
            return $this->redirectToRoute(
                $redirectToRoute ?? 'admin_transfer_order_manage',
                ['id' => $transferOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        return $this->render('admin/pages/transfer_order/transition_confirm.html.twig', [
            'transferOrder' => $transferOrder,
            'form' => $form->createView(),
            'transition' => $transition,
        ]);
    }

    #[Route('/{id}/run', name: 'admin_transfer_order_run', methods: ['GET'])]
    #[IsGranted('ROLE_FINANCIAL_OPS')]
    public function runOrder(Request $request, TransferOrder $transferOrder): Response
    {
        $this->logger->info('Run Transfer Order', [$transferOrder->getId()]);
        try {
            $this->transferOrderService->runOrder($transferOrder);
            $this->addFlash(
                'success',
                'Transfer order successfully run. Order is '
                    . $transferOrder->getStatus(),
            );
        } catch (\Exception $e) {
            $this->addFlash(
                'error',
                'Unable to run transfer order to completion. ' . $e->getMessage(),
            );
            $this->logger->error('Unable to run transfer order to completion. ', [$e->getMessage()]);
        }
        // Flush any changes to entities
        $this->doctrine->getManager()->flush();
        if (in_array(
            $request->query->get('redirectRoute'),
            MonthEndController::REDIRECT_ROUTES,
        )) {
            $redirectToRoute = $request->query->get('redirectRoute');
        }
        return $this->redirectToRoute(
            $redirectToRoute ?? 'admin_transfer_order_manage',
            ['id' => $transferOrder->getId()],
            Response::HTTP_SEE_OTHER,
        );
    }

    #[Route(
        '/{id}/run-batch',
        name: 'admin_transfer_order_run_batch',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_FINANCIAL_OPS')]
    public function runBatch(
        Request $request,
        TransferOrder $transferOrder,
        MessageBusInterface $bus,
    ): Response {
        $this->logger->info('Run Transfer Order in background', [$transferOrder->getId()]);
        /** @var UserInterface|User $currentUser  */
        $currentUser = $this->getUser();
        $bus->dispatch(new OrderBatchRun(
            orderFqcn: TransferOrder::class,
            orderId: $transferOrder->getId(),
            submittedByUserId: $currentUser->getId(),
            autoContinue: true,
        ));
        $this->addFlash(
            'success',
            'Transfer order run submitted as a background job. Refresh this page for progress updates. You will be notified on completion.',
        );
        if (in_array(
            $request->query->get('redirectRoute'),
            MonthEndController::REDIRECT_ROUTES,
        )) {
            $redirectToRoute = $request->query->get('redirectRoute');
        }
        return $this->redirectToRoute(
            $redirectToRoute ?? 'admin_transfer_order_manage',
            ['id' => $transferOrder->getId()],
            Response::HTTP_SEE_OTHER,
        );
    }

    #[Route(
        '/{id}/force-complete',
        name: 'admin_transfer_order_force_complete',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_FINANCIAL_OPS')]
    public function forceComplete(
        Request $request,
        TransferOrder $transferOrder,
    ): Response {
        $options = [
            'reasonPlaceholder' => 'e.g. Some transfers not needed',
            'reasonHelpText' => 'Provide a reason for force completing this transfer order. This will be added to the description',
            'additionalAction' => [
                'name' => 'truncate',
                'label' => 'Delete incomplete requests (instead of zeroing)',
            ],
        ];
        $form = $this->createForm(ActionConfirmationType::class, null, $options);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->logger->info('Force completing Transfer Order. Zeroing incomplete requests.', [$transferOrder->getId()]);

            $reason = $form->getData()['reason'] ?? '';
            if ($reason) {
                $transferOrder->setDescription(
                    "[$reason] " . $transferOrder->getDescription(),
                );
            }

            try {
                $this->transferOrderService->forceCompleteOrder(
                    $transferOrder,
                    $form->getData()['truncate'],
                );
                $this->addFlash(
                    'success',
                    'Transfer order successfully run. Order is '
                        . $transferOrder->getStatus(),
                );
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Unable to force transfer order to completion. ' . $e->getMessage(),
                );
                $this->logger->error('Unable to force transfer order to completion. ', [$e->getMessage()]);
            }

            // Flush any changes to entities
            $this->doctrine->getManager()->flush();

            if (in_array(
                $request->query->get('redirectRoute'),
                MonthEndController::REDIRECT_ROUTES,
            )) {
                $redirectToRoute = $request->query->get('redirectRoute');
            }
            return $this->redirectToRoute(
                $redirectToRoute ?? 'admin_transfer_order_manage',
                ['id' => $transferOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        return $this->render('admin/pages/transfer_order/transition_confirm.html.twig', [
            'transferOrder' => $transferOrder,
            'form' => $form->createView(),
            'transition' => AbstractOrder::META_TRANSITION_FORCE_COMPLETE,
        ]);
    }

    #[Route(
        '/{id}/add-transfer',
        name: 'admin_transfer_order_add_transfer',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function addTransfer(
        Request $request,
        TransferOrder $transferOrder,
    ): Response {
        $this->logger->info('Add transfer to Transfer Order', [$transferOrder->getId()]);
        if (AbstractOrder::STATE_DRAFT != $transferOrder->getStatus()) {
            $this->addFlash(
                'warning',
                'Transfers can only be added when the order is in draft mode',
            );
            return $this->redirectToRoute(
                'admin_transfer_order_manage',
                ['id' => $transferOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        $transferRequest = new TransferRequest();
        $transferOrder->addTransfer($transferRequest);
        $form = $this->createForm(TransferRequestType::class, $transferRequest);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->doctrine->getManager()->flush();
                $this->addFlash(
                    'success',
                    "Transfer from {$transferRequest->getDebitWalletId()} to {$transferRequest->getCreditWalletId()} successfully created",
                );
                return $this->redirectToRoute(
                    'admin_transfer_order_manage',
                    ['id' => $transferOrder->getId()],
                    Response::HTTP_SEE_OTHER,
                );
            } catch (\Exception $e) {
                $this->logger->error('Could not add transfer to order', [$e->getMessage()]);
            }
        }
        return $this->render('admin/pages/transfer_request/new.html.twig', [
            'transferOrder' => $transferOrder,
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        '/{id}/add-asset-transfer',
        name: 'admin_add_asset_transfer',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function addAssetTransfer(
        Request $request,
        TransferOrder $transferOrder,
    ): Response {
        $this->logger->info('Add transfer to Transfer Order', [$transferOrder->getId()]);
        // Guard clauses to prevent creation of asset transfer if not safe
        $canCreateTransfer = true;
        if (AbstractOrder::STATE_DRAFT != $transferOrder->getStatus()) {
            $this->addFlash(
                'warning',
                'Transfers can only be added when the order is in draft mode',
            );
            $canCreateTransfer = false;
        }
        if (!$transferOrder->getAsset()?->getMainWalletId()) {
            $this->addFlash(
                'warning',
                'Asset transfers can only be created if the order is linked to an asset with a wallet',
            );
            $canCreateTransfer = false;
        }
        if (!$canCreateTransfer) {
            return $this->redirectToRoute(
                'admin_transfer_order_manage',
                ['id' => $transferOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }

        // Prepare customised form
        $creditWalletChoices = $this->monthEndService->getAssetWalletChoices($transferOrder->getAsset());
        $superadminWallet = $this->userManager->getSuperAdmin()->getMangoPayWalletId();
        if ($superadminWallet) {
            $debitWalletChoices = array_merge($creditWalletChoices, [
                'superadmin' => $superadminWallet,
            ]);
        }
        $transferRequest = new TransferRequest();
        $transferOrder->addTransfer($transferRequest);
        $transferRequest->setDebitWalletId($creditWalletChoices['deposit']);
        $form = $this->createForm(AssetTransferRequestType::class, $transferRequest, [
            'debitWalletChoices' => $debitWalletChoices ?? $creditWalletChoices,
            'creditWalletChoices' => $creditWalletChoices,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (
                $transferRequest->getDebitWalletId() === $transferRequest->getCreditWalletId()
            ) {
                $this->addFlash(
                    'warning',
                    'Cannot transfer from and to the same wallet',
                );
            } else {
                $this->doctrine->getManager()->flush();
                $this->addFlash(
                    'success',
                    "Transfer from {$transferRequest->getDebitWalletId()} to {$transferRequest->getCreditWalletId()} successfully created",
                );
                return $this->redirectToRoute(
                    'admin_transfer_order_manage',
                    ['id' => $transferOrder->getId()],
                    Response::HTTP_SEE_OTHER,
                );
            }
        }

        return $this->render('admin/pages/monthend/new.html.twig', [
            'transferOrder' => $transferOrder,
            'form' => $form->createView(),
            'offering' => $this->offeringRepository->findFirstPartyByAssetId(
                $transferOrder->getAsset()->getId(),
            ),
        ]);
    }

    #[Route(
        '/{id}/clear-transfers',
        name: 'admin_transfer_order_clear_transfers',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function clearTransfers(
        Request $request,
        TransferOrder $transferOrder,
    ): Response {
        if (in_array(
            $request->query->get('redirectRoute'),
            MonthEndController::REDIRECT_ROUTES,
        )) {
            $redirectToRoute = $request->query->get('redirectRoute');
        }
        if (AbstractOrder::STATE_DRAFT != $transferOrder->getStatus()) {
            $this->addFlash(
                'warning',
                'Transfers can only be cleared when the order is in draft mode',
            );
            return $this->redirectToRoute(
                $redirectToRoute ?? 'admin_transfer_order_manage',
                ['id' => $transferOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->logger->info('Clear Transfers for Order', [$transferOrder->getId()]);
            $transferOrder->getTransfers()->clear();
            $this->doctrine->getManager()->flush();
            return $this->redirectToRoute(
                $redirectToRoute ?? 'admin_transfer_order_manage',
                ['id' => $transferOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        return $this->render('admin/pages/transfer_order/clear_transfers.html.twig', [
            'transferOrder' => $transferOrder,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/export', name: 'admin_transfer_order_export', methods: ['GET'])]
    #[IsGranted('ROLE_ANALYST')]
    public function exportOrders(
        Request $request,
        TransferOrderRepository $transferOrderRepository,
    ): Response {
        $this->logger->info('Export Transfer Orders');
        $format = ExportHelper::validateExportFormat($request->query->get(
            'format',
            'csv',
        ));
        $fileName = ExportHelper::generateFileName('transfer_orders_', $format);
        // Want greater control over the output of the asset property
        $source = new IteratorCallbackSourceIterator(
            new \ArrayIterator($transferOrderRepository->findAll()),
            $this->transferOrderService->formatTransferOrdersCallable(),
        );
        return $this->exporter->getResponse($format, $fileName, $source);
    }

    #[Route(
        '/{id}/export',
        name: 'admin_transfer_order_transfers_export',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_ANALYST')]
    public function exportTransfers(
        Request $request,
        TransferOrder $transferOrder,
    ): Response {
        $this->logger->info('Export transfers for Order', [$transferOrder->getId()]);
        $format = ExportHelper::validateExportFormat($request->query->get(
            'format',
            'csv',
        ));
        $fileName = ExportHelper::generateFileName(
            preg_replace('/\s+/', '_', $transferOrder->getDescription()) . '_transfers',
            $format,
        );
        $source = new IteratorCallbackSourceIterator(
            new \ArrayIterator($transferOrder->getTransfers()->toArray()),
            $this->transferOrderService->formatTransfersCallable(),
        );
        return $this->exporter->getResponse($format, $fileName, $source);
    }
}
