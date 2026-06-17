<?php

namespace App\Entity\Enum;

enum TransferType: string
{
    case AssetIncomeProcessing = 'asset income processing';
    case FeeCollection = 'fee collection';
    case InvestmentSettlement = 'investment settlement';
    case PaymentAllocation = 'payment allocation';
    case IncomeDisaggregation = 'income disaggregation';
    case Custom = 'custom';
}
