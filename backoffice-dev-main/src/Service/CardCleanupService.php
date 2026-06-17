<?php

namespace App\Service;

use App\Entity\Enum\TaskStatus;
use App\Entity\Enum\TaskTrackerType;
use App\Entity\TaskTracker;
use App\Entity\User;
use App\Repository\TaskTrackerRepository;
use App\Service\MangopayWalletService;
use MangoPay\Card;
use MangoPay\Pagination;
use Psr\Log\LoggerInterface;

class CardCleanupService
{
    public const array DEFAULT_TASKS = [
        'cardCleanup' => TaskStatus::Pending,
    ];
    public const array DEFAULT_METADATA = [
        'lastUserId' => null,
        'lastRunAt' => null,
        'lastRunCleanupCount' => 0, // most recent single run
        'currentRoundCleanupCount' => 0, // current round of cleanup where one round is a cleanup of all users
        'currentJobCleanupCount' => 0, // for background job system chaining multiple runs
        'totalCleanupCount' => 0, // lifetime counter
        'jobInProgress' => false, // whether a background job is currently being process
    ];

    public function __construct(
        private LoggerInterface $logger,
        private TaskTrackerRepository $taskTrackerRepository,
        private MangopayWalletService $walletService,
    ) {}

    /**
     * @return array{cards: Card[], pagination: Pagination}
     */
    public function listCardsForUser(User $user): array
    {
        $pagination = new Pagination();
        try {
            $cards = $this->walletService->listUserCards(
                $user->getMangoPayUserId(),
                true,
                $pagination,
            );
        } catch (\Mangopay\Libraries\ResponseException $th) {
            $this->logger->error('Unable to retrieve user cards', [
                $th->getMessage(),
                $th->GetErrorDetails()?->Errors,
            ]);
        } catch (\Throwable $th) {
            $this->logger->error('Unable to retrieve user cards', [$th->getMessage()]);
        }
        return ['cards' => $cards ?? [], 'pagination' => $pagination];
    }

    /**
     * @param Card[] $cards
     * @return string[]
     */
    public function cleanupCards(
        TaskTracker $taskTracker,
        array $cards,
        string $currentUserId,
        int $totalItems,
        int $batchSize,
    ): array {
        // $this->logger->debug('Cleaning up cards');
        $deactivated = [];
        try {
            $this->updateTaskStatusInTracker($taskTracker, TaskStatus::Started);
            foreach ($cards as $card) {
                // $this->logger->debug("Deactivating {$card->Id}");
                $this->walletService->deactivateCard($card);
                $deactivated[] = $card->Id;
                if (count($deactivated) >= $batchSize) {
                    break;
                }
            }
            $itemsLeft = $totalItems - count($deactivated);
            $this->updateTaskTrackerProgress(
                taskTracker: $taskTracker,
                lastRunCount: count($deactivated),
                lastUserId: $itemsLeft < 1 ? $currentUserId : null,
            );
        } catch (\Mangopay\Libraries\ResponseException $th) {
            $this->logger->error('Unable to deactivate card', [
                $th->getMessage(),
                $th->GetErrorDetails()?->Errors,
            ]);
        } catch (\Throwable $th) {
            $this->logger->error('Unable to deactivate card', [$th->getMessage()]);
        }
        return $deactivated;
    }

    public function getTaskTracker(): TaskTracker
    {
        $taskTracker = $this->taskTrackerRepository->findOneBy([
            'taskTrackerType' => TaskTrackerType::CardCleanup,
        ], ['createdAt' => 'DESC']);
        if (is_null($taskTracker)) {
            $taskTracker = $this->createCardCleanupTaskTracker();
        } else {
            $taskTracker = $this->validateTaskTracker($taskTracker);
        }
        return $taskTracker;
    }

    public function updateTaskStatusInTracker(
        TaskTracker $taskTracker,
        TaskStatus $updateToStatus,
    ): TaskTracker {
        $tasks = $taskTracker->getTasks();
        if (in_array('cardCleanup', array_keys($tasks))) {
            $tasks['cardCleanup'] = $updateToStatus;
        }
        $taskTracker->setTasks($tasks);
        return $taskTracker;
    }

    public function updateTaskTrackerProgress(
        TaskTracker $taskTracker,
        int $lastRunCount,
        ?string $lastUserId = null,
    ): TaskTracker {
        // $this->logger->debug('Updating task tracker progress');
        $metadata = $taskTracker->getMetadata();
        $metadata['lastRunAt'] = new \DateTime()->format(\DateTime::ATOM);
        $metadata['lastRunCleanupCount'] = $lastRunCount;
        $metadata['currentRoundCleanupCount'] += $lastRunCount;
        $metadata['currentJobCleanupCount'] += $lastRunCount;
        $metadata['totalCleanupCount'] += $lastRunCount;
        if ($lastUserId) {
            $metadata['lastUserId'] = $lastUserId;
        }
        $taskTracker->setMetadata($metadata);
        return $taskTracker;
    }

