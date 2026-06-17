<?php

namespace App\Entity;

use App\Entity\Enum\UserStatus;
use App\Repository\UserStatusLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: UserStatusLogRepository::class)]
class UserStatusLog
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $notes = null;

    #[ORM\ManyToOne]
    private ?User $transitionedBy = null;

    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'statusLogs')]
        #[ORM\JoinColumn(nullable: false)]
        private ?User $user = null,
        #[ORM\Column(enumType: UserStatus::class)]
        private ?UserStatus $status = UserStatus::Pending,
        #[ORM\Column]
        private ?\DateTime $occuredAt = new \DateTime(),
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getStatus(): ?UserStatus
    {
        return $this->status;
    }

    public function setStatus(UserStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getOccuredAt(): ?\DateTime
    {
        return $this->occuredAt;
    }

    public function setOccuredAt(\DateTime $occuredAt): static
    {
        $this->occuredAt = $occuredAt;

        return $this;
    }

    public function getTransitionedBy(): ?User
    {
        return $this->transitionedBy;
    }

    public function setTransitionedBy(?User $transitionedBy): static
    {
        $this->transitionedBy = $transitionedBy;

        return $this;
    }
}
