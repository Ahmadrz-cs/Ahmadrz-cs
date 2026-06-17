<?php

namespace App\Controller\Admin;

use App\Entity\Enum\ExportReportType;
use App\Entity\Enum\TradeStatus;
use App\Entity\ShareTrade;
use App\Entity\ShareTradeStatusLog;
use App\Entity\User;
use App\Form\ShareTradeType;
use App\Form\StatusLogType;
use App\Form\Type\QueryTradeType;
use App\Repository\ShareTradeRepository;
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

#[Route('/share-trades')]
class ShareTradeController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private ShareTradeRepository $shareTradeRepository,
    ) {}

    #[Route('', name: 'admin_share_trade_index', methods: ['GET'])]
    #[IsGranted('ROLE_ANALYST')]
    public function index(
        Request $request,
        ExportService $exportService,
        Exporter $exporter,
    ): Response {
        $form = $this->createForm(QueryTradeType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
            if ($request->query->get('export')) {
                $format = ExportHelper::validateExportFormat($request->query->get(
                    'format',
                    'csv',
                ));
                $fields = $exportService->getOrmFieldNames(ExportReportType::ShareTrades);
                $this->logger->debug('fields', $fields);
                $query = $this->shareTradeRepository->buildQueryWithAssociations(
                    filters: $filters ?? [],
                );

                return $exporter->getResponse(
                    $format,
                    ExportHelper::generateFileName('share_trades_', $format),
                    new DoctrineORMQuerySourceIterator(
                        query: $query,
                        fields: $fields,
                        dateTimeFormat: \DateTimeInterface::ATOM,
                    ),
                );
            }
        }
        // $this->logger->debug('filters', $filters ?? []);

        $results = $this->shareTradeRepository->findByWithAssociations(
            $filters ?? [],
            [
                $filters['orderBy'] ?? 'createdAt' =>
                    $filters['orderDirection'] ?? 'DESC',
            ],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/share_trades/list.html.twig', [
            'objects' => $results,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/create', name: 'admin_share_trade_create', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_OPERATIONS')]
    public function create(Request $request): Response
    {
        $this->logger->debug('Create new share trade');
        $redirectToRoute = 'admin_share_trade_view';
        $shareTrade = new ShareTrade();

        $form = $this->createForm(ShareTradeType::class, $shareTrade);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('status')->getData()) {
                $statusLog = new ShareTradeStatusLog(
                    $shareTrade,
                    $form->get('status')->getData(),
                );
                $shareTrade->addStatusLog($statusLog);
            }
            if ($shareTrade->getTradeValue() < 0) {
                $shareTrade->deriveTradeValue();
            }
            $this->entityManager->persist($shareTrade);
            // $this->entityManager->persist($statusLog);
            $this->entityManager->flush();
            $redirectToId = $shareTrade->getId();
            $this->logger->debug('Successfully created new share trade', ['id' => $shareTrade->getId()]);
            $this->addFlash('success', 'Successfully created new share trade');
            return $this->redirectToRoute($redirectToRoute, ['id' => $redirectToId]);
        }
        return $this->render('admin/pages/share_trades/editor.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_share_trade_view', methods: ['GET'])]
    #[IsGranted('ROLE_ANALYST')]
    public function view(#[MapEntity(id: 'id')] ShareTrade $shareTrade): Response
    {
        return $this->render('admin/pages/share_trades/view.html.twig', [
            'shareTrade' => $shareTrade,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_share_trade_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_OPERATIONS')]
    public function edit(
        Request $request,
        #[MapEntity(id: 'id')]
        ShareTrade $shareTrade,
    ): Response {
        $this->logger->debug('Edit share trade', ['id' => $shareTrade->getId()]);
        $redirectToRoute = 'admin_share_trade_view';
        $redirectToId = $shareTrade->getId();
        $form = $this->createForm(ShareTradeType::class, $shareTrade);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($shareTrade->getTradeValue() < 0) {
                $shareTrade->deriveTradeValue();
            }
            $this->entityManager->flush();

            $this->addFlash('success', 'Successfully updated share trade');
            return $this->redirectToRoute($redirectToRoute, ['id' => $redirectToId]);
        }
        return $this->render('admin/pages/share_trades/editor.html.twig', [
            'form' => $form,
            'shareTrade' => $shareTrade,
        ]);
    }

    #[Route(
        '/{id}/status-logs/create',
        name: 'admin_share_trade_status_log_create',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function createStateLog(
        Request $request,
        #[CurrentUser]
        User $currentUser,
        #[MapEntity(id: 'id')]
        ShareTrade $shareTrade,
    ): Response {
        $this->logger->debug('Create new share trade status log');
        $redirectToRoute = 'admin_share_trade_view';
        $redirectToId = $shareTrade->getId();

        $statusLog = new ShareTradeStatusLog();
        $statusLog->setTransitionedBy($currentUser);
        $form = $this->createForm(StatusLogType::class, $statusLog, [
            'data_class' => ShareTradeStatusLog::class,
            'status_class' => TradeStatus::class,
            'parent_entity_name' => 'share trade',
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $shareTrade->addStatusLog($statusLog);
            $this->entityManager->persist($statusLog);
            $this->entityManager->flush();
            $this->addFlash(
                'success',
                'Successfully created new share trade status log',
            );
            return $this->redirectToRoute($redirectToRoute, ['id' => $redirectToId]);
        }
        return $this->render('admin/pages/status_logs/create.html.twig', [
            'statusLog' => $statusLog,
            'form' => $form,
            'redirectRoute' => $redirectToRoute,
            'redirectToId' => $redirectToId,
            'parentEntityName' => 'Share Trade',
            'parentEntity' => $shareTrade,
        ]);
    }

    #[Route(
        '/status-logs/{id}',
        name: 'admin_share_trade_status_log_edit',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function editStateLog(
        Request $request,
        #[MapEntity(id: 'id')]
        ShareTradeStatusLog $statusLog,
    ): Response {
        $this->logger->debug('Edit new share trade status log');
        $redirectToRoute = 'admin_share_trade_view';
        $shareTrade = $statusLog->getShareTrade();
        $redirectToId = $shareTrade->getId();

        $form = $this->createForm(StatusLogType::class, $statusLog, [
            'data_class' => ShareTradeStatusLog::class,
            'status_class' => TradeStatus::class,
            'parent_entity_name' => 'share trade',
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Successfully updated share trade status log');
            return $this->redirectToRoute($redirectToRoute, ['id' => $redirectToId]);
        }
        return $this->render('admin/pages/status_logs/edit.html.twig', [
            'statusLog' => $statusLog,
            'form' => $form,
            'redirectRoute' => $redirectToRoute,
            'redirectToId' => $redirectToId,
            'parentEntityName' => 'Share Trade',
            'parentEntity' => $shareTrade,
        ]);
    }
}
