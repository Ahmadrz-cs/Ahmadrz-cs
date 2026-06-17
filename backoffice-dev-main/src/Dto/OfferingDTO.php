<?php

namespace App\Dto;

use App\Dto\Traits\OfferingTrait;
use App\Validator as CommonAssert;
use JMS\Serializer\Annotation as JMS;

abstract class OfferingDTO
{
    use OfferingTrait;

    #[JMS\Type('string')]
    protected ?string $name = null;

    #[JMS\Type('int')]
    protected ?int $assetId = null;

    #[JMS\Type('double')]
    protected ?float $fundingGoal = null;

    #[JMS\Type('double')]
    protected ?float $externalCommitments = null;

    #[JMS\Type('bool')]
    protected ?bool $isFeatured = null;

    #[JMS\Type('int')]
    protected ?int $numberOfShares = null;

    #[JMS\Type('double')]
    protected ?float $pricePerShare = null;

    #[JMS\Type('double')]
    protected ?float $netAnnualYield = null;

    #[JMS\Type('double')]
    protected ?float $netTotalReturn = null;

    #[JMS\Type('double')]
    protected ?float $minCommit = null;

    #[JMS\Type('double')]
    protected ?float $maxCommit = null;

    #[JMS\Type('string')]
    #[CommonAssert\LifecycleStatus]
    protected ?string $status = null;

    public function __construct(
        ?string $name,
        ?int $assetId,
        ?float $fundingGoal,
        ?float $externalCommitments,
        ?bool $isFeatured,
        ?float $numberOfShares,
        ?float $pricePerShare,
        ?float $netAnnualYield,
        ?float $netTotalReturn,
        ?float $minCommit,
        ?float $maxCommit,
        ?string $status,
    ) {
        $this->name = $name;
        $this->assetId = $assetId;
        $this->fundingGoal = $fundingGoal;
        $this->externalCommitments = $externalCommitments;
        $this->isFeatured = $isFeatured;
        $this->numberOfShares = $numberOfShares;
        $this->pricePerShare = $pricePerShare;
        $this->netAnnualYield = $netAnnualYield;
        $this->netTotalReturn = $netTotalReturn;
        $this->minCommit = $minCommit;
        $this->maxCommit = $maxCommit;
        $this->status = $status;
    }
}
