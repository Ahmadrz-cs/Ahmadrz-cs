<?php

namespace App\Dto\BankAccount;

use App\Entity\Enum\ActionRequest;
use Symfony\Component\Validator\Constraints as Assert;

readonly class BankAccountActionCompletionRequestDto
{
    public function __construct(
        #[Assert\Choice(callback: [ActionRequest::class, 'values'], multiple: true)]
        public array $actionRequests = [],
    ) {}
}
