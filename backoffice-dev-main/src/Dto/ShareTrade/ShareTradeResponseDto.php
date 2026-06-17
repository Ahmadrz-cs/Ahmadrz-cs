<?php

namespace App\Dto\ShareTrade;

use App\Entity\Enum\ShareTradeType;
use App\Entity\Enum\TradeStatus;
use BcMath\Number;
use Symfony\Component\Uid\Uuid;

readonly class ShareTradeResponseDto
{
    public function __construct(
        public ?string $id = null,
        public ?Uuid $uuid = null,
        public ?string $assetId = null,
        public ?string $assetName = null,
        public ?string $sellerId = null,
        public ?string $buyerId = null,
        public ?Number $pricePerShare = null,
        public ?Number $numberOfShares = null,
        public ?Number $tradeValue = null,
        public ?TradeStatus $status = null,
        public ?\DateTimeInterface $statusOccuredAt = null,
        public ?ShareTradeType $type = null,
        public ?\DateTimeInterface $createdAt = null,
        public ?\DateTimeInterface $updatedAt = null,
    ) {}
}
