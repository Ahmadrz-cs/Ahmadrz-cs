<?php

namespace App\Dto\TradeOrder;

use App\Entity\Enum\TradeOrderStatus;
use Symfony\Component\Validator\Constraints as Assert;

readonly class TradeOrderStatusLogRequestDto
{
    public function __construct(
        #[Assert\NotBlank(groups: ['create'])]
        public ?TradeOrderStatus $status = null,
        #[Assert\Length(max: 240)]
        public ?string $notes = null,
        #[Assert\NotBlank(groups: ['create'])]
        public ?\DateTime $occuredAt = null,
    ) {}
}
