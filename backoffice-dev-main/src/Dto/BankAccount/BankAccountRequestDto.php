<?php

namespace App\Dto\BankAccount;

use App\Entity\Enum\BankAccountHolderType;
use Symfony\Component\Validator\Constraints as Assert;

readonly class BankAccountRequestDto
{
    public function __construct(
        #[Assert\Country]
        #[Assert\NotBlank(groups: ['create'])]
        public ?string $country = null,
        #[Assert\Length(max: 34)]
        #[Assert\NotBlank(groups: ['create'])]
        public ?string $accountNumber = null,
        #[Assert\Length(max: 11)]
        #[Assert\NotBlank(groups: ['create'])]
        public ?string $bic = null,
        public ?BankAccountHolderType $accountHolderType = BankAccountHolderType::Personal,
        public ?string $description = null,
    ) {}
}
