<?php

namespace App\Dto\ShareTrade;

use App\Entity\Enum\ShareTradeType;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TradeStatus;
use Symfony\Component\Validator\Constraints as Assert;

readonly class ShareTradeQueryDto
{
    /**
     * @param TradeOrderType[] $buyOrderType
     * @param TradeOrderType[] $sellOrderType
     * @param TradeStatus[] $status
     */
    public function __construct(
        // Alphanumeric and a dash means we support integers, alphanum strings, and uuid with - (dash)
        // Bit overkill, but flexible for future rather than just supporting int
        // More of an experiment than definitive pattern going forward
        #[Assert\Regex('/^[[:alnum:]-]*$/')]
        public ?string $assetId = null,
        #[Assert\Regex('/^[[:alnum:]-]*$/')]
        public ?string $userId = null,
        #[Assert\Regex('/^[[:alnum:]-]*$/')]
        public ?string $buyerId = null,
        #[Assert\Regex('/^[[:alnum:]-]*$/')]
        public ?string $sellerId = null,
        #[Assert\Regex('/^[[:alnum:]-]*$/')]
        public ?string $buyOrderId = null,
        #[Assert\Regex('/^[[:alnum:]-]*$/')]
        public ?string $sellOrderId = null,
        public array $buyOrderType = [],
        public array $sellOrderType = [],
        public array $status = [TradeStatus::Settled],
        public ?ShareTradeType $shareTradeType = null,
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
