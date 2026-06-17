<?php

namespace App\Entity;

use App\Entity\Enum\QuestionType;
use App\Entity\Traits\AssociationBlameableEntity;
use App\Repository\UserAssessmentRepository;
use App\Service\Util\Helper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: UserAssessmentRepository::class)]
class UserAssessment implements \JsonSerializable
{
    use AssociationBlameableEntity;
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'assessments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?OnboardingProfile $profile = null;

    #[ORM\Column(nullable: true)]
    private ?bool $passed = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $expiry = null;

    /**
     * @var Collection<int, AssessmentResponse>
     */
    #[ORM\OneToMany(
        mappedBy: 'assessment',
        targetEntity: AssessmentResponse::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    private Collection $responses;

    #[ORM\Column]
    private bool $complete = false;

    public function __construct(
        #[ORM\Column(nullable: true, enumType: QuestionType::class)]
        private ?QuestionType $questionType = null,
    ) {
        $this->responses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProfile(): ?OnboardingProfile
    {
        return $this->profile;
    }

    public function setProfile(?OnboardingProfile $profile): static
    {
        $this->profile = $profile;

        return $this;
    }

    public function isPassed(): ?bool
    {
        return $this->passed;
    }

    public function setPassed(?bool $passed): static
    {
        $this->passed = $passed;

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

    public function getExpiry(): ?\DateTimeInterface
    {
        return $this->expiry;
    }

    public function setExpiry(?\DateTimeInterface $expiry): static
    {
        $this->expiry = $expiry;

        return $this;
    }

    /**
     * @return Collection<int, AssessmentResponse>
     */
    public function getResponses(): Collection
    {
        return $this->responses;
    }

    public function addResponse(AssessmentResponse $response): static
    {
        if (!$this->responses->contains($response)) {
            $this->responses->add($response);
            $response->setAssessment($this);
        }

        return $this;
    }

    public function removeResponse(AssessmentResponse $response): static
    {
        if ($this->responses->removeElement($response)) {
            // set the owning side to null (unless already changed)
            if ($response->getAssessment() === $this) {
                $response->setAssessment(null);
            }
        }

        return $this;
    }

    public function isComplete(): bool
    {
        return $this->complete;
    }

    public function setComplete(bool $complete): static
    {
        $this->complete = $complete;

        return $this;
    }

    /**
     * Compatibility mode for APIv1 self route with fields in snake_case
     *
     * For newer APIs, use a proper serializer
     *
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->getId(),
            'userId' => $this->getProfile()->getUser()->getId(),
            'passed' => $this->isPassed(),
            'complete' => $this->isComplete(),
            'expiry' => Helper::formatDate($this->getExpiry()),
            'notes' => $this->getNotes(),
            'responses' => $this->getResponses()->getValues(),
        ];
    }

    public function getQuestionType(): ?QuestionType
    {
        return $this->questionType;
    }

    public function setQuestionType(?QuestionType $questionType): static
    {
        $this->questionType = $questionType;

        return $this;
    }
}
