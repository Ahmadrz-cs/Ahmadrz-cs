<?php

namespace App\Dto;

use App\Entity\Enum\BankAccountHolderType;
use Symfony\Component\Validator\Constraints as Assert;

class BankAccountRegistrationDto
{
    public function __construct(
        #[Assert\Country]
        public readonly string $country,
        public readonly BankAccountHolderType $accountHolderType,
        public readonly string $accountNumber,
        public readonly ?string $bic = null,
    ) {}
}
