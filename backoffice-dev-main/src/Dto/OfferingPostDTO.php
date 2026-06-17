<?php

namespace App\Dto;

use App\Validator as CommonAssert;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

final class OfferingPostDTO extends OfferingDTO
{
    #[JMS\Type('int')]
    #[CommonAssert\Asset]
    #[Assert\NotNull]
    protected ?int $assetId;

    #[JMS\Type('int')]
    #[Assert\NotNull]
    protected ?int $numberOfShares;

    public function __construct(
        ?string $name,
        int $assetId,
        ?float $fundingGoal,
        ?float $externalCommitments,
        ?bool $isFeatured,
        float $numberOfShares,
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

    public function getAssetId(): int
    {
        return $this->assetId;
    }

    public function getNumberOfShares(): int
    {
        return $this->numberOfShares;
    }
}
