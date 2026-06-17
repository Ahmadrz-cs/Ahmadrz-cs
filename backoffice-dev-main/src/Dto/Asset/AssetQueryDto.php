<?php

namespace App\Dto\Asset;

use App\Entity\Enum\AssetStatus;
use App\Entity\Enum\Visibility;
use Symfony\Component\Validator\Constraints as Assert;

readonly class AssetQueryDto
{
    /**
     * @param AssetStatus[] $status
     */
    public function __construct(
        #[Assert\Regex('/^[[:alnum:]-]*$/')]
        public ?string $id = null,
        #[Assert\Length(max: 80)]
        public ?string $name = null,
        #[Assert\Regex('/^[[:alpha:]-]*$/')]
        public ?string $type = null,
        public ?bool $featured = null,
        public array $status = [AssetStatus::Active, AssetStatus::Closing],
        public Visibility $visibility = Visibility::Auto,
    ) {}
}
