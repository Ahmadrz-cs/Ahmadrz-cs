<?php

namespace App\Controller\Admin;

use App\Entity\Enum\OrderingDirection;
use App\Entity\Enum\PaymentType;
use App\Entity\PaymentOrder;
use App\Form\Type\AssetRelationType;
use App\Form\Type\PaymentOrderGenerateType;
use App\Repository\AssetRepository;
use App\Repository\OfferingRepository;
use App\Repository\ShareTradeRepository;
use App\Service\Manager\AssetManagerV2;
use App\Service\MonthEndEmailService;
use App\Service\MonthEndService;
use App\Service\PaymentGeneratorService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/monthend/divestments')]
class DivestmentController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private MonthEndService $monthEndService,
        private AssetRepository $assetRepository,
        private OfferingRepository $offeringRepository,
        private ShareTradeRepository $shareTradeRepository,
        private PaymentGeneratorService $paymentGeneratorService,
        private MonthEndEmailService $monthEndEmailService,
        private AssetManagerV2 $assetManager,
    ) {}

    #[Route(
        '/create',
        name: 'admin_monthend_divestment_create',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function create(Request $request): Response
    {
        $paymentOrder = $this->monthEndService->createPaymentOrderByType(PaymentType::Divestment);
        if ((int) $request->query->get('assetId')) {
            $asset = $this->assetRepository->find($request->query->get('assetId'));
            if (!is_null($asset)) {
                $paymentOrder->setAsset($asset);
            }
        }
        $form = $this->createForm(AssetRelationType::class, $paymentOrder, [
            'data_class' => PaymentOrder::class,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->persist($paymentOrder);
            $this->doctrine->getManager()->flush();
            $this->logger->debug("Created new divestment #{$paymentOrder->getId()}");
            $this->addFlash('success', 'Payment order successfully created');
            return $this->redirectToRoute('admin_payment_order_date', [
                'id' => $paymentOrder->getId(),
                'setup' => 1,
                'redirectRoute' => 'admin_monthend_divestment_manage',
            ]);
        }
        return $this->render('admin/pages/monthend/payments/create.html.twig', [
            'form' => $form->createView(),
            'paymentType' => PaymentType::Divestment->value,
        ]);
    }

    #[Route('/{id}', name: 'admin_monthend_divestment_manage', methods: ['GET'])]
    #[IsGranted('ROLE_ANALYST')]
    public function manage(PaymentOrder $paymentOrder): Response
    {
        if (!in_array($paymentOrder->getPaymentType(), [
            PaymentType::Divestment->value,
            PaymentType::InvestmentExit->value,
        ])) {
            $this->addFlash(
                'warning',
                "Payment order #{$paymentOrder->getId()} is not for divestments",
            );
            return $this->redirectToRoute('admin_payment_order_manage', ['id' => $paymentOrder->getId()]);
        }
        try {
            $balance = $this->assetManager->getAssetWalletByType(
                $paymentOrder->getAsset(),
                $paymentOrder->getDebitWallet() == 'distribution'
                    ? $paymentOrder->getDebitWallet()
                    : 'settlement',
            )['balance'];
        } catch (\Exception $e) {
            $this->addFlash(
                'warning',
                'Unable to retrieve wallet balance. ' . $e->getMessage(),
            );
            $this->logger->error('Unable to retreive wallet balance', [
                'asset #' . $paymentOrder->getAsset()->getId(),
                $e->getMessage(),
            ]);
        }
        $shareholders = $this->shareTradeRepository->aggregateAssetShareholdingsByUser(
            assetId: $paymentOrder->getAsset()->getId(),
            nonZero: true,
            shareholderOrdering: OrderingDirection::Descending,
        );
        // $this->logger->notice('shareholders', $shareholders);
        $sharesInCirculation = (int) array_sum(array_column($shareholders, 'shares'));
        if ($this->monthEndService->determineDivestmentType(
            $paymentOrder,
            $sharesInCirculation,
        )) {
            $this->logger->debug(
                "Changing payment order to type: {$paymentOrder->getPaymentType()}",
            );
            $this->doctrine->getManager()->flush();
        }
        return $this->render('admin/pages/monthend/payments/manage_divestment.html.twig', [
            'paymentOrder' => $paymentOrder,
            'walletBalance' => $balance ?? 0,
            'currentShareholders' => $shareholders,
        ]);
    }

    #[Route(
        '/{id}/generate',
        name: 'admin_monthend_divestment_generate',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function generate(Request $request, PaymentOrder $paymentOrder): Response
    {
        if (!in_array($paymentOrder->getPaymentType(), [
            PaymentType::Divestment->value,
            PaymentType::InvestmentExit->value,
        ])) {
            $this->addFlash(
                'warning',
                "Payment order #{$paymentOrder->getId()} is not for divestments",
            );
            return $this->redirectToRoute('admin_payment_order_manage', ['id' => $paymentOrder->getId()]);
        }
        if (PaymentOrder::STATE_DRAFT != $paymentOrder->getStatus()) {
            $this->addFlash(
                'warning',
                'Payments can only be added when the order is in draft mode',
            );
            return $this->redirectToRoute(
                'admin_monthend_divestment_manage',
                ['id' => $paymentOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        // try {
        //     $balance = $this->assetManager->getAssetWalletByType(
        //         $paymentOrder->getAsset(),
        //         $paymentOrder->getDebitWallet() == 'distribution' ? $paymentOrder->getDebitWallet() : 'settlement',
        //     )['balance'];
        // } catch (\Exception $e) {
        //     $this->addFlash('warning', 'Unable to retrieve wallet balance. ' . $e->getMessage());
        //     $this->logger->error('Unable to retreive wallet balance', [
        //         'asset #' . $paymentOrder->getAsset()->getId(),
        //         $e->getMessage()
        //     ]);
        // }
        $shareholders = $this->shareTradeRepository->aggregateAssetShareholdingsByUser(
            assetId: $paymentOrder->getAsset()->getId(),
            nonZero: true,
            shareholderOrdering: OrderingDirection::Descending,
        );
        if (empty($shareholders)) {
            $this->addFlash(
                'warning',
                'Unable to run generate payments. There are no shareholders in this asset!',
            );
            return $this->redirectToRoute(
                'admin_monthend_divestment_manage',
                ['id' => $paymentOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }

        $form = $this->createForm(
            PaymentOrderGenerateType::class,
            [],
            [
                'paymentType' => PaymentType::Divestment,
            ],
        );
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->logger->info('Generate payments for Payment Order', [$paymentOrder->getId()]);
            try {
                $this->paymentGeneratorService->generateDivestments(
                    paymentOrder: $paymentOrder,
                    shareholdings: $shareholders,
                    payoutPot: $form->getData()['amount'],
                    sharesToLiquidate: $form->getData()['shares'],
                );
                $this->doctrine->getManager()->flush();
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Unable to run generate payments. ' . $e->getMessage(),
                );
                $this->logger->error('Unable to run generate payments. ', [$e->getMessage()]);
            }
            return $this->redirectToRoute(
                'admin_monthend_divestment_manage',
                ['id' => $paymentOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        $offering = $this->offeringRepository->findFirstPartyByAssetId(
            $paymentOrder->getAsset()->getId(),
        );
        return $this->render('admin/pages/monthend/payments/generate_divestment.html.twig', [
            'currentShareholders' => $shareholders,
            'form' => $form->createView(),
            'offering' => $offering,
            'paymentOrder' => $paymentOrder,
            // 'walletBalance' => $balance ?? 0,
        ]);
    }
}
