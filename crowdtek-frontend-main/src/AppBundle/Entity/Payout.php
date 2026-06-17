<?php

namespace AppBundle\Entity;

use AppBundle\Entity\Enum\PayoutType;

class Payout
{
    public function __construct(
        public ?string $id = null,
        public ?string $userId = null,
        public ?string $assetId = null,
        public ?string $assetName = null,
        public ?string $shares = null,
        public ?string $value = null,
        public ?PayoutType $type = null,
        public ?\DateTimeInterface $createdAt = null,
        public ?\DateTimeInterface $updatedAt = null,
    ) {}
}
