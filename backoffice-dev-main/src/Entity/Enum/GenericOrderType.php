<?php

namespace App\Entity\Enum;

/**
 * Any other order types that don't fit under TransferType or PaymentType
 * Generally for cases where the order does not involve a movement of money
 * which was the original purpose of the "orders" concept
 */
enum GenericOrderType: string
{
    case ShareTransfer = 'share transfer';
}
