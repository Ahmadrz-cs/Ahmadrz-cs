<?php

namespace App\Entity\Enum;

enum TaskStatus: string
{
    case Pending = 'pending';
    case Started = 'started';
    case Completed = 'completed';
    case Skipped = 'skipped';
}
