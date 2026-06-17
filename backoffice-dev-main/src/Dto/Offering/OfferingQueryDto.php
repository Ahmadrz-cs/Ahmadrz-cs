<?php

namespace App\Dto\Offering;

use Symfony\Component\Validator\Constraints as Assert;

readonly class OfferingQueryDto
{
    public function __construct(
        public ?string $id = null,
        #[Assert\Length(max: 80)]
        public ?string $name = null,
        #[Assert\Choice([
            'draft',
            'submitted',
            'rejected',
            'approved',
            'restricted',
            'published',
            'closed',
            'settled',
            'cancelled',
        ])]
        public ?string $status = null,
    ) {}
}
