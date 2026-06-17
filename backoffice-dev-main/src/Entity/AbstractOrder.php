<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\MappedSuperclass]
abstract class AbstractOrder
{
    use BlameableEntity;
    use TimestampableEntity;

    public const STATE_DRAFT = 'draft';
    public const STATE_APPROVED = 'approved';
    public const STATE_IN_PROGRESS = 'in_progress';
    public const STATE_COMPLETED = 'completed';
    public const STATE_CLOSED = 'closed';
    public const STATE_ABANDONED = 'abandoned';

    public const TRANSITION_APPROVE = 'approve';
    public const TRANSITION_REQUEST_CHANGE = 'request_change';
    public const TRANSITION_RUN = 'run';
    public const TRANSITION_COMPLETE = 'complete';
    public const TRANSITION_REOPEN = 'reopen';
    public const TRANSITION_REJECT = 'reject';
    public const TRANSITION_ABANDON = 'abandon';

    public const META_TRANSITION_FORCE_COMPLETE = 'force_complete';

    public const ISSUE_LIMIT = 4;

    public const STATES_INCOMPLETE = [
        self::STATE_DRAFT,
        self::STATE_APPROVED,
        self::STATE_IN_PROGRESS,
    ];

    public const STATES_CANCELLED = [
        self::STATE_CLOSED,
        self::STATE_ABANDONED,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[Assert\Length(
        max: 240,
        maxMessage: 'The description cannot be longer than {{ limit }} characters',
    )]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(type: 'date')]
    protected ?\DateTimeInterface $scheduledFor = null;

    #[ORM\Column(type: 'string', length: 255)]
    protected string $status = self::STATE_DRAFT;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getScheduledFor(): ?\DateTimeInterface
    {
        return $this->scheduledFor;
    }

    public function setScheduledFor(\DateTimeInterface $scheduledFor): self
    {
        $this->scheduledFor = $scheduledFor;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }
}
