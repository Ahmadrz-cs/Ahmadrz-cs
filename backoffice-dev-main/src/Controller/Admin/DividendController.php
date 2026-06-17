<?php

namespace App\Controller\Admin;

use App\Entity\Asset;
use App\Entity\Enum\AllocationMethod;
use App\Entity\Enum\OrderingDirection;
use App\Entity\Enum\PaymentType;
use App\Entity\PaymentOrder;
use App\Entity\PaymentRequest;
use App\Form\Type\AssetRelationType;
use App\Form\Type\PaymentOrderGenerateType;
use App\Repository\AssetRepository;
use App\Repository\HoldingRepository;
use App\Repository\ShareTradeRepository;
use App\Service\Manager\AssetManagerV2;
use App\Service\MonthEndService;
use App\Service\PaymentGeneratorService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/monthend/dividends')]
class DividendController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private MonthEndService $monthEndService,
        private AssetRepository $assetRepository,
        private HoldingRepository $holdingRepository,
        private ShareTradeRepository $shareTradeRepository,
        private PaymentGeneratorService $paymentGeneratorService,
        private AssetManagerV2 $assetManager,
    ) {}

    #[Route(
        '/create',
        name: 'admin_monthend_dividend_create',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function create(Request $request): Response
    {
        $paymentOrder = $this->monthEndService->createPaymentOrderByType(PaymentType::Dividend);
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
            $this->logger->debug(
                "Created new dividend payment #{$paymentOrder->getId()}",
            );
            $this->addFlash('success', 'Payment order successfully created');
            return $this->redirectToRoute('admin_payment_order_date', [
                'id' => $paymentOrder->getId(),
                'setup' => 1,
                'redirectRoute' => 'admin_monthend_dividend_manage',
            ]);
        }
        return $this->render('admin/pages/monthend/payments/create.html.twig', [
            'form' => $form->createView(),
            'paymentType' => PaymentType::Dividend->value,
        ]);
    }

    #[Route(
        '/create/{id}',
        name: 'admin_monthend_dividend_create_monthend',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function createForMonthend(Asset $asset): Response
    {
        $paymentOrder = $this->monthEndService->createPaymentOrderByType(PaymentType::Dividend);
        $paymentOrder->setAsset($asset);
        $this->doctrine->getManager()->persist($paymentOrder);
        $this->doctrine->getManager()->flush();
        $this->logger->debug("Created new dividend payment #{$paymentOrder->getId()}");
        $this->addFlash('success', 'Payment order successfully created');
        return $this->redirectToRoute('admin_monthend_dividend_generate', [
            'id' => $paymentOrder->getId(),
        ]);
    }

    #[Route('/{id}', name: 'admin_monthend_dividend_manage', methods: ['GET'])]
    #[IsGranted('ROLE_ANALYST')]
    public function manage(PaymentOrder $paymentOrder): Response
    {
        if (!(PaymentType::Dividend->value === $paymentOrder->getPaymentType())) {
            $this->addFlash(
                'warning',
                "Payment order #{$paymentOrder->getId()} is not for dividends",
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
        $shareholders = $this->holdingRepository->getShareHoldings([
            'assetId' => $paymentOrder->getAsset()->getId(),
            'currentHolding' => 1,
        ]);
        return $this->render('admin/pages/monthend/payments/manage_dividend.html.twig', [
            'paymentOrder' => $paymentOrder,
            'walletBalance' => $balance ?? 0,
            'currentShareholders' => $shareholders,
        ]);
    }

    #[Route(
        '/{id}/generate',
        name: 'admin_monthend_dividend_generate',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function generate(Request $request, PaymentOrder $paymentOrder): Response
    {
        if (!(PaymentType::Dividend->value === $paymentOrder->getPaymentType())) {
            $this->addFlash(
                'warning',
                "Payment order #{$paymentOrder->getId()} is not for dividends",
            );
            return $this->redirectToRoute('admin_payment_order_manage', ['id' => $paymentOrder->getId()]);
        }
        if (PaymentOrder::STATE_DRAFT != $paymentOrder->getStatus()) {
            $this->addFlash(
                'warning',
                'Payments can only be added when the order is in draft mode',
            );
            return $this->redirectToRoute(
                'admin_monthend_dividend_manage',
                ['id' => $paymentOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
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
        // $this->logger->debug('shareholders', [$shareholders]);
        if (empty($shareholders)) {
            $this->addFlash(
                'warning',
                'Unable to run generate payments. There are no shareholders in this asset!',
            );
            return $this->redirectToRoute(
                'admin_monthend_dividend_manage',
                ['id' => $paymentOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        $defaults = [
            'method' => AllocationMethod::Accrue,
        ];
        $form = $this->createForm(PaymentOrderGenerateType::class, $defaults, [
            'paymentType' => PaymentType::Dividend,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->logger->info('Generate payments for Payment Order', [$paymentOrder->getId()]);
            try {
                $this->paymentGeneratorService->generateDividends(
                    $paymentOrder,
                    $shareholders,
                    $form->getData()['amount'],
                    $form->getData()['method'],
                );
                $this->doctrine->getManager()->flush();
                $totalGenerated = array_reduce(
                    $paymentOrder->getPayments()->toArray(),
                    fn(?float $total, PaymentRequest $request) => $total +=
                        $request->getAmount(),
                );
                $accrual = round($form->getData()['amount'] - $totalGenerated, 2);
                if ($accrual > 0) {
                    $this->addFlash(
                        'warning',
                        "£{$accrual} will be accrued for distribution next month.",
                    );
                }
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Unable to run generate payments. ' . $e->getMessage(),
                );
                $this->logger->error('Unable to run generate payments. ', [$e->getMessage()]);
            }
            return $this->redirectToRoute(
                'admin_monthend_dividend_manage',
                ['id' => $paymentOrder->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        return $this->render('admin/pages/monthend/payments/generate_dividend.html.twig', [
            'currentShareholders' => $shareholders,
            'form' => $form->createView(),
            'paymentOrder' => $paymentOrder,
            'walletBalance' => $balance ?? 0,
        ]);
    }
}
