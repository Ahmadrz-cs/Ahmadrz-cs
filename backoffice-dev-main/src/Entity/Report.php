<?php

namespace App\Entity;

use App\Entity\Enum\ReportStatus;
use App\Repository\ReportRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReportRepository::class)]
class Report
{
    use BlameableEntity;
    use TimestampableEntity;

    public const ORIGIN_MANGOPAY = 'mangopay';
    public const ORIGIN_MERGED = 'merged';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        max: 240,
        maxMessage: 'The description cannot be longer than {{ limit }} characters',
    )]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $origin = null;

    #[ORM\Column(length: 255)]
    private ?string $resourceId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $referenceId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(type: Types::STRING, enumType: ReportStatus::class, options: [
        'default' => ReportStatus::Draft,
    ])]
    private ReportStatus $status = ReportStatus::Draft;

    #[ORM\ManyToMany(targetEntity: ReportSet::class, mappedBy: 'reports')]
    private Collection $reportSets;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $step = null;

    public function __construct()
    {
        $this->reportSets = new ArrayCollection();
    }

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

    public function getOrigin(): ?string
    {
        return $this->origin;
    }

    public function setOrigin(string $origin): self
    {
        $this->origin = $origin;

        return $this;
    }

    public function getResourceId(): ?string
    {
        return $this->resourceId;
    }

    public function setResourceId(string $resourceId): self
    {
        $this->resourceId = $resourceId;

        return $this;
    }

    public function getReferenceId(): ?string
    {
        return $this->referenceId;
    }

    public function setReferenceId(?string $referenceId): self
    {
        $this->referenceId = $referenceId;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getStatus(): ReportStatus
    {
        return $this->status;
    }

    public function setStatus(ReportStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<int, ReportSet>
     */
    public function getReportSets(): Collection
    {
        return $this->reportSets;
    }

    public function addReportSet(ReportSet $reportSet): self
    {
        if (!$this->reportSets->contains($reportSet)) {
            $this->reportSets->add($reportSet);
            $reportSet->addReport($this);
        }

        return $this;
    }

    public function removeReportSet(ReportSet $reportSet): self
    {
        if ($this->reportSets->removeElement($reportSet)) {
            $reportSet->removeReport($this);
        }

        return $this;
    }

    public function getStep(): ?string
    {
        return $this->step;
    }

    public function setStep(?string $step): self
    {
        $this->step = $step;

        return $this;
    }
}
