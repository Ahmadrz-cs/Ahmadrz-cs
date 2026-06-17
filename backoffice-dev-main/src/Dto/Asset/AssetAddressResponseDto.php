<?php

namespace App\Dto\Asset;

readonly class AssetAddressResponseDto
{
    public function __construct(
        public ?string $assetId = null,
        public ?string $address1 = null,
        public ?string $address2 = null,
        public ?string $address3 = null,
        public ?string $city = null,
        public ?string $postCode = null,
        public ?string $country = null,
        public ?string $latitude = null,
        public ?string $longitude = null,
        // public ?\DateTimeInterface $createdAt = null,
        // public ?\DateTimeInterface $updatedAt = null,
    ) {}
}
