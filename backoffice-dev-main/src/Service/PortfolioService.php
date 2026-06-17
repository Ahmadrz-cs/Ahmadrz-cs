<?php

namespace App\Service;

use App\Dto\Struct\Portfolio;
use App\Dto\Struct\PortfolioPosition;
use App\Entity\Asset;
use App\Entity\Enum\QueryGrouping;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\User;
use App\Repository\AssetRepository;
use App\Repository\PayoutRepository;
use App\Repository\ShareTradeRepository;
use App\Repository\TradeOrderRepository;
use App\Service\Util\Helper;
use BcMath\Number;
use Psr\Log\LoggerInterface;

class PortfolioService
{
    public function __construct(
        private LoggerInterface $logger,
        private AssetRepository $assetRepository,
        private PayoutRepository $payoutRepository,
        private ShareTradeRepository $shareTradeRepository,
        private TradeOrderRepository $tradeOrderRepository,
        private ShareholdingService $shareholdingService,
        private DivestmentService $divestmentService,
    ) {}

    public function compilePortfolio(User $user): Portfolio
    {
        // Get share trade aggregates
        $tradeBasedHoldings = $this->shareTradeRepository->aggregateUserShareholdingsByAsset(
            $user->getId(),
        );
        $tradeBasedHoldings =
            $this->shareholdingService->annotateAggregateShareholdings(
                $tradeBasedHoldings,
            );

        $assets = Helper::convertArrayKeysAsIds($this->assetRepository->findBy([
            'id' => array_column($tradeBasedHoldings, 'assetid'),
        ]));

        // Get dividend summary
        $dividendSummary = $this->payoutRepository->getDividendSummaryByAsset(
            $user->getId(),
            false,
        );
        $dividendSummary = array_combine(
            array_column($dividendSummary, 'assetId'),
            $dividendSummary,
        );

        // Get sell order summary
        $sellOrderAggregate = $this->shareTradeRepository->aggregateUserTradeOrdersByAsset(
            userId: $user->getId(),
            direction: TradeDirection::Sell,
            orderStatuses: TradeOrderStatus::nonCancelledStates(),
            orderTypes: TradeOrderType::circulatingSellTypes(),
        );
        $sellOrderAggregate = array_combine(
            array_column($sellOrderAggregate, 'assetId'),
            $sellOrderAggregate,
        );

        // Get the account wide aggregates
        // Note that array_sum() can only be used on integers and floats
        // So we'll use array_reduce() with our own callback to do the sum
        $portfolio = new Portfolio(
            userId: (string) $user->getId(),
            value: array_reduce(
                array_column($tradeBasedHoldings, 'currentValue'),
                fn(Number $carry, Number|string $item) => $carry->add($item),
                new Number(0),
            )->round(2),
            dividends: array_reduce(
                array_column($dividendSummary, 'dividendsTotal'),
                fn(Number $carry, Number|string $item) => $carry->add($item),
                new Number(0),
            )->round(2),
            capitalGains: array_reduce(
                array_column($tradeBasedHoldings, 'profit'),
                fn(Number $carry, Number|string $item) => $carry->add($item),
                new Number(0),
            )->round(2),
        );

        // Collate the results per asset
        foreach ($tradeBasedHoldings as $source) {
            $assetId = $source['assetid'];
            $dividends = array_key_exists($assetId, $dividendSummary)
                ? $dividendSummary[$assetId]['dividendsTotal']
                : 0;
            $sharesListedRemaining = 0;
            if (array_key_exists($assetId, $sellOrderAggregate)) {
                // The term "sharesAvailable" is from the PoV of the TradeOrder
                // Which in this context means any unsettled shares in the TradeOrder
                // Whether invested but unsettled, or still open to investment
                $sharesListedRemaining =
                    $sellOrderAggregate[$assetId]['sharesAvailable'];
            }
            $position = new PortfolioPosition(
                asset: $assets[$assetId],
                averagePrice: new Number((string) $source['buyMean'])->round(2),
                shares: new Number($source['shares']),
                value: new Number((string) $source['currentValue'])->round(2),
                dividends: new Number((string) $dividends)->round(2),
                capitalGains: new Number((string) $source['profit'])->round(2),
                buyShares: new Number((string) $source['buyShares']),
                buyValue: new Number((string) $source['buyValue'])->round(2),
                sellShares: new Number((string) $source['sellShares']),
                sellValue: new Number((string) $source['sellValue'])->round(2),
                sharesAvailable: new Number((string) $source['shares'])->sub(
                    $sharesListedRemaining,
                )->round(0),
            );
            $portfolio->positions[] = $position;
        }

        return $portfolio;
    }

