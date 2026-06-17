<?php

namespace App\Dto;

use App\Entity\Enum\KycReviewStatus;
use Symfony\Component\Validator\Constraints as Assert;

readonly class KycReviewRequestDto
{
    public function __construct(
        public ?KycReviewStatus $status = null,
    ) {}
}
