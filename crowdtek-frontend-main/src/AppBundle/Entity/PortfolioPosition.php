<?php

namespace AppBundle\Entity;

class PortfolioPosition
{
    public function __construct(
        public ?string $assetId = null,
        public ?string $assetName = null,
        public ?string $assetYield = null,
        public ?string $averagePrice = null,
        public ?string $shares = null,
        public ?string $value = null,
        public ?string $dividends = null,
        public ?string $capitalGains = null,
        public ?string $buyShares = null,
        public ?string $buyValue = null,
        public ?string $sellShares = null,
        public ?string $sellValue = null,
        public ?string $sharesAvailable = null,
    ) {}
}
