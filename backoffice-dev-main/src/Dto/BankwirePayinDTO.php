<?php

namespace App\Dto;

use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

class BankwirePayinDTO
{
    #[JMS\Type('int')]
    #[Assert\NotBlank]
    protected $amount;

    #[JMS\Type('bool')]
    protected $notification = false;

    public function __construct(int $amount, ?bool $notification)
    {
        $this->amount = $amount;
        $this->notification = $notification;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function getNotification()
    {
        return $this->notification;
    }
}
