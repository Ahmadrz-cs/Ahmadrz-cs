<?php

namespace App\Entity\Enum;

enum ShareTradeType: string
{
    case FirstParty = 'first_party';
    case SecondaryMarket = 'secondary_market';
    case Prefunding = 'prefunding';
    case Divestment = 'divestment';
    case Repayment = 'repayment';

    /**
     * Return list of TradeOrderTypes that are valid for the buy side of a ShareTradeType
     * @return TradeOrderType[]
     */
    public function validBuyTypes(): array
    {
        return match ($this) {
            ShareTradeType::FirstParty => TradeOrderType::retailBuyTypes(),
            ShareTradeType::SecondaryMarket => TradeOrderType::retailBuyTypes(),
            ShareTradeType::Prefunding => [TradeOrderType::Prefunding],
            ShareTradeType::Divestment => [TradeOrderType::BuyBack],
            ShareTradeType::Repayment => [TradeOrderType::Proxy],
            default => [],
        };
    }

    /**
     * Return list of TradeOrderTypes that are valid for the sell side of a ShareTradeType
     * @return TradeOrderType[]
     */
    public function validSellTypes(): array
    {
        return match ($this) {
            ShareTradeType::FirstParty => [TradeOrderType::Initial],
            ShareTradeType::SecondaryMarket => TradeOrderType::marketTradingTypes(),
            ShareTradeType::Prefunding => [TradeOrderType::Initial],
            ShareTradeType::Divestment => [TradeOrderType::BuyBack],
            ShareTradeType::Repayment => [TradeOrderType::Prefunding],
            default => [],
        };
    }

    public static function fromBuySellTypes(
        ?TradeOrderType $buyType,
        ?TradeOrderType $sellType,
    ): ?ShareTradeType {
        if ($sellType === null || $buyType === null) {
            return null;
        }
        foreach (ShareTradeType::cases() as $type) {
            if (
                in_array($buyType, $type->validBuyTypes())
                && in_array($sellType, $type->validSellTypes())
            ) {
                return $type;
            }
        }
        return null;
    }
}
