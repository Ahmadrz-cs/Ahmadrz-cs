<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

readonly class OAuth2LogoutQueryDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Url]
        public ?string $continue_url = null,
    ) {}
}
