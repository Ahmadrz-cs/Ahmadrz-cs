<?php

namespace App\Entity;

use App\Entity\Asset;
use App\Entity\Enum\ReportSetType;
use App\Entity\Enum\ReportStatus;
use App\Repository\ReportSetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReportSetRepository::class)]
class ReportSet
{
    use BlameableEntity;
    use TimestampableEntity;

    public const PROGRESS_START = 1;
    public const PROGRESS_END = 100;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToMany(targetEntity: Report::class, inversedBy: 'reportSets')]
    private Collection $reports;

    #[ORM\Column(type: Types::STRING, enumType: ReportSetType::class, options: [
        'default' => ReportSetType::Custom,
    ])]
    private ReportSetType $reportSetType = ReportSetType::Custom;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        max: 240,
        maxMessage: 'The description cannot be longer than {{ limit }} characters',
    )]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Asset::class)]
    private ?Asset $asset = null;

    /**
     * This is should be inclusive of this date (>= this date)
     */
    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $periodStart = null;

    /**
     * This is should be exclusive of this date (< this date)
     */
    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $periodEnd = null;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => self::PROGRESS_START])]
    #[Assert\Range(
        min: 0,
        max: 100,
        notInRangeMessage: 'Progress must be between {{ min }}% and {{ max }}%',
    )]
    private int $progress = self::PROGRESS_START;

    public function __construct()
    {
        $this->reports = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Report>
     */
    public function getReports(): Collection
    {
        return $this->reports;
    }

    public function addReport(Report $report): self
    {
        if (!$this->reports->contains($report)) {
            $this->reports->add($report);
        }

        return $this;
    }

    public function removeReport(Report $report): self
    {
        $this->reports->removeElement($report);

        return $this;
    }

    public function getReportSetType(): ReportSetType
    {
        return $this->reportSetType;
    }

    public function setReportSetType(ReportSetType $reportSetType): self
    {
        $this->reportSetType = $reportSetType;

        return $this;
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

    public function getAsset(): ?Asset
    {
        return $this->asset;
    }

    public function setAsset(?Asset $asset): self
    {
        $this->asset = $asset;

        return $this;
    }

    public function getPeriodStart(): ?\DateTimeInterface
    {
        return $this->periodStart;
    }

    public function setPeriodStart(?\DateTimeInterface $periodStart): self
    {
        $this->periodStart = $periodStart;

        return $this;
    }

    public function getPeriodEnd(): ?\DateTimeInterface
    {
        return $this->periodEnd;
    }

    public function setPeriodEnd(?\DateTimeInterface $periodEnd): self
    {
        $this->periodEnd = $periodEnd;

        return $this;
    }

    public function getProgress(): int
    {
        return $this->progress;
    }

    public function setProgress(int $progress): self
    {
        $this->progress = $progress;

        return $this;
    }

    public function getStatus(): ReportStatus
    {
        return match ($this->progress) {
            0 => ReportStatus::Cancelled,
            1 => ReportStatus::Draft,
            100 => ReportStatus::Available,
            default => ReportStatus::Pending,
        };
    }
}
