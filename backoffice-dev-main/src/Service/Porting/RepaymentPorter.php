<?php

namespace App\Service\Porting;

use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TradeStatus;
use App\Entity\Investment;
use App\Entity\ShareTrade;
use App\Entity\ShareTradeStatusLog;
use App\Entity\TradeOrder;
use App\Entity\TradeOrderStatusLog;
use App\Repository\TransactionRepository;
use BcMath\Number;
use Psr\Log\LoggerInterface;

class RepaymentPorter
{
    public function __construct(
        private LoggerInterface $logger,
        private TransactionRepository $transactionRepository,
    ) {}

    /**
     * Convert prefunding liquidation investments into the prefunding sell order
     */
    public function portInvestmentSellOrder(
        Investment $investment,
        \DateTime $lastBuyBack,
    ): TradeOrder {
        $offering = $investment->getOffering();
        $asset = $offering->getAsset();
        $tradeOrder = new TradeOrder(
            direction: TradeDirection::Sell,
            asset: $asset,
            user: $investment->getUser(),
            numberOfShares: $investment->getShareAmount(),
            pricePerShare: new Number($investment->getOrgPricePerShare()),
            type: TradeOrderType::Prefunding,
        );
        $tradeOrder->setNotes(
            "port:o{$offering->getId()}:i{$investment->getId()} {$investment->getComments()}",
        );
        $transaction = $this->transactionRepository->findOneBy(['external_id' =>
            $investment->getTransactionId()]);
        $tradeOrder->setTransaction($transaction);
        $tradeOrder->setTransactionReference($investment->getTransactionId());
        $tradeOrder->setCreatedAt($investment->getCreatedAt());
        $tradeOrder->setCreatedBy($investment->getUser());
        $activeLog = new TradeOrderStatusLog(
            $tradeOrder,
            TradeOrderStatus::Active,
            $investment->getCreatedAt(),
        );
        $tradeOrder->addStatusLog($activeLog);
        if ($investment->getShareAmount() == $investment->getDivestedShares()) {
            $completeLog = new TradeOrderStatusLog(
                $tradeOrder,
                TradeOrderStatus::Completed,
                // minimum 1 second more than active log to help with sorting
                // Create a new datetime object before modifying
                // otherwise you'll modify the created at as well
                max(
                    $lastBuyBack,
                    \Datetime::createFromInterface($investment->getCreatedAt())->modify(
                        '+1 second',
                    ),
                ),
            );
            $tradeOrder->addStatusLog($completeLog);
        }
        return $tradeOrder;
    }

    public function portRepaymentTrade(
        int $numberOfShares,
        TradeOrder $sellOrder,
        TradeOrder $buyOrder,
    ): ShareTrade {
        $shareTrade = new ShareTrade(
            buyOrder: $buyOrder,
            sellOrder: $sellOrder,
            numberOfShares: $numberOfShares,
            pricePerShare: $buyOrder->getPricePerShare(),
        );
        $createdAt = max($buyOrder->getCreatedAt(), $sellOrder->getCreatedAt());
        $shareTrade->setCreatedAt($createdAt);
        $shareTrade->setCreatedBy($buyOrder->getCreatedBy());
        $settledLog = new ShareTradeStatusLog(
            $shareTrade,
            TradeStatus::Settled,
            $shareTrade->getCreatedAt(),
        );
        $shareTrade->addStatusLog($settledLog);
        return $shareTrade;
    }

    /**
     * Create the buy back order for gen-1 repayments
     * The amount is aggregated for the total repaid this way
     * as there is no incremental data
     *
     * $initiationDate is either the last investment or the first payment order
     * $initiationOffset is to set the month ahead or before respectively
     *   - initiation for last retail investment is next monthend
     *   - initiation for first repayment order is previous monthend
     */
    public function createBuyBackOrder(
        TradeOrder $initialOrder,
        int $numberOfShares,
        \DateTime $initiationDate,
        bool $initiatedByInvestment = true,
    ): TradeOrder {
        // If it's an investment, we'll need to set to next monthend
        if ($initiatedByInvestment) {
            // Change date to near start of the next month
            $initiationDateString = $initiationDate->format('Y-m-03 H:i:s');
            $initiationDate = new \DateTime($initiationDateString);
            $initiationDate = $initiationDate->modify('+1 months');
        }

        $buyBackOrder = new TradeOrder(
            direction: TradeDirection::Buy,
            asset: $initialOrder->getAsset(),
            user: $initialOrder->getUser(),
            numberOfShares: $numberOfShares,
            pricePerShare: new Number($initialOrder->getAsset()->getPricePerShare()),
            type: TradeOrderType::Proxy,
        );
        $buyBackOrder->setNotes('port:aggregated_repayments');
        $buyBackOrder->setCreatedAt($initiationDate);
        $buyBackOrder->setCreatedBy($initialOrder->getUser());
        $completedLog = new TradeOrderStatusLog(
            $buyBackOrder,
            TradeOrderStatus::Completed,
            $initiationDate,
        );
        $buyBackOrder->addStatusLog($completedLog);
        return $buyBackOrder;
    }

    /**
     * Create buy back order for gen-2 repayments
     * These are based on a repayment order, so will be incremental
     * and have proper dates, rather than estimates
     *
     * May not be used due to looping and matching complexity
     */
    // public function createBuyBackOrderFromPaymentOrder(
    //     TradeOrder $initialOrder,
    //     PaymentOrder $paymentOrder,
    // ): TradeOrder {
    //     $totals = [
    //         'shares' => 0,
    //         'value' => 0,
    //         'latest' => null,
    //     ];
    //     foreach ($paymentOrder->getPayments() as $payment) {
    //         $totals['shares'] += $payment->getShareholding();
    //         $totals['value'] += $payment->getAmount();
    //         if ($payment->getUpdatedAt() > $totals['latest']) {
    //             $totals['latest'] = $payment->getUpdatedAt();
    //         }
    //     }
    //     $buyBackOrder = new TradeOrder(
    //         direction: TradeDirection::Buy,
    //         asset: $initialOrder->getAsset(),
    //         user: $initialOrder->getUser(),
    //         numberOfShares: $totals['shares'],
    //         pricePerShare: $initialOrder->getPricePerShare(),
    //         type: TradeOrderType::Proxy,
    //     );
    //     $buyBackOrder->setNotes(
    //         "port:po{$paymentOrder->getId()} {$paymentOrder->getDescription()}",
    //     );
    //     $buyBackOrder->setCreatedAt($paymentOrder->getCreatedAt());
    //     $initiator = $this->userRepository->findOneBy(['username' =>
    //         $paymentOrder->getCreatedBy()]);
    //     $buyBackOrder->setCreatedBy($initiator);
    //     $activeLog = new TradeOrderStatusLog(
    //         $buyBackOrder,
    //         TradeOrderStatus::Active,
    //         $buyBackOrder->getCreatedAt(),
    //     );
    //     $completedLog = new TradeOrderStatusLog(
    //         $buyBackOrder,
    //         TradeOrderStatus::Completed,
    //         $totals['latest'],
    //     );
    //     $buyBackOrder->addStatusLog($activeLog);
    //     $buyBackOrder->addStatusLog($completedLog);
    //     return $buyBackOrder;
    // }
}
