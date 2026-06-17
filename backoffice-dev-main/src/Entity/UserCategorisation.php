<?php

namespace App\Entity;

use App\Entity\Enum\UserCategory;
use App\Entity\Traits\AssociationBlameableEntity;
use App\Entity\User;
use App\Repository\UserCategorisationRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: UserCategorisationRepository::class)]
class UserCategorisation implements \JsonSerializable
{
    use AssociationBlameableEntity;
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'categorisations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?OnboardingProfile $profile = null;

    #[ORM\Column(enumType: UserCategory::class)]
    private ?UserCategory $category = null;

    #[ORM\Column(nullable: true)]
    private ?array $details = null; // JSON field for additional information capture

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $notes = null; // for staff doing verification

    #[ORM\Column(nullable: true)]
    private ?bool $verified = null;

    #[ORM\ManyToOne]
    private ?User $verifiedBy = null;

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

    public function getCategory(): ?UserCategory
    {
        return $this->category;
    }

    public function setCategory(UserCategory $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getDetails(): ?array
    {
        return $this->details;
    }

    public function setDetails(?array $details): static
    {
        $this->details = $details;

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

    public function isVerified(): ?bool
    {
        return $this->verified;
    }

    public function setVerified(?bool $verified): static
    {
        $this->verified = $verified;

        return $this;
    }

    public function getVerifiedBy(): ?User
    {
        return $this->verifiedBy;
    }

    public function setVerifiedBy(?User $verifiedBy): static
    {
        $this->verifiedBy = $verifiedBy;

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
            'userId' => $this->getProfile()->getUser()->getId(),
            'category' => $this->getCategory()->value,
            'details' => $this->getDetails(),
            'notes' => $this->getNotes(),
            'verified' => $this->isVerified(),
        ];
    }
}
