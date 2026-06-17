<?php

namespace App\Dto\Sca;

/**
 * Part of SCA flows where an action may require SCA verification
 */
readonly class ScaActionResponseDto
{
    public function __construct(
        public ?string $id = null,
        public ?string $object = null,
        public ?string $status = null,
        public ?string $providerId = null,
        public ?string $providerStatus = null,
        public ?array $pendingUserAction = null,
    ) {}
}
