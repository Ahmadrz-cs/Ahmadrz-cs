<?php

namespace App\Dto;

use JMS\Serializer\Annotation as JMS;

#[JMS\ExclusionPolicy('all')]
class Marketing
{
    #[JMS\Type('string')]
    protected $investmentIntent;

    public function __construct(?string $investmentIntent = null)
    {
        $this->investmentIntent = $investmentIntent;
    }

    public function getInvestmentIntent(): ?string
    {
        return $this->investmentIntent;
    }
}
