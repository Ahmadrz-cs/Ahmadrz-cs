<?php

namespace App\Dto\Payout;

use App\Entity\Enum\PayoutType;
use Symfony\Component\Validator\Constraints as Assert;

readonly class PayoutQueryDto
{
    public function __construct(
        #[Assert\Positive]
        public ?int $assetId = null,
        #[Assert\Positive]
        public ?int $userId = null,
        public ?PayoutType $payoutType = null,
        #[Assert\PositiveOrZero]
        public int $page = 1,
        #[Assert\PositiveOrZero]
        public int $perPage = 10,
        public ?\DateTime $createdAt_gte = null,
        public ?\DateTime $createdAt_lt = null,
    ) {}
}
