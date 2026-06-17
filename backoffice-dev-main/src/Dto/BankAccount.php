<?php

namespace App\Dto;

use JMS\Serializer\Annotation as JMS;

#[JMS\ExclusionPolicy('all')]
final class BankAccount
{
    #[JMS\Expose]
    #[JMS\Groups(['standard'])]
    #[JMS\Type('string')]
    protected $ownerName;

    #[JMS\Expose]
    #[JMS\Groups(['standard'])]
    #[JMS\Type("App\Dto\AddressDTO")]
    protected $address;

    #[JMS\Expose]
    #[JMS\Groups(['standard'])]
    #[JMS\Type('string')]
    protected $iban;

    #[JMS\Expose]
    #[JMS\Groups(['standard'])]
    #[JMS\Type('string')]
    protected $bic;

    public function __construct(
        string $ownerName,
        AddressDTO $address,
        string $iban,
        string $bic,
    ) {
        $this->ownerName = $ownerName;
        $this->address = $address;
        $this->iban = $iban;
        $this->bic = $bic;
    }
}
