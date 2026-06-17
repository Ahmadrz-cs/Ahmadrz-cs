<?php

namespace AppBundle\Entity;

use AppBundle\Entity\Enum\AssetStatus;
use AppBundle\Entity\Enum\Visibility;

class AssetProduct
{
    /**
     * @param RelationalDocument[] $documents
     */
    public function __construct(
        public ?string $id = null,
        public ?string $name = null,
        /** Note that SPV000123 is the company name, rather than the companies house company number */
        public ?string $companyName = null,
        public ?string $description = null,
        public ?string $pricePerShare = null,
        public ?int $numberOfShares = null,
        public ?int $sharesAvailable = null,
        public ?string $type = null,
        public ?AssetStatus $status = null,
        public ?\DateTimeInterface $statusOccuredAt = null,
        public ?\DateTimeInterface $termStart = null,
        public ?\DateTimeInterface $termEnd = null,
        public ?int $termRemaining = null,
        public ?int $termLength = null,
        public ?string $netProjectedIncome = null,
        public ?string $netProjectedYield = null,
        public ?int $featured = null,
        public ?bool $buyRestricted = null,
        public ?bool $sellRestricted = null,
        public ?Visibility $visibility = null,
        public array $documents = [],
        public ?AssetAddress $address = null,
        public array $fees = [],
        public ?\DateTimeInterface $createdAt = null,
        public ?\DateTimeInterface $updatedAt = null,
    ) {}
}
