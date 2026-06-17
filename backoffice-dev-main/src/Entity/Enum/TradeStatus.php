<?php

namespace App\Entity\Enum;

enum TradeStatus: string
{
    case Draft = 'draft';
    case Reserved = 'reserved';
    case Unsettled = 'unsettled';
    case Settled = 'settled';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';

    /**
     * Return list of statuses that are considered "payment-complete" statuses
     *
     * @return TradeStatus[]
     */
    public static function activeStatuses(): array
    {
        return [
            TradeStatus::Unsettled,
            TradeStatus::Settled,
        ];
    }

    /**
     * Return statuses that are count towards a Trade Order's fulfillment progress
     *
     * @return TradeStatus[]
     */
    public static function countedStatuses(): array
    {
        return [
            TradeStatus::Reserved,
            TradeStatus::Unsettled,
            TradeStatus::Settled,
            TradeStatus::Suspended,
        ];
    }
}
