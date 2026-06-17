<?php

namespace App\Service\Porting;

use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TradeStatus;
use App\Entity\Investment;
use App\Entity\Lifecycle\InvestmentLifecycle;
use App\Entity\ShareTrade;
use App\Entity\ShareTradeStatusLog;
use App\Entity\TradeOrder;
use App\Entity\TradeOrderStatusLog;
use App\Repository\TransactionRepository;
use BcMath\Number;
use Psr\Log\LoggerInterface;

class InvestmentPorter
{
    public function __construct(
        private LoggerInterface $logger,
        private TransactionRepository $transactionRepository,
    ) {}

    public function portInvestmentOrder(Investment $investment): TradeOrder
    {
        $offering = $investment->getOffering();
        $asset = $offering->getAsset();
        $type = match ($investment->getType()) {
            'prefunding' => TradeOrderType::Prefunding,
            'off-market' => TradeOrderType::OffMarket,
            default => TradeOrderType::Market,
        };
        $transaction = $this->transactionRepository->findOneBy(['external_id' =>
            $investment->getTransactionId()]);
        $tradeOrder = new TradeOrder(
            direction: TradeDirection::Buy,
            asset: $asset,
            user: $investment->getUser(),
            numberOfShares: $investment->getShareAmount(),
            pricePerShare: new Number($investment->getOrgPricePerShare()),
            type: $type,
        );
        $tradeOrder->setNotes(
            "port:o{$offering->getId()}:i{$investment->getId()} {$investment->getComments()}",
        );
        $tradeOrder->setTransaction($transaction);
        $tradeOrder->setTransactionReference($investment->getTransactionId());
        $tradeOrder->setCreatedAt($investment->getCreatedAt());
        $tradeOrder->setCreatedBy($investment->getUser());
        $completeLog = new TradeOrderStatusLog(
            $tradeOrder,
            TradeOrderStatus::Completed,
            $investment->getCreatedAt(),
        );
        $tradeOrder->addStatusLog($completeLog);

        $investment->setTradeOrder($tradeOrder);
        return $tradeOrder;
    }

    public function portInvestmentTrade(
        Investment $investment,
        TradeOrder $buyOrder,
    ): ShareTrade {
        $shareTrade = new ShareTrade(
            buyOrder: $buyOrder,
            sellOrder: $investment->getOffering()->getTradeOrder(),
            numberOfShares: $investment->getShareAmount(),
            pricePerShare: new Number($investment->getOrgPricePerShare()),
            tradeValue: new Number($investment->getInvestmentValue()),
        );
        $shareTrade->setCreatedAt($investment->getCreatedAt());
        $shareTrade->setCreatedBy($investment->getUser());
        $this->portTradeStatuses($investment, $shareTrade);

        $investment->setShareTrade($shareTrade);
        return $shareTrade;
    }

    private function portTradeStatuses(
        Investment $investment,
        ShareTrade $shareTrade,
    ): ShareTrade {
        $statuses = $investment->getStatus();
        if ($statuses->getApprovedOn()) {
            $unsettledLog = new ShareTradeStatusLog(
                $shareTrade,
                TradeStatus::Unsettled,
                $statuses->getApprovedOn(),
            );
            $shareTrade->addStatusLog($unsettledLog);
        }
        $lastStatus = max(
            $statuses->getOpenOn(),
            $statuses->getApprovedOn(),
            $statuses->getSettledOn(),
            $statuses->getRejectedOn(),
            $statuses->getWithdrawnOn(),
        );
        if ($statuses->getLifecycleStatus() == InvestmentLifecycle::STATE_SETTLED) {
            // Mainly to handle manually added investments configured incorrectly
            if ($statuses->getSettledOn() === null) {
                $lastStatus = $this->expectedSettlementDay($lastStatus);
            }
            $settledLog = new ShareTradeStatusLog(
                $shareTrade,
                TradeStatus::Settled,
                $lastStatus,
            );
            $shareTrade->addStatusLog($settledLog);
        }
        if ($statuses->getLifecycleStatus() == InvestmentLifecycle::STATE_REJECTED) {
            $cancelRejectLog = new ShareTradeStatusLog(
                $shareTrade,
                TradeStatus::Cancelled,
                $lastStatus,
            );
            $shareTrade->addStatusLog($cancelRejectLog);
        }
        if ($statuses->getLifecycleStatus() == InvestmentLifecycle::STATE_WITHDRAWN) {
            $cancelWithdrawnLog = new ShareTradeStatusLog(
                $shareTrade,
                TradeStatus::Cancelled,
                $lastStatus,
            );
            $shareTrade->addStatusLog($cancelWithdrawnLog);
        }
        return $shareTrade;
    }

    private function expectedSettlementDay(\DateTimeInterface $investmentDate): \DateTime
    {
        // Datetime Immutable will create a new instance if you modify - which we want, so original isn't changed
        $investmentDate = \DateTimeImmutable::createFromInterface($investmentDate);
        // +1 month causes issues for long months like Jan with 31 days, where +1 month pushes it in the month after
        // Trick is to set the day to the earlier part of the month before adding the month
        $earlyMonthDateString = $investmentDate->format('Y-m-03 H:i:s');
        $settlementDate = new \DateTime($earlyMonthDateString);
        $settlementDate->modify('+1 month');
        return $settlementDate;
    }
}
