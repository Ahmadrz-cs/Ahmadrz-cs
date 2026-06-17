<?php

namespace App\Entity;

use App\Entity\Enum\KycReviewStatus;
use App\Entity\Enum\KycReviewType;
use App\Entity\User;
use App\Repository\KycReviewRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: KycReviewRepository::class)]
class KycReview implements \JsonSerializable
{
    use BlameableEntity;
    use TimestampableEntity;

    #[ORM\Column, ORM\Id, ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, enumType: KycReviewStatus::class, options: [
        'default' => KycReviewStatus::Open,
    ])]
    private KycReviewStatus $status = KycReviewStatus::Open;

    #[ORM\Column(nullable: true)]
    private ?bool $decision = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column]
    private bool $identityReview = false;

    #[ORM\Column]
    private bool $addressReview = false;

    #[ORM\Column]
    private bool $countryReview = false;

    #[ORM\Column]
    private bool $kycProviderReview = false;

    #[ORM\Column]
    private bool $dueDiligenceLevelReview = false;

    #[ORM\Column]
    private bool $kycSurveyReview = false;

    #[ORM\Column]
    private bool $transactionsReview = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $completedAt = null;

    public function __construct(
        #[ORM\Column(type: Types::STRING, enumType: KycReviewType::class, options: [
            'default' => KycReviewType::Adhoc,
        ])]
        private KycReviewType $reviewType,
        #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'kycReviews')]
        #[ORM\JoinColumn(nullable: false)]
        private UserInterface&User $subject,
        #[ORM\ManyToOne]
        private ?User $reviewedBy = null,
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): KycReviewStatus
    {
        return $this->status;
    }

    public function setStatus(KycReviewStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getReviewType(): KycReviewType
    {
        return $this->reviewType;
    }

    public function setReviewType(KycReviewType $reviewType): self
    {
        $this->reviewType = $reviewType;

        return $this;
    }

    public function getSubject(): ?User
    {
        return $this->subject;
    }

    public function setSubject(?User $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getReviewedBy(): ?User
    {
        return $this->reviewedBy;
    }

    public function setReviewedBy(?User $reviewedBy): self
    {
        $this->reviewedBy = $reviewedBy;

        return $this;
    }

    public function isDecision(): ?bool
    {
        return $this->decision;
    }

    public function setDecision(?bool $decision): self
    {
        $this->decision = $decision;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    public function isIdentityReview(): bool
    {
        return $this->identityReview;
    }

    public function setIdentityReview(bool $identityReview): self
    {
        $this->identityReview = $identityReview;

        return $this;
    }

    public function isAddressReview(): bool
    {
        return $this->addressReview;
    }

    public function setAddressReview(bool $addressReview): self
    {
        $this->addressReview = $addressReview;

        return $this;
    }

    public function isCountryReview(): bool
    {
        return $this->countryReview;
    }

    public function setCountryReview(bool $countryReview): self
    {
        $this->countryReview = $countryReview;

        return $this;
    }

    public function isKycProviderReview(): bool
    {
        return $this->kycProviderReview;
    }

    public function setKycProviderReview(bool $kycProviderReview): self
    {
        $this->kycProviderReview = $kycProviderReview;

        return $this;
    }

    public function isDueDiligenceLevelReview(): bool
    {
        return $this->dueDiligenceLevelReview;
    }

    public function setDueDiligenceLevelReview(bool $dueDiligenceLevelReview): self
    {
        $this->dueDiligenceLevelReview = $dueDiligenceLevelReview;

        return $this;
    }

    public function isKycSurveyReview(): bool
    {
        return $this->kycSurveyReview;
    }

    public function setKycSurveyReview(bool $kycSurveyReview): self
    {
        $this->kycSurveyReview = $kycSurveyReview;

        return $this;
    }

    public function isTransactionsReview(): bool
    {
        return $this->transactionsReview;
    }

    public function setTransactionsReview(bool $transactionsReview): self
    {
        $this->transactionsReview = $transactionsReview;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeInterface $completedAt): self
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    /**
     * Compatibility mode for APIv1
     *
     * For newer APIs, use a proper serializer
     */
    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->getId(),
            'status' => $this->getStatus()->value,
            'decision' => $this->isDecision(),
            'notes' => $this->getNotes(),
            'identityReview' => $this->isIdentityReview(),
            'addressReview' => $this->isAddressReview(),
            'countryReview' => $this->isCountryReview(),
            'kycProviderReview' => $this->isKycProviderReview(),
            'dueDiligenceLevelReview' => $this->isDueDiligenceLevelReview(),
            'kycSurveyReview' => $this->isKycSurveyReview(),
            'transactionsReview' => $this->isTransactionsReview(),
            'reviewType' => $this->getReviewType()->value,
            'subjectId' => $this->getSubject()->getId(),
            'reviewedById' => $this->getReviewedBy()?->getId(),
        ];
    }
}
