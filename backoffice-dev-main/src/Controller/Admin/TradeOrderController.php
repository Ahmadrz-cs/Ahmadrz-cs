<?php

namespace App\Controller\Admin;

use App\Entity\Enum\ExportReportType;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\TradeOrder;
use App\Entity\TradeOrderStatusLog;
use App\Entity\User;
use App\Form\StatusLogType;
use App\Form\TradeOrderType;
use App\Form\Type\QueryTradeOrderType;
use App\Repository\TradeOrderRepository;
use App\Service\ExportService;
use App\Service\Util\ExportHelper;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sonata\Exporter\Exporter;
use Sonata\Exporter\Source\DoctrineORMQuerySourceIterator;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/trade-orders')]
class TradeOrderController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private TradeOrderRepository $tradeOrderRepository,
    ) {}

    #[Route('', name: 'admin_trade_order_index', methods: ['GET'])]
    #[IsGranted('ROLE_ANALYST')]
    public function index(
        Request $request,
        ExportService $exportService,
        Exporter $exporter,
    ): Response {
        $form = $this->createForm(QueryTradeOrderType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
            if ($request->query->get('export')) {
                $format = ExportHelper::validateExportFormat($request->query->get(
                    'format',
                    'csv',
                ));
                $fields = $exportService->getOrmFieldNames(ExportReportType::TradeOrders);
                $this->logger->debug('fields', $fields);
                $query = $this->tradeOrderRepository->buildQueryWithAssociations(
                    filters: $filters ?? [],
                );

                return $exporter->getResponse(
                    $format,
                    ExportHelper::generateFileName('trade_orders_', $format),
                    new DoctrineORMQuerySourceIterator(
                        query: $query,
                        fields: $fields,
                        dateTimeFormat: \DateTimeInterface::ATOM,
                    ),
                );
            }
        }
        $results = $this->tradeOrderRepository->findByWithAssociations(
            $filters ?? [],
            [
                $filters['orderBy'] ?? 'createdAt' =>
                    $filters['orderDirection'] ?? 'DESC',
            ],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/trade_orders/list.html.twig', [
            'objects' => $results,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/create', name: 'admin_trade_order_create', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_OPERATIONS')]
    public function create(Request $request): Response
    {
        $this->logger->debug('Create new trade order');
        $redirectToRoute = 'admin_trade_order_view';
        $tradeOrder = new TradeOrder();

        $form = $this->createForm(TradeOrderType::class, $tradeOrder);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('status')->getData()) {
                $statusLog = new TradeOrderStatusLog(
                    $tradeOrder,
                    $form->get('status')->getData(),
                );
                $tradeOrder->addStatusLog($statusLog);
            }
            $this->entityManager->persist($tradeOrder);
            $this->entityManager->flush();
            $redirectToId = $tradeOrder->getId();
            $this->logger->debug('Successfully created new trade order', ['id' => $tradeOrder->getId()]);
            $this->addFlash('success', 'Successfully created new trade order');
            return $this->redirectToRoute($redirectToRoute, ['id' => $redirectToId]);
        }
        return $this->render('admin/pages/trade_orders/editor.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_trade_order_view', methods: ['GET'])]
    #[IsGranted('ROLE_ANALYST')]
    public function view(#[MapEntity(id: 'id')] TradeOrder $tradeOrder): Response
    {
        return $this->render('admin/pages/trade_orders/view.html.twig', [
            'tradeOrder' => $tradeOrder,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_trade_order_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_OPERATIONS')]
    public function edit(
        Request $request,
        #[MapEntity(id: 'id')]
        TradeOrder $tradeOrder,
    ): Response {
        $this->logger->debug('Edit trade order', ['id' => $tradeOrder->getId()]);
        $redirectToRoute = 'admin_trade_order_view';
        $redirectToId = $tradeOrder->getId();
        $form = $this->createForm(TradeOrderType::class, $tradeOrder);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Successfully updated trade order');
            return $this->redirectToRoute($redirectToRoute, ['id' => $redirectToId]);
        }
        return $this->render('admin/pages/trade_orders/editor.html.twig', [
            'form' => $form,
            'tradeOrder' => $tradeOrder,
        ]);
    }

    #[Route(
        '/{id}/status-logs/create',
        name: 'admin_trade_order_status_log_create',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function createStatusLog(
        Request $request,
        #[CurrentUser]
        User $currentUser,
        #[MapEntity(id: 'id')]
        TradeOrder $tradeOrder,
    ): Response {
        $this->logger->debug('Create new trade order status log');
        $redirectToRoute = 'admin_trade_order_view';
        $redirectToId = $tradeOrder->getId();

        $statusLog = new TradeOrderStatusLog();
        $statusLog->setTransitionedBy($currentUser);
        $form = $this->createForm(StatusLogType::class, $statusLog, [
            'data_class' => TradeOrderStatusLog::class,
            'status_class' => TradeOrderStatus::class,
            'parent_entity_name' => 'trade order',
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $tradeOrder->addStatusLog($statusLog);
            $this->entityManager->persist($statusLog);
            $this->entityManager->flush();
            $this->addFlash(
                'success',
                'Successfully created new trade order status log',
            );
            return $this->redirectToRoute($redirectToRoute, ['id' => $redirectToId]);
        }
        return $this->render('admin/pages/status_logs/create.html.twig', [
            'statusLog' => $statusLog,
            'form' => $form,
            'redirectRoute' => $redirectToRoute,
            'redirectToId' => $redirectToId,
            'parentEntityName' => 'Trade Order',
            'parentEntity' => $tradeOrder,
        ]);
    }

    #[Route(
        '/status-logs/{id}',
        name: 'admin_trade_order_status_log_edit',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function editStatusLog(
        Request $request,
        #[MapEntity(id: 'id')]
        TradeOrderStatusLog $statusLog,
    ): Response {
        $this->logger->debug('Edit trade order status log');
        $redirectToRoute = 'admin_trade_order_view';
        $tradeOrder = $statusLog->getTradeOrder();
        $redirectToId = $tradeOrder->getId();

        $form = $this->createForm(StatusLogType::class, $statusLog, [
            'data_class' => TradeOrderStatusLog::class,
            'status_class' => TradeOrderStatus::class,
            'parent_entity_name' => 'trade order',
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Successfully updated trade order status log');
            return $this->redirectToRoute($redirectToRoute, ['id' => $redirectToId]);
        }
        return $this->render('admin/pages/status_logs/edit.html.twig', [
            'statusLog' => $statusLog,
            'form' => $form,
            'redirectRoute' => $redirectToRoute,
            'redirectToId' => $redirectToId,
            'parentEntityName' => 'Trade Order',
            'parentEntity' => $tradeOrder,
        ]);
    }

    #[Route(
        '/{id}/status-logs/create/{status}',
        name: 'admin_trade_order_status_log_create_quick',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function quickStatusLog(
        Request $request,
        TradeOrderStatus $status,
        #[CurrentUser]
        User $currentUser,
        #[MapEntity(id: 'id')]
        TradeOrder $tradeOrder,
    ): Response {
        $this->logger->debug('Quick create new trade order status log');

        $redirectToRoute = 'admin_trade_order_view';
        $redirectToId = $tradeOrder->getId();
        if (in_array(
            $request->query->get('redirectRoute'),
            [
                'admin_trading_hub_sell_orders_pending',
            ],
        )) {
            $redirectToRoute = $request->query->get('redirectRoute');
            $redirectToId = $request->query->get('redirectId', null);
        }

        $suitableTargetStatus = match ($tradeOrder->getStatus()) {
            TradeOrderStatus::Draft, TradeOrderStatus::Submitted => [
                TradeOrderStatus::Active,
                TradeOrderStatus::Cancelled,
            ],
            default => [],
        };
        if (!in_array($status, $suitableTargetStatus)) {
            $this->addFlash(
                'error',
                "Quick transitioning from {$tradeOrder->getStatus()->value} to {$status->value} is not supported.",
            );
            return $this->redirectToRoute($redirectToRoute, ['id' => $redirectToId]);
        }

        $statusLog = new TradeOrderStatusLog($tradeOrder, $status);
        $statusLog->setTransitionedBy($currentUser);

        $tradeOrder->addStatusLog($statusLog);
        $this->entityManager->persist($statusLog);
        $this->entityManager->flush();
        $this->addFlash(
            'success',
            "Successfully created new trade order status log. Status is now {$status->value}",
        );
        return $this->redirectToRoute(
            $redirectToRoute,
            $redirectToId ? ['id' => $redirectToId] : [],
        );
    }
}
