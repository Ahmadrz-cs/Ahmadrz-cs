<?php

namespace App\Dto;

use JMS\Serializer\Annotation as JMS;

#[JMS\ExclusionPolicy('all')]
final class AccountSummary
{
    #[JMS\Expose]
    #[JMS\Groups(['standard'])]
    #[JMS\Type('int')]
    protected $totalInvestmentCount;

    #[JMS\Expose]
    #[JMS\Groups(['standard'])]
    #[JMS\Type('float')]
    protected $totalInvestmentValue;

    #[JMS\Expose]
    #[JMS\Groups(['standard'])]
    #[JMS\Type('float')]
    protected $totalReturn;

    #[JMS\Expose]
    #[JMS\Groups(['standard'])]
    #[JMS\Type('float')]
    protected $totalDividend;

    #[JMS\Expose]
    #[JMS\Groups(['standard'])]
    #[JMS\Type('float')]
    protected $totalCapitalAppreciation;

    #[JMS\Expose]
    #[JMS\Groups(['standard'])]
    #[JMS\Type('array<string, float>')]
    protected $monthlyPayouts;

    public function __construct(
        int $totalInvestmentCount,
        float $totalInvestmentValue,
        float $totalReturn,
        float $totalDividend,
        float $totalCapitalAppreciation,
        ?array $monthlyPayouts = null,
    ) {
        $this->totalInvestmentCount = $totalInvestmentCount;
        $this->totalInvestmentValue = $totalInvestmentValue;
        $this->totalReturn = $totalReturn;
        $this->totalDividend = $totalDividend;
        $this->totalCapitalAppreciation = $totalCapitalAppreciation;
        $this->monthlyPayouts = $monthlyPayouts;
    }

    public function getTotalInvestmentCount()
    {
        return $this->totalInvestmentCount;
    }

    public function getTotalInvestmentValue()
    {
        return $this->totalInvestmentValue;
    }

    public function getTotalReturn()
    {
        return $this->totalReturn;
    }

    public function getTotalDividend()
    {
        return $this->totalDividend;
    }

    public function getTotalCapitalAppreciation()
    {
        return $this->totalCapitalAppreciation;
    }

    public function getMonthlyPayouts()
    {
        return $this->monthlyPayouts;
    }
}
