<?php

namespace App\Controller\Admin;

use App\Entity\Enum\AssetStatus;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TradeStatus;
use App\Entity\TradeOrder;
use App\Form\Type\QueryTradeOrderType;
use App\Repository\AssetRepository;
use App\Repository\ShareTradeRepository;
use App\Repository\TradeOrderRepository;
use App\Service\Util\Helper;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/trading')]
class TradingHubController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private AssetRepository $assetRepository,
        private ShareTradeRepository $shareTradeRepository,
        private TradeOrderRepository $tradeOrderRepository,
    ) {}

    #[Route('', name: 'admin_trading_hub_index', methods: ['GET'])]
    #[IsGranted('ROLE_ANALYST')]
    public function index(Request $request): Response
    {
        return $this->redirectToRoute('admin_trading_hub_sell_orders_pending');

        // return $this->render('admin/pages/trading/index.html.twig', []);
    }

    #[Route('/products', name: 'admin_trading_hub_products', methods: ['GET'])]
    #[IsGranted('ROLE_ANALYST')]
    public function products(Request $request): Response
    {
        $publicAssets = $this->assetRepository->findWithAssociations([
            'status' => AssetStatus::publicCases(),
        ], ['id' => 'DESC']);
        $assetIds = Helper::convertArrayKeysAsIds($publicAssets);

        $initialSellorders = $this->tradeOrderRepository->findWithAssociations([
            'id' => $assetIds,
            'direction' => TradeDirection::Sell,
            'type' => [TradeOrderType::Initial],
            'status' => TradeOrderStatus::nonCancelledStates(),
        ]);
        $groupedInitialSellOrders = [];
        foreach ($initialSellorders as $sellOrder) {
            $assetId = $sellOrder->getAsset()->getId();
            if (!array_key_exists($assetId, $groupedInitialSellOrders)) {
                $groupedInitialSellOrders[$assetId] = [];
            }
            $groupedInitialSellOrders[$assetId][] = $sellOrder;
        }

        return $this->render('admin/pages/trading/products.html.twig', [
            'assets' => $publicAssets,
            'assetInitialSellOrders' => $groupedInitialSellOrders,
        ]);
    }

    #[Route(
        '/trade-orders/sell',
        name: 'admin_trading_hub_sell_orders_pending',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_ANALYST')]
    public function pendingSellOrders(Request $request): Response
    {
        $form = $this->createForm(QueryTradeOrderType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $pendingSellOrders = $this->tradeOrderRepository->findByWithAssociations(
            [
                'direction' => TradeDirection::Sell,
                'type' => TradeOrderType::marketTradingTypes(),
                'status' => [TradeOrderStatus::Draft, TradeOrderStatus::Submitted],
            ],
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/trading/sell_orders.html.twig', [
            'tradeOrders' => $pendingSellOrders,
        ]);
    }

    #[Route(
        '/share-trades',
        name: 'admin_trading_hub_share_trades_pending',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_ANALYST')]
    public function pendingShareTrades(Request $request): Response
    {
        $pendingShareTrades = $this->shareTradeRepository->findByWithAssociations([
            'status' => [
                TradeStatus::Draft,
                TradeStatus::Reserved,
                TradeStatus::Unsettled,
            ],
        ]);
        return $this->render('admin/pages/trading/share_trades.html.twig', [
            'shareTrades' => $pendingShareTrades,
        ]);
    }
}
