<?php

namespace App\Entity\Enum;

enum BankAccountType: string
{
    case GB = 'GB';
    case International = 'international';
    case IBAN = 'IBAN';
}
