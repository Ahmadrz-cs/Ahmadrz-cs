<?php

namespace App\Dto\Offering;

use App\Entity\Enum\ProductMode;
use Symfony\Component\Validator\Constraints as Assert;

readonly class OfferingRequestDto
{
    public function __construct(
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Length(max: 80)]
        public ?string $name = null,
        #[Assert\NotBlank(groups: ['create'])]
        public ?string $assetId = null,
        public ?string $investmentId = null,
        #[Assert\Choice([ProductMode::Retail->value, ProductMode::Prefunding->value])]
        public ?string $type = null,
        public ?bool $featured = null,
        #[Assert\Choice([
            'draft',
            'submitted',
            'rejected',
            'approved',
            'restricted',
            'published',
            'closed',
            'settled',
            'cancelled',
        ])]
        public ?string $status = null,
        #[Assert\PositiveOrZero]
        #[Assert\Type(type: ['numeric'])]
        public ?string $pricePerShare = null,
        #[Assert\PositiveOrZero]
        public ?int $numberOfShares = null,
    ) {}
}
