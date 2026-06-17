<?php

namespace App\Entity\Enum;

enum ProductMode: string
{
    case Retail = 'retail';
    case Prefunding = 'prefunding';
}
