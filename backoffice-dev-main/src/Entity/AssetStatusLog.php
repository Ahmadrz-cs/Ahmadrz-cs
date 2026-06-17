<?php

namespace App\Entity;

use App\Entity\Asset;
use App\Entity\Enum\AssetStatus;
use App\Repository\AssetStatusLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: AssetStatusLogRepository::class)]
class AssetStatusLog
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
        private ?Asset $asset = null,
        #[ORM\Column(enumType: AssetStatus::class)]
        private ?AssetStatus $status = null,
        #[ORM\Column(type: Types::DATETIME_MUTABLE)]
        private ?\DateTimeInterface $occuredAt = new \DateTime(),
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAsset(): ?Asset
    {
        return $this->asset;
    }

    public function setAsset(?Asset $asset): static
    {
        $this->asset = $asset;

        return $this;
    }

    public function getStatus(): ?AssetStatus
    {
        return $this->status;
    }

    public function setStatus(AssetStatus $status): static
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

    public function getOccuredAt(): ?\DateTimeInterface
    {
        return $this->occuredAt;
    }

    public function setOccuredAt(\DateTimeInterface $occuredAt): static
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
