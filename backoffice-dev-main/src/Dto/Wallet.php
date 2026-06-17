<?php

namespace App\Dto;

use DateTime;
use JMS\Serializer\Annotation as JMS;

#[JMS\ExclusionPolicy('all')]
final class Wallet
{
    #[JMS\Expose]
    #[JMS\Groups(['standard'])]
    #[JMS\Type('int')]
    protected $id;

    #[JMS\Expose]
    #[JMS\Groups(['standard'])]
    #[JMS\Type('DateTime')]
    protected $creationDate;

    #[JMS\Expose]
    #[JMS\Groups(['standard'])]
    #[JMS\Type('string')]
    protected $currency;

    #[JMS\Expose]
    #[JMS\Groups(['standard'])]
    #[JMS\Type('int')]
    protected $balance;

    public function __construct(
        string $id,
        DateTime $creationDate,
        string $currency,
        int $balance,
    ) {
        $this->id = $id;
        $this->creationDate = $creationDate;
        $this->currency = $currency;
        $this->balance = $balance;
    }
}
