<?php

namespace App\Entity\Enum;

enum OrderStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Closed = 'closed';
    case Abandoned = 'abandoned';
}
