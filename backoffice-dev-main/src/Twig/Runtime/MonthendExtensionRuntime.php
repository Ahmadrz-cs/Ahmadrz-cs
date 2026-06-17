<?php

namespace App\Twig\Runtime;

use App\Entity\Asset;
use App\Entity\Enum\TaskTrackerType;
use App\Service\MonthEndTaskTrackerService;
use Twig\Extension\RuntimeExtensionInterface;

class MonthendExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private MonthEndTaskTrackerService $monthEndTaskTrackerService,
    ) {
        // Inject dependencies if needed
        $this->monthEndTaskTrackerService = $monthEndTaskTrackerService;
    }

    public function monthendChecklist(Asset $asset)
    {
        $taskTracker = $asset->getTaskTracker();
        if (is_null($taskTracker)) {
            $taskTracker = $this->monthEndTaskTrackerService->createMonthendTaskTracker(TaskTrackerType::AssetMonthend);
            $asset->setTaskTracker($taskTracker);
        } else {
            $taskTracker =
                $this->monthEndTaskTrackerService->validateTaskTracker($taskTracker);
        }
        return $taskTracker->getTasks();
    }
}
