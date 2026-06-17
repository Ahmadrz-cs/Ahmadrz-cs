<?php

namespace App\Dto\User;

use Symfony\Component\Validator\Constraints as Assert;

readonly class UserRequestDto
{
    public function __construct(
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Email]
        #[Assert\Length(max: 140)]
        public ?string $username = null, // ignored outside of creation by mapper
        #[Assert\NotBlank(groups: ['create'])]
        #[Assert\Length(min: 8)]
        public ?string $password = null, // ignored outside of creation by mapper
        #[Assert\Email]
        #[Assert\Length(max: 140)]
        public ?string $contactEmail = null,
        #[Assert\Length(max: 80)]
        public ?string $firstName = null,
        #[Assert\Length(max: 80)]
        public ?string $lastName = null,
        #[Assert\Length(max: 160)]
        public ?string $middleNames = null,
    ) {}
}
