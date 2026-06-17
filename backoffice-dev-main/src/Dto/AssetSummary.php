<?php

namespace App\Dto;

use JMS\Serializer\Annotation as JMS;

#[JMS\ExclusionPolicy('all')]
final class AssetSummary
{
    #[JMS\Expose]
    #[JMS\Groups(['standard'])]
    #[JMS\Type('int')]
    protected $assetInvestmentCount;

    #[JMS\Expose]
    #[JMS\Groups(['standard'])]
    #[JMS\Type('float')]
    protected $assetInvestmentValue;

    #[JMS\Expose]
    #[JMS\Groups(['standard'])]
    #[JMS\Type('float')]
    protected $assetReturn;

    #[JMS\Expose]
    #[JMS\Groups(['standard'])]
    #[JMS\Type('float')]
    protected $assetDividend;

    #[JMS\Expose]
    #[JMS\Groups(['standard'])]
    #[JMS\Type('float')]
    protected $assetCapitalAppreciation;

    #[JMS\Expose]
    #[JMS\Groups(['standard'])]
    #[JMS\Type('array<string, float>')]
    protected $monthlyPayouts;

    public function __construct(
        int $assetInvestmentCount,
        float $assetInvestmentValue,
        float $assetReturn,
        float $assetDividend,
        float $assetCapitalAppreciation,
        ?array $monthlyPayouts = null,
    ) {
        $this->assetInvestmentCount = $assetInvestmentCount;
        $this->assetInvestmentValue = $assetInvestmentValue;
        $this->assetReturn = $assetReturn;
        $this->assetDividend = $assetDividend;
        $this->assetCapitalAppreciation = $assetCapitalAppreciation;
        $this->monthlyPayouts = $monthlyPayouts;
    }

    public function getAssetInvestmentCount(): int
    {
        return $this->assetInvestmentCount;
    }

    public function getAssetInvestmentValue(): float
    {
        return $this->assetInvestmentValue;
    }

    public function getAssetReturn(): float
    {
        return $this->assetReturn;
    }

    public function getAssetDividend(): float
    {
        return $this->assetDividend;
    }

    public function getAssetCapitalAppreciation(): float
    {
        return $this->assetCapitalAppreciation;
    }

    public function getMonthlyPayouts(): ?array
    {
        return $this->monthlyPayouts;
    }
}
