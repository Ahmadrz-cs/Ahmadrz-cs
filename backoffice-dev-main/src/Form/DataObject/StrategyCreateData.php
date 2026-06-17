<?php

namespace App\Form\DataObject;

use App\Entity\Asset;
use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

final class StrategyCreateData
{
    #[Assert\NotBlank]
    public User $user;

    public Asset $asset;

    #[Assert\NotBlank]
    public int $maxInvPerAsset;

    #[Assert\NotBlank]
    public float $minNetAnnualYield;

    #[Assert\NotBlank]
    public int $minNumInvestments;

    #[Assert\NotBlank]
    public int $minNumMonthsRemaining;

    public static function createDefault(): self
    {
        $strategyCreateData = new self();
        $strategyCreateData->maxInvPerAsset = 10000;
        $strategyCreateData->minNetAnnualYield = 0.055;
        $strategyCreateData->minNumInvestments = 2;
        $strategyCreateData->minNumMonthsRemaining = 24;

        return $strategyCreateData;
    }

    public static function create(
        int $maxInvPerAsset,
        float $minNetAnnualYield,
        int $minNumInvestments,
        int $minNumMonthsRemaining,
        ?Asset $asset,
    ): self {
        $strategyCreateData = new self();
        $strategyCreateData->maxInvPerAsset = $maxInvPerAsset;
        $strategyCreateData->minNetAnnualYield = $minNetAnnualYield / 100;
        $strategyCreateData->minNumInvestments = $minNumInvestments;
        $strategyCreateData->minNumMonthsRemaining = $minNumMonthsRemaining;
        if ($asset) {
            $strategyCreateData->asset = $asset;
        }
        return $strategyCreateData;
    }
}
