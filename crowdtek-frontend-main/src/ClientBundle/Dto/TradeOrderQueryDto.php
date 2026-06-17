<?php

namespace ClientBundle\Dto;

use AppBundle\Entity\Enum\TradeDirection;
use AppBundle\Entity\Enum\TradeOrderStatus;
use AppBundle\Entity\Enum\TradeOrderType;

class TradeOrderQueryDto
{
    /**
     * @param TradeOrderStatus[] $status
     * @param TradeOrderType[] $type
     */
    public function __construct(
        public ?string $id = null,
        public ?string $assetId = null,
        public ?string $userId = null,
        public ?TradeDirection $direction = null,
        public array $status = [TradeOrderStatus::Active],
        public array $type = [TradeOrderType::Initial, TradeOrderType::Market],
        public ?bool $excludeOwn = false,
        public ?\DateTime $createdAt_gte = null,
        public ?\DateTime $createdAt_lt = null,
    ) {}
}
