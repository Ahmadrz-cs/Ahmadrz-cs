<?php

namespace AppBundle\Entity\Enum;

enum BankAccountFormatType: string
{
    case GB = 'gb';
    case IBAN = 'iban';
}
