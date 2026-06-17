<?php

namespace AppBundle\Entity;

class ScaAction
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
