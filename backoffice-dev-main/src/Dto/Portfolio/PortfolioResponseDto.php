<?php

namespace App\Dto\Portfolio;

use BcMath\Number;

readonly class PortfolioResponseDto
{
    /**
     * @param PortfolioPositionResponseDto[] $positions
     */
    public function __construct(
        public ?string $userId = null,
        public ?Number $value = null,
        public ?Number $dividends = null,
        public ?Number $capitalGains = null,
        public array $positions = [],
    ) {}
}
