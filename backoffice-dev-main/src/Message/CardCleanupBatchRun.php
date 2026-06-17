<?php

namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('async')]
readonly class CardCleanupBatchRun
{
    // Maximum number of cleanups per run
    public const int BATCH_LIMIT = 10;
    // Maximum number of cleanups before job stops even with autoContinue
    public const int JOB_LIMIT = 50;

    public int $batchSize;
    public int $jobSize;

    public function __construct(
        public int $submittedByUserId,
        public bool $autoContinue = true,
        int $batchSize = self::BATCH_LIMIT,
        int $jobSize = self::JOB_LIMIT,
    ) {
        $this->batchSize = min(self::BATCH_LIMIT, $batchSize, $jobSize);
        $this->jobSize = min(self::JOB_LIMIT, $jobSize);
    }
}
