<?php

namespace AppBundle\Entity;

class ScaOutcome
{
    public function __construct(
        public ?string $id = null,
        public ?string $object = null,
        public ?string $status = null,
        public ?string $providerId = null,
        public ?bool $success = null,
    ) {}
}
