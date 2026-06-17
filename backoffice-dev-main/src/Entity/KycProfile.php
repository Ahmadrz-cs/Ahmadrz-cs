<?php

namespace App\Entity;

use App\Entity\Enum\KycDueDiligenceLevel;
use App\Entity\User;
use App\Repository\KycProfileRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: KycProfileRepository::class)]
class KycProfile
{
    use BlameableEntity;
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private bool $verified = false;

    #[ORM\ManyToOne]
    private ?User $verifiedBy = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastReviewedAt = null;

    #[ORM\Column]
    private KycDueDiligenceLevel $dueDiligenceLevel = KycDueDiligenceLevel::Standard;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $buyRestricted = false;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $sellRestricted = false;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $depositRestricted = false;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $withdrawRestricted = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function setVerified(bool $verified): self
    {
        $this->verified = $verified;

        return $this;
    }

    public function getVerifiedBy(): ?User
    {
        return $this->verifiedBy;
    }

    public function setVerifiedBy(?User $verifiedBy): self
    {
        $this->verifiedBy = $verifiedBy;

        return $this;
    }

    public function getLastReviewedAt(): ?\DateTimeInterface
    {
        return $this->lastReviewedAt;
    }

    public function setLastReviewedAt(?\DateTimeInterface $lastReviewedAt): self
    {
        $this->lastReviewedAt = $lastReviewedAt;

        return $this;
    }

    public function getDueDiligenceLevel(): KycDueDiligenceLevel
    {
        return $this->dueDiligenceLevel;
    }

    public function setDueDiligenceLevel(KycDueDiligenceLevel $dueDiligenceLevel): self
    {
        $this->dueDiligenceLevel = $dueDiligenceLevel;

        return $this;
    }

    public function isBuyRestricted(): bool
    {
        return $this->buyRestricted;
    }

    public function setBuyRestricted(bool $buyRestricted): static
    {
        $this->buyRestricted = $buyRestricted;

        return $this;
    }

    public function isSellRestricted(): bool
    {
        return $this->sellRestricted;
    }

    public function setSellRestricted(bool $sellRestricted): static
    {
        $this->sellRestricted = $sellRestricted;

        return $this;
    }

    public function isDepositRestricted(): bool
    {
        return $this->depositRestricted;
    }

    public function setDepositRestricted(bool $depositRestricted): static
    {
        $this->depositRestricted = $depositRestricted;

        return $this;
    }

    public function isWithdrawRestricted(): bool
    {
        return $this->withdrawRestricted;
    }

    public function setWithdrawRestricted(bool $withdrawRestricted): static
    {
        $this->withdrawRestricted = $withdrawRestricted;

        return $this;
    }
}
