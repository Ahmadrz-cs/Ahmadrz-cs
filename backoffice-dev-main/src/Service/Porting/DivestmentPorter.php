<?php

namespace App\Service\Porting;

use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TradeStatus;
use App\Entity\PaymentOrder;
use App\Entity\PaymentRequest;
use App\Entity\Payout;
use App\Entity\ShareTrade;
use App\Entity\ShareTradeStatusLog;
use App\Entity\TradeOrder;
use App\Entity\TradeOrderStatusLog;
use App\Repository\UserRepository;
use BcMath\Number;
use Psr\Log\LoggerInterface;

class DivestmentPorter
{
    public function __construct(
        private LoggerInterface $logger,
        private UserRepository $userRepository,
    ) {}

    public function portPayoutOrder(Payout $payout): TradeOrder
    {
        if ($payout->getInvestment() === null) {
            throw new \RuntimeException(
                "Payout {$payout->getId()} is missing an investment relation.",
            );
        }
        $investment = $payout->getInvestment();
        /**
         * Always round DOWN for the sell side, so share price is LESS than actual
         * Reason: if deriving totals, you'll be conservatively claiming what was paid out for divestment
         * Whereas the true ShareTrade:tradeValue will be slightly higher
         */
        $roundedPrice = (string) round(
            $payout->getPayoutAmount() / $investment->getShareAmount(),
            6,
            \RoundingMode::TowardsZero,
        );
        $tradeOrder = new TradeOrder(
            direction: TradeDirection::Sell,
            asset: $payout->getAsset(),
            user: $payout->getCreditedUser(),
            numberOfShares: $investment->getShareAmount(),
            pricePerShare: new Number($roundedPrice),
            type: TradeOrderType::BuyBack,
        );
        $tradeOrder->setNotes(
            "port:i{$investment->getId()}:p{$payout->getId()} {$investment->getComments()}",
        );
        $tradeOrder->setTransactionReference($payout->getTransactionId());
        $initiator = $this->userRepository->findOneBy(['username' =>
            $payout->getCreatedBy()]);
        $tradeOrder->setCreatedAt($payout->getCreatedAt());
        $tradeOrder->setCreatedBy($initiator);
        $completeLog = new TradeOrderStatusLog(
            $tradeOrder,
            TradeOrderStatus::Completed,
            $payout->getCreatedAt(),
        );
        $tradeOrder->addStatusLog($completeLog);
        return $tradeOrder;
    }

    public function portPaymentRequestOrder(PaymentRequest $payment): TradeOrder
    {
        /**
         * Always round DOWN for the sell side, so share price is LESS than actual
         * Reason: if deriving totals, you'll be conservatively claiming what was paid out for divestment
         * Whereas the true ShareTrade:tradeValue will be slightly higher
         */
        $roundedPrice = (string) round(
            $payment->getAmount() / $payment->getShareholding(),
            6,
            \RoundingMode::TowardsZero,
        );
        $tradeOrder = new TradeOrder(
            direction: TradeDirection::Sell,
            asset: $payment->getPaymentOrder()->getAsset(),
            user: $payment->getPayee(),
            numberOfShares: $payment->getShareholding(),
            pricePerShare: new Number($roundedPrice),
            type: TradeOrderType::BuyBack,
        );
        $payout = $payment->getPayout();
        $tradeOrder->setNotes("port:pr{$payment->getId()}:p{$payout->getId()}");
        $tradeOrder->setTransactionReference($payout->getTransactionId());
        $initiator = $this->userRepository->findOneBy(['username' =>
            $payout->getCreatedBy()]);
        $tradeOrder->setCreatedAt($payout->getCreatedAt());
        $tradeOrder->setCreatedBy($initiator);
        $completeLog = new TradeOrderStatusLog(
            $tradeOrder,
            TradeOrderStatus::Completed,
            $payout->getCreatedAt(),
        );
        $tradeOrder->addStatusLog($completeLog);
        return $tradeOrder;
    }

