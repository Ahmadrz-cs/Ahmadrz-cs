<?php

namespace AppBundle\Entity;

use AppBundle\Entity\Enum\TradeDirection;
use AppBundle\Entity\Enum\TradeOrderStatus;
use AppBundle\Entity\Enum\TradeOrderType;
use Symfony\Component\Uid\Uuid;

class TradeOrder
{
    public function __construct(
        public ?string $id = null,
        public ?Uuid $uuid = null,
        public ?string $assetId = null,
        public ?string $assetName = null,
        public ?string $userId = null,
        public ?TradeDirection $direction = null,
        public ?string $pricePerShare = null,
        public ?int $numberOfShares = null,
        public ?int $sharesTraded = null,
        public ?int $sharesAvailable = null,
        public ?TradeOrderStatus $status = null,
        public ?int $minimumShares = null,
        public ?int $maximumShares = null,
        public ?string $notes = null,
        public ?TradeOrderType $type = null,
        public ?\DateTimeInterface $createdAt = null,
        public ?\DateTimeInterface $updatedAt = null,
    ) {}
}
