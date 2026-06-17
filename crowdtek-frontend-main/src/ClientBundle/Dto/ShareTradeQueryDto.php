<?php

namespace ClientBundle\Dto;

use AppBundle\Entity\Enum\TradeOrderType;
use AppBundle\Entity\Enum\TradeStatus;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

class ShareTradeQueryDto
{
    /**
     * @param TradeOrderType[] $buyOrderType
     * @param TradeOrderType[] $sellOrderType
     * @param TradeStatus[] $status
     */
    public function __construct(
        public ?string $id = null,
        public ?Uuid $uuid = null,
        public ?string $assetId = null,
        public ?string $userId = null,
        public ?string $buyerId = null,
        public ?string $sellerId = null,
        public ?string $buyOrderId = null,
        public ?string $sellOrderId = null,
        public array $buyOrderType = [],
        public array $sellOrderType = [],
        public array $status = [TradeStatus::Settled],
        #[Assert\PositiveOrZero]
        public int $page = 1,
        #[Assert\PositiveOrZero]
        public int $perPage = 10,
        // #[Assert\Regex('/^[A-Za-z]+/')]
        // public string $sortBy = 'id',
        // #[Assert\Choice(choices: ['ASC', 'DESC'])]
        // public string $sortDirection = 'DESC',
        public ?\DateTime $createdAt_gte = null,
        public ?\DateTime $createdAt_lt = null,
    ) {}
}
