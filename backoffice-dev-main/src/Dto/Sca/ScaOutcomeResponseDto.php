<?php

namespace App\Dto\Sca;

/**
 * Part of SCA flows where frontend wants to indicate the outcome of an SCA action
 */
readonly class ScaOutcomeResponseDto
{
    public function __construct(
        public ?string $id = null,
        public ?string $object = null,
        public ?string $status = null,
        public ?string $providerId = null,
        public ?bool $success = null,
    ) {}
}
