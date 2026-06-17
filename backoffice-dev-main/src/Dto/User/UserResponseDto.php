<?php

namespace App\Dto\User;

use App\Entity\Enum\UserStatus;

readonly class UserResponseDto
{
    public function __construct(
        public ?string $id = null,
        public ?string $username = null,
        public ?string $contactEmail = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $middleNames = null,
        public ?UserStatus $status = null,
        public ?\DateTimeInterface $createdAt = null,
        public ?\DateTimeInterface $updatedAt = null,
        // public ?Visibility $visibility = null,
    ) {}
}
