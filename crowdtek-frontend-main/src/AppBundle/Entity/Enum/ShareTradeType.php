<?php

namespace AppBundle\Entity\Enum;

enum ShareTradeType: string
{
    case FirstParty = 'first_party';
    case SecondaryMarket = 'secondary_market';
    case Prefunding = 'prefunding';
    case Divestment = 'divestment';
    case Repayment = 'repayment';

    public function groupName(): string
    {
        return match ($this) {
                // ShareTradeType::FirstParty, ShareTradeType::SecondaryMarket => 'market',
            ShareTradeType::Prefunding, ShareTradeType::Repayment => 'prefunding',
            ShareTradeType::Divestment => 'maturity',
            default => 'market',
        };
    }
}
