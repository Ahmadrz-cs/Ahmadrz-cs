<?php

namespace App\Entity;

use App\Entity\Enum\AccountFeature;
use App\Entity\Enum\UserCategory;
use App\Entity\Traits\AssociationBlameableEntity;
use App\Entity\User;
use App\Repository\OnboardingProfileRepository;
use App\Service\Util\Helper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: OnboardingProfileRepository::class)]
class OnboardingProfile implements \JsonSerializable
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(mappedBy: 'onboardingProfile', cascade: ['persist', 'remove'])]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $cooloffEnd = null;

    #[ORM\Column(nullable: true)]
    private ?bool $cooloffAccepted = null;

    #[ORM\Column(nullable: true)]
    private ?bool $riskWarningAccepted = null;

    #[ORM\Column(nullable: true, enumType: UserCategory::class)]
    private ?UserCategory $category = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $categoryReviewedAt = null;

    #[ORM\Column(nullable: true)]
    private ?bool $assessmentPassed = null;

    /**
     * @var Collection<int, UserCategorisation>
     */
    #[ORM\OneToMany(
        mappedBy: 'profile',
        targetEntity: UserCategorisation::class,
        orphanRemoval: true,
    )]
    #[ORM\OrderBy(['createdAt' => 'DESC', 'id' => 'DESC'])]
    private Collection $categorisations;

    /**
     * @var Collection<int, UserAssessment>
     */
    #[ORM\OneToMany(
        mappedBy: 'profile',
        targetEntity: UserAssessment::class,
        orphanRemoval: true,
    )]
    #[ORM\OrderBy(['createdAt' => 'DESC', 'id' => 'DESC'])]
    private Collection $assessments;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $realEstatePlanAccess = false;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $realEstateBuildAccess = false;

    public function __construct()
    {
        $this->categorisations = new ArrayCollection();
        $this->assessments = new ArrayCollection();
    }

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
        // Users registered before full PS22/10 rules do NOT need to retake the assessment by default
        $cutoff = new \DateTime('2023-02-01');
        if (
            is_null($this->assessmentPassed)
            && $user->getCreatedAt()
            && $user->getCreatedAt() < $cutoff
        ) {
            $this->setAssessmentPassed(true);
        }
        return $this;
    }

    public function getCooloffEnd(): ?\DateTimeInterface
    {
        if (is_null($this->cooloffEnd) && $this->user) {
            $userCreated = \DateTime::createFromInterface(
                $this->user->getCreatedAt() ?? new \Datetime(),
            );
            $cooloff = $userCreated->modify('+1 day');
            $this->setCooloffEnd($cooloff);
        }
        return $this->cooloffEnd;
    }

    public function setCooloffEnd(?\DateTimeInterface $cooloffEnd): static
    {
        $this->cooloffEnd = $cooloffEnd;

        return $this;
    }

    public function isCooloffAccepted(): ?bool
    {
        return $this->cooloffAccepted;
    }

    public function setCooloffAccepted(?bool $cooloffAccepted): static
    {
        $this->cooloffAccepted = $cooloffAccepted;

        return $this;
    }

    public function isRiskWarningAccepted(): ?bool
    {
        return $this->riskWarningAccepted;
    }

    public function setRiskWarningAccepted(?bool $riskWarningAccepted): static
    {
        $this->riskWarningAccepted = $riskWarningAccepted;

        return $this;
    }

    public function getCategory(): ?UserCategory
    {
        return $this->category;
    }

    public function setCategory(?UserCategory $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getCategoryReviewedAt(): ?\DateTimeInterface
    {
        return $this->categoryReviewedAt;
    }

    public function setCategoryReviewedAt(?\DateTimeInterface $categoryReviewedAt): static
    {
        $this->categoryReviewedAt = $categoryReviewedAt;

        return $this;
    }

    public function isAssessmentPassed(): ?bool
    {
        return $this->assessmentPassed;
    }

    public function setAssessmentPassed(?bool $assessmentPassed): static
    {
        $this->assessmentPassed = $assessmentPassed;

        return $this;
    }

    /**
     * @return Collection<int, UserCategorisation>
     */
    public function getCategorisations(): Collection
    {
        return $this->categorisations;
    }

    public function addCategorisation(UserCategorisation $categorisation): static
    {
        if (!$this->categorisations->contains($categorisation)) {
            $this->categorisations->add($categorisation);
            $categorisation->setProfile($this);
        }

        return $this;
    }

    public function removeCategorisation(UserCategorisation $categorisation): static
    {
        if ($this->categorisations->removeElement($categorisation)) {
            // set the owning side to null (unless already changed)
            if ($categorisation->getProfile() === $this) {
                $categorisation->setProfile(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserAssessment>
     */
    public function getAssessments(): Collection
    {
        return $this->assessments;
    }

    public function addAssessment(UserAssessment $assessment): static
    {
        if (!$this->assessments->contains($assessment)) {
            $this->assessments->add($assessment);
            $assessment->setProfile($this);
        }

        return $this;
    }

    public function removeAssessment(UserAssessment $assessment): static
    {
        if ($this->assessments->removeElement($assessment)) {
            // set the owning side to null (unless already changed)
            if ($assessment->getProfile() === $this) {
                $assessment->setProfile(null);
            }
        }

        return $this;
    }

    /**
     * Compatibility mode for APIv1
     *
     * For newer APIs, use a proper serializer
     *
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        $lastAssessment = $this
            ->getAssessments()
            ->filter(fn(UserAssessment $a) => $a?->isComplete())
            ->first();
        return [
            'cooloffEnd' => Helper::formatDate($this->getCooloffEnd()),
            'cooloffAccepted' => $this->isCooloffAccepted(),
            'riskWarningAccepted' => $this->isRiskWarningAccepted(),
            'category' => $this->getCategory(),
            'categoryReviewedAt' => Helper::formatDate($this->getCategoryReviewedAt()),
            'assessmentPassed' => $this->isAssessmentPassed(),
            'assessmentAttempts' => $this
                ->getAssessments()
                ->filter(fn(UserAssessment $a) => $a?->isComplete())
                ->count(),
            'assessmentAttemptedAt' => Helper::formatDate(
                $lastAssessment ? $lastAssessment->getCreatedAt() : null,
            ),
        ];
    }

    public function isRealEstatePlanAccess(): bool
    {
        return $this->realEstatePlanAccess;
    }

    public function setRealEstatePlanAccess(bool $realEstatePlanAccess): static
    {
        $this->realEstatePlanAccess = $realEstatePlanAccess;

        return $this;
    }

    public function isRealEstateBuildAccess(): bool
    {
        return $this->realEstateBuildAccess;
    }

    public function setRealEstateBuildAccess(bool $realEstateBuildAccess): static
    {
        $this->realEstateBuildAccess = $realEstateBuildAccess;

        return $this;
    }
}
