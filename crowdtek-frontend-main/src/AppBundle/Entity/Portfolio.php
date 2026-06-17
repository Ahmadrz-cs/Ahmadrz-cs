<?php

namespace AppBundle\Entity;

class Portfolio
{
    /**
     * @param PortfolioPosition[] $positions
     */
    public function __construct(
        public ?string $userId = null,
        public ?string $value = null,
        public ?string $dividends = null,
        public ?string $capitalGains = null,
        public array $positions = [],
    ) {}
}
