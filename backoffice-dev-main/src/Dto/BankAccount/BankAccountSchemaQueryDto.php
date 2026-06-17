<?php

namespace App\Dto\BankAccount;

use Symfony\Component\Validator\Constraints as Assert;

readonly class BankAccountSchemaQueryDto
{
    public function __construct(
        #[Assert\Country]
        public ?string $country = null,
    ) {}
}
