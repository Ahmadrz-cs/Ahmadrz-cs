<?php

namespace App\EventListener;

use App\Entity\TradeOrder;
use App\Repository\ShareTradeRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

/**
 * See docs for more info
 * https://symfony.com/doc/current/doctrine/events.html#doctrine-entity-listeners
 */
#[AsEntityListener(
    event: Events::postLoad,
    method: 'postLoad',
    entity: TradeOrder::class,
)]
class TradeOrderAggregateListener
{
    public function __construct(
        private LoggerInterface $logger,
        private ShareTradeRepository $shareTradeRepository,
    ) {}

    public function postLoad(TradeOrder $tradeOrder): void
    {
        // $this->logger->debug('Loading trade order aggregates');
        $aggregateData =
            $this->shareTradeRepository->getTradeOrderAggregates($tradeOrder);
        // $this->logger->debug('Loading trade order aggregates', $aggregateData);
        if (empty($aggregateData) || !array_key_exists('shares', $aggregateData)) {
            $tradeOrder->setSharesTraded(0);
        } else {
            $sharesTraded = $aggregateData['shares'] ?? 0;
            $tradeOrder->setSharesTraded((int) $sharesTraded);
        }
    }
}
