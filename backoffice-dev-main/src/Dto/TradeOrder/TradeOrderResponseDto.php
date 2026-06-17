<?php

namespace App\Dto\TradeOrder;

use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use BcMath\Number;
use Symfony\Component\Uid\Uuid;

readonly class TradeOrderResponseDto
{
    public function __construct(
        public ?string $id = null,
        public ?Uuid $uuid = null,
        public ?string $assetId = null,
        public ?string $assetName = null,
        public ?string $userId = null,
        public ?TradeDirection $direction = null,
        public ?Number $pricePerShare = null,
        public ?int $numberOfShares = null,
        public ?int $sharesTraded = null,
        public ?int $sharesAvailable = null,
        public ?TradeOrderStatus $status = null,
        public ?\DateTimeInterface $statusOccuredAt = null,
        public ?int $minimumShares = null,
        public ?int $maximumShares = null,
        public ?Number $fees = null,
        public ?Number $taxes = null,
        public ?string $notes = null,
        public ?TradeOrderType $type = null,
        public ?\DateTimeInterface $createdAt = null,
        public ?\DateTimeInterface $updatedAt = null,
    ) {}
}
