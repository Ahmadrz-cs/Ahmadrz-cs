<?php

namespace App\Controller\Admin;

use App\Entity\TransferOrder;
use App\Entity\TransferRequest;
use App\Form\Type\IncomeTransferType;
use App\Repository\OfferingRepository;
use App\Service\Manager\AssetManagerV2;
use App\Service\MonthEndService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/monthend/income-transfers/{id}/builder')]
#[IsGranted('ROLE_OPERATIONS')]
class IncomeTransferBuilderController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private OfferingRepository $offeringRepository,
        private MonthEndService $monthEndService,
        private AssetManagerV2 $assetManager,
    ) {}

    #[Route(
        '/starting-balance',
        name: 'admin_monthend_income_transfer_builder_start',
        methods: ['GET', 'POST'],
    )]
    public function startingBalance(
        Request $request,
        TransferOrder $transferOrder,
    ): Response {
        if (!$this->monthEndService->isTransferOrderValid($transferOrder)) {
            $this->addFlash(
                'warning',
                "Transfer order #{$transferOrder->getId()} is not an income transfer order",
            );
            return $this->redirectToRoute('admin_monthend_income_transfer_manage', ['id' => $transferOrder->getId()]);
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
        $form = $this->createFormBuilder([
            'amountToProcess' =>
                $transferOrder->getTargetTotal() ?? $depositBalance ?? null,
        ])->add('amountToProcess', MoneyType::class, [
            'attr' => ['placeholder' => 'e.g. 1.23'],
            'currency' => 'GBP',
            'help' => 'The gross income that you want to process. Defaults to the full balance of the deposit wallet.',
            'label' => 'Amount to Process (£)',
            'required' => true,
            'scale' => 2,
        ])->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $transferOrder->setTargetTotal($form->getData()['amountToProcess']);
            $this->doctrine->getManager()->flush();
            $this->addFlash('success', 'Transfer order successfully updated');
            return $this->redirectToRoute('admin_monthend_income_transfer_builder_expenses', [
                'id' => $transferOrder->getId(),
            ]);
        }
        return $this->render('admin/pages/monthend/income_transfers/builder/starting_balance.html.twig', [
            'form' => $form->createView(),
            'transferOrder' => $transferOrder,
            'depositWalletBalance' => $depositBalance ?? 0,
            'walletIds' => $walletChoices,
            'walletIdMap' => array_flip($walletChoices),
        ]);
    }

    #[Route(
        '/expenses',
        name: 'admin_monthend_income_transfer_builder_expenses',
        methods: ['GET'],
    )]
    public function expenses(Request $request, TransferOrder $transferOrder): Response
    {
        if (!$this->monthEndService->isTransferOrderValid($transferOrder)) {
            $this->addFlash(
                'warning',
                "Transfer order #{$transferOrder->getId()} is not an income transfer order",
            );
            return $this->redirectToRoute('admin_monthend_income_transfer_manage', ['id' => $transferOrder->getId()]);
        }
        $walletChoices = $this->monthEndService->getAssetWalletChoices($transferOrder->getAsset());
        $walletIdMap = array_flip($walletChoices);
        $groupedTransfers = $this->monthEndService->groupIncomeTransfers(
            $transferOrder,
            $walletIdMap,
        );
        return $this->render('admin/pages/monthend/income_transfers/builder/expenses.html.twig', [
            'transferOrder' => $transferOrder,
            'groupedTransfers' => $groupedTransfers,
            'walletIds' => $walletChoices,
            'walletIdMap' => $walletIdMap,
        ]);
    }

    #[Route(
        '/tax',
        name: 'admin_monthend_income_transfer_builder_tax',
        methods: ['GET'],
    )]
    public function tax(Request $request, TransferOrder $transferOrder): Response
    {
        if (!$this->monthEndService->isTransferOrderValid($transferOrder)) {
            $this->addFlash(
                'warning',
                "Transfer order #{$transferOrder->getId()} is not an income transfer order",
            );
            return $this->redirectToRoute('admin_monthend_income_transfer_manage', ['id' => $transferOrder->getId()]);
        }
        $walletChoices = $this->monthEndService->getAssetWalletChoices($transferOrder->getAsset());
        $walletIdMap = array_flip($walletChoices);
        $groupedTransfers = $this->monthEndService->groupIncomeTransfers(
            $transferOrder,
            $walletIdMap,
        );
        return $this->render('admin/pages/monthend/income_transfers/builder/tax.html.twig', [
            'transferOrder' => $transferOrder,
            'groupedTransfers' => $groupedTransfers,
            'walletIds' => $walletChoices,
            'walletIdMap' => array_flip($walletChoices),
        ]);
    }

    #[Route(
        '/treasury',
        name: 'admin_monthend_income_transfer_builder_treasury',
        methods: ['GET'],
    )]
    public function treasury(Request $request, TransferOrder $transferOrder): Response
    {
        if (!$this->monthEndService->isTransferOrderValid($transferOrder)) {
            $this->addFlash(
                'warning',
                "Transfer order #{$transferOrder->getId()} is not an income transfer order",
            );
            return $this->redirectToRoute('admin_monthend_income_transfer_manage', ['id' => $transferOrder->getId()]);
        }
        $walletChoices = $this->monthEndService->getAssetWalletChoices($transferOrder->getAsset());
        $walletIdMap = array_flip($walletChoices);
        $groupedTransfers = $this->monthEndService->groupIncomeTransfers(
            $transferOrder,
            $walletIdMap,
        );
        return $this->render('admin/pages/monthend/income_transfers/builder/treasury.html.twig', [
            'transferOrder' => $transferOrder,
            'groupedTransfers' => $groupedTransfers,
            'walletIds' => $walletChoices,
            'walletIdMap' => array_flip($walletChoices),
        ]);
    }

    #[Route(
        '/distribution',
        name: 'admin_monthend_income_transfer_builder_distribution',
        methods: ['GET'],
    )]
    public function distribution(
        Request $request,
        TransferOrder $transferOrder,
    ): Response {
        if (!$this->monthEndService->isTransferOrderValid($transferOrder)) {
            $this->addFlash(
                'warning',
                "Transfer order #{$transferOrder->getId()} is not an income transfer order",
            );
            return $this->redirectToRoute('admin_monthend_income_transfer_manage', ['id' => $transferOrder->getId()]);
        }
        $walletChoices = $this->monthEndService->getAssetWalletChoices($transferOrder->getAsset());
        $walletIdMap = array_flip($walletChoices);
        $groupedTransfers = $this->monthEndService->groupIncomeTransfers(
            $transferOrder,
            $walletIdMap,
        );
        return $this->render('admin/pages/monthend/income_transfers/builder/distribution.html.twig', [
            'transferOrder' => $transferOrder,
            'offering' => $this->offeringRepository->findFirstPartyByAssetId(
                $transferOrder->getAsset()->getId(),
            ),
            'groupedTransfers' => $groupedTransfers,
            'walletIds' => $walletChoices,
            'walletIdMap' => array_flip($walletChoices),
        ]);
    }

    #[Route(
        '/add-transfer',
        name: 'admin_monthend_income_transfer_builder_add',
        methods: ['GET', 'POST'],
    )]
    #[Route(
        '/edit-transfer/{transferRequest}',
        name: 'admin_monthend_income_transfer_builder_edit',
        methods: ['GET', 'POST'],
    )]
    public function request(
        Request $request,
        #[MapEntity(id: 'id')] TransferOrder $transferOrder,
        #[MapEntity(id: 'transferRequest')] ?TransferRequest $transferRequest = null,
    ): Response {
        if (!$this->monthEndService->isTransferOrderValid($transferOrder)) {
            $this->addFlash(
                'warning',
                "Transfer order #{$transferOrder->getId()} is not an income transfer order",
            );
            return $this->redirectToRoute('admin_monthend_income_transfer_manage', ['id' => $transferOrder->getId()]);
        }
        $walletChoices = $this->monthEndService->getAssetWalletChoices($transferOrder->getAsset());
        $walletIdMap = array_flip($walletChoices);
        if (
            array_diff(AssetManagerV2::SUPPORTED_WALLETS, array_keys($walletChoices))
            !== []
        ) {
            $this->addFlash(
                'warning',
                'Asset is missing wallets. Configure any missing wallets in the product hub.',
            );
            return $this->redirectToRoute('admin_monthend_income_transfer_manage', [
                'id' => $transferOrder->getId(),
            ]);
        }

        // For the create new use case, create the new TransferRequest object
        if (is_null($transferRequest)) {
            $transferRequest = new TransferRequest();
            $transferOrder->addTransfer($transferRequest);
            $transferRequest->setDebitWalletId($walletChoices['deposit']);
            if (in_array($request->query->get('creditWallet'), [
                'expenses',
                'tax',
                'distribution',
                'treasury',
            ])) {
                $transferRequest->setCreditWalletId(
                    $walletChoices[$request->query->get('creditWallet')],
                );
            }
            if (array_key_exists(
                $request->query->get('descriptionPreset'),
                MonthEndService::DESCRIPTION_PRESETS,
            )) {
                $transferRequest->setDescription(
                    MonthEndService::DESCRIPTION_PRESETS[$request->query->get(
                        'descriptionPreset',
                    )],
                );
            }
        }
        $form = $this->createForm(IncomeTransferType::class, $transferRequest, [
            'creditWalletChoices' => array_filter(
                $walletChoices,
                fn($k) => in_array($k, ['expenses', 'tax', 'treasury', 'distribution']),
                ARRAY_FILTER_USE_KEY,
            ),
        ]);
        $form->get('amountType')->setData('absolute');
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ('percentage' == $form->get('amountType')->getData()) {
                $transferRequest = $this->monthEndService->transformPercentageToAbsolute(
                    $transferRequest,
                    $walletIdMap,
                );
            }
            $this->doctrine->getManager()->flush();
            $this->addFlash('success', 'Transfer request successfully created');
            $returnToRoute = match (
                $walletIdMap[$transferRequest->getCreditWalletId()]
            ) {
                'expenses' => 'admin_monthend_income_transfer_builder_expenses',
                'tax' => 'admin_monthend_income_transfer_builder_tax',
                'treasury' => 'admin_monthend_income_transfer_builder_treasury',
                'distribution' => 'admin_monthend_income_transfer_builder_distribution',
                default => 'admin_monthend_income_transfer_manage',
            };
            return $this->redirectToRoute($returnToRoute, [
                'id' => $transferOrder->getId(),
            ]);
        }
        $creditWallet =
            $walletIdMap[$transferRequest->getCreditWalletId()] ?? $request->query->get(
                'creditWallet',
            );
        $returnToRoute = match ($creditWallet) {
            'expenses' => 'admin_monthend_income_transfer_builder_expenses',
            'tax' => 'admin_monthend_income_transfer_builder_tax',
            'treasury' => 'admin_monthend_income_transfer_builder_treasury',
            'distribution' => 'admin_monthend_income_transfer_builder_distribution',
            default => 'admin_monthend_income_transfer_manage',
        };
        return $this->render('admin/pages/monthend/income_transfers/builder/request_editor.html.twig', [
            'form' => $form->createView(),
            'transferOrder' => $transferOrder,
            'transferRequest' => $transferRequest,
            'walletIds' => $walletChoices,
            'walletIdMap' => $walletIdMap,
            'exitRoute' => $returnToRoute,
        ]);
    }
}
