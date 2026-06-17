<?php

namespace App\Entity\Enum;

enum PaymentType: string
{
    case Dividend = 'Dividend';
    case Repayment = 'Repayment';
    case Divestment = 'Divestment';
    case InvestmentExit = 'Investment Exit';
    case Liquidation = 'Liquidation';
}