    public function resetTaskTracker(TaskTracker $taskTracker): TaskTracker
    {
        $this->validateTaskTracker($taskTracker);
        $this->updateTaskStatusInTracker($taskTracker, TaskStatus::Completed);

        $metadata = $taskTracker->getMetadata();
        $metadata['lastUserId'] = null;
        $metadata['lastRunCleanupCount'] = 0;
        $metadata['currentRoundCleanupCount'] = 0;
        $metadata['currentJobCleanupCount'] = 0;
        $metadata['jobInProgress'] = false;

        // lastRunAt and totalCleanupCount should be retained unless they're invalid
        // Sanity check the lastRunAt - must be a valid datetime string
        if (
            !is_string($metadata['lastRunAt'])
            || is_string($metadata['lastRunAt']) && !strtotime($metadata['lastRunAt'])
        ) {
            $metadata['lastRunAt'] = null;
        }
        // Sanity check the totalCleanupCount - must be an int
        if (!is_int($metadata['totalCleanupCount'])) {
            $metadata['totalCleanupCount'] = is_numeric($metadata['totalCleanupCount'])
                ? (int) $metadata['totalCleanupCount']
                : 0;
        }
        $taskTracker->setMetadata($metadata);
        return $taskTracker;
    }

    public function resetTaskTrackerJobCounter(TaskTracker $taskTracker): TaskTracker
    {
        $taskTracker = $this->validateTaskTracker($taskTracker);
        $metadata = $taskTracker->getMetadata();
        $metadata['currentJobCleanupCount'] = 0;
        $taskTracker->setMetadata($metadata);
        return $taskTracker;
    }

    public function setJobInProgress(
        TaskTracker $taskTracker,
        bool $jobInProgress,
    ): TaskTracker {
        $taskTracker = $this->validateTaskTracker($taskTracker);
        $metadata = $taskTracker->getMetadata();
        $metadata['jobInProgress'] = $jobInProgress;
        $taskTracker->setMetadata($metadata);
        return $taskTracker;
    }

    private function validateTaskTracker(TaskTracker $taskTracker): TaskTracker
    {
        // Regenerate the tracker if it is currently invalid
        if (!$this->isTaskTrackerValid($taskTracker)) {
            // $this->logger->debug("Regenerating task tracker");
            return $this->createCardCleanupTaskTracker($taskTracker);
        }
        return $taskTracker;
    }

    private function createCardCleanupTaskTracker(?TaskTracker $taskTracker = null): TaskTracker
    {
        // Factory reset on an existing instance if provided
        if (!is_null($taskTracker)) {
            $taskTracker->setTaskTrackerType(TaskTrackerType::CardCleanup);
            $taskTracker->setTasks(self::DEFAULT_TASKS);
            // Repopulate with existing metadata values
            $metadata = array_filter(
                $taskTracker->getMetadata(),
                fn($key) => in_array($key, array_keys(self::DEFAULT_METADATA)),
                ARRAY_FILTER_USE_KEY,
            );
            $taskTracker->setMetadata(array_replace(self::DEFAULT_METADATA, $metadata));
        } else {
            $taskTracker = new TaskTracker(
                TaskTrackerType::CardCleanup,
                self::DEFAULT_TASKS,
                self::DEFAULT_METADATA,
            );
        }
        return $taskTracker;
    }

    private function isTaskTrackerValid(TaskTracker $taskTracker): bool
    {
        /**
         * - Have at least the default metadata fields
         * - Not missing any tasks
         * - No extra tasks
         * - Task statuses are all TaskStatus enums
         */
        // $this->logger->debug("Card cleanup task metadata", $taskTracker->getMetadata());
        if (
            !empty(array_diff(
                array_keys(self::DEFAULT_METADATA),
                array_keys($taskTracker->getMetadata()),
            ))
            || !empty(array_diff(
                array_keys(self::DEFAULT_TASKS),
                array_keys($taskTracker->getTasks()),
            ))
            || !empty(array_diff(
                array_keys($taskTracker->getTasks()),
                array_keys(self::DEFAULT_TASKS),
            ))
            || !empty(array_filter(
                $taskTracker->getTasks(),
                fn(mixed $taskStatus) => !$taskStatus instanceof TaskStatus,
            ))
        ) {
            return false;
        }
        return true;
    }
}
