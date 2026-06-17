<?php

namespace App\Entity;

use App\Entity\Asset;
use App\Entity\Enum\OrderStatus;
use App\Entity\Traits\AssociationBlameableEntity;
use App\Entity\User;
use App\Repository\ShareTransferOrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ShareTransferOrderRepository::class)]
class ShareTransferOrder
{
    use AssociationBlameableEntity;
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[Assert\Length(
        max: 240,
        maxMessage: 'The description cannot be longer than {{ limit }} characters',
    )]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    protected ?\DateTimeInterface $scheduledFor = null;

    #[ORM\Column(type: Types::STRING, enumType: OrderStatus::class, options: [
        'default' => OrderStatus::Draft,
    ])]
    protected OrderStatus $status = OrderStatus::Draft;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Asset $asset = null;

    #[ORM\ManyToOne]
    private ?User $approvedBy = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $periodStart = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $periodEnd = null;

    /**
     * @var Collection<int, ShareTransferRequest>
     */
    #[ORM\OneToMany(
        mappedBy: 'shareTransferOrder',
        targetEntity: ShareTransferRequest::class,
        orphanRemoval: true,
        cascade: ['persist'],
    )]
    private Collection $shareTransfers;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $repaymentStart = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $repaymentEnd = null;

    public function __construct()
    {
        $this->shareTransfers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getScheduledFor(): ?\DateTimeInterface
    {
        return $this->scheduledFor;
    }

    public function setScheduledFor(\DateTimeInterface $scheduledFor): static
    {
        $this->scheduledFor = \DateTime::createFromInterface($scheduledFor);

        return $this;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function setStatus(OrderStatus $status): static
    {
        $this->status = $status;

        return $this;
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

    public function getApprovedBy(): ?User
    {
        return $this->approvedBy;
    }

    public function setApprovedBy(?User $approvedBy): static
    {
        $this->approvedBy = $approvedBy;

        return $this;
    }

    public function getPeriodStart(): ?\DateTimeInterface
    {
        return $this->periodStart;
    }

    public function setPeriodStart(\DateTimeInterface $periodStart): static
    {
        $this->periodStart = \DateTime::createFromInterface($periodStart);

        return $this;
    }

    public function getPeriodEnd(): ?\DateTimeInterface
    {
        return $this->periodEnd;
    }

    public function setPeriodEnd(\DateTimeInterface $periodEnd): static
    {
        $this->periodEnd = \DateTime::createFromInterface($periodEnd);

        return $this;
    }

    /**
     * @return Collection<int, ShareTransferRequest>
     */
    public function getShareTransfers(): Collection
    {
        return $this->shareTransfers;
    }

    public function addShareTransfer(ShareTransferRequest $shareTransfers): static
    {
        if (!$this->shareTransfers->contains($shareTransfers)) {
            $this->shareTransfers->add($shareTransfers);
            $shareTransfers->setShareTransferOrder($this);
        }

        return $this;
    }

    public function removeShareTransfer(ShareTransferRequest $shareTransfers): static
    {
        if ($this->shareTransfers->removeElement($shareTransfers)) {
            // set the owning side to null (unless already changed)
            if ($shareTransfers->getShareTransferOrder() === $this) {
                $shareTransfers->setShareTransferOrder(null);
            }
        }

        return $this;
    }

    public function getRepaymentStart(): ?\DateTime
    {
        return $this->repaymentStart;
    }

    public function setRepaymentStart(?\DateTimeInterface $repaymentStart): static
    {
        $this->repaymentStart = \DateTime::createFromInterface($repaymentStart);

        return $this;
    }

    public function getRepaymentEnd(): ?\DateTime
    {
        return $this->repaymentEnd;
    }

    public function setRepaymentEnd(?\DateTimeInterface $repaymentEnd): static
    {
        $this->repaymentEnd = \DateTime::createFromInterface($repaymentEnd);

        return $this;
    }
}
