<?php

namespace App\Dto\Payout;

use App\Entity\Enum\PayoutType;
use BcMath\Number;

readonly class PayoutResponseDto
{
    public function __construct(
        public ?string $id = null,
        public ?string $userId = null,
        public ?string $assetId = null,
        public ?string $assetName = null,
        public ?Number $shares = null,
        public ?Number $value = null,
        public ?PayoutType $type = null,
        public ?\DateTimeInterface $createdAt = null,
        public ?\DateTimeInterface $updatedAt = null,
    ) {}
}
