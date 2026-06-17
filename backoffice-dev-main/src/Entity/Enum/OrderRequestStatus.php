<?php

namespace App\Entity\Enum;

enum OrderRequestStatus: string
{
    case Pending = 'pending';
    case Failed = 'failed';
    case Completed = 'completed';
}
