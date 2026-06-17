<?php

namespace AppBundle\Entity\Enum;

enum TradeStatus: string
{
    case Draft = 'draft';
    case Unsettled = 'unsettled';
    case Settled = 'settled';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';
}
