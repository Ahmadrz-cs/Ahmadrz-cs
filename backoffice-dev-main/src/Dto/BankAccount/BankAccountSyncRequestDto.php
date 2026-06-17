<?php

namespace App\Dto\BankAccount;

use Symfony\Component\Validator\Constraints as Assert;

readonly class BankAccountSyncRequestDto
{
    public function __construct(
        #[Assert\Range(
            min: 1,
            max: 5,
            notInRangeMessage: 'Limit must be between {{ min }} and {{ max }}',
        )]
        public ?int $limit = 5,
        public ?bool $force = false,
    ) {}
}
