<?php

namespace App\Dto;

use App\Dto\BankAccount;
use JMS\Serializer\Annotation as JMS;

#[JMS\ExclusionPolicy('all')]
class BankwireDetails
{
    #[JMS\Expose]
    #[JMS\Groups(['standard'])]
    #[JMS\Type("App\Dto\BankAccount")]
    protected $bankAccount;

    #[JMS\Expose]
    #[JMS\Groups(['standard'])]
    #[JMS\Type('string')]
    protected $wireReference;

    #[JMS\Expose]
    #[JMS\Groups(['standard'])]
    #[JMS\Type('int')]
    protected $amount;

    #[JMS\Expose]
    #[JMS\Groups(['standard'])]
    #[JMS\Type('string')]
    protected $currency;

    public function __construct(
        BankAccount $bankAccount,
        string $wireReference,
        int $amount,
        string $currency,
    ) {
        $this->bankAccount = $bankAccount;
        $this->wireReference = $wireReference;
        $this->amount = $amount;
        $this->currency = $currency;
    }
}
