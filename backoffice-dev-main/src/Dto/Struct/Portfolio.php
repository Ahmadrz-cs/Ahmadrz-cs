<?php

namespace App\Dto\Struct;

use BcMath\Number;

class Portfolio
{
    /**
     * @param PortfolioPosition[] $positions
     */
    public function __construct(
        public ?string $userId = null,
        public ?Number $value = new Number('0.00'),
        public ?Number $dividends = new Number('0.00'),
        public ?Number $capitalGains = new Number('0.00'),
        public array $positions = [],
    ) {}
}
