<?php

namespace App\Entity;

use App\Entity\Enum\TaskStatus;
use App\Entity\Enum\TaskTrackerType;
use App\Repository\TaskTrackerRepository;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: TaskTrackerRepository::class)]
#[ORM\HasLifecycleCallbacks]
class TaskTracker
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct(
        #[ORM\Column]
        private TaskTrackerType $taskTrackerType,
        #[ORM\Column]
        private array $tasks = [],
        #[ORM\Column(nullable: true)]
        private array $metadata = [],
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTasks(): array
    {
        return $this->tasks;
    }

    public function setTasks(array $tasks): self
    {
        $this->tasks = $tasks;

        return $this;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getTaskTrackerType(): ?TaskTrackerType
    {
        return $this->taskTrackerType;
    }

    public function setTaskTrackerType(TaskTrackerType $taskTrackerType): self
    {
        $this->taskTrackerType = $taskTrackerType;

        return $this;
    }

    #[ORM\PostLoad]
    public function validateTaskStatuses(): void
    {
        // Convert the status strings back into TaskStatus enums
        // If the status string is invalid, default to Pending state
        foreach ($this->tasks as $taskName => $taskStatus) {
            if (!$taskStatus instanceof TaskStatus) {
                $this->tasks[$taskName] =
                    TaskStatus::tryFrom($taskStatus) ?? TaskStatus::Pending;
            }
        }
        ;
    }
}
