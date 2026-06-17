<?php

namespace App\Dto\Investment;

use App\Entity\Lifecycle\InvestmentLifecycle;
use Symfony\Component\Validator\Constraints as Assert;

readonly class InvestmentRequestDto
{
    public function __construct(
        #[Assert\NotBlank(groups: ['create'])]
        public ?string $offeringId = null,
        #[Assert\NotBlank(groups: ['create'])]
        public ?string $userId = null,
        #[Assert\PositiveOrZero]
        #[Assert\Type(type: ['numeric'])]
        public ?string $pricePerShare = null,
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\PositiveOrZero]
        public ?int $numberOfShares = null,
        public ?string $transactionId = null,
        #[Assert\Choice([
            InvestmentLifecycle::STATE_OPEN,
            InvestmentLifecycle::STATE_REJECTED,
            InvestmentLifecycle::STATE_APPROVED,
            InvestmentLifecycle::STATE_WITHDRAWN,
            InvestmentLifecycle::STATE_SETTLED,
        ])]
        public ?string $status = null,
        #[Assert\Choice(['normal', 'prefunding'])]
        public ?string $type = null,
    ) {}
}
