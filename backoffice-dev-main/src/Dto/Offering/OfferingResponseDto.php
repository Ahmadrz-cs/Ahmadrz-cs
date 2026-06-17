<?php

namespace App\Dto\Offering;

readonly class OfferingResponseDto
{
    public function __construct(
        public ?string $id = null,
        public ?string $assetId = null,
        public ?string $investmentId = null,
        public ?string $name = null,
        public ?string $type = null,
        public ?string $pricePerShare = null,
        public ?int $numberOfShares = null,
        public ?int $numberOfSharesSold = null,
        public ?bool $featured = null,
        public ?string $status = null,
        public ?\DateTimeInterface $createdAt = null,
        public ?\DateTimeInterface $updatedAt = null,
        // public ?Visibility $visibility = null,
    ) {}
}
