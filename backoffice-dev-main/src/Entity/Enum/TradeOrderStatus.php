<?php

namespace App\Entity\Enum;

enum TradeOrderStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Active = 'active';
    case Completed = 'completed';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';

    /**
     * Return list of cases that are considered end states
     * @return TradeOrderStatus[]
     */
    public static function endStates(): array
    {
        return [
            TradeOrderStatus::Completed,
            TradeOrderStatus::Cancelled,
        ];
    }

    /**
     * Return list of cases that are able to reserve shares
     * @return TradeOrderStatus[]
     */
    public static function reservingStates(): array
    {
        return [
            TradeOrderStatus::Draft,
            TradeOrderStatus::Submitted,
            TradeOrderStatus::Active,
        ];
    }

    /**
     * Return list of cases that are considered part of trade execution
     * @return TradeOrderStatus[]
     */
    public static function tradeExecutionStates(): array
    {
        return [
            TradeOrderStatus::Active,
            TradeOrderStatus::Completed,
        ];
    }

    /**
     * Return list of cases that is not considered cancelled
     * @return TradeOrderStatus[]
     */
    public static function nonCancelledStates(): array
    {
        return array_udiff(
            self::cases(),
            [TradeOrderStatus::Cancelled],
            fn($r1, $r2) => $r1->value <=> $r2->value,
        );
    }
}
