<?php

namespace AppBundle\Entity\Enum;

enum TradeOrderType: string
{
    // Default - Buy at best price - makes more sense at floating share price supported
    case Market = 'market';
    // Buy once lower price reached, or sell once higher price reached
    case Limit = 'limit';
    // Sell once lower price reached
    case StopLoss = 'stop_loss';
    // Used for launching new assets
    case Initial = 'initial';
    // Special sell-only type for internal processing of prefunder repayments (liquidation portion)
    case Prefunding = 'prefunding';
    // For retrospective records of investments made off-market
    case OffMarket = 'off_market';
    // For reclaiming shares during a divest, exit, capital repayment
    case BuyBack = 'buy_back';
    // For proxying share trades between a seller and buyer - mainly used for prefunder repayments
    case Proxy = 'proxy';
}
