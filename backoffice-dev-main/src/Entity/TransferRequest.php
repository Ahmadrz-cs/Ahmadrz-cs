<?php

namespace App\Entity;

use App\Entity\Asset;
use App\Entity\Enum\TransferMode;
use App\Entity\Investment;
use App\Entity\Transaction;
use App\Repository\TransferRequestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'transfer_request')]
#[ORM\Entity(repositoryClass: TransferRequestRepository::class)]
class TransferRequest
{
    use BlameableEntity;
    use TimestampableEntity;

    public const STATE_PENDING = 'pending';
    public const STATE_FAILED = 'failed';
    public const STATE_COMPLETE = 'complete';

    public const TRANSITION_FAIL = 'fail';
    public const TRANSITION_TRANSFER = 'transfer';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TransferOrder::class, inversedBy: 'transfers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TransferOrder $transferOrder = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $debitWalletId = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $creditWalletId = null;

    #[Assert\Length(
        max: 240,
        maxMessage: 'The description cannot be longer than {{ limit }} characters',
    )]
    #[ORM\Column(type: 'string', length: 255)]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(type: 'string', length: 255, options: [
        'default' => self::STATE_PENDING,
    ])]
    private string $status = self::STATE_PENDING;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $statusInfo = null;

    #[ORM\OneToOne(targetEntity: Transaction::class, cascade: ['persist'])]
    private ?Transaction $transaction = null;

    #[ORM\ManyToOne(targetEntity: Investment::class)]
    private ?Investment $investment = null;

    #[ORM\ManyToOne(targetEntity: Asset::class)]
    private ?Asset $asset = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $userNotifiedAt = null;

    #[ORM\Column(type: Types::SMALLINT, enumType: TransferMode::class, options: [
        'default' => TransferMode::Default,
    ])]
    private TransferMode $mode = TransferMode::Default;

    #[ORM\ManyToOne(targetEntity: ShareTrade::class)]
    private ?ShareTrade $shareTrade = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTransferOrder(): ?TransferOrder
    {
        return $this->transferOrder;
    }

    public function setTransferOrder(?TransferOrder $transferOrder): self
    {
        $this->transferOrder = $transferOrder;

        return $this;
    }

    public function getDebitWalletId(): ?string
    {
        return $this->debitWalletId;
    }

    public function setDebitWalletId(string $debitWalletId): self
    {
        $this->debitWalletId = $debitWalletId;

        return $this;
    }

    public function getCreditWalletId(): ?string
    {
        return $this->creditWalletId;
    }

    public function setCreditWalletId(string $creditWalletId): self
    {
        $this->creditWalletId = $creditWalletId;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

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

    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    public function setTransaction(?Transaction $transaction): self
    {
        $this->transaction = $transaction;

        return $this;
    }

    public function getInvestment(): ?Investment
    {
        return $this->investment;
    }

    public function setInvestment(?Investment $investment): self
    {
        $this->investment = $investment;

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

    public function getUserNotifiedAt(): ?\DateTimeInterface
    {
        return $this->userNotifiedAt;
    }

    public function setUserNotifiedAt(?\DateTimeInterface $userNotifiedAt): self
    {
        $this->userNotifiedAt = $userNotifiedAt;

        return $this;
    }

    public function getMode(): TransferMode
    {
        return $this->mode;
    }

    public function setMode(TransferMode $mode): self
    {
        $this->mode = $mode;

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
}
