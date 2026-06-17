<?php

namespace App\Dto\TradeOrder;

use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use BcMath\Number;
use Symfony\Component\Validator\Constraints as Assert;

readonly class TradeOrderRequestDto
{
    public function __construct(
        #[Assert\NotBlank(groups: ['create'])]
        public ?string $assetId = null,
        // #[Assert\NotBlank(groups: ['create'])]
        public ?string $userId = null,
        public ?TradeDirection $direction = null,
        #[Assert\PositiveOrZero]
        #[Assert\NotBlank(groups: ['create'])]
        public ?Number $pricePerShare = null,
        #[Assert\PositiveOrZero]
        #[Assert\NotBlank(groups: ['create'])]
        public ?int $numberOfShares = null,
        #[Assert\PositiveOrZero]
        public ?int $minimumShares = null,
        #[Assert\PositiveOrZero]
        public ?int $maximumShares = null,
        #[Assert\Length(max: 240)]
        public ?string $notes = null,
        public ?Number $fees = null,
        public ?Number $taxes = null,
        #[Assert\Blank(groups: ['update'])]
        public ?TradeOrderStatus $status = null,
        public ?TradeOrderType $type = null,
        /**
         * Specify a counterparty (w.r.t direction) TradeOrder to use when matching.
         * For a BuyOrder, counterparty must be a SellOrder, and vice versa.
         * Note that this is NOT stored in the database, so must be used in conjunction
         * with $reserveShares to do anything useful.
         */
        #[Assert\Regex('/^[[:alnum:]-]*$/')]
        public ?string $counterpartyOrderId = null,
        /**
         * Toggle whether to reserve shares by generating a ShareTrade in the relevant state
         */
        public bool $reserveShares = false,
        /**
         * Used for prefunding, primarily when setting up the liquidation sell order
         */
        #[Assert\Regex('/^[[:alnum:]-]*$/')]
        public ?string $complementaryOrderId = null,
    ) {}
}
