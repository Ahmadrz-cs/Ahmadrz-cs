<?php

namespace AppBundle\Entity\Enum;

enum BankAccountHolderType: string
{
    case Personal = 'personal';
    case Business = 'business';
}
