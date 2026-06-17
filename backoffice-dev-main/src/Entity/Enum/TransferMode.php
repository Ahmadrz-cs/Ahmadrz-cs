<?php

namespace App\Entity\Enum;

enum TransferMode: int
{
    case Default = 0;
    case Settlement = 1;
    case StampDuty = 2;
}
