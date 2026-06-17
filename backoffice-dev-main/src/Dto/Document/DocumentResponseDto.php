<?php

namespace App\Dto\Document;

readonly class DocumentResponseDto
{
    public function __construct(
        public ?string $id = null,
        public ?string $relationLinkId = null,
        public ?string $relationId = null,
        public ?string $filename = null,
        public ?string $description = null,
        public ?string $type = null,
        public ?string $tag = null,
        public ?string $path = null,
        public ?string $url = null,
        public ?\DateTimeInterface $createdAt = null,
        public ?\DateTimeInterface $updatedAt = null,
    ) {}
}
