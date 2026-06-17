<?php

namespace App\Dto\Portfolio;

use BcMath\Number;

readonly class PortfolioPositionResponseDto
{
    public function __construct(
        public ?string $assetId = null,
        public ?string $assetName = null,
        public ?string $assetYield = null,
        public ?string $assetTermRemaining = null,
        public ?Number $averagePrice = null,
        public ?Number $shares = null,
        public ?Number $value = null,
        public ?Number $dividends = null,
        public ?Number $capitalGains = null,
        public ?Number $buyShares = null,
        public ?Number $buyValue = null,
        public ?Number $sellShares = null,
        public ?Number $sellValue = null,
        public ?Number $sharesAvailable = null,
    ) {}
}
