<?php

namespace App\Dto\Traits;

trait OfferingTrait
{
    public function getName(): ?string
    {
        return $this->name;
    }

    public function getAssetId(): ?int
    {
        return $this->assetId;
    }

    public function getFundingGoal(): ?float
    {
        return $this->fundingGoal;
    }

    public function getExternalCommitments(): ?float
    {
        return $this->externalCommitments;
    }

    public function getIsFeatured(): ?bool
    {
        return $this->isFeatured;
    }

    public function getNumberOfShares(): ?int
    {
        return $this->numberOfShares;
    }

    public function getPricePerShare(): ?float
    {
        return $this->pricePerShare;
    }

    public function getNetAnnualYield(): ?float
    {
        return $this->netAnnualYield;
    }

    public function getNetTotalReturn(): ?float
    {
        return $this->netTotalReturn;
    }

    public function getMinCommit(): ?float
    {
        return $this->minCommit;
    }

    public function getMaxCommit(): ?float
    {
        return $this->maxCommit;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }
}
