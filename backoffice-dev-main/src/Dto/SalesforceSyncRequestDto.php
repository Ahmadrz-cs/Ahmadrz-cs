<?php

namespace App\Dto;

readonly class SalesforceSyncRequestDto
{
    public function __construct(
        public bool $createIfMissing = true,
        public ?SalesforceSyncExtraFieldsDto $extraFields = null,
    ) {}
}
