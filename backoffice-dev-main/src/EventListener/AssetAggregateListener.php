<?php

namespace App\EventListener;

use App\Entity\Asset;
use App\Repository\ShareTradeRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

/**
 * See docs for more info
 * https://symfony.com/doc/current/doctrine/events.html#doctrine-entity-listeners
 */
#[AsEntityListener(event: Events::postLoad, method: 'postLoad', entity: Asset::class)]
class AssetAggregateListener
{
    public function __construct(
        private LoggerInterface $logger,
        private ShareTradeRepository $shareTradeRepository,
    ) {}

    public function postLoad(Asset $asset): void
    {
        // $this->logger->debug('Loading asset aggregates');
        $aggregateData = $this->shareTradeRepository->getAssetTradeAggregates(
            $asset->getId(),
        );
        // $this->logger->debug('Loading asset aggregates', $aggregateData ?? []);
        if (
            empty($aggregateData)
            || !array_key_exists('sharesAvailable', $aggregateData)
        ) {
            $asset->setSharesAvailable(0);
        } else {
            $sharesAvailable = max(0, $aggregateData['sharesAvailable'] ?? 0);
            $asset->setSharesAvailable((int) $sharesAvailable);
        }
    }
}