    public function compilePrefundingPortfolio(User $user): Portfolio
    {
        $prefunderSellOrders = $this->tradeOrderRepository->findWithAssociations([
            'userId' => $user->getId(),
            'status' => [TradeOrderStatus::Active, TradeOrderStatus::Completed],
            'type' => TradeOrderType::Prefunding,
            'direction' => TradeDirection::Sell,
        ], ['numberOfShares' => 'ASC']);

        $repaymentSummary = $this->divestmentService->compileRepaymentProgress(
            $prefunderSellOrders,
            QueryGrouping::Asset,
        );

        /**
         * @var Asset[] $assets
         */
        $assets = Helper::convertArrayKeysAsIds($this->assetRepository->findBy([
            'id' => array_column($repaymentSummary, 'assetid'),
        ]));

        $portfolio = new Portfolio(userId: (string) $user->getId());

        // Collate the results per asset
        foreach ($repaymentSummary as $source) {
            $assetId = $source['assetid'];
            $asset = $assets[$assetId];
            $sharePrice = new Number($asset?->getPricePerShare() ?? 0);
            $position = new PortfolioPosition(
                asset: $asset,
                averagePrice: $sharePrice,
                shares: new Number($source['shares']),
                value: new Number((string) $source['shares'])->mul($sharePrice)->round(
                    2,
                ),
                dividends: new Number(0)->round(2),
                capitalGains: new Number(0)->round(2),
                buyShares: new Number((string) $source['initialShares']),
                buyValue: new Number((string) $source['initialShares'])->mul(
                    $sharePrice,
                )->round(2),
                sellShares: new Number((string) $source['repaidShares']),
                sellValue: new Number((string) $source['repaidShares'])->mul(
                    $sharePrice,
                )->round(2),
            );
            $portfolio->positions[] = $position;
        }

        // Calculate aggregate after, as we are deriving the per asset value based on share price
        $portfolio->value = array_reduce(
            array_column($portfolio->positions, 'value'),
            fn(Number $carry, Number|string $item) => $carry->add($item),
            new Number(0),
        )->round(2);

        return $portfolio;
    }

    public function getSharesAvailableToSell(User $user, Asset $asset): int
    {
        $tradeBasedHoldings = $this->shareTradeRepository->aggregateUserShareholdingsByAsset(
            $user->getId(),
        );
        $tradeBasedHoldings = array_combine(
            array_column($tradeBasedHoldings, 'assetid'),
            $tradeBasedHoldings,
        );
        // To continue querying for listings user must have shareholdings in the asset
        // Otherwise, there's nothing to sell
        if (
            array_key_exists($asset->getId(), $tradeBasedHoldings)
            && $tradeBasedHoldings[$asset->getId()]['shares'] > 0
        ) {
            $sellOrderAggregate = $this->shareTradeRepository->aggregateUserTradeOrdersByAsset(
                userId: $user->getId(),
                direction: TradeDirection::Sell,
                orderStatuses: TradeOrderStatus::nonCancelledStates(),
                orderTypes: TradeOrderType::circulatingSellTypes(),
            );
            $sellOrderAggregate = array_combine(
                array_column($sellOrderAggregate, 'assetId'),
                $sellOrderAggregate,
            );
            $sharesListedRemaining = 0;
            if (array_key_exists($asset->getId(), $sellOrderAggregate)) {
                // The term "sharesAvailable" is from the PoV of the TradeOrder
                // Which in this context means any unsettled shares in the TradeOrder
                // Whether invested but unsettled, or still open to investment
                $sharesListedRemaining =
                    $sellOrderAggregate[$asset->getId()]['sharesAvailable'];
            }
            return (int) (
                $tradeBasedHoldings[$asset->getId()]['shares'] - $sharesListedRemaining
            );
        }
        return 0;
    }
}
