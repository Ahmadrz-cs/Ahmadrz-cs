<?php

namespace App\Dto\Sca;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Part of SCA flows where frontend wants to indicate the outcome of an SCA action
 */
readonly class ScaOutcomeRequestDto
{
    public function __construct(
        #[Assert\NotNull]
        public ?bool $success = null,
        // What the payment was for
        // Can be used to affect behaviour, e.g. for prefunding split investments
        public ?string $type = null,
        // Should the claimed success be verified with Mangopay
        // Disable if the verification has been completed already
        public bool $verify = true,
    ) {}
}
