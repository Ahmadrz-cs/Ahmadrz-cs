<?php

namespace AppBundle\Entity;

use AppBundle\Entity\Enum\ShareTradeType;
use AppBundle\Entity\Enum\TradeStatus;
use Symfony\Component\Uid\Uuid;

class ShareTrade
{
    public function __construct(
        public ?string $id = null,
        public ?Uuid $uuid = null,
        public ?string $assetId = null,
        public ?string $assetName = null,
        public ?string $sellerId = null,
        public ?string $buyerId = null,
        public ?string $pricePerShare = null,
        public ?string $numberOfShares = null,
        public ?string $tradeValue = null,
        public ?TradeStatus $status = null,
        public ?\DateTimeInterface $statusOccuredAt = null,
        public ?ShareTradeType $type = null,
        public ?\DateTimeInterface $createdAt = null,
        public ?\DateTimeInterface $updatedAt = null,
    ) {}
}
