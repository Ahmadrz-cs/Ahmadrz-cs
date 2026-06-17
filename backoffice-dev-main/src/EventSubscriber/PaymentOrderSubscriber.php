<?php

namespace App\EventSubscriber;

use App\Entity\Enum\AssetStatus;
use App\Entity\Enum\PaymentType;
use App\Event\PaymentOrder\PaymentOrderCompletedEvent;
use App\Service\AssetService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PaymentOrderSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private AssetService $assetService,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            PaymentOrderCompletedEvent::class => 'processOrderCompletion',
        ];
    }

    public function processOrderCompletion(PaymentOrderCompletedEvent $event)
    {
        $paymentOrder = $event->getPaymentOrder();
        $this->logger->debug('Processing payment order completion', [
            'id' => $paymentOrder->getId(),
            'type' => $paymentOrder->getPaymentType(),
        ]);
        if ($paymentOrder->getPaymentType() == PaymentType::InvestmentExit->value) {
            $this->logger->info('Transitioning asset status after investment exit');
            // Could double check that the shareholdings are empty
            // Currently assumed to be correct upstream
            // Should potentially check if a transition is needed?
            // Although there's no harm in multiple of the same log being added, just looks a bit stranged
            $this->assetService->applyStatusChange(
                asset: $paymentOrder->getAsset(),
                status: AssetStatus::Archived,
                reason: 'Investment exit - funds distributed to shareholders',
            );
            $this->entityManager->flush();
        }
    }
}
