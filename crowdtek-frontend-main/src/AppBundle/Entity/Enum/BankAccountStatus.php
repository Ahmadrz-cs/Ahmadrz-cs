<?php

namespace AppBundle\Entity\Enum;

enum BankAccountStatus: string
{
    case Pending = 'pending'; // New bank accounts are in this status
    case Validated = 'validated'; // Schema validated with payment/wallet provider
    case Approved = 'approved'; // Staff have approved the bank account
    case Active = 'active'; // Bank account created on payment/wallet provider
    case Closed = 'closed'; // Staff or payment/wallet provider have disabled the bank account
    case Rejected = 'rejected'; // Staff or payment/wallet provider have rejected the bank account application
}
