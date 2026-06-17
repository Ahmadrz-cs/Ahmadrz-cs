<?php

namespace App\Dto\TradeOrder;

use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use Symfony\Component\Validator\Constraints as Assert;

readonly class TradeOrderQueryDto
{
    /**
     * @param TradeOrderStatus[] $status
     * @param TradeOrderType[] $type
     */
    public function __construct(
        #[Assert\Regex('/^[[:alnum:]-]*$/')]
        public ?string $id = null,
        #[Assert\Regex('/^[[:alnum:]-]*$/')]
        public ?string $assetId = null,
        #[Assert\Regex('/^[[:alnum:]-]*$/')]
        public ?string $userId = null,
        public ?TradeDirection $direction = null,
        public array $status = [TradeOrderStatus::Active],
        public array $type = [TradeOrderType::Initial, TradeOrderType::Market],
        public ?bool $excludeOwn = false,
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
