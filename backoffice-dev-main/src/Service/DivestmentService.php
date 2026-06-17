<?php

namespace App\Service;

use App\Entity\Enum\QueryGrouping;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TradeStatus;
use App\Entity\PaymentOrder;
use App\Entity\PaymentRequest;
use App\Entity\ShareTrade;
use App\Entity\ShareTradeStatusLog;
use App\Entity\TradeOrder;
use App\Entity\TradeOrderStatusLog;
use BcMath\Number;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Support service for processing divestments and their execution
 */
class DivestmentService
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
    ) {}

    public function createBuyBackOrder(
        PaymentOrder $paymentOrder,
        TradeOrder $initialOrder,
        TradeOrderType $type = TradeOrderType::BuyBack,
    ): TradeOrder {
        $numberOfShares = 0;
        $paymentTotal = new Number(0);
        foreach ($paymentOrder->getPayments() as $payment) {
            $numberOfShares += $payment->getShareholding();
            $paymentTotal = $paymentTotal->add(new Number($payment->getAmount()));
        }
        $roundedPrice = $paymentTotal->div($numberOfShares);
        $buyBackOrder = new TradeOrder(
            direction: TradeDirection::Buy,
            asset: $initialOrder->getAsset(),
            user: $initialOrder->getUser(),
            numberOfShares: $numberOfShares,
            pricePerShare: new Number($roundedPrice),
            type: $type,
        );
        $activeLog = new TradeOrderStatusLog($buyBackOrder, TradeOrderStatus::Active);
        $buyBackOrder->addStatusLog($activeLog);
        $paymentOrder->setTradeOrder($buyBackOrder);

        // Explicitly persist here as it's not cascaded
        $this->entityManager->persist($buyBackOrder);
        return $buyBackOrder;
    }

    public function finishBuyBackOrder(TradeOrder $buyBackOrder): TradeOrder
    {
        if (in_array($buyBackOrder->getStatus(), TradeOrderStatus::endStates())) {
            // Already at end state
            return $buyBackOrder;
        }
        $fulfilled = 0;
        foreach ($buyBackOrder->getShareTrades() as $trade) {
            if ($trade->getStatus() == TradeStatus::Settled) {
                $fulfilled += $trade->getNumberOfShares();
            }
        }
        $this->logger->debug('Finishing buyback order', [
            'fulfilled' => $fulfilled,
            'order shares' => $buyBackOrder->getNumberOfShares(),
        ]);
        $status = match (true) {
            $fulfilled < $buyBackOrder->getNumberOfShares()
                => TradeOrderStatus::Cancelled,
            default => TradeOrderStatus::Completed,
        };
        $statusLog = new TradeOrderStatusLog($buyBackOrder, $status);
        $buyBackOrder->addStatusLog($statusLog);
        return $buyBackOrder;
    }

    public function createDivestmentOrder(PaymentRequest $payment): TradeOrder
    {
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
        if ($payout) {
            $tradeOrder->setTransactionReference($payout->getTransactionId());
        }

        $completeLog = new TradeOrderStatusLog(
            $tradeOrder,
            TradeOrderStatus::Completed,
        );
        $tradeOrder->addStatusLog($completeLog);

        // Explicitly persist here as it's not cascaded
        $this->entityManager->persist($tradeOrder);
        return $tradeOrder;
    }

    public function createBuyBackTrade(
        PaymentRequest $payment,
        TradeOrder $sellOrder,
    ): ShareTrade {
        $buyOrder = $payment->getPaymentOrder()->getTradeOrder();
        $tradeValue = new Number($payment->getAmount());
        $pricePerShare = $tradeValue->div($payment->getShareholding());
        $shareTrade = new ShareTrade(
            buyOrder: $buyOrder,
            sellOrder: $sellOrder,
            numberOfShares: $payment->getShareholding(),
            pricePerShare: $pricePerShare,
            tradeValue: $tradeValue,
        );
        $settledLog = new ShareTradeStatusLog(
            $shareTrade,
            TradeStatus::Settled,
            $sellOrder->getCreatedAt(),
        );
        $shareTrade->addStatusLog($settledLog);
        $payment->setShareTrade($shareTrade);
        $sellOrder->addShareTrade($shareTrade);
        $buyOrder->addShareTrade($shareTrade);

        // Explicitly persist here as it's not cascaded
        $this->entityManager->persist($shareTrade);
        return $shareTrade;
    }

    public function checkTradeOrderProgression(TradeOrder $tradeOrder): bool
    {
        // Refresh sharesTraded with current share trades (including unpersisted)
        if ($tradeOrder->getNumberOfShares() <= $tradeOrder->deriveSharesTraded()) {
            if (!in_array($tradeOrder->getStatus(), TradeOrderStatus::endStates())) {
                $statusLog = new TradeOrderStatusLog(
                    $tradeOrder,
                    TradeOrderStatus::Completed,
                );
                $tradeOrder->addStatusLog($statusLog);
            }
            return true;
        }
        return false;
    }

    /**
     * Asset verification is not performed
     * It is assumed all sellOrders are for the same asset
     * @param TradeOrder[] $sellOrders
     * @return array
     */
    public function compileRepaymentProgress(
        array $sellOrders,
        QueryGrouping $grouping = QueryGrouping::User,
    ): array {
        $progress = [];
        foreach ($sellOrders as $sellOrder) {
            if ($sellOrder->getDirection() === TradeDirection::Buy) {
                continue;
            }
            $groupingId = match ($grouping) {
                QueryGrouping::Asset => $sellOrder->getAsset()->getId(),
                default => $sellOrder->getUser()->getId(),
            };
            // groupingIdField is a compatibility field for payment generator service
            // More useful for userid than assetid
            $groupingIdField = match ($grouping) {
                QueryGrouping::Asset => ['assetid' => $groupingId],
                default => ['userid' => $groupingId],
            };
            if (!array_key_exists($groupingId, $progress)) {
                // New prefunder record initialisation
                $progress[$groupingId] = [
                    ...$groupingIdField,
                    'initialShares' => 0,
                    'repaidShares' => 0,
                    'shares' => 0,
                    'sellOrders' => [],
                    'openSellOrders' => [],
                ];
            }
            // Refresh sharesTraded with current share trades (including unpersisted)
            $sellOrder->deriveSharesTraded();
            $progress[$groupingId]['initialShares'] += $sellOrder->getNumberOfShares();
            $progress[$groupingId]['repaidShares'] += $sellOrder->getSharesTraded();
            // Generic "shares" represents the current "shareholding" left to be repaid
            // Intended to be used by payment generator service
            $progress[$groupingId]['shares'] +=
                $sellOrder->getNumberOfShares() - $sellOrder->getSharesTraded();
            $progress[$groupingId]['sellOrders'][] = $sellOrder;
            if ($sellOrder->getNumberOfShares() > $sellOrder->getSharesTraded()) {
                // Could also check the status of the trade order
                // but matters more that there is actually space left for new share trades
                $progress[$groupingId]['openSellOrders'][] = $sellOrder;
            }
        }
        // Sort by largest shareholder first
        // Note that for payment generator service, if there are existing payments, their position is retained
        \uasort($progress, fn(array $a, array $b) => $b['shares'] <=> $a['shares']);
        // $this->logger->debug('repayment progress report', $progress);
        return $progress;
    }
}
