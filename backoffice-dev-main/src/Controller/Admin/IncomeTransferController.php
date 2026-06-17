<?php

namespace App\Controller\Admin;

use App\Entity\Asset;
use App\Entity\Enum\TransferOrderPreset;
use App\Entity\Enum\TransferType;
use App\Entity\TransferOrder;
use App\Entity\TransferRequest;
use App\Form\Type\AssetRelationType;
use App\Form\Type\AssetTransferRequestType;
use App\Form\Type\MonthendOrderEditType;
use App\Repository\AssetRepository;
use App\Repository\OfferingRepository;
use App\Repository\TransferOrderRepository;
use App\Service\Manager\AssetManagerV2;
use App\Service\MonthEndService;
use App\Service\TransferOrderService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/monthend/income-transfers')]
class IncomeTransferController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private MonthEndService $monthEndService,
        private TransferOrderService $transferOrderService,
        private AssetRepository $assetRepository,
        private OfferingRepository $offeringRepository,
        private TransferOrderRepository $transferOrderRepository,
        private AssetManagerV2 $assetManager,
    ) {}

    #[Route(
        '/create',
        name: 'admin_monthend_income_transfer_create',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function create(Request $request): Response
    {
        $transferOrder = $this->monthEndService->createTransferOrderByPreset(TransferOrderPreset::IncomeTransfer);
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
                "Created new asset income transfer #{$transferOrder->getId()}",
            );
            $this->addFlash('success', 'Transfer order successfully created');
            return $this->redirectToRoute('admin_monthend_income_transfer_template', [
                'id' => $transferOrder->getId(),
            ]);
        }
        return $this->render('admin/pages/monthend/income_transfers/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        '/create/{id}',
        name: 'admin_monthend_income_transfer_create_monthend',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function createMonthend(Asset $asset): Response
    {
        $transferOrder = $this->monthEndService->createTransferOrderByPreset(TransferOrderPreset::IncomeTransfer);
        $transferOrder->setAsset($asset);
        $this->doctrine->getManager()->persist($transferOrder);
        $this->doctrine->getManager()->flush();
        $this->logger->debug(
            "Created new asset income transfer #{$transferOrder->getId()}",
        );
        $this->addFlash('success', 'Transfer order successfully created');
        return $this->redirectToRoute('admin_monthend_income_transfer_template', [
            'id' => $transferOrder->getId(),
        ]);
    }

    #[Route('/{id}', name: 'admin_monthend_income_transfer_manage', methods: ['GET'])]
    #[IsGranted('ROLE_ANALYST')]
    public function manage(TransferOrder $transferOrder): Response
    {
        if (!$this->monthEndService->isTransferOrderValid(
            $transferOrder,
            TransferType::AssetIncomeProcessing,
        )) {
            $this->addFlash(
                'warning',
                "Transfer order #{$transferOrder->getId()} is not an income transfer order",
            );
            return $this->redirectToRoute('admin_transfer_order_manage', ['id' => $transferOrder->getId()]);
        }
        try {
            $depositBalance = $this->assetManager->getAssetWalletByType(
                $transferOrder->getAsset(),
                'deposit',
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
        }
        $walletChoices = $this->monthEndService->getAssetWalletChoices($transferOrder->getAsset());
        $walletIdMap = array_flip($walletChoices);
        $groupedTransfersTotal = $this->monthEndService->groupIncomeTransfers(
            $transferOrder,
            $walletIdMap,
            true,
        );
        return $this->render('admin/pages/monthend/income_transfers/manage.html.twig', [
            'transferOrder' => $transferOrder,
            'depositWalletBalance' => $depositBalance ?? 0,
            'groupedTransfersTotal' => $groupedTransfersTotal,
            'walletIdMap' => $walletIdMap,
            'issues' => $this->monthEndService->validateTransferOrder($transferOrder),
        ]);
    }

    #[Route(
        '/{id}/edit',
        name: 'admin_monthend_income_transfer_edit',
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
            return $this->redirectToRoute('admin_monthend_income_transfer_manage', [
                'id' => $transferOrder->getId(),
            ]);
        }
        return $this->render('admin/pages/monthend/order_edit.html.twig', [
            'form' => $form->createView(),
            'order' => $transferOrder,
            'exitRoute' => 'admin_monthend_income_transfer_manage',
        ]);
    }

    #[Route(
        '/{id}/template',
        name: 'admin_monthend_income_transfer_template',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function template(TransferOrder $transferOrder): Response
    {
        if (!$this->monthEndService->isTransferOrderValid(
            $transferOrder,
            TransferType::AssetIncomeProcessing,
        )) {
            $this->addFlash(
                'warning',
                "Transfer order #{$transferOrder->getId()} is not an income transfer order",
            );
            return $this->redirectToRoute('admin_transfer_order_manage', ['id' => $transferOrder->getId()]);
        }
        $walletChoices = $this->monthEndService->getAssetWalletChoices($transferOrder->getAsset());
        $defaultTransferOrder = $this->monthEndService->createTransferOrderByPreset(TransferOrderPreset::IncomeTransfer);
        $defaultTransferOrder->setAsset($transferOrder->getAsset());
        $this->monthEndService->applyTemplateToTransferOrder(
            $defaultTransferOrder,
            $this->monthEndService->createDefaultIncomeTransferPlan($walletChoices),
        );
        $previousIncomeTransfer = $this->transferOrderRepository
            ->buildQueryWithAssociations([
                'assetId' => $transferOrder->getAsset()->getId(),
                'description' => TransferOrderPreset::IncomeTransfer,
                'scheduledFor_gte' => new \DateTime('first day of last month')->setTime(
                    0,
                    0,
                ),
                'scheduledFor_lt' => new \DateTime('first day of this month')->setTime(
                    0,
                    0,
                ),
            ], ['id' => 'DESC'])
            ->setMaxResults(1)
            ->getOneOrNullResult();
        // Prevent the same order from being offered as a template
        if (
            !is_null($previousIncomeTransfer)
            && $transferOrder->getId() === $previousIncomeTransfer->getId()
        ) {
            $previousIncomeTransfer = null;
        }
        return $this->render('admin/pages/monthend/income_transfers/template.html.twig', [
            'transferOrder' => $transferOrder,
            'defaultTemplate' => $defaultTransferOrder,
            'previousIncomeTransfer' => $previousIncomeTransfer,
            'walletIdMap' => array_flip($walletChoices),
        ]);
    }

    #[Route(
        '/{id}/template-default',
        name: 'admin_monthend_income_transfer_default',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function templateDefault(TransferOrder $transferOrder): Response
    {
        if (!$this->monthEndService->isTransferOrderValid(
            $transferOrder,
            TransferType::AssetIncomeProcessing,
        )) {
            $this->addFlash(
                'warning',
                "Transfer order #{$transferOrder->getId()} is not an income transfer order",
            );
            return $this->redirectToRoute('admin_transfer_order_manage', ['id' => $transferOrder->getId()]);
        }
        $walletChoices = $this->monthEndService->getAssetWalletChoices($transferOrder->getAsset());
        $defaultTemplatePlan =
            $this->monthEndService->createDefaultIncomeTransferPlan($walletChoices);
        $this->monthEndService->applyTemplateToTransferOrder(
            $transferOrder,
            $defaultTemplatePlan,
        );
        $this->doctrine->getManager()->flush();
        $this->logger->debug(
            "Applied default template to asset income transfer #{$transferOrder->getId()}",
        );
        $this->addFlash('success', 'Template successfully applied to transfer order');
        return $this->redirectToRoute('admin_monthend_income_transfer_builder_start', [
            'id' => $transferOrder->getId(),
        ]);
    }

    #[Route(
        '/{id}/template-existing/{existing}',
        name: 'admin_monthend_income_transfer_template_existing',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function templateExisting(
        TransferOrder $transferOrder,
        TransferOrder $existing,
    ): Response {
        if (
            !$this->monthEndService->isTransferOrderValid(
                $transferOrder,
                TransferType::AssetIncomeProcessing,
            )
            || !$this->monthEndService->isTransferOrderValid($existing)
        ) {
            $this->addFlash(
                'warning',
                'Transfer order is not an income transfer order',
            );
            return $this->redirectToRoute('admin_transfer_order_manage', [
                'id' => $transferOrder->getId(),
            ]);
        }
        $transferOrder->getTransfers()->clear();
        $transferOrder = $this->transferOrderService->copyRequestsFromExisting(
            $transferOrder,
            $existing,
        );
        $this->doctrine->getManager()->flush();
        $this->logger->debug(
            "Applied default template to asset income transfer #{$transferOrder->getId()}",
        );
        $this->addFlash('success', 'Template successfully applied to transfer order');
        return $this->redirectToRoute('admin_monthend_income_transfer_manage', [
            'id' => $transferOrder->getId(),
        ]);
    }

    #[Route(
        '/{id}/add-transfer',
        name: 'admin_monthend_add_income_transfer',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function addTransfer(
        Request $request,
        TransferOrder $transferOrder,
    ): Response {
        if (!$this->monthEndService->isTransferOrderValid(
            $transferOrder,
            TransferType::AssetIncomeProcessing,
        )) {
            $this->addFlash(
                'warning',
                "Transfer order #{$transferOrder->getId()} is not an income transfer order",
            );
            return $this->redirectToRoute('admin_transfer_order_manage', ['id' => $transferOrder->getId()]);
        }
        $walletChoices = $this->monthEndService->getAssetWalletChoices($transferOrder->getAsset());
        $transferRequest = new TransferRequest();
        $transferOrder->addTransfer($transferRequest);
        $transferRequest->setDebitWalletId($walletChoices['deposit']);
        $form = $this->createForm(AssetTransferRequestType::class, $transferRequest, [
            'debitWalletChoices' => ['deposit' => $walletChoices['deposit']],
            'creditWalletChoices' => array_filter(
                $walletChoices,
                fn($k) => in_array($k, ['expenses', 'tax', 'treasury', 'distribution']),
                ARRAY_FILTER_USE_KEY,
            ),
            'lockDebitWallet' => true,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();
            $this->addFlash('success', 'Transfer request successfully created');
            $this->logger->debug(
                "Add transfer to asset income transfer #{$transferOrder->getId()}",
            );
            return $this->redirectToRoute('admin_monthend_income_transfer_manage', [
                'id' => $transferOrder->getId(),
            ]);
        }
        return $this->render('admin/pages/monthend/income_transfers/add_transfer.html.twig', [
            'form' => $form->createView(),
            'offering' => $this->offeringRepository->findFirstPartyByAssetId(
                $transferOrder->getAsset()->getId(),
            ),
            'transferOrder' => $transferOrder,
        ]);
    }

    #[Route(
        '/edit-transfer/{id}',
        name: 'admin_monthend_edit_income_transfer',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function editTransfer(
        Request $request,
        TransferRequest $transferRequest,
    ): Response {
        $transferOrder = $transferRequest->getTransferOrder();
        if (!$this->monthEndService->isTransferOrderValid(
            $transferOrder,
            TransferType::AssetIncomeProcessing,
        )) {
            $this->addFlash(
                'warning',
                "Transfer order #{$transferOrder->getId()} is not an income transfer order",
            );
            return $this->redirectToRoute('admin_transfer_order_manage', ['id' => $transferOrder->getId()]);
        }
        $walletChoices = $this->monthEndService->getAssetWalletChoices($transferOrder->getAsset());
        $form = $this->createForm(AssetTransferRequestType::class, $transferRequest, [
            'debitWalletChoices' => ['deposit' => $walletChoices['deposit']],
            'creditWalletChoices' => array_diff($walletChoices, [
                'deposit' => $walletChoices['deposit'],
            ]),
            'lockDebitWallet' => true,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();
            $this->logger->debug(
                "Saved changes to transfer request #{$transferRequest->getId()}",
            );
            $this->addFlash('success', 'Transfer request successfully updated');
            return $this->redirectToRoute('admin_monthend_income_transfer_manage', [
                'id' => $transferOrder->getId(),
            ]);
        }
        return $this->render('admin/pages/monthend/income_transfers/edit_transfer.html.twig', [
            'form' => $form->createView(),
            'offering' => $this->offeringRepository->findFirstPartyByAssetId(
                $transferOrder->getAsset()->getId(),
            ),
            'transferOrder' => $transferOrder,
        ]);
    }
}
