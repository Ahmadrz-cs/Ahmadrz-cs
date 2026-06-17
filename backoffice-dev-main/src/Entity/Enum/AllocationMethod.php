<?php

namespace App\Entity\Enum;

enum AllocationMethod: string
{
    case Accrue = 'accrue';
    case Distribute = 'distribute';
}
