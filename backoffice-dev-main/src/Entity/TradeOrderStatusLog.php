<?php

namespace App\Entity;

use App\Entity\Enum\TradeOrderStatus;
use App\Repository\TradeOrderStatusLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: TradeOrderStatusLogRepository::class)]
class TradeOrderStatusLog
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
        private ?TradeOrder $tradeOrder = null,
        #[ORM\Column(enumType: TradeOrderStatus::class)]
        private TradeOrderStatus $status = TradeOrderStatus::Draft,
        #[ORM\Column(type: Types::DATETIME_MUTABLE)]
        private \DateTimeInterface $occuredAt = new \DateTime(),
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTradeOrder(): ?TradeOrder
    {
        return $this->tradeOrder;
    }

    public function setTradeOrder(?TradeOrder $tradeOrder): static
    {
        $this->tradeOrder = $tradeOrder;

        return $this;
    }

    public function getStatus(): TradeOrderStatus
    {
        return $this->status;
    }

    public function setStatus(TradeOrderStatus $status): static
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
