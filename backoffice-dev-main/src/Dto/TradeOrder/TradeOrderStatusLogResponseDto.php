<?php

namespace App\Dto\TradeOrder;

use App\Entity\Enum\TradeOrderStatus;

readonly class TradeOrderStatusLogResponseDto
{
    public function __construct(
        public ?string $id = null,
        public ?string $tradeOrderId = null,
        public ?TradeOrderStatus $status = null,
        public ?string $notes = null,
        public ?\DateTime $occuredAt = null,
    ) {}
}
