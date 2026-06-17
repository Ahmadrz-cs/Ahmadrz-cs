<?php

namespace App\Dto\Payment;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Used LinkedPayment if the entity to link is specified in the url/route.
 * This DTO does not support specifying an entity ID to link to in the request body.
 */
readonly class LinkedPaymentRequestDto
{
    public function __construct(
        #[Assert\Positive]
        #[Assert\Type(type: ['numeric'])]
        public ?string $amount = null,
        public ?bool $sca = null,
    ) {}
}
