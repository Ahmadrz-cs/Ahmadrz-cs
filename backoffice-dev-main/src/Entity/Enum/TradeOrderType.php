<?php

namespace App\Entity\Enum;

enum TradeOrderType: string
{
    // Default - Buy at best price - makes more sense at floating share price supported
    case Market = 'market';
    // Buy once lower price reached, or sell once higher price reached
    case Limit = 'limit';
    // Sell once lower price reached
    case StopLoss = 'stop_loss';
    // Used for launching new assets
    case Initial = 'initial';
    // Special sell-only type for internal processing of prefunder repayments (liquidation portion)
    case Prefunding = 'prefunding';
    // For retrospective records of investments made off-market
    case OffMarket = 'off_market';
    // For reclaiming shares during a divest, exit, capital repayment
    case BuyBack = 'buy_back';
    // For proxying share trades between a seller and buyer - mainly used for prefunder repayments
    case Proxy = 'proxy';

    /**
     * Return list of cases that are considered standard market trading types
     * Refering to the secondary market
     * @return TradeOrderType[]
     */
    public static function marketTradingTypes(): array
    {
        return [
            TradeOrderType::Market,
            TradeOrderType::Limit,
            TradeOrderType::StopLoss,
        ];
    }

    /**
     * Return list of cases that are considered the core selling types
     * rather than used for internal functions
     * @return TradeOrderType[]
     */
    public static function tradingSellTypes(): array
    {
        return [
            TradeOrderType::Initial,
            TradeOrderType::Market,
            TradeOrderType::Limit,
            TradeOrderType::StopLoss,
        ];
    }

    /**
     * Return list of cases that are considered the retail buying types (i.e. not prefunding investments)
     * @return TradeOrderType[]
     */
    public static function retailBuyTypes(): array
    {
        return [
            TradeOrderType::Market,
            TradeOrderType::Limit,
            TradeOrderType::StopLoss,
            TradeOrderType::OffMarket,
        ];
    }

    /**
     * Return list of cases that are considered the core buying types
     * rather than used for internal functions
     * @return TradeOrderType[]
     */
    public static function tradingBuyTypes(): array
    {
        return [
            TradeOrderType::Market,
            TradeOrderType::Limit,
            TradeOrderType::StopLoss,
            TradeOrderType::OffMarket,
            TradeOrderType::Prefunding,
        ];
    }

    /**
     * Return list of cases that are considered normal trading types
     * rather than used for internal functions
     * @return TradeOrderType[]
     */
    public static function allTradingTypes(): array
    {
        return [
            TradeOrderType::Initial,
            TradeOrderType::Market,
            TradeOrderType::Limit,
            TradeOrderType::StopLoss,
            TradeOrderType::OffMarket,
            TradeOrderType::Prefunding,
        ];
    }

    /**
     * Return list of cases that are considered internal buy types
     * and should be excluded when calculating shares circulating
     * @return TradeOrderType[]
     */
    public static function internalBuyTypes(): array
    {
        return [
            // Effectively a share cancellation, need to exclude otherwise
            // it nullifies the buyback-sell resulting in no share cancellations
            TradeOrderType::BuyBack,
            // Proxy for an existing Buy, so don't want to double count
            TradeOrderType::Proxy,
        ];
    }

    /**
     * Return list of cases that are considered internal sell types
     * and should be excluded when calculating shares circulating
     * @return TradeOrderType[]
     */
    public static function internalSellTypes(): array
    {
        return [
            // Effectively a share issuance, need to exclude otherwise
            // it nullifies all shares in first-party investments resulting in no share issuances
            TradeOrderType::Initial,
        ];
    }

    /**
     * Return list of cases that are considered buys when calculating shares circulating
     * Basically the inverse of internalBuyTypes
     * @return TradeOrderType[]
     */
    public static function circulatingBuyTypes(): array
    {
        return array_udiff(
            self::cases(),
            TradeOrderType::internalBuyTypes(),
            fn($r1, $r2) => $r1->value <=> $r2->value,
        );
    }

    /**
     * Return list of cases that are considered sells when calculating shares circulating
     * Basically the inverse of internalSellTypes
     * @return TradeOrderType[]
     */
    public static function circulatingSellTypes(): array
    {
        return array_udiff(
            self::cases(),
            TradeOrderType::internalSellTypes(),
            fn($r1, $r2) => $r1->value <=> $r2->value,
        );
    }
}
