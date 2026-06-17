<?php

namespace App\Dto\Investment;

readonly class InvestmentResponseDto
{
    public function __construct(
        public ?string $id = null,
        public ?string $userId = null,
        public ?string $offeringId = null,
        public ?string $type = null,
        public ?string $pricePerShare = null,
        public ?int $numberOfShares = null,
        public ?string $transactionId = null,
        public ?string $status = null,
        public ?\DateTimeInterface $createdAt = null,
        public ?\DateTimeInterface $updatedAt = null,
        // public ?Visibility $visibility = null,
    ) {}
}
