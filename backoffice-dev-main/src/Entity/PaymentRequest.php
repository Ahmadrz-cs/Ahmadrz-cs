<?php

namespace App\Entity;

use App\Entity\Payout;
use App\Entity\User;
use App\Repository\PaymentRequestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'payment_request')]
#[ORM\Entity(repositoryClass: PaymentRequestRepository::class)]
class PaymentRequest
{
    use BlameableEntity;
    use TimestampableEntity;

    public const STATE_PENDING = 'pending';
    public const STATE_FAILED = 'failed';
    public const STATE_PAID = 'paid';

    public const TRANSITION_FAIL = 'fail';
    public const TRANSITION_PAY = 'pay';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PaymentOrder::class, inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PaymentOrder $paymentOrder = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $payee = null;

    #[ORM\Column(type: 'string', length: 255, options: [
        'default' => self::STATE_PENDING,
    ])]
    private string $status = self::STATE_PENDING;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $statusInfo = null;

    /**
     * Note that during entity generation, this had also had cascade remove
     */
    #[ORM\OneToOne(targetEntity: Payout::class, cascade: ['persist'])]
    private ?Payout $payout = null;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    private string $amount;

    #[ORM\Column(type: 'integer')]
    private int $shareholding = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $payeeNotifiedAt = null;

    #[ORM\ManyToOne(targetEntity: ShareTrade::class)]
    private ?ShareTrade $shareTrade = null;

    #[ORM\ManyToOne]
    private ?TradeOrder $tradeOrder = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPaymentOrder(): ?PaymentOrder
    {
        return $this->paymentOrder;
    }

    public function setPaymentOrder(?PaymentOrder $paymentOrder): self
    {
        $this->paymentOrder = $paymentOrder;

        return $this;
    }

    public function getPayee(): ?User
    {
        return $this->payee;
    }

    public function setPayee(?User $payee): self
    {
        $this->payee = $payee;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getstatusInfo(): ?string
    {
        return $this->statusInfo;
    }

    public function setstatusInfo(?string $statusInfo): self
    {
        $this->statusInfo = $statusInfo;

        return $this;
    }

    public function getPayout(): ?Payout
    {
        return $this->payout;
    }

    public function setPayout(?Payout $payout): self
    {
        $this->payout = $payout;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getShareholding(): ?int
    {
        return $this->shareholding;
    }

    public function setShareholding(int $shareholding): self
    {
        $this->shareholding = $shareholding;

        return $this;
    }

    public function getPayeeNotifiedAt(): ?\DateTimeInterface
    {
        return $this->payeeNotifiedAt;
    }

    public function setPayeeNotifiedAt(?\DateTimeInterface $payeeNotifiedAt): self
    {
        $this->payeeNotifiedAt = $payeeNotifiedAt;

        return $this;
    }

    public function getShareTrade(): ?ShareTrade
    {
        return $this->shareTrade;
    }

    public function setShareTrade(?ShareTrade $shareTrade): static
    {
        $this->shareTrade = $shareTrade;

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
