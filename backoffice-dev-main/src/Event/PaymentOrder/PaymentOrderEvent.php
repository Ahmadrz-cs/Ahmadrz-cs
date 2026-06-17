<?php

namespace App\Event\PaymentOrder;

use App\Entity\PaymentOrder;
use Symfony\Contracts\EventDispatcher\Event;

abstract class PaymentOrderEvent extends Event
{
    public function __construct(
        protected PaymentOrder $paymentOrder,
    ) {}

    public function getPaymentOrder()
    {
        return $this->paymentOrder;
    }
}
