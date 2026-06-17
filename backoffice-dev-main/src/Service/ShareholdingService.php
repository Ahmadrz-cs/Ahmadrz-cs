<?php

namespace App\Service;

use BcMath\Number;
use Psr\Log\LoggerInterface;

class ShareholdingService
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    /**
     * Accept result from ShareTradeRepository methods as input
     * - aggregateAssetShareholdingsByUser
     * - aggregateUserShareholdingsByAsset
     * Or the result from executing a query made from buildAggregateShareholdingQuery
     */
    public function annotateAggregateShareholdings(array $shareholdings): array
    {
        /**
         * - Calculate the average (mean) price paid when buying and selling
         * - The difference (spread) can then be used to calculate the profit on the shares sold
         *   - If the current shareholding is zero, this is the same as the net value
         *   - The spread is the "profit per share"
         *   - The direction of the spread is from the investor's PoV, so sell > buy == positive
         * - Calculate the value of the shareholding based on the average buy price
         *   - This will be (rightfully) 0 (zero) when current shareholding is zero
         */
        foreach ($shareholdings as &$entry) {
            $averageBuyPrice = new Number(0);
            $averageSellPrice = new Number(0);
            $averageSpread = new Number(0);
            if ($entry['buyShares'] > 0) {
                $averageBuyPrice = new Number((string) $entry['buyValue'])->div(
                    $entry['buyShares'],
                );
            }
            if ($entry['sellShares'] > 0) {
                $averageSellPrice = new Number((string) $entry['sellValue'])->div(
                    $entry['sellShares'],
                );
            }
            if ($entry['buyShares'] > 0 && $entry['sellShares'] > 0) {
                $averageSpread = $averageSellPrice->sub($averageBuyPrice);
            }
            $entry['buyMean'] = $averageBuyPrice;
            $entry['sellMean'] = $averageSellPrice;
            $entry['spread'] = $averageSpread;

            if ($entry['shares']) {
                $entry['profit'] = $averageSpread->mul($entry['sellShares']);
            } else {
                $entry['profit'] = $entry['value'];
            }

            $entry['currentValue'] = $averageBuyPrice->mul($entry['shares']);
        }
        // $this->logger->debug('Annotated Shareholdings', $shareholdings);
        return $shareholdings;
    }
}
