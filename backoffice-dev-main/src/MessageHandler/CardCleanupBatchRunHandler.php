<?php

namespace App\MessageHandler;

use App\Entity\TaskTracker;
use App\Message\CardCleanupBatchRun;
use App\Repository\UserRepository;
use App\Service\CardCleanupService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Message\RedispatchMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

#[AsMessageHandler]
class CardCleanupBatchRunHandler
{
    // Could adjust the delay based on Mangopay API rate limit
    public const int AUTO_CONTINUE_DELAY = 15000; // in milliseconds

    public ?\Exception $orderRunException = null;

    public function __construct(
        private LoggerInterface $logger,
        private MessageBusInterface $bus,
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private CardCleanupService $cardCleanupService,
        private NotificationService $notificationService,
    ) {}

    public function __invoke(CardCleanupBatchRun $message): void
    {
        // Clear any exception set from previous invocations
        $this->orderRunException = null;
        $this->logger->info(
            "Running CardCleanupBatchRun with batch size {$message->batchSize} and job size {$message->jobSize} ",
        );

        $taskTracker = $this->cardCleanupService->getTaskTracker();
        if (is_null($taskTracker->getId())) {
            $this->em->persist($taskTracker);
            $this->em->flush();
        }
        $jobSizeRemaining =
            $message->jobSize - $taskTracker->getMetadata()['currentJobCleanupCount'];
        $this->logger->debug(
            'Card cleanup job previously deactivated '
            . $taskTracker->getMetadata()['currentJobCleanupCount']
            . " cards. {$jobSizeRemaining} remaining.",
        );

        $continue = $this->runCleanup($message, $taskTracker);
        // Either continue the batch run, or notify user of its completion/termination
        if ($continue) {
            $this->logger->debug('Redispatching message for continued run after delay: '
            . self::AUTO_CONTINUE_DELAY
            . 'ms');
            $this->bus->dispatch(new RedispatchMessage(
                new Envelope($message, [new DelayStamp(self::AUTO_CONTINUE_DELAY)]),
                'async',
            ));
        } else {
            $this->cardCleanupService->setJobInProgress($taskTracker, false);
            $this->em->flush();

            $notificationContent =
                'CardCleanupBatchRun has finished. Deactivated '
                . $taskTracker->getMetadata()['currentJobCleanupCount']
                . ' cards';
            if ($this->orderRunException) {
                $notificationContent .= " Some issues were encountered during the run, including {$this->orderRunException->getMessage()}.";
            }
            $notificationContent .= '. Check card cleanup tool for more info.';
            $this->logger->info($notificationContent);

            $recipient = $this->userRepository->find($message->submittedByUserId);
            $this->notificationService->notifyUserByEmail(
                recipient: $recipient,
                subject: 'CMS CardCleanupBatchRun job finished',
                content: $notificationContent,
                isUserStaff: true,
            );
        }
    }

    private function runCleanup(
        CardCleanupBatchRun $message,
        TaskTracker $taskTracker,
    ): bool {
        if (!$taskTracker->getMetadata()['jobInProgress']) {
            $this->logger->warning(
                'Card cleanup jobInProgress set to false, ending run without further actions.',
            );
            return false;
        }

        $jobSizeRemaining =
            $message->jobSize - $taskTracker->getMetadata()['currentJobCleanupCount'];
        $batchSize = min($jobSizeRemaining, $message->batchSize);
        if ($batchSize < 1) {
            $this->logger->debug('No cleanups remaining for current job.');
            return false;
        }

        $userToProcess = $this->userRepository->findNextMangopayReadyUser(
            $taskTracker->getMetadata()['lastUserId'],
        );
        if ($userToProcess === null) {
            $this->logger->notice('Card cleanup round completed.');
            return false;
        }
        $deactivated = [];
        try {
            ['cards' => $cards, 'pagination' => $pagination] =
                $this->cardCleanupService->listCardsForUser($userToProcess);
            $deactivated = $this->cardCleanupService->cleanupCards(
                taskTracker: $taskTracker,
                cards: $cards,
                currentUserId: $userToProcess->getId(),
                totalItems: $pagination->TotalItems,
                batchSize: $batchSize,
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Card cleanup run encountered some non-terminal issues: '
                    . $e->getMessage(),
            );
            $this->orderRunException = $e;
        } finally {
            $this->em->flush();
        }
        $this->em->flush();
        $this->logger->info(
            'Card cleanup job deactivated ' . count($deactivated) . ' cards:',
            $deactivated,
        );

        if (count($cards) > 0 && count($deactivated) === 0) {
            // No cards were cleaned up despite there being some to
            // Something may have gone wrong - don't continue run
            return false;
        }

        $jobSizeRemaining =
            $message->jobSize - $taskTracker->getMetadata()['currentJobCleanupCount'];
        if ($message->autoContinue && $jobSizeRemaining > 0) {
            // Continue if not reached there are still cleanups to go in current job
            return true;
        }
        return false;
    }
}