    public function portPayoutTrade(
        Payout $payout,
        TradeOrder $sellOrder,
        TradeOrder $buyOrder,
    ): ShareTrade {
        $shareTrade = new ShareTrade(
            buyOrder: $buyOrder,
            sellOrder: $sellOrder,
            numberOfShares: $sellOrder->getNumberOfShares(),
            pricePerShare: $sellOrder->getPricePerShare(),
            tradeValue: new Number($payout->getPayoutAmount()),
        );
        $shareTrade->setCreatedAt($sellOrder->getCreatedAt());
        $shareTrade->setCreatedBy($sellOrder->getCreatedBy());
        $settledLog = new ShareTradeStatusLog(
            $shareTrade,
            TradeStatus::Settled,
            $sellOrder->getCreatedAt(),
        );
        $shareTrade->addStatusLog($settledLog);

        return $shareTrade;
    }

    public function createBuyBackOrderFromPaymentOrder(
        TradeOrder $initialOrder,
        PaymentOrder $paymentOrder,
    ): TradeOrder {
        $totals = [
            'shares' => 0,
            'value' => 0,
            'latest' => null,
        ];
        foreach ($paymentOrder->getPayments() as $payment) {
            $totals['shares'] += $payment->getShareholding();
            $totals['value'] += $payment->getAmount();
            if ($payment->getUpdatedAt() > $totals['latest']) {
                $totals['latest'] = $payment->getUpdatedAt();
            }
        }
        $sharePrice = (string) round(
            $totals['value'] / $totals['shares'],
            6,
            \RoundingMode::TowardsZero,
        );
        $buyBackOrder = new TradeOrder(
            direction: TradeDirection::Buy,
            asset: $initialOrder->getAsset(),
            user: $initialOrder->getUser(),
            numberOfShares: $totals['shares'],
            pricePerShare: new Number($sharePrice),
            type: TradeOrderType::BuyBack,
        );
        $buyBackOrder->setNotes(
            "port:po{$paymentOrder->getId()} {$paymentOrder->getDescription()}",
        );
        $buyBackOrder->setCreatedAt($paymentOrder->getCreatedAt());
        $initiator = $this->userRepository->findOneBy(['username' =>
            $paymentOrder->getCreatedBy()]);
        $buyBackOrder->setCreatedBy($initiator);
        $activeLog = new TradeOrderStatusLog(
            $buyBackOrder,
            TradeOrderStatus::Active,
            $buyBackOrder->getCreatedAt(),
        );
        $completedLog = new TradeOrderStatusLog(
            $buyBackOrder,
            TradeOrderStatus::Completed,
            $totals['latest'],
        );
        $buyBackOrder->addStatusLog($activeLog);
        $buyBackOrder->addStatusLog($completedLog);
        return $buyBackOrder;
    }

    public function createBuyBackOrder(
        TradeOrder $initialOrder,
        int $numberOfShares,
        Number|string $totalPaid,
        array $payouts,
    ): TradeOrder {
        $roundedPrice = (string) round(
            $totalPaid / $numberOfShares,
            6,
            \RoundingMode::TowardsZero,
        );
        usort(
            $payouts,
            fn(Payout $a, Payout $b) => $a->getCreatedAt() <=> $b->getCreatedAt(),
        );
        $buyBackOrder = new TradeOrder(
            direction: TradeDirection::Buy,
            asset: $initialOrder->getAsset(),
            user: $initialOrder->getUser(),
            numberOfShares: $numberOfShares,
            pricePerShare: new Number($roundedPrice),
            type: TradeOrderType::BuyBack,
        );
        $firstPayout = array_first($payouts);
        $lastPayout = array_last($payouts);
        $buyBackOrder->setCreatedAt($firstPayout->getCreatedAt());
        $initiator = $this->userRepository->findOneBy(['username' =>
            $firstPayout->getCreatedBy()]);
        $buyBackOrder->setCreatedBy($initiator);
        $activeLog = new TradeOrderStatusLog(
            $buyBackOrder,
            TradeOrderStatus::Active,
            $firstPayout->getCreatedAt(),
        );
        $completedLog = new TradeOrderStatusLog(
            $buyBackOrder,
            TradeOrderStatus::Completed,
            $lastPayout->getCreatedAt(),
        );
        $buyBackOrder->addStatusLog($activeLog);
        $buyBackOrder->addStatusLog($completedLog);
        return $buyBackOrder;
    }
}
