<?php

namespace App\Service;

use App\Entity\Enum\TaskStatus;
use App\Entity\Enum\TaskTrackerType;
use App\Entity\KycReport;
use App\Entity\TaskTracker;
use App\Repository\TaskTrackerRepository;
use Psr\Log\LoggerInterface;

/**
 * Support service to managerial tasks involving KycReports
 */
class KycReportService
{
    public const array DEFAULT_TASKS = [
        'reviewCheck' => TaskStatus::Pending,
    ];
    public const array DEFAULT_METADATA = [
        'lastReportId' => null,
        'lastRunAt' => null,
    ];

    public function __construct(
        private LoggerInterface $logger,
        private TaskTrackerRepository $taskTrackerRepository,
    ) {}

    public function getTaskTracker(): TaskTracker
    {
        $taskTracker = $this->taskTrackerRepository->findOneBy([
            'taskTrackerType' => TaskTrackerType::KycReportCheck,
        ], ['createdAt' => 'DESC']);
        if (is_null($taskTracker)) {
            $taskTracker = $this->createKycReportCheckTaskTracker();
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
        if (in_array('reviewCheck', array_keys($tasks))) {
            $tasks['reviewCheck'] = $updateToStatus;
        }
        $taskTracker->setTasks($tasks);
        return $taskTracker;
    }

    public function updateTaskTrackerProgress(
        TaskTracker $taskTracker,
        ?string $lastReportId = null,
    ): TaskTracker {
        // $this->logger->debug('Updating task tracker progress');
        $metadata = $taskTracker->getMetadata();
        $metadata['lastRunAt'] = new \DateTime()->format(\DateTime::ATOM);
        if ($lastReportId) {
            $metadata['lastReportId'] = $lastReportId;
        }
        $taskTracker->setMetadata($metadata);
        return $taskTracker;
    }

    private function validateTaskTracker(TaskTracker $taskTracker): TaskTracker
    {
        // Regenerate the tracker if it is currently invalid
        if (!$this->isTaskTrackerValid($taskTracker)) {
            // $this->logger->debug("Regenerating task tracker");
            return $this->createKycReportCheckTaskTracker($taskTracker);
        }
        return $taskTracker;
    }

    private function createKycReportCheckTaskTracker(?TaskTracker $taskTracker = null): TaskTracker
    {
        // Factory reset on an existing instance if provided
        if (!is_null($taskTracker)) {
            $taskTracker->setTaskTrackerType(TaskTrackerType::KycReportCheck);
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
                TaskTrackerType::KycReportCheck,
                self::DEFAULT_TASKS,
                self::DEFAULT_METADATA,
            );
        }
        return $taskTracker;
    }

    public function isSimilarReport(KycReport $report1, KycReport $report2): bool
    {
        if ($report1->subject->getId() != $report2->subject->getId()) {
            return false;
        }
        if ($report1->providerName != $report2->providerName) {
            return false;
        }
        if ($report1->providerReferenceId != $report2->providerReferenceId) {
            return false;
        }
        if ($report1->checkType != $report2->checkType) {
            return false;
        }
        if ($report1->result != $report2->result) {
            return false;
        }
        if ($report1->score != $report2->score) {
            return false;
        }
        if ($report1->verified != $report2->verified) {
            return false;
        }
        return true;
    }

    private function isTaskTrackerValid(TaskTracker $taskTracker): bool
    {
        /**
         * - Have at least the default metadata fields
         * - Not missing any tasks
         * - No extra tasks
         * - Task statuses are all TaskStatus enums
         */
        // $this->logger->debug("Kyc report check task metadata", $taskTracker->getMetadata());
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
