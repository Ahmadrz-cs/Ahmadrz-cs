<?php

namespace AppBundle\Entity;

use AppBundle\Entity\Enum\BankAccountHolderType;
use AppBundle\Entity\Enum\BankAccountStatus;

class BankAccount
{
    public ?string $id = null;
    public ?string $uuid = null;
    public ?string $userId = null;
    public ?string $country = null;
    public ?string $currency = null;
    public ?BankAccountHolderType $accountHolderType = null;
    public ?string $accountNumber = null;
    public ?string $bic = null;
    public ?string $method = null;
    public ?BankAccountStatus $status = null;
    public ?string $displayName = null;
    public ?string $providerId = null;
    public ?string $description = null;
    public ?array $metadata = null;
    public ?\DateTime $createdAt = null;
    public ?\DateTime $updatedAt = null;
}
