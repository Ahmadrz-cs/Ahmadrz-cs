<?php

namespace App\Entity\Enum;

enum TransferOrderPreset: string
{
    case IncomeTransfer = 'Process asset income';
    case YieldersFees = 'Collect Yielders fees';
    case InvestmentSettlement = 'Settle investments and stamp duty';
    case PrefunderRepaymentTransfer = 'Allocate funds to repay prefunders';
    case IncomeDisaggregation = 'Split multi-asset aggregated income';
}
