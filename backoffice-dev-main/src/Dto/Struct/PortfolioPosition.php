<?php

namespace App\Dto\Struct;

use App\Entity\Asset;
use BcMath\Number;

class PortfolioPosition
{
    public function __construct(
        public ?Asset $asset = null,
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
