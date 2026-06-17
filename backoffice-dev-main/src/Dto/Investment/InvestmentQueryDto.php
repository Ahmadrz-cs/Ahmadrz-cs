<?php

namespace App\Dto\Investment;

use Symfony\Component\Validator\Constraints as Assert;

readonly class InvestmentQueryDto
{
    public function __construct(
        public ?string $id = null,
        public ?string $userId = null,
        #[Assert\Choice(['normal', 'prefunding'])]
        public ?string $type = null,
    ) {}
}
