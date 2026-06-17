<?php

namespace App\Dto\BankAccount;

use App\Entity\Enum\BankAccountHolderType;
use App\Entity\Enum\BankAccountStatus;
use Symfony\Component\Uid\Uuid;

readonly class BankAccountResponseDto
{
    public function __construct(
        public ?string $id = null,
        public ?Uuid $uuid = null,
        public ?string $userId = null,
        public ?string $country = null,
        public ?string $currency = null,
        public ?BankAccountHolderType $accountHolderType = null,
        public ?string $accountNumber = null,
        public ?string $bic = null,
        public ?string $method = null,
        public ?BankAccountStatus $status = null,
        public ?string $displayName = null,
        public ?string $providerId = null,
        public ?string $description = null,
        public ?array $metadata = null,
        public ?\DateTimeInterface $createdAt = null,
        public ?\DateTimeInterface $updatedAt = null,
    ) {}
}
