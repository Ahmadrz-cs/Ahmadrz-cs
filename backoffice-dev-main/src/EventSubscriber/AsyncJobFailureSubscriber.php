<?php

namespace App\EventSubscriber;

use App\Message\OrderBatchRun;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;

class AsyncJobFailureSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private UserRepository $userRepository,
        private NotificationService $notificationService,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageFailedEvent::class => 'processMessageFailure',
        ];
    }

    public function processMessageFailure(WorkerMessageFailedEvent $event)
    {
        $message = $event->getEnvelope()->getMessage();
        $reflection = new \ReflectionClass($message);
        $notifyUser = null;

        if (!$event->willRetry()) {
            if ($message instanceof OrderBatchRun) {
                $notifyUser = $this->userRepository->find($message->submittedByUserId);
            }
            $this->notificationService->notifyUserByEmail(
                recipient: $notifyUser,
                subject: "CMS {$reflection->getShortName()} job crashed before finishing",
                content: "CMS {$reflection->getShortName()} encountered an issue during processing and crashed before finishing. {$event
     ->getThrowable()
     ->getMessage()}",
                isUserStaff: true,
            );
        } else {
            $this->logger->debug(
                "Retrying failed message of type: {$reflection->getName()}",
            );
        }
    }
}
