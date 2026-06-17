<?php

namespace App\Controller\Admin;

use App\Entity\Asset;
use App\Entity\Enum\OrderRequestStatus;
use App\Entity\Enum\OrderStatus;
use App\Entity\Enum\ShareTradeType;
use App\Entity\ShareTransferOrder;
use App\Entity\ShareTransferRequest;
use App\Form\QueryShareTransferOrderForm;
use App\Form\ShareTransferOrderForm;
use App\Repository\AssetRepository;
use App\Repository\ShareTradeRepository;
use App\Repository\ShareTransferOrderRepository;
use App\Service\MonthEndService;
use App\Service\ShareTransferService;
use App\Service\Util\ExportHelper;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sonata\Exporter\Exporter;
use Sonata\Exporter\Source\IteratorCallbackSourceIterator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/monthend')]
#[IsGranted('ROLE_ANALYST')]
class ShareTransferController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private MonthEndService $monthEndService,
        private ShareTransferService $shareTransferService,
        private Exporter $exporter,
        private ShareTradeRepository $shareTradeRepository,
        private ShareTransferOrderRepository $shareTransferOrderRepository,
    ) {}

    #[Route(
        '/share-transfers/list',
        name: 'admin_monthend_share_transfers_list',
        methods: ['GET', 'POST'],
    )]
    public function index(Request $request): Response
    {
        $this->logger->info('List share transfer orders');
        $form = $this->createForm(QueryShareTransferOrderForm::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->shareTransferOrderRepository->findByWithAssociations(
            $filters ?? [],
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/monthend/share_transfers/index.html.twig', [
            'objects' => $results,
            // 'form' => $form,
        ]);
    }

    #[Route(
        '/share-transfers/create',
        name: 'admin_monthend_share_transfers_create',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function create(Request $request, AssetRepository $assetRepository): Response
    {
        $assetIds = array_map(function ($x) {
            return $x['assetid'];
        }, $this->shareTradeRepository->aggregateSharesInCirculation());
        $shareTransferOrder = new ShareTransferOrder();
        if ((int) $request->query->get('assetId')) {
            $asset = $assetRepository->find($request->query->get('assetId'));
            if (!is_null($asset)) {
                $shareTransferOrder->setAsset($asset);
            }
        }
        $shareTransferOrder->setScheduledFor(new \DateTime('first day of this month'));
        $monthendDateRange = $this->monthEndService->getMonthEndDateRangeFromDateTime(
            $shareTransferOrder->getScheduledFor(),
            -1,
        );
        $repaymentDateRange = $this->monthEndService->getMonthEndDateRangeFromDateTime(
            $shareTransferOrder->getScheduledFor(),
        );
        $shareTransferOrder->setPeriodStart($monthendDateRange['start']);
        $shareTransferOrder->setPeriodEnd($monthendDateRange['end']);
        $shareTransferOrder->setRepaymentStart($repaymentDateRange['start']);
        $shareTransferOrder->setRepaymentEnd($repaymentDateRange['end']);
        $form = $this->createForm(ShareTransferOrderForm::class, $shareTransferOrder, [
            'assetIds' => $assetIds,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($shareTransferOrder);
            $this->entityManager->flush();
            $this->logger->debug(
                "Created new share transfer order #{$shareTransferOrder->getId()}",
            );
            $this->addFlash('success', 'Share transfer order successfully created');
            return $this->redirectToRoute('admin_monthend_share_transfers_manage', [
                'id' => $shareTransferOrder->getId(),
            ]);
        }
        return $this->render('admin/pages/monthend/share_transfers/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(
        '/share-transfers/create/{id}',
        name: 'admin_monthend_share_transfers_create_monthend',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function createMonthend(Asset $asset): Response
    {
        $shareTransferOrder = new ShareTransferOrder();
        $shareTransferOrder->setAsset($asset);
        $shareTransferOrder->setScheduledFor(new \DateTime('first day of this month'));
        $monthendDateRange = $this->monthEndService->getMonthEndDateRangeFromDateTime(
            $shareTransferOrder->getScheduledFor(),
            -1,
        );
        $repaymentDateRange = $this->monthEndService->getMonthEndDateRangeFromDateTime(
            $shareTransferOrder->getScheduledFor(),
        );
        $shareTransferOrder->setPeriodStart($monthendDateRange['start']);
        $shareTransferOrder->setPeriodEnd($monthendDateRange['end']);
        $shareTransferOrder->setRepaymentStart($repaymentDateRange['start']);
        $shareTransferOrder->setRepaymentEnd($repaymentDateRange['end']);
        $this->entityManager->persist($shareTransferOrder);
        $this->entityManager->flush();
        $this->logger->debug(
            "Created new share transfer order #{$shareTransferOrder->getId()}",
        );
        $this->addFlash('success', 'Share transfer order successfully created');
        return $this->redirectToRoute('admin_monthend_share_transfers_edit', [
            'id' => $shareTransferOrder->getId(),
        ]);
    }

    #[Route(
        '/share-transfers/{id}',
        name: 'admin_monthend_share_transfers_manage',
        methods: ['GET'],
    )]
    public function manage(
        Request $request,
        ShareTransferOrder $shareTransferOrder,
    ): Response {
        $relistings = $this->shareTradeRepository
            ->buildQueryWithAssociations($this->shareTransferService->getShareTradeQueryFilter(
                $shareTransferOrder,
                ShareTradeType::SecondaryMarket,
            ))
            ->getResult();
        $firstParty = $this->shareTradeRepository
            ->buildQueryWithAssociations($this->shareTransferService->getShareTradeQueryFilter(
                $shareTransferOrder,
                ShareTradeType::FirstParty,
            ))
            ->getResult();
        $proxyBuyBacks = $this->shareTradeRepository
            ->buildQueryWithAssociations($this->shareTransferService->getShareTradeQueryFilter(
                $shareTransferOrder,
                ShareTradeType::Repayment,
            ))
            ->getResult();
        if (!empty($proxyBuyBacks)) {
            $pooledInvestments =
                $this->shareTransferService->poolShareTrades($firstParty);
            $pooledBuyBacks = $this->shareTransferService->poolShareTrades(
                $proxyBuyBacks,
                ShareTradeType::Repayment,
            );
        }
        $this->logger->debug('Share transfer order sources', [
            'relisting' => count($relistings),
            'firstParty' => count($firstParty),
            'proxyBuyBacks' => count($proxyBuyBacks),
        ]);
        $format = ExportHelper::validateExportFormat($request->query->get(
            'format',
            'csv',
        ));
        if ($request->query->get('export')) {
            return $this->exporter->getResponse(
                $format,
                ExportHelper::generateFileName(
                    $shareTransferOrder->getAsset()->getCompanyNumber()
                        . '_shareAllocations',
                    $format,
                ),
                new IteratorCallbackSourceIterator(
                    new \ArrayIterator(
                        $shareTransferOrder->getShareTransfers()->toArray(),
                    ),
                    $this->shareTransferService->formatShareTransferCallable(),
                ),
            );
        }
        return $this->render('admin/pages/monthend/share_transfers/manage.html.twig', [
            'shareTransferOrder' => $shareTransferOrder,
            'relistings' => $relistings,
            'firstParty' => $firstParty,
            'proxyBuyBacks' => $proxyBuyBacks,
            'pooledInvestments' => $pooledInvestments ?? [],
            'pooledBuyBacks' => $pooledBuyBacks ?? [],
        ]);
    }

    #[Route(
        '/share-transfers/{id}/edit',
        name: 'admin_monthend_share_transfers_edit',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function editOrder(
        Request $request,
        ShareTransferOrder $shareTransferOrder,
    ): Response {
        $assetIds = array_map(function ($x) {
            return $x['assetid'];
        }, $this->shareTradeRepository->aggregateSharesInCirculation());
        $form = $this->createForm(ShareTransferOrderForm::class, $shareTransferOrder, [
            'assetIds' => $assetIds,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($shareTransferOrder);
            $this->entityManager->flush();

            $this->addFlash('success', 'Share transfer order successfully updated');
            return $this->redirectToRoute('admin_monthend_share_transfers_manage', [
                'id' => $shareTransferOrder->getId(),
            ]);
        }
        return $this->render('admin/pages/monthend/share_transfers/edit.html.twig', [
            'form' => $form,
            'shareTransferOrder' => $shareTransferOrder,
        ]);
    }

    #[Route(
        '/share-transfers/{id}/toggle-state',
        name: 'admin_monthend_share_transfers_toggle_state',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function toggleState(ShareTransferOrder $shareTransferOrder): Response
    {
        if (OrderStatus::Completed == $shareTransferOrder->getStatus()) {
            $shareTransferOrder->setStatus(OrderStatus::Draft);
            $shareTransferOrder->setApprovedBy(null);
            $transfersToStatus = OrderRequestStatus::Pending;
        } else {
            $shareTransferOrder->setStatus(OrderStatus::Completed);
            $shareTransferOrder->setApprovedBy($this->getUser());
            $transfersToStatus = OrderRequestStatus::Completed;
        }
        foreach ($shareTransferOrder->getShareTransfers() as $shareTransfer) {
            $shareTransfer->setStatus($transfersToStatus);
        }
        $this->entityManager->flush();
        return $this->redirectToRoute('admin_monthend_share_transfers_manage', [
            'id' => $shareTransferOrder->getId(),
        ]);
    }

    #[Route(
        '/share-transfers/{id}/clear-transfers',
        name: 'admin_monthend_share_transfers_clear',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function clearTransfers(ShareTransferOrder $shareTransferOrder): Response
    {
        $shareTransferOrder->getShareTransfers()->clear();
        $this->entityManager->flush();
        return $this->redirectToRoute('admin_monthend_share_transfers_manage', [
            'id' => $shareTransferOrder->getId(),
        ]);
    }

    #[Route(
        '/share-transfers/{id}/toggle-state-single',
        name: 'admin_monthend_share_transfers_toggle_state_single',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function toggleSingleState(ShareTransferRequest $shareTransferRequest): Response
    {
        if (OrderRequestStatus::Completed == $shareTransferRequest->getStatus()) {
            $shareTransferRequest->setStatus(OrderRequestStatus::Pending);
        } else {
            $shareTransferRequest->setStatus(OrderRequestStatus::Completed);
        }
        $this->entityManager->flush();
        return $this->redirectToRoute('admin_monthend_share_transfers_manage', [
            'id' => $shareTransferRequest->getShareTransferOrder()->getId(),
        ]);
    }

    #[Route(
        '/share-transfers/{id}/generate/auto',
        name: 'admin_monthend_share_transfers_generate_auto',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function generateAuto(ShareTransferOrder $shareTransferOrder): Response
    {
        $relistings = $this->shareTradeRepository
            ->buildQueryWithAssociations($this->shareTransferService->getShareTradeQueryFilter(
                $shareTransferOrder,
                ShareTradeType::SecondaryMarket,
            ))
            ->getResult();
        $firstParty = $this->shareTradeRepository
            ->buildQueryWithAssociations($this->shareTransferService->getShareTradeQueryFilter(
                $shareTransferOrder,
                ShareTradeType::FirstParty,
            ))
            ->getResult();
        $proxyBuyBacks = $this->shareTradeRepository
            ->buildQueryWithAssociations($this->shareTransferService->getShareTradeQueryFilter(
                $shareTransferOrder,
                ShareTradeType::Repayment,
            ))
            ->getResult();
        try {
            $shareTransferOrder->getShareTransfers()->clear();
            if (empty($proxyBuyBacks)) {
                $this->logger->debug('Direct share transfers');
                $this->shareTransferService->generateDirectShareTransfers($shareTransferOrder, [
                    ...$relistings,
                    ...$firstParty,
                ]);
            } else {
                $this->logger->debug('Pooled share transfers');
                $this->shareTransferService->generatePooledShareTransfers(
                    $shareTransferOrder,
                    $firstParty,
                    $proxyBuyBacks,
                );
            }
            $this->entityManager->flush();
        } catch (\Throwable $th) {
            $this->addFlash('error', $th->getMessage());
        }

        return $this->redirectToRoute('admin_monthend_share_transfers_manage', [
            'id' => $shareTransferOrder->getId(),
        ]);
    }
}
