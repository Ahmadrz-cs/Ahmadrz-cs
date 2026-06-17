<?php

namespace App\Entity\Enum;

enum KycDueDiligenceLevel: int
{
    case Standard = 1;
    case Enhanced = 2;
}
