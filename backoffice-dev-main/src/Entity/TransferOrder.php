<?php

namespace App\Entity;

use App\Entity\Asset;
use App\Entity\Enum\TransferType;
use App\Entity\User;
use App\Repository\TransferOrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'transfer_order')]
#[ORM\Entity(repositoryClass: TransferOrderRepository::class)]
class TransferOrder extends AbstractOrder
{
    #[ORM\Column(type: Types::STRING, enumType: TransferType::class, options: [
        'default' => TransferType::Custom,
    ])]
    private TransferType $transferType = TransferType::Custom;

    #[ORM\ManyToOne(targetEntity: Asset::class)]
    private ?Asset $asset = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $approvedBy = null;

    /**
     * @var Collection<int, TransferRequest> $transfers
     */
    #[ORM\OneToMany(
        targetEntity: TransferRequest::class,
        mappedBy: 'transferOrder',
        orphanRemoval: true,
        cascade: ['persist'],
    )]
    private Collection $transfers;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $targetTotal = null;

    public function __construct()
    {
        $this->transfers = new ArrayCollection();
    }

    public function getTransferType(): TransferType
    {
        return $this->transferType;
    }

    public function setTransferType(TransferType $transferType): self
    {
        $this->transferType = $transferType;

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

    public function getApprovedBy(): ?User
    {
        return $this->approvedBy;
    }

    public function setApprovedBy(?User $approvedBy): self
    {
        $this->approvedBy = $approvedBy;

        return $this;
    }

    /**
     * @return Collection<int, TransferRequest>
     */
    public function getTransfers(): Collection
    {
        return $this->transfers;
    }

    public function addTransfer(TransferRequest $transfer): self
    {
        if (!$this->transfers->contains($transfer)) {
            $this->transfers[] = $transfer;
            $transfer->setTransferOrder($this);
        }

        return $this;
    }

    public function removeTransfer(TransferRequest $transfer): self
    {
        if ($this->transfers->removeElement($transfer)) {
            // set the owning side to null (unless already changed)
            if ($transfer->getTransferOrder() === $this) {
                $transfer->setTransferOrder(null);
            }
        }

        return $this;
    }

    public function getTargetTotal(): ?string
    {
        return $this->targetTotal;
    }

    public function setTargetTotal(?string $targetTotal): self
    {
        $this->targetTotal = $targetTotal;

        return $this;
    }
}
