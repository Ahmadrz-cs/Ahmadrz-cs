<?php

namespace App\Entity;

use App\Entity\Traits\AssociationBlameableEntity;
use App\Repository\AssessmentResponseRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: AssessmentResponseRepository::class)]
class AssessmentResponse implements \JsonSerializable
{
    use AssociationBlameableEntity;
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'responses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?UserAssessment $assessment = null;

    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'responses')]
        #[ORM\JoinColumn(nullable: false)]
        private ?Question $question = null,
        #[ORM\ManyToOne]
        #[ORM\JoinColumn(nullable: false)]
        private ?QuestionChoice $choice = null,
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAssessment(): ?UserAssessment
    {
        return $this->assessment;
    }

    public function setAssessment(?UserAssessment $assessment): static
    {
        $this->assessment = $assessment;

        return $this;
    }

    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    public function setQuestion(?Question $question): static
    {
        $this->question = $question;

        return $this;
    }

    public function getChoice(): ?QuestionChoice
    {
        return $this->choice;
    }

    public function setChoice(?QuestionChoice $choice): static
    {
        $this->choice = $choice;

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
            'assessment_id' => $this->getAssessment()->getId(),
            'question' => $this->getQuestion()->getId(),
            'choice' => $this->getChoice()->getId(),
        ];
    }
}
