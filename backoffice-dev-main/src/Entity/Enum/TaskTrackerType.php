<?php

namespace App\Entity\Enum;

enum TaskTrackerType: string
{
    case Monthend = 'monthend';
    case AssetMonthend = 'asset monthend';
    case CardCleanup = 'card_cleanup';
    case KycReportCheck = 'kyc_report_check';
}
