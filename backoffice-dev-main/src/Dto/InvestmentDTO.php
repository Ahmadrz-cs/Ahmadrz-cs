<?php

namespace App\Dto;

use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

class InvestmentDTO
{
    #[JMS\Type('int')]
    protected ?int $offeringId = null;

    #[JMS\Type('int')]
    protected ?int $numberOfShares = null;

    #[JMS\Type('string')]
    #[Assert\Choice(callback: ['App\Entity\Investment', 'getInvestmentTypes'])]
    protected ?string $type = null;

    #[JMS\Type('int')]
    protected ?int $userId = null;

    #[JMS\Type('string')]
    #[Assert\Currency]
    protected ?string $currency = null;

    #[JMS\Type('string')]
    protected ?string $status = null;

    #[JMS\Type('string')]
    protected ?string $transactionId = null;

    #[JMS\Type('double')]
    protected ?int $pricePerShare = null;

    public function __construct(
        ?int $offeringId,
        ?int $numberOfShares,
        ?string $type,
        ?int $userId,
        ?string $currency,
        ?string $status,
        ?string $transactionId,
    ) {
        $this->offeringId = $offeringId;
        $this->numberOfShares = $numberOfShares;
        $this->type = $type;
        $this->userId = $userId;
        $this->currency = $currency;
        $this->status = $status;
        $this->transactionId = $transactionId;
    }

    public function getOfferingId(): ?int
    {
        return $this->offeringId;
    }

    public function getNumberOfShares(): ?int
    {
        return $this->numberOfShares;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function getPricePerShare(): ?float
    {
        return $this->pricePerShare;
    }
}
