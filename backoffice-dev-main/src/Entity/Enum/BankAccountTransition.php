<?php

namespace App\Entity\Enum;

enum BankAccountTransition: string
{
    case Approve = 'approve';
    case Unapprove = 'unapprove';
    case Validate = 'validate';
    case Reject = 'reject';
    case Enable = 'enable';
    case Disable = 'disable';
    case Reopen = 'reopen';
}
