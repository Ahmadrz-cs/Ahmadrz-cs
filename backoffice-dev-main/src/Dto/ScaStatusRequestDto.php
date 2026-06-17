<?php

namespace App\Dto;

use App\Entity\Enum\ScaStatus;
use Symfony\Component\Validator\Constraints as Assert;

readonly class ScaStatusRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        public ?ScaStatus $status = null,
    ) {}
}
