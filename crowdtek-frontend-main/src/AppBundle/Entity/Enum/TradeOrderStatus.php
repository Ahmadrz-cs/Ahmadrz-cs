<?php

namespace AppBundle\Entity\Enum;

enum TradeOrderStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Active = 'active';
    case Completed = 'completed';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';

    /**
     * Return list of cases that are considered open (contributes to shares being sold)
     * @return TradeOrderStatus[]
     */
    public static function openStates(): array
    {
        return [
            TradeOrderStatus::Draft,
            TradeOrderStatus::Submitted,
            TradeOrderStatus::Active,
            TradeOrderStatus::Suspended,
        ];
    }
}
