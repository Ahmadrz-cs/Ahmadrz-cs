<?php

namespace App\Scheduler\Task;

use Psr\Log\LoggerInterface;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

#[AsPeriodicTask(frequency: '20 seconds', jitter: 5, schedule: 'liveness_log')]
class PeriodicDebugLog
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function __invoke()
    {
        $this->logger->debug(message: 'Scheduled log test - timestamp: ' . time());
    }
}
