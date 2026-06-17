<?php

namespace App\Entity\Enum;

enum ScaStatus: string
{
    case Inactive = 'inactive';
    case Pending = 'pending';
    case Active = 'active';
}
