<?php

namespace App\Entity\Enum;

enum TradeDirection: int
{
    case Buy = 1;
    case Sell = -1;

    public function opposite(): ?TradeDirection
    {
        return match ($this) {
            TradeDirection::Buy => TradeDirection::Sell,
            TradeDirection::Sell => TradeDirection::Buy,
            default => null,
        };
    }

    /**
     * Return list of TradeOrderTypes that are suitable trading types for the counterparty of a given direction
     * @return TradeOrderType[]
     */
    public function counterpartyTradingTypes(): array
    {
        return match ($this) {
            TradeDirection::Buy => TradeOrderType::tradingSellTypes(),
            TradeDirection::Sell => TradeOrderType::tradingBuyTypes(),
        };
    }
}
