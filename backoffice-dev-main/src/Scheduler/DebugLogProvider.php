<?php

namespace App\Scheduler;

use App\Message\DebugLog;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('debuglog_provider')]
class DebugLogProvider implements ScheduleProviderInterface
{
    private ?Schedule $schedule = null;

    public function getSchedule(): Schedule
    {
        return $this->schedule ??= new Schedule()->with(RecurringMessage::every(
            '20 seconds',
            new DebugLog('Scheduled log test - timestamp: ' . time()),
        )->withJitter(5));
    }
}
