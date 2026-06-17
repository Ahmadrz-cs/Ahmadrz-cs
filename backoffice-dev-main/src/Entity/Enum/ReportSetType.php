<?php

namespace App\Entity\Enum;

enum ReportSetType: string
{
    case WalletTransaction = 'wallet transaction';
    case Custom = 'custom';
}
