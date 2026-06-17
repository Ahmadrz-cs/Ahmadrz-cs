<?php

namespace App\EventSubscriber;

use App\Entity\AbstractOrder;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeStatus;
use App\Entity\Enum\TransferType;
use App\Entity\TradeOrder;
use App\Event\TransferOrder\TransferOrderCompletedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TransferOrderSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            TransferOrderCompletedEvent::class => 'processOrderCompletion',
        ];
    }

    public function processOrderCompletion(TransferOrderCompletedEvent $event): bool
    {
        $transferOrder = $event->getTransferOrder();
        if ($transferOrder->getStatus() != AbstractOrder::STATE_COMPLETED) {
            return false;
        }
        $this->logger->debug('Processing payment order completion', [
            'id' => $transferOrder->getId(),
            'type' => $transferOrder->getTransferType()->name,
        ]);
        if ($transferOrder->getTransferType() == TransferType::InvestmentSettlement) {
            $this->logger->debug('Checking trade order statuses');
            $processedOrders = [];
            $processedTrades = [];
            foreach ($transferOrder->getTransfers() as $transfer) {
                $shareTrade = $transfer->getShareTrade();
                if (
                    $shareTrade === null
                    || in_array($shareTrade->getId(), $processedTrades)
                    || $shareTrade->getStatus() !== TradeStatus::Settled
                ) {
                    // Can only do stuff if share trade exists and is settled
                    // Skip if already processed that share trade
                    continue;
                }
                $buyOrder = $shareTrade->getBuyOrder();
                $sellOrder = $shareTrade->getSellOrder();
                if (!in_array($buyOrder->getId(), $processedOrders)) {
                    $this->processTradeOrder($buyOrder);
                    $processedOrders[] = $buyOrder->getId();
                }
                if (!in_array($sellOrder->getId(), $processedOrders)) {
                    $this->processTradeOrder($sellOrder);
                    $processedOrders[] = $sellOrder->getId();
                }
                $processedTrades[] = $shareTrade->getId();
            }
            $this->entityManager->flush();
        }
        return true;
    }

    private function processTradeOrder(TradeOrder $tradeOrder): TradeOrder
    {
        if (
            $tradeOrder->getSharesAvailable() <= 0
            && $tradeOrder->getStatus() === TradeOrderStatus::Active
        ) {
            $tradeOrder->setStatus(TradeOrderStatus::Completed);
            $this->logger->debug('Completing trade order', [
                'id' => $tradeOrder->getId(),
                'direction' => $tradeOrder->getDirection()->name,
            ]);
        }
        return $tradeOrder;
    }
}
