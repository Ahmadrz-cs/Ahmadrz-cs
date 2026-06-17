<?php

namespace App\Dto;

use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

final class InvestmentPostDTO extends InvestmentDTO
{
    #[JMS\Type('int')]
    #[Assert\NotBlank]
    protected ?int $offeringId;

    #[JMS\Type('int')]
    #[Assert\NotBlank]
    protected ?int $numberOfShares;

    #[JMS\Type('int')]
    protected ?int $sharesToKeep = null;

    #[JMS\Type('int')]
    protected ?int $prefundingId = null;

    public function __construct(
        int $offeringId,
        int $numberOfShares,
        ?string $type,
        ?int $userId,
        ?int $sharesToKeep,
        ?int $prefundingId,
        ?string $currency,
        ?string $status,
    ) {
        $this->offeringId = $offeringId;
        $this->numberOfShares = $numberOfShares;
        $this->type = $type;
        $this->userId = $userId;
        $this->sharesToKeep = $sharesToKeep;
        $this->prefundingId = $prefundingId;
        $this->currency = $currency;
        $this->status = $status;
    }

    public function getOfferingId(): int
    {
        return $this->offeringId;
    }

    public function getNumberOfShares(): int
    {
        return $this->numberOfShares;
    }

    public function getSharesToKeep(): ?int
    {
        return $this->sharesToKeep;
    }

    public function getPrefundingId(): ?int
    {
        return $this->prefundingId;
    }
}
