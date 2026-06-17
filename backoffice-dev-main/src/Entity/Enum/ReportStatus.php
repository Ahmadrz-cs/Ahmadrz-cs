<?php

namespace App\Entity\Enum;

enum ReportStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Available = 'available';
    case Cancelled = 'cancelled';
}
