<?php

namespace App\Dto\BankAccount;

use App\Entity\Enum\BankAccountStatus;

readonly class BankAccountQueryDto
{
    public function __construct(
        public ?string $id = null,
        public BankAccountStatus|array|null $status = null,
    ) {}
}
