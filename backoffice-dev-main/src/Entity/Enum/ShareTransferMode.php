<?php

namespace App\Entity\Enum;

enum ShareTransferMode: string
{
    case Direct = 'direct';
    case Pooled = 'pooled';
}
