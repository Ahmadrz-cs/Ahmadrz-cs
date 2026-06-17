<?php

namespace App\Event\TransferOrder;

use App\Entity\TransferOrder;
use Symfony\Contracts\EventDispatcher\Event;

abstract class TransferOrderEvent extends Event
{
    public function __construct(
        protected TransferOrder $transferOrder,
    ) {}

    public function getTransferOrder()
    {
        return $this->transferOrder;
    }
}
