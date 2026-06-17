<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Enum\TradeDirection;
use AppBundle\Entity\Enum\TradeOrderStatus;
use AppBundle\Entity\Enum\TradeOrderType;
use AppBundle\Entity\Enum\TradeStatus;
use AppBundle\Entity\Payout;
use AppBundle\Entity\PortfolioPosition;
use AppBundle\Entity\ShareTrade;
use AppBundle\Entity\TradeOrder;
use AppBundle\Form\QueryTradeHistoryType;
use ClientBundle\Dto\PayoutQueryDto;
use ClientBundle\Dto\ShareTradeQueryDto;
use ClientBundle\Dto\TradeOrderQueryDto;
use ClientBundle\Service\AssetProductService;
use ClientBundle\Service\ExportService;
use ClientBundle\Service\PortfolioService;
use ClientBundle\Service\ScaService;
use ClientBundle\Service\UserService;
use Psr\Log\LoggerInterface;
use Sonata\Exporter\Exporter;
use Sonata\Exporter\Source\IteratorCallbackSourceIterator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PortfolioController extends AbstractController
{
    private $user;

    public function __construct(
        private LoggerInterface $logger,
        private RequestStack $requestStack,
        private UrlGeneratorInterface $router,
        private AssetProductService $assetProductService,
        private PortfolioService $portfolioService,
        private UserService $userService,
        private ExportService $exportService,
        private Exporter $exporter,
    ) {
        $this->logger->info("==================IN containerInitialized=====================");

        $authenticated = $this->requestStack->getSession()->get('authenticated');
        if (!$authenticated) {
            header('Location: ' . $this->router->generate('login'));
            exit;
        }
        $this->user = $this->requestStack->getSession()->get('userInfo');
    }

    #[Route(path: '/my-portfolio', name: 'my_portfolio', methods: ['GET'])]
    public function portfolioDashboard(Request $request, ScaService $scaService): Response
    {
        $this->logger->info("IN PortfolioController->portfolioDashboard");

        $authenticated = $this->requestStack->getSession()->get('authenticated');
        if (!$authenticated) {
            return $this->redirectToRoute('login');
        }

        // Refresh wallet balance and check wallet sca verification requirement
        if ($request->query->get('refreshBalance', false)) {
            $this->userService->setBalance();
            return $this->redirectToRoute('my_portfolio');
        }

        // Refresh portfolio (to clear cache)
        if ($request->query->get('refreshPortfolio', false)) {
            $this->portfolioService->clearAuthenticatedUserPortfolioCache();
            return $this->redirectToRoute('my_portfolio');
        }

        $scaRequired = $this->requestStack->getSession()->get('walletScaRequired');

        $portfolioSummary = $this->portfolioService->retrievePortfolio();
        // $this->logger->debug("portfolio", [$portfolioSummary]);
        $unsettledInvestments = $this->portfolioService->retrievePortfolioUnsettled();

        $sellOrders = $this->portfolioService->retrievePortfolioTradeOrders(new TradeOrderQueryDto(
            direction: TradeDirection::Sell,
            status: TradeOrderStatus::openStates(),
            type: [TradeOrderType::Market],
        ));
        $dividends = $this->portfolioService->retrievePortfolioDividends(new PayoutQueryDto(perPage: 10));

        return $this->render('@AppBundle/Portfolio/dashboard.html.twig', [
            'wallet_balance' => $this->requestStack->getSession()->get('balance'),
            'scaRequired' => $scaRequired,
            'canScaEnroll' => $scaService->canScaEnroll(
                $this->requestStack->getSession()->get('userInfo'),
            ),
            'user_info' => $this->user,
            'active' => 'dashboard',
            'pageinfo' => 'Dashboard',
            'portfolioSummary' => $portfolioSummary,
            'unsettledInvestments' => $unsettledInvestments,
            'sellOrders' => $sellOrders,
            'dividends' => $dividends,
        ]);
    }

    #[Route(path: '/my-portfolio/top-yielders', name: 'portfolio_top_yielders', methods: ['GET'])]
    public function portfolioTopYielder(): Response
    {
        $this->logger->info("IN PortfolioController->portfolioTopYielder");
        if (
            !$this->requestStack->getSession()->get('authenticated')
            || !$this->requestStack->getSession()->get('userInfo')['is_vip'] ?? true
        ) {
            return $this->redirectToRoute('my_portfolio');
        }

        $prefundingRepayments = $this->portfolioService->retrievePortfolioPrefunding();

        return $this->render('@AppBundle/Portfolio/prefunding.html.twig', [
            'prefundingRepayments' => $prefundingRepayments,
            'active' => 'topyielders',
        ]);
    }

    #[Route(path: '/my-portfolio/trade-history', name: 'portfolio_trade_history', methods: ['GET'])]
    public function portfolioTradeHistory(): Response
    {
        $this->logger->info("IN PortfolioController->portfolioTradeHistory");
        $authenticated = $this->requestStack->getSession()->get('authenticated');
        if (!$authenticated) {
            return $this->redirectToRoute('login');
        }
        $query = new ShareTradeQueryDto(
            status: [TradeStatus::Settled],
            buyOrderType: [
                TradeOrderType::Prefunding,
                TradeOrderType::Market,
                TradeOrderType::OffMarket,
                TradeOrderType::BuyBack,
                TradeOrderType::Proxy,
            ],
            createdAt_gte: new \DateTime('midnight -366 days'),
        );
        $shareTrades = $this->portfolioService->retrievePortfolioShareTrades($query);

        return $this->render('@AppBundle/Portfolio/trade_history.html.twig', [
            'shareTrades' => $shareTrades,
            'active' => 'history',
        ]);
    }

    #[Route(path: '/my-portfolio/trade-history/export', name: 'portfolio_trade_history_export', methods: ['GET', 'POST'])]
    public function portfolioTradeHistoryExport(Request $request): Response
    {
        $this->logger->info("IN PortfolioController->portfolioTradeHistoryExport");
        $authenticated = $this->requestStack->getSession()->get('authenticated');
        if (!$authenticated) {
            return $this->redirectToRoute('login');
        }

        $filters = new ShareTradeQueryDto(
            status: [TradeStatus::Settled],
            buyOrderType: [
                TradeOrderType::Prefunding,
                TradeOrderType::Market,
                TradeOrderType::OffMarket,
                TradeOrderType::BuyBack,
                TradeOrderType::Proxy,
            ],
            createdAt_gte: new \DateTime('midnight -12 months'),
            createdAt_lt: new \DateTime('midnight tomorrow'),
        );
        $form = $this->createForm(QueryTradeHistoryType::class, $filters);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $filters = $form->getData();
                $shareTrades = $this->portfolioService->retrievePortfolioShareTrades($filters);
                $fileName = $this->exportService->generateFileName('trade_history_', 'csv');
                $source = new IteratorCallbackSourceIterator(
                    new \ArrayIterator($shareTrades),
                    $this->exportService->formatTradeHistoryCallable($this->user['id']),
                );
            } catch (\Throwable $th) {
                $this->logger->error("Error encountered formatting share trade export", [$th->getMessage()]);
                $this->addFlash("error", "An error was encountered when preparing your trade history export");
                return $this->render('@AppBundle/Portfolio/trade_history_export.html.twig', [
                    'form' => $form,
                    'active' => 'history',
                ]);
            }

            return $this->exporter->getResponse('csv', $fileName, $source);
        }


        return $this->render('@AppBundle/Portfolio/trade_history_export.html.twig', [
            'form' => $form,
            'active' => 'history',
        ]);
    }

    #[Route(path: '/my-portfolio/positions/{id}', name: 'my_portfolio_asset_position', methods: ['GET'])]
    public function portfolioAssetPosition(Request $request, int|string $id): Response
    {
        $this->logger->info("IN PortfolioController->portfolioAssetPosition");

        $authenticated = $this->requestStack->getSession()->get('authenticated');
        if (!$authenticated) {
            return $this->redirectToRoute('login');
        }

        // Refresh portfolio (to clear cache)
        if ($request->query->get('refreshPortfolio', false)) {
            $this->portfolioService->clearAuthenticatedUserPortfolioCache();
            return $this->redirectToRoute('my_portfolio_asset_position', ['id' => $id]);
        }
        $portfolioSummary = $this->portfolioService->retrievePortfolio();
        $position = array_find(
            $portfolioSummary->positions,
            fn(PortfolioPosition|array $item): bool => ($item instanceof PortfolioPosition ? $item->assetId : $item['assetId']) == $id,
        );
        if (empty($position)) {
            $this->logger->debug("User has no positions in asset", ["assetId" => $id]);
            // $this->addFlash("error", "Asset product not found in portfolio");
            return $this->redirectToRoute('my_portfolio');
        }
        $unsettledInvestments = array_filter(
            $this->portfolioService->retrievePortfolioUnsettled(),
            fn(ShareTrade $item): bool => $item->assetId == $id,
        );
        $dividends = $this->portfolioService->retrievePortfolioDividends(new PayoutQueryDto(assetId: $id));
        $sellOrders = array_filter(
            $this->portfolioService->retrievePortfolioTradeOrders(new TradeOrderQueryDto(
                direction: TradeDirection::Sell,
                status: TradeOrderStatus::openStates(),
                type: [TradeOrderType::Market],
            )),
            fn(TradeOrder $item): bool => $item->assetId == $id,
        );
        $asset = $this->assetProductService->getSingleAssetProduct($id);

        return $this->render('@AppBundle/Portfolio/asset_position.html.twig', [
            'user_info' => $this->user,
            'active' => 'dashboard',
            'pageinfo' => 'Dashboard',
            'asset' => $asset,
            'portfolioSummary' => $portfolioSummary,
            'position' => $position,
            'unsettledInvestments' => $unsettledInvestments,
            'dividends' => $dividends,
            'sellOrders' => $sellOrders,
        ]);
    }
}
