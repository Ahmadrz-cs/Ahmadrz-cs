<?php

namespace App\Entity;

use App\Entity\AbstractOrder;
use App\Entity\Asset;
use App\Entity\User;
use App\Repository\PaymentOrderRepository;
use App\Service\PaymentService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'payment_order')]
#[ORM\Entity(repositoryClass: PaymentOrderRepository::class)]
class PaymentOrder extends AbstractOrder
{
    public const STATE_DRAFT = 'draft';
    public const STATE_APPROVED = 'approved';
    public const STATE_IN_PROGRESS = 'in_progress';
    public const STATE_COMPLETED = 'completed';
    public const STATE_CLOSED = 'closed';
    public const STATE_ABANDONED = 'abandoned';

    #[ORM\Column(type: 'string', length: 255)]
    private string $paymentType = PaymentService::TYPE_DIVIDEND;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $debitWallet = 'main';

    #[ORM\ManyToOne(targetEntity: Asset::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Asset $asset = null;

    /**
     * @var Collection<int, PaymentRequest> $payments
     */
    #[ORM\OneToMany(
        targetEntity: PaymentRequest::class,
        mappedBy: 'paymentOrder',
        cascade: ['persist'],
        orphanRemoval: true,
    )]
    private Collection $payments;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $approvedBy = null;

    #[ORM\ManyToOne]
    private ?TradeOrder $tradeOrder = null;

    public function __construct()
    {
        $this->payments = new ArrayCollection();
    }

    public function getPaymentType(): ?string
    {
        return $this->paymentType;
    }

    public function setPaymentType(string $paymentType): self
    {
        $this->paymentType = $paymentType;

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

    /**
     * @return Collection|PaymentRequest[]
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(PaymentRequest $payment): self
    {
        if (!$this->payments->contains($payment)) {
            $this->payments[] = $payment;
            $payment->setPaymentOrder($this);
        }

        return $this;
    }

    public function removePayment(PaymentRequest $payment): self
    {
        if ($this->payments->removeElement($payment)) {
            // set the owning side to null (unless already changed)
            if ($payment->getPaymentOrder() === $this) {
                $payment->setPaymentOrder(null);
            }
        }

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

    public function getDebitWallet(): ?string
    {
        return $this->debitWallet;
    }

    public function setDebitWallet(?string $debitWallet): self
    {
        $this->debitWallet = $debitWallet;

        return $this;
    }

    public function getTradeOrder(): ?TradeOrder
    {
        return $this->tradeOrder;
    }

    public function setTradeOrder(?TradeOrder $tradeOrder): static
    {
        $this->tradeOrder = $tradeOrder;

        return $this;
    }
}
