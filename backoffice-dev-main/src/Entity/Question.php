<?php

namespace App\Entity;

use App\Entity\Enum\QuestionArea;
use App\Entity\Enum\QuestionType;
use App\Entity\Traits\AssociationBlameableEntity;
use App\Repository\QuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: QuestionRepository::class)]
class Question implements \JsonSerializable
{
    use AssociationBlameableEntity;
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true, enumType: QuestionArea::class)]
    private ?QuestionArea $section = null;

    #[ORM\Column(length: 255)]
    private ?string $content = null;

    #[ORM\Column]
    private bool $active = false;

    #[ORM\Column]
    private bool $locked = false;

    /**
     * @var Collection<int, QuestionChoice>
     */
    #[ORM\OneToMany(
        mappedBy: 'question',
        targetEntity: QuestionChoice::class,
        orphanRemoval: true,
    )]
    private Collection $choices;

    /**
     * @var Collection<int, AssessmentResponse>
     */
    #[ORM\OneToMany(
        mappedBy: 'question',
        targetEntity: AssessmentResponse::class,
        orphanRemoval: true,
    )]
    private Collection $responses;

    public function __construct(
        #[ORM\Column(enumType: QuestionType::class)]
        private ?QuestionType $questionType = null,
    ) {
        $this->choices = new ArrayCollection();
        $this->responses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuestionType(): ?QuestionType
    {
        return $this->questionType;
    }

    public function setQuestionType(QuestionType $questionType): static
    {
        $this->questionType = $questionType;

        return $this;
    }

    public function getSection(): ?QuestionArea
    {
        return $this->section;
    }

    public function setSection(?QuestionArea $section): static
    {
        $this->section = $section;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function isLocked(): ?bool
    {
        return $this->locked;
    }

    public function setLocked(bool $locked): static
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * @return Collection<int, QuestionChoice>
     */
    public function getChoices(): Collection
    {
        return $this->choices;
    }

    public function addChoice(QuestionChoice $choice): static
    {
        if (!$this->choices->contains($choice)) {
            $this->choices->add($choice);
            $choice->setQuestion($this);
        }

        return $this;
    }

    public function removeChoice(QuestionChoice $choice): static
    {
        if ($this->choices->removeElement($choice)) {
            // set the owning side to null (unless already changed)
            if ($choice->getQuestion() === $this) {
                $choice->setQuestion(null);
            }
        }

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
            $response->setQuestion($this);
        }

        return $this;
    }

    public function removeResponse(AssessmentResponse $response): static
    {
        if ($this->responses->removeElement($response)) {
            // set the owning side to null (unless already changed)
            if ($response->getQuestion() === $this) {
                $response->setQuestion(null);
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
        return [
            'id' => $this->getId(),
            'questionType' => $this->getQuestionType()->value,
            'section' => $this->getSection()->value,
            'content' => $this->getContent(),
            'active' => $this->isActive(),
            'locked' => $this->isLocked(),
            'choices' => $this
                ->getChoices()
                ->filter(fn(QuestionChoice $qc) => $qc?->isActive())
                ->getValues(),
        ];
    }
}
