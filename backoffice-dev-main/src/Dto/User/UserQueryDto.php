<?php

namespace App\Dto\User;

use Symfony\Component\Validator\Constraints as Assert;

readonly class UserQueryDto
{
    public function __construct(
        public ?string $id = null,
        #[Assert\Length(max: 140)]
        public ?string $username = null,
    ) {}
}
