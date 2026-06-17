<?php

namespace App\Dto\Asset;

use Symfony\Component\Validator\Constraints as Assert;

readonly class AssetRequestDto
{
    public function __construct(
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Length(max: 80)]
        public ?string $name = null,
        #[Assert\PositiveOrZero]
        #[Assert\Type(type: ['numeric'])]
        public ?string $pricePerShare = null,
        #[Assert\PositiveOrZero]
        public ?int $numberOfShares = null,
        public ?\DateTimeInterface $termStart = null,
        #[Assert\PositiveOrZero]
        public ?int $termLength = null,
        #[Assert\PositiveOrZero]
        #[Assert\Type(type: ['numeric'])]
        public ?string $netProjectedIncome = null,
        #[Assert\PositiveOrZero]
        #[Assert\Type(type: ['numeric'])]
        public ?string $netProjectedYield = null,
    ) {}
}
