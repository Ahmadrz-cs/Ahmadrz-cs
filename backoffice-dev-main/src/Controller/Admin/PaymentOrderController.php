<?php

namespace App\Controller\Admin;

use App\Entity\Enum\OrderingDirection;
use App\Entity\Enum\PaymentType;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\PaymentOrder;
use App\Entity\PaymentRequest;
use App\Entity\User;
use App\Form\Type\ActionConfirmationType;
use App\Form\Type\PaymentGeneratorType;
use App\Form\Type\PaymentOrderDateType;
use App\Form\Type\PaymentOrderDescriptionType;
use App\Form\Type\PaymentOrderType;
use App\Form\Type\PaymentRequestType;
use App\Form\Type\QueryPaymentOrderType;
use App\Message\OrderBatchRun;
use App\Repository\PaymentOrderRepository;
use App\Repository\PayoutRepository;
use App\Repository\ShareTradeRepository;
use App\Repository\TradeOrderRepository;
use App\Service\DivestmentService;
use App\Service\Manager\AssetManagerV2;
use App\Service\MangopayWalletService;
use App\Service\PaymentGeneratorService;
use App\Service\PaymentOrderService;
use App\Service\PaymentService;
use App\Service\Util\ExportHelper;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Sonata\Exporter\Exporter;
use Sonata\Exporter\Source\IteratorCallbackSourceIterator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class PaymentOrderController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private TagAwareCacheInterface $defaultAppCache,
        private PaymentOrderRepository $paymentOrderRepository,
        private ShareTradeRepository $shareTradeRepository,
        private TradeOrderRepository $tradeOrderRepository,
        private PaymentOrderService $paymentOrderService,
        private PayoutRepository $payoutRepository,
        private PaymentGeneratorService $paymentGeneratorService,
        private AssetManagerV2 $assetManager,
        private DivestmentService $divestmentService,
        private MangopayWalletService $mangopayWalletService,
        private Exporter $exporter,
    ) {}

    #[Route(path: '/payment-order', name: 'admin_payment_order')]
    #[IsGranted('ROLE_ANALYST')]
    public function index(Request $request): Response
    {
        $this->logger->info('List payouts');
        $form = $this->createForm(QueryPaymentOrderType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->paymentOrderRepository->findByWithAssociations(
            $filters ?? [],
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/payment_order/list.html.twig', [
            'objects' => $results,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/payment-order/{id}/manage', name: 'admin_payment_order_manage')]
    #[IsGranted('ROLE_ANALYST')]
    public function manage(PaymentOrder $paymentOrder): Response
    {
        $shareholders = $this->shareTradeRepository->aggregateAssetShareholdingsByUser(
            assetId: $paymentOrder->getAsset()->getId(),
            nonZero: true,
            shareholderOrdering: OrderingDirection::Descending,
        );
        return $this->render('admin/pages/payment_order/manage.html.twig', [
            'paymentOrder' => $paymentOrder,
            'currentShareholders' => $shareholders,
        ]);
    }

    #[Route(path: '/payment-order/create', name: 'admin_payment_order_create')]
    #[IsGranted('ROLE_OPERATIONS')]
    public function create(Request $request): Response
    {
        $this->logger->info('New Payment Order');
        $paymentOrder = new PaymentOrder();
        $paymentOrder->setStatus('draft');
        $paymentOrder->setScheduledFor(new \DateTime('first day of this month'));
        // Only get asset ids that have non zero current shareholdings otherwise you can't add payments
        // Add a way to show historical ones for testing
        $hideHistorical = $request->query->get('hideHistorical', true);
        $assetIds = array_map(function ($x) {
            return $x['assetid'];
        }, $this->shareTradeRepository->aggregateSharesInCirculation(nonZero: $hideHistorical));
        $form = $this->createForm(PaymentOrderType::class, $paymentOrder, [
            'assetIds' => $assetIds,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->persist($paymentOrder);
            $this->doctrine->getManager()->flush();
            return $this->redirectToRoute(
                'admin_payment_order_manage',
                ['id' => $paymentOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        return $this->render('admin/pages/payment_order/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/payment-order/{id}/edit', name: 'admin_payment_order_edit')]
    #[IsGranted('ROLE_OPERATIONS')]
    public function edit(Request $request, PaymentOrder $paymentOrder): Response
    {
        $this->logger->info('Edit Payment Order', [$paymentOrder->getId()]);
        $hideHistorical = $request->query->get('hideHistorical', true);
        $assetIds = array_map(function ($x) {
            return $x['assetid'];
        }, $this->shareTradeRepository->aggregateSharesInCirculation(nonZero: $hideHistorical));
        $form = $this->createForm(PaymentOrderType::class, $paymentOrder, [
            'assetIds' => $assetIds,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();
            return $this->redirectToRoute(
                'admin_payment_order_manage',
                ['id' => $paymentOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        return $this->render('admin/pages/payment_order/edit.html.twig', [
            'paymentOrder' => $paymentOrder,
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        '/payment-orders/{id}/date',
        name: 'admin_payment_order_date',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function editDate(Request $request, PaymentOrder $paymentOrder): Response
    {
        $form = $this->createForm(PaymentOrderDateType::class, $paymentOrder);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();
            $this->logger->debug("Updated payment order #{$paymentOrder->getId()}");
            $this->addFlash('success', 'Payment order successfully updated');
            if (in_array(
                $request->query->get('redirectRoute'),
                MonthEndController::REDIRECT_ROUTES,
            )) {
                $redirectToRoute = $request->query->get('redirectRoute');
            }
            if ($request->query->get('setup')) {
                return $this->redirectToRoute('admin_payment_order_description', [
                    'id' => $paymentOrder->getId(),
                    'setup' => 1,
                    'redirectRoute' => $redirectToRoute,
                ]);
            }
            return $this->redirectToRoute(
                $redirectToRoute ?? 'admin_payment_order_manage',
                ['id' => $paymentOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        return $this->render('admin/pages/monthend/payments/edit_date.html.twig', [
            'form' => $form->createView(),
            'paymentOrder' => $paymentOrder,
        ]);
    }

    #[Route(
        '/payment-orders/{id}/description',
        name: 'admin_payment_order_description',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function editDescription(
        Request $request,
        PaymentOrder $paymentOrder,
    ): Response {
        $form = $this->createForm(PaymentOrderDescriptionType::class, $paymentOrder);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();
            $this->logger->debug("Updated payment order #{$paymentOrder->getId()}");
            $this->addFlash('success', 'Payment order successfully updated');
            if (in_array(
                $request->query->get('redirectRoute'),
                MonthEndController::REDIRECT_ROUTES,
            )) {
                $redirectToRoute = $request->query->get('redirectRoute');
            }
            if ($request->query->get('setup')) {
                $continueToRoute = match ($paymentOrder->getPaymentType()) {
                    PaymentType::Dividend->value => 'admin_monthend_dividend_generate',
                    PaymentType::Repayment->value
                        => 'admin_monthend_repayment_generate',
                    PaymentType::Divestment->value
                        => 'admin_monthend_divestment_generate',
                    PaymentType::InvestmentExit->value
                        => 'admin_monthend_divestment_generate',
                    default => 'admin_payment_order_manage',
                };
                return $this->redirectToRoute($continueToRoute, [
                    'id' => $paymentOrder->getId(),
                    'setup' => 1,
                    'redirectRoute' => $redirectToRoute,
                ]);
            }
            return $this->redirectToRoute(
                $redirectToRoute ?? 'admin_payment_order_manage',
                ['id' => $paymentOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        return $this->render('admin/pages/monthend/payments/edit_description.html.twig', [
            'form' => $form->createView(),
            'paymentOrder' => $paymentOrder,
        ]);
    }

    #[Route(path: '/payment-order/{id}/approve', name: 'admin_payment_order_approve')]
    #[IsGranted('ROLE_FINANCIAL_OPS')]
    public function approve(Request $request, PaymentOrder $paymentOrder): Response
    {
        $this->logger->info('Approve Payment Order', [$paymentOrder->getId()]);
        try {
            $this->paymentOrderService->transitionPaymentOrder(
                $paymentOrder,
                'approve',
            );
            $paymentOrder->setApprovedBy($this->getUser());
            $this->doctrine->getManager()->flush();
            $this->addFlash(
                'success',
                'Payment order successfully updated to ' . $paymentOrder->getStatus(),
            );
        } catch (\Exception $e) {
            $this->addFlash(
                'error',
                'Could not apply state transition. ' . $e->getMessage(),
            );
            $this->logger->error('Unable to transition payment order', [$e->getMessage()]);
        }
        if (in_array(
            $request->query->get('redirectRoute'),
            MonthEndController::REDIRECT_ROUTES,
        )) {
            $redirectToRoute = $request->query->get('redirectRoute');
        }
        return $this->redirectToRoute(
            $redirectToRoute ?? 'admin_payment_order_manage',
            ['id' => $paymentOrder->getId()],
            Response::HTTP_SEE_OTHER,
        );
    }

    #[Route(
        path: '/payment-order/{id}/request-changes',
        name: 'admin_payment_order_request_changes',
    )]
    #[IsGranted('ROLE_FINANCIAL_OPS')]
    public function requestChanges(
        Request $request,
        PaymentOrder $paymentOrder,
    ): Response {
        $this->logger->info('Request Changes for Payment Order', [$paymentOrder->getId()]);
        try {
            $this->paymentOrderService->transitionPaymentOrder(
                $paymentOrder,
                'request_change',
            );
            $paymentOrder->setApprovedBy(null);
            $this->doctrine->getManager()->flush();
            $this->addFlash(
                'success',
                'Payment order successfully updated to ' . $paymentOrder->getStatus(),
            );
        } catch (\Exception $e) {
            $this->addFlash(
                'error',
                'Could not apply state transition. ' . $e->getMessage(),
            );
            $this->logger->error('Unable to transition payment order', [$e->getMessage()]);
        }
        if (in_array(
            $request->query->get('redirectRoute'),
            MonthEndController::REDIRECT_ROUTES,
        )) {
            $redirectToRoute = $request->query->get('redirectRoute');
        }
        return $this->redirectToRoute(
            $redirectToRoute ?? 'admin_payment_order_manage',
            ['id' => $paymentOrder->getId()],
            Response::HTTP_SEE_OTHER,
        );
    }

    #[Route(path: '/payment-order/{id}/close', name: 'admin_payment_order_close')]
    #[IsGranted('ROLE_FINANCIAL_OPS')]
    public function close(Request $request, PaymentOrder $paymentOrder): Response
    {
        $options = [
            'reasonPlaceholder' => 'e.g. Duplicate',
            'reasonHelpText' => 'Provide a reason for closing this payment order. This will be added to the description',
        ];
        $form = $this->createForm(ActionConfirmationType::class, null, $options);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $reason = $form->getData()['reason'] ?? '';
            if ($reason) {
                $paymentOrder->setDescription(
                    "[$reason] " . $paymentOrder->getDescription(),
                );
            }
            try {
                $this->logger->info('Payment Order Closure', [$paymentOrder->getId()]);
                $this->paymentOrderService->transitionPaymentOrder(
                    $paymentOrder,
                    'reject',
                );
                $paymentOrder->setApprovedBy(null);
                $this->doctrine->getManager()->flush();
                $this->addFlash(
                    'success',
                    'Payment order successfully updated to '
                        . $paymentOrder->getStatus(),
                );
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Could not apply state transition. ' . $e->getMessage(),
                );
                $this->logger->error('Unable to transition payment order', [$e->getMessage()]);
            }
            if (in_array(
                $request->query->get('redirectRoute'),
                MonthEndController::REDIRECT_ROUTES,
            )) {
                $redirectToRoute = $request->query->get('redirectRoute');
            }
            return $this->redirectToRoute(
                $redirectToRoute ?? 'admin_payment_order_manage',
                ['id' => $paymentOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        return $this->render('admin/pages/payment_order/reject.html.twig', [
            'paymentOrder' => $paymentOrder,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/payment-order/{id}/reopen', name: 'admin_payment_order_reopen')]
    #[IsGranted('ROLE_FINANCIAL_OPS')]
    public function reopen(Request $request, PaymentOrder $paymentOrder): Response
    {
        $this->logger->info('Reopen Payment Order', [$paymentOrder->getId()]);
        try {
            $this->paymentOrderService->transitionPaymentOrder($paymentOrder, 'reopen');
            $this->doctrine->getManager()->flush();
            $this->addFlash(
                'success',
                'Payment order successfully updated to ' . $paymentOrder->getStatus(),
            );
        } catch (\Exception $e) {
            $this->addFlash(
                'error',
                'Could not apply state transition. ' . $e->getMessage(),
            );
            $this->logger->error('Unable to transition payment order', [$e->getMessage()]);
        }
        if (in_array(
            $request->query->get('redirectRoute'),
            MonthEndController::REDIRECT_ROUTES,
        )) {
            $redirectToRoute = $request->query->get('redirectRoute');
        }
        return $this->redirectToRoute(
            $redirectToRoute ?? 'admin_payment_order_manage',
            ['id' => $paymentOrder->getId()],
            Response::HTTP_SEE_OTHER,
        );
    }

    #[Route(path: '/payment-order/{id}/abandon', name: 'admin_payment_order_abandon')]
    #[IsGranted('ROLE_FINANCIAL_OPS')]
    public function abandon(Request $request, PaymentOrder $paymentOrder): Response
    {
        $options = [
            'reasonPlaceholder' => 'e.g. Under-payment',
            'reasonHelpText' => 'Provide a reason for closing this payment order. This will be added to the description',
        ];
        $form = $this->createForm(ActionConfirmationType::class, null, $options);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $reason = $form->getData()['reason'] ?? '';
            if ($reason) {
                $paymentOrder->setDescription(
                    "[$reason] " . $paymentOrder->getDescription(),
                );
            }
            try {
                $this->logger->info('Payment Order abandon', [$paymentOrder->getId()]);
                $this->paymentOrderService->transitionPaymentOrder(
                    $paymentOrder,
                    'abandon',
                );
                $paymentOrder->setApprovedBy(null);
                $this->doctrine->getManager()->flush();
                $this->addFlash(
                    'success',
                    'Payment order successfully updated to '
                        . $paymentOrder->getStatus(),
                );
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Could not apply state transition. ' . $e->getMessage(),
                );
                $this->logger->error('Unable to transition payment order', [$e->getMessage()]);
            }
            if (in_array(
                $request->query->get('redirectRoute'),
                MonthEndController::REDIRECT_ROUTES,
            )) {
                $redirectToRoute = $request->query->get('redirectRoute');
            }
            return $this->redirectToRoute(
                $redirectToRoute ?? 'admin_payment_order_manage',
                ['id' => $paymentOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        return $this->render('admin/pages/payment_order/abandon.html.twig', [
            'paymentOrder' => $paymentOrder,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/payment-order/export', name: 'admin_payment_order_export')]
    #[IsGranted('ROLE_ANALYST')]
    public function exportOrders(
        Request $request,
        PaymentOrderRepository $paymentOrderRepository,
    ): Response {
        $this->logger->info('Export Payment Orders');
        $format = ExportHelper::validateExportFormat($request->query->get(
            'format',
            'csv',
        ));
        $fileName = ExportHelper::generateFileName('payment_orders_', $format);
        // Want greater control over the output of the asset property
        $source = new IteratorCallbackSourceIterator(
            new \ArrayIterator($paymentOrderRepository->findAll()),
            $this->paymentOrderService->formatPaymentOrdersCallable(),
        );
        return $this->exporter->getResponse($format, $fileName, $source);
    }

    #[Route(
        path: '/payment-order/{id}/export',
        name: 'admin_payment_order_payments_export',
    )]
    #[IsGranted('ROLE_ANALYST')]
    public function exportPayments(
        Request $request,
        PaymentOrder $paymentOrder,
    ): Response {
        $this->logger->info('Export payments for Order', [$paymentOrder->getId()]);
        $format = ExportHelper::validateExportFormat($request->query->get(
            'format',
            'csv',
        ));
        $fileName = ExportHelper::generateFileName(
            $paymentOrder->getAsset()->getCompanyNumber()
            . '_'
            . preg_replace('/\s+/', '_', $paymentOrder->getPaymentType())
            . 's_',
            $format,
        );
        $source = new IteratorCallbackSourceIterator(
            new \ArrayIterator($paymentOrder->getPayments()->toArray()),
            $this->paymentOrderService->formatPaymentsCallable(),
        );
        return $this->exporter->getResponse($format, $fileName, $source);
    }

    #[Route(
        path: '/payment-order/{id}/add-payment',
        name: 'admin_payment_order_add_payment',
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function addPayment(Request $request, PaymentOrder $paymentOrder): Response
    {
        $this->logger->info('Add payment to Payment Order', [$paymentOrder->getId()]);
        if (PaymentOrder::STATE_DRAFT != $paymentOrder->getStatus()) {
            $this->addFlash(
                'warning',
                'Payments can only be added when the order is in draft mode',
            );
            return $this->redirectToRoute(
                'admin_payment_order_manage',
                ['id' => $paymentOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        $paymentRequest = new PaymentRequest();
        $paymentRequest->setPaymentOrder($paymentOrder);
        if (PaymentService::TYPE_REPAYMENT == $paymentOrder->getPaymentType()) {
            $prefunderSellOrders = $this->tradeOrderRepository
                ->buildQueryWithAssociations([
                    'assetId' => $paymentOrder->getAsset()->getId(),
                    'status' => [TradeOrderStatus::Active, TradeOrderStatus::Completed],
                    'type' => TradeOrderType::Prefunding,
                    'direction' => TradeDirection::Sell,
                ], ['numberOfShares' => 'ASC'])
                ->getResult();
            $repaymentSummary =
                $this->divestmentService->compileRepaymentProgress(
                    $prefunderSellOrders,
                );
            $shareholderIds = array_keys($repaymentSummary);
        } else {
            $assetShareholdings = $this->shareTradeRepository->aggregateAssetShareholdingsByUser(
                assetId: $paymentOrder->getAsset()->getId(),
                nonZero: true,
                shareholderOrdering: OrderingDirection::Descending,
            );
            $shareholderIds = array_map(fn($x) => $x['userid'], $assetShareholdings);
        }
        // $this->logger->debug('', $shareholderIds);
        $currentPayees = array_map(function ($x) {
            return $x->getPayee()->getId();
        }, $paymentOrder->getPayments()->toArray());
        $shareholderIds = array_diff($shareholderIds, $currentPayees);
        $form = $this->createForm(PaymentRequestType::class, $paymentRequest, [
            'shareholderIds' => $shareholderIds,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $payeeShareholdings = $this->shareTradeRepository->aggregateAssetShareholdingsByUser(
                assetId: $paymentOrder->getAsset()->getId(),
                userId: $paymentRequest->getPayee()->getId(),
            );
            try {
                $shareholding = $payeeShareholdings[0]['shares'];
                // If no shareholding was given or it was higher than the whole shareholding
                // Set the shareholding to the whole shareholding
                if (
                    is_null($paymentRequest->getShareholding())
                    || $paymentRequest->getShareholding() > $shareholding
                ) {
                    $paymentRequest->setShareholding($shareholding);
                }
                $this->doctrine->getManager()->persist($paymentRequest);
                $this->doctrine->getManager()->flush();
                $this->addFlash(
                    'success',
                    'Payment for '
                    . $paymentRequest->getPayee()->getFullname()
                    . ' successfully created',
                );
                if (in_array(
                    $request->query->get('redirectRoute'),
                    MonthEndController::REDIRECT_ROUTES,
                )) {
                    $redirectToRoute = $request->query->get('redirectRoute');
                }
                return $this->redirectToRoute(
                    $redirectToRoute ?? 'admin_payment_order_manage',
                    ['id' => $paymentOrder->getId()],
                    Response::HTTP_SEE_OTHER,
                );
            } catch (\Exception $e) {
                $this->addFlash('warning', 'Payee is not a shareholder in the asset');
                $this->logger->warning('Could not find shareholding for user', [$e->getMessage()]);
            }
        }
        return $this->render('admin/pages/payment_request/new.html.twig', [
            'paymentOrder' => $paymentOrder,
            'form' => $form->createView(),
            'shareholderIds' => $shareholderIds,
        ]);
    }

    #[Route(path: '/payment-request/{id}/edit', name: 'admin_payment_request_edit')]
    #[IsGranted('ROLE_OPERATIONS')]
    public function editPayment(
        Request $request,
        PaymentRequest $paymentRequest,
    ): Response {
        $this->logger->info('Edit payment', [$paymentRequest->getId()]);
        if (
            PaymentOrder::STATE_DRAFT != $paymentRequest->getPaymentOrder()->getStatus()
        ) {
            $this->addFlash(
                'warning',
                'Payments can only be edited when the order is in draft mode',
            );
            return $this->redirectToRoute(
                'admin_payment_order_manage',
                ['id' => $paymentRequest->getPaymentOrder()->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        $assetShareholdings = $this->shareTradeRepository->aggregateAssetShareholdingsByUser(
            assetId: $paymentRequest->getPaymentOrder()->getAsset()->getId(),
            nonZero: true,
            shareholderOrdering: OrderingDirection::Descending,
        );

        $shareholderIds = array_map(fn($x) => $x['userid'], $assetShareholdings);
        $currentPayees = array_map(function ($x) {
            return $x->getPayee()->getId();
        }, $paymentRequest->getPaymentOrder()->getPayments()->toArray());
        $shareholderIds = array_diff($shareholderIds, $currentPayees);

        // always ensure current payee is selectable
        $shareholderIds[] = $paymentRequest->getPayee()->getId();
        $form = $this->createForm(PaymentRequestType::class, $paymentRequest, [
            'shareholderIds' => $shareholderIds,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $payeeShareholdings = $this->shareTradeRepository->aggregateAssetShareholdingsByUser(
                assetId: $paymentRequest->getPaymentOrder()->getAsset()->getId(),
                userId: $paymentRequest->getPayee()->getId(),
            );
            $shareholding = $payeeShareholdings[0]['shares'];
            // Prevent shareholding from being set to above the whole shareholding
            $paymentRequest->setShareholding(min(
                $paymentRequest->getShareholding(),
                $shareholding,
            ));
            $this->doctrine->getManager()->flush();
            $payee = $paymentRequest->getPayee()->getFullname();
            $this->addFlash('success', "Payment for {$payee} successfully updated");
            if (in_array(
                $request->query->get('redirectRoute'),
                MonthEndController::REDIRECT_ROUTES,
            )) {
                $redirectToRoute = $request->query->get('redirectRoute');
            }
            return $this->redirectToRoute(
                $redirectToRoute ?? 'admin_payment_order_manage',
                ['id' => $paymentRequest->getPaymentOrder()->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        return $this->render('admin/pages/payment_request/edit.html.twig', [
            'paymentRequest' => $paymentRequest,
            'form' => $form->createView(),
            'shareholderIds' => $shareholderIds,
        ]);
    }

    #[Route(path: '/payment-request/{id}/delete', name: 'admin_payment_request_delete')]
    #[IsGranted('ROLE_OPERATIONS')]
    public function deletePayment(
        Request $request,
        PaymentRequest $paymentRequest,
    ): Response {
        if (
            PaymentOrder::STATE_DRAFT != $paymentRequest->getPaymentOrder()->getStatus()
        ) {
            $this->addFlash(
                'warning',
                'Payments can only be removed when the order is in draft mode',
            );
            return $this->redirectToRoute(
                'admin_payment_order_manage',
                ['id' => $paymentRequest->getPaymentOrder()->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        $this->logger->info('Delete payment', [$paymentRequest->getId()]);
        $payee = $paymentRequest->getPayee()->getFullname();
        $this->doctrine->getManager()->remove($paymentRequest);
        $this->doctrine->getManager()->flush();
        $this->addFlash('success', "Payment for $payee successfully deleted");
        if (in_array(
            $request->query->get('redirectRoute'),
            MonthEndController::REDIRECT_ROUTES,
        )) {
            $redirectToRoute = $request->query->get('redirectRoute');
        }
        return $this->redirectToRoute(
            $redirectToRoute ?? 'admin_payment_order_manage',
            ['id' => $paymentRequest->getPaymentOrder()->getId()],
            Response::HTTP_SEE_OTHER,
        );
    }

    #[Route(
        path: '/payment-order/{id}/clear-payments',
        name: 'admin_payment_order_clear_payments',
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function clearPayments(
        Request $request,
        PaymentOrder $paymentOrder,
    ): Response {
        if (PaymentOrder::STATE_DRAFT != $paymentOrder->getStatus()) {
            $this->addFlash(
                'warning',
                'Payments can only be cleared when the order is in draft mode',
            );
            return $this->redirectToRoute(
                'admin_payment_order_manage',
                ['id' => $paymentOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->logger->info('Clear Payments for Order', [$paymentOrder->getId()]);
            $paymentOrder->getPayments()->clear();
            $this->doctrine->getManager()->flush();
            if (in_array(
                $request->query->get('redirectRoute'),
                MonthEndController::REDIRECT_ROUTES,
            )) {
                $redirectToRoute = $request->query->get('redirectRoute');
            }
            return $this->redirectToRoute(
                $redirectToRoute ?? 'admin_payment_order_manage',
                ['id' => $paymentOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        return $this->render('admin/pages/payment_order/clear_payments.html.twig', [
            'paymentOrder' => $paymentOrder,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/payment-order/{id}/run', name: 'admin_payment_order_run')]
    #[IsGranted('ROLE_FINANCIAL_OPS')]
    public function run(Request $request, PaymentOrder $paymentOrder): Response
    {
        $this->logger->info('Run Payment Order', [$paymentOrder->getId()]);
        try {
            $this->paymentOrderService->runOrder($paymentOrder);
            $this->addFlash(
                'success',
                'Payment order successfully run. Order is '
                    . $paymentOrder->getStatus(),
            );
        } catch (\Exception $e) {
            $this->addFlash(
                'error',
                'Unable to run payment order to completion. ' . $e->getMessage(),
            );
            $this->logger->error('Unable to run payment order to completion. ', [$e->getMessage()]);
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
            $redirectToRoute ?? 'admin_payment_order_manage',
            ['id' => $paymentOrder->getId()],
            Response::HTTP_SEE_OTHER,
        );
    }

    #[Route(
        path: '/payment-order/{id}/run-batch',
        name: 'admin_payment_order_run_batch',
    )]
    #[IsGranted('ROLE_FINANCIAL_OPS')]
    public function runBatch(
        Request $request,
        PaymentOrder $paymentOrder,
        MessageBusInterface $bus,
    ): Response {
        $this->logger->info('Run Payment Order in background', [$paymentOrder->getId()]);
        /** @var UserInterface|User $currentUser  */
        $currentUser = $this->getUser();
        $bus->dispatch(new OrderBatchRun(
            orderFqcn: PaymentOrder::class,
            orderId: $paymentOrder->getId(),
            submittedByUserId: $currentUser->getId(),
            autoContinue: true,
        ));
        $this->addFlash(
            'success',
            'Payment order run submitted as a background job. Refresh this page for progress updates. You will be notified on completion.',
        );
        if (in_array(
            $request->query->get('redirectRoute'),
            MonthEndController::REDIRECT_ROUTES,
        )) {
            $redirectToRoute = $request->query->get('redirectRoute');
        }
        return $this->redirectToRoute(
            $redirectToRoute ?? 'admin_payment_order_manage',
            ['id' => $paymentOrder->getId()],
            Response::HTTP_SEE_OTHER,
        );
    }

    #[Route(path: '/payment-request/{id}/pay', name: 'admin_payment_request_pay')]
    #[IsGranted('ROLE_FINANCIAL_OPS')]
    public function pay(Request $request, PaymentRequest $paymentRequest): Response
    {
        $this->logger->info('Paying single request', [$paymentRequest->getId()]);
        try {
            $this->paymentOrderService->runRequest($paymentRequest);
            $this->addFlash('success', 'Payment successfully made');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Unable to make payment. ' . $e->getMessage());
            $this->logger->error('Unable to make payment. ', [$e->getMessage()]);
        }
        $this->doctrine->getManager()->flush();
        if (in_array(
            $request->query->get('redirectRoute'),
            MonthEndController::REDIRECT_ROUTES,
        )) {
            $redirectToRoute = $request->query->get('redirectRoute');
        }
        return $this->redirectToRoute(
            $redirectToRoute ?? 'admin_payment_order_manage',
            ['id' => $paymentRequest->getPaymentOrder()->getId()],
            Response::HTTP_SEE_OTHER,
        );
    }

    #[Route(
        '/payment-request/{id}/search-transfer',
        name: 'admin_payment_request_search_transfer',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_FINANCIAL_OPS')]
    public function searchTransfer(
        Request $request,
        PaymentRequest $paymentRequest,
    ): Response {
        $this->logger->info('Find Mangopay transfer to link to request', [$paymentRequest->getId()]);
        $form = $this
            ->createFormBuilder()
            ->add('transferId', TextType::class, [
                'label' => 'Mangopay transfer ID',
            ])
            ->add('submit', SubmitType::class, ['label' => 'Search for Transfer'])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            return $this->redirectToRoute(
                'admin_payment_request_link',
                [
                    'id' => $paymentRequest->getId(),
                    'transferId' => $form->getData()['transferId'],
                ],
                Response::HTTP_SEE_OTHER,
            );
        }
        if (in_array(
            $request->query->get('redirectRoute'),
            MonthEndController::REDIRECT_ROUTES,
        )) {
            $redirectToRoute = $request->query->get('redirectRoute');
        }
        return $this->render('admin/pages/payment_request/search.html.twig', [
            'paymentRequest' => $paymentRequest,
            'debitWalletId' => $this->paymentOrderService->getDebitWalletIdForOrder(
                $paymentRequest->getPaymentOrder(),
            ),
            'form' => $form->createView(),
            'exitRoute' => $redirectToRoute ?? 'admin_payment_order_manage',
        ]);
    }

    #[Route(
        '/payment-request/{id}/link/{transferId}',
        name: 'admin_payment_request_link',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_FINANCIAL_OPS')]
    public function linkTransfer(
        Request $request,
        PaymentRequest $paymentRequest,
        string $transferId,
    ): Response {
        $this->logger->info('Compare Mangopay transfer to link to request', [
            $paymentRequest->getId(),
            $transferId,
        ]);
        try {
            $transfer = $this->defaultAppCache->get(
                "mangopay_{$transferId}",
                function (ItemInterface $item) use ($transferId): ?\Mangopay\Transfer {
                    $item->tag(['mangopay', 'transfer']);
                    return $this->mangopayWalletService->getTransfer($transferId);
                },
            );
        } catch (\Exception $e) {
            $this->addFlash(
                'warning',
                "Unable to find transfer {$transferId}. " . $e->getMessage(),
            );
            $this->logger->warning(
                "Unable to find transfer {$transferId}.",
                [$e->getMessage()],
            );
            return $this->redirectToRoute(
                'admin_payment_request_search_transfer',
                ['id' => $paymentRequest->getPaymentOrder()->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        $notAlreadyLinked = empty($this->payoutRepository->findBy(
            ['transactionId' => $transferId],
            null,
            1,
        ));
        $isLinkable = $this->paymentOrderService->isTransferLinkable(
            $paymentRequest,
            $transfer,
        );
        if ($isLinkable and $notAlreadyLinked) {
            $form = $this
                ->createFormBuilder()
                ->add('submit', SubmitType::class, [
                    'label' => 'Link Transfer',
                ])
                ->getForm();
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $this->paymentOrderService->runRequest($paymentRequest, $transfer);
                $this->doctrine->getManager()->flush();
                return $this->redirectToRoute(
                    $redirectToRoute ?? 'admin_payment_order_manage',
                    ['id' => $paymentRequest->getPaymentOrder()->getId()],
                    Response::HTTP_SEE_OTHER,
                );
            }
        } else {
            $this->addFlash(
                'warning',
                "Cannot link transfer {$transferId}. Some hard requirements not met.",
            );
        }
        if (in_array(
            $request->query->get('redirectRoute'),
            MonthEndController::REDIRECT_ROUTES,
        )) {
            $redirectToRoute = $request->query->get('redirectRoute');
        }
        return $this->render('admin/pages/payment_request/link.html.twig', [
            'paymentRequest' => $paymentRequest,
            'walletTransfer' => $transfer,
            'isLinkable' => $isLinkable,
            'notAlreadyLinked' => $notAlreadyLinked,
            'debitWalletId' => $this->paymentOrderService->getDebitWalletIdForOrder(
                $paymentRequest->getPaymentOrder(),
            ),
            'form' => $form ?? null,
            'exitRoute' => $redirectToRoute ?? 'admin_payment_order_manage',
        ]);
    }
}
