<?php

namespace ClientBundle\Dto;

use AppBundle\Entity\Enum\AssetStatus;
use AppBundle\Entity\Enum\Visibility;

class AssetQueryDto
{
    /**
     * @param AssetStatus[] $status
     */
    public function __construct(
        public ?string $id = null,
        public ?string $name = null,
        public ?string $type = null,
        public ?bool $featured = null,
        public array $status = [AssetStatus::Active, AssetStatus::Closing],
        public Visibility $visibility = Visibility::Auto,
    ) {}
}
