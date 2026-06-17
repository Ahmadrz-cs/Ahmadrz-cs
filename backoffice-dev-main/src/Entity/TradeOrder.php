<?php

namespace App\Entity;

use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TradeStatus;
use App\Entity\Traits\AssociationBlameableEntity;
use App\Repository\TradeOrderRepository;
use BcMath\Number;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: TradeOrderRepository::class)]
class TradeOrder
{
    use AssociationBlameableEntity;
    use TimestampableEntity;

    private const int PRICE_SCALE = 6;
    private const int TAX_FEE_SCALE = 2;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $minimumShares = null;

    #[ORM\Column(nullable: true)]
    private ?int $maximumShares = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $notes = null;

    /**
     * @var Collection<int, TradeOrderStatusLog>
     */
    #[ORM\OneToMany(
        mappedBy: 'tradeOrder',
        targetEntity: TradeOrderStatusLog::class,
        orphanRemoval: true,
        cascade: ['persist'],
    )]
    #[ORM\OrderBy(['occuredAt' => 'ASC', 'id' => 'ASC'])]
    private Collection $statusLogs;

    /**
     * @var Collection<int, ShareTrade>
     */
    #[ORM\OneToMany(
        mappedBy: 'buyOrder',
        targetEntity: ShareTrade::class,
        orphanRemoval: true,
    )]
    private Collection $buyTrades;

    /**
     * @var Collection<int, ShareTrade>
     */
    #[ORM\OneToMany(
        mappedBy: 'sellOrder',
        targetEntity: ShareTrade::class,
        orphanRemoval: true,
    )]
    private Collection $sellTrades;

    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private ?Uuid $uuid = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $expiration = null;

    #[ORM\OneToOne(inversedBy: 'tradeOrder', cascade: ['persist', 'remove'])]
    private ?Transaction $transaction = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $transactionReference = null;

    /**
     * Used for prefunding, where you will have a complementary order
     * Note this is not a relation that is represented through a ShareTrade as it is NOT itself a trade relation
     */
    #[ORM\OneToOne(targetEntity: self::class, cascade: ['persist'])]
    private ?self $complementaryOrder = null;

    /**
     * Aggregated field
     */
    private int $sharesTraded = 0;

    public function __construct(
        #[ORM\Column(enumType: TradeDirection::class)]
        private ?TradeDirection $direction = null,
        #[ORM\ManyToOne(inversedBy: 'tradeOrders')]
        #[ORM\JoinColumn(nullable: false)]
        private ?Asset $asset = null,
        #[ORM\ManyToOne(inversedBy: 'tradeOrders')]
        #[ORM\JoinColumn(nullable: false)]
        private ?User $user = null,
        #[ORM\Column(nullable: false, options: ['default' => 0])]
        private int $numberOfShares = 0,
        #[ORM\Column(
            type: Types::NUMBER,
            precision: 12, // 6-6 split before and after dp, so supports up to 1mn - 0.000001 (GBP)
            scale: self::PRICE_SCALE,
            nullable: false,
            options: ['default' => '0.000000'],
        )]
        private Number $pricePerShare = new Number(0),
        #[ORM\Column(enumType: TradeOrderType::class, options: [
            'default' => TradeOrderType::Market,
        ])]
        private TradeOrderType $type = TradeOrderType::Market,
        #[ORM\Column(
            type: Types::NUMBER,
            precision: 12, // 10-2 split before and after dp, so supports up to 10bn - 0.01 (GBP)
            scale: self::TAX_FEE_SCALE,
            nullable: false,
            options: ['default' => '0.00'],
        )]
        private Number $fees = new Number(0),
        #[ORM\Column(
            type: Types::NUMBER,
            precision: 12, // 10-2 split before and after dp, so supports up to 10bn - 0.01 (GBP)
            scale: self::TAX_FEE_SCALE,
            nullable: false,
            options: ['default' => '0.00'],
        )]
        private Number $taxes = new Number(0),
    ) {
        $this->uuid = Uuid::v7();
        $this->statusLogs = new ArrayCollection();
        $this->buyTrades = new ArrayCollection();
        $this->sellTrades = new ArrayCollection();

        // Set scale to match what will be stored in database
        $this->pricePerShare = $this->pricePerShare->round(self::PRICE_SCALE);
        $this->fees = $this->fees->round(self::TAX_FEE_SCALE);
        $this->taxes = $this->taxes->round(self::TAX_FEE_SCALE);
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getDirection(): ?TradeDirection
    {
        return $this->direction;
    }

    public function setDirection(TradeDirection $direction): static
    {
        $this->direction = $direction;

        return $this;
    }

    public function getNumberOfShares(): int
    {
        return $this->numberOfShares;
    }

    public function setNumberOfShares(int $numberOfShares): static
    {
        $this->numberOfShares = $numberOfShares;

        return $this;
    }

    public function getMinimumShares(): ?int
    {
        return $this->minimumShares;
    }

    public function setMinimumShares(?int $minimumShares): static
    {
        $this->minimumShares = $minimumShares;

        return $this;
    }

    public function getMaximumShares(): ?int
    {
        return $this->maximumShares;
    }

    public function setMaximumShares(?int $maximumShares): static
    {
        $this->maximumShares = $maximumShares;

        return $this;
    }

    public function getPricePerShare(): Number
    {
        return $this->pricePerShare;
    }

    public function setPricePerShare(int|string|Number $pricePerShare): static
    {
        if (!$pricePerShare instanceof Number) {
            $pricePerShare = new Number($pricePerShare);
        }
        $this->pricePerShare = $pricePerShare->round(self::PRICE_SCALE);

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

    /**
     * @return Collection<int, TradeOrderStatusLog>
     */
    public function getStatusLogs(): Collection
    {
        return $this->statusLogs;
    }

    public function addStatusLog(TradeOrderStatusLog $statusLog): static
    {
        if (!$this->statusLogs->contains($statusLog)) {
            $this->statusLogs->add($statusLog);
            $statusLog->setTradeOrder($this);
        }

        return $this;
    }

    public function removeStatusLog(TradeOrderStatusLog $statusLog): static
    {
        if ($this->statusLogs->removeElement($statusLog)) {
            // set the owning side to null (unless already changed)
            if ($statusLog->getTradeOrder() === $this) {
                $statusLog->setTradeOrder(null);
            }
        }

        return $this;
    }

    public function getStatus(): TradeOrderStatus
    {
        return $this->statusLogs->isEmpty()
            ? TradeOrderStatus::Draft
            : $this->statusLogs->last()->getStatus();
    }

    /**
     * Helper method to add chosen status as new status log with current time
     *
     * For more complex status updates, e.g. custom times and notes, use addStatusLog
     */
    public function setStatus(TradeOrderStatus $status): static
    {
        $statusLog = new TradeOrderStatusLog($this, $status);
        $this->addStatusLog($statusLog);
        return $this;
    }

    public function getCurrentStatusLog(): ?TradeOrderStatusLog
    {
        return $this->statusLogs->isEmpty() ? null : $this->statusLogs->last();
    }

    /**
     * @return Collection<int, ShareTrade>
     */
    public function getBuyTrades(): Collection
    {
        return $this->buyTrades;
    }

    /**
     * @return Collection<int, ShareTrade>
     */
    public function getSellTrades(): Collection
    {
        return $this->sellTrades;
    }

    /**
     * @return Collection<int, ShareTrade>
     */
    public function getShareTrades(): Collection
    {
        return match ($this->direction) {
            TradeDirection::Buy => $this->buyTrades,
            TradeDirection::Sell => $this->sellTrades,
            default => new ArrayCollection(),
        };
    }

    /**
     * @throws \InvalidArgumentException if direction property is null
     */
    public function addShareTrade(ShareTrade $shareTrade): static
    {
        if (!$this->getShareTrades()->contains($shareTrade)) {
            // If not, we will throw an exception as we don't know which property to set
            if (null === $this->direction) {
                throw new \InvalidArgumentException('Direction must be set');
            }
            if (TradeDirection::Buy === $this->direction) {
                $this->buyTrades->add($shareTrade);
                $shareTrade->setBuyOrder($this);
            }
            if (TradeDirection::Sell === $this->direction) {
                $this->sellTrades->add($shareTrade);
                $shareTrade->setSellOrder($this);
            }
        }

        return $this;
    }

    public function removeShareTrade(ShareTrade $shareTrade): static
    {
        if ($this->buyTrades->removeElement($shareTrade)) {
            // set the owning side to null (unless already changed)
            if ($shareTrade->getBuyOrder() === $this) {
                $shareTrade->setBuyOrder(null);
            }
        } elseif ($this->sellTrades->removeElement($shareTrade)) {
            // set the owning side to null (unless already changed)
            if ($shareTrade->getSellOrder() === $this) {
                $shareTrade->setSellOrder(null);
            }
        }

        return $this;
    }

    public function getFees(): Number
    {
        return $this->fees;
    }

    public function setFees(int|string|Number $fees): static
    {
        if (!$fees instanceof Number) {
            $fees = new Number($fees);
        }
        $this->fees = $fees->round(self::TAX_FEE_SCALE);

        return $this;
    }

    public function getTaxes(): Number
    {
        return $this->taxes;
    }

    public function setTaxes(int|string|Number $taxes): static
    {
        if (!$taxes instanceof Number) {
            $taxes = new Number($taxes);
        }
        $this->taxes = $taxes->round(self::TAX_FEE_SCALE);

        return $this;
    }

    public function getUuid(): ?Uuid
    {
        return $this->uuid;
    }

    public function setUuid(Uuid $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getType(): TradeOrderType
    {
        return $this->type;
    }

    public function setType(TradeOrderType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getExpiration(): ?\DateTime
    {
        return $this->expiration;
    }

    public function setExpiration(?\DateTime $expiration): static
    {
        $this->expiration = $expiration;

        return $this;
    }

    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    public function setTransaction(?Transaction $transaction): static
    {
        $this->transaction = $transaction;

        return $this;
    }

    /**
     * Complements Transaction relation for legacy or offline investments
     * If a Transaction relation exists, it should typically have the same external_id as this
     */
    public function getTransactionReference(): ?string
    {
        return $this->transactionReference;
    }

    public function setTransactionReference(?string $transactionReference): static
    {
        $this->transactionReference = $transactionReference;

        return $this;
    }

    public function getExpectedStampDuty(): Number
    {
        if (
            $this->direction === TradeDirection::Buy
            && in_array($this->type, TradeOrderType::marketTradingTypes())
        ) {
            $tradeValue = $this->getPricePerShare()->mul($this->getNumberOfShares());
            if ($tradeValue >= 1000) {
                return $tradeValue->div(1000)->ceil()->mul(5);
            }
        }

        return new Number(0);
    }

    public function getComplementaryOrder(): ?self
    {
        return $this->complementaryOrder;
    }

    public function setComplementaryOrder(?self $complementaryOrder): static
    {
        // Note that you'll need to manually set the other side as well
        // To avoid circular reference issue
        $this->complementaryOrder = $complementaryOrder;

        return $this;
    }

    /**
     * Aggregate field not persisted
     * Populated on postLoad via TradeOrderAggregateListener
     */
    public function setSharesTraded(int $sharesTraded): static
    {
        $this->sharesTraded = $sharesTraded;

        return $this;
    }

    /**
     * Aggregate field not persisted
     */
    public function getSharesTraded(): int
    {
        return $this->sharesTraded;
    }

    /**
     * Refresh sharesTraded based on current set of shareTrades.
     * If sharesTrades are not hydrated yet, this can be quite slow.
     * Useful if performance is not primary concern and you need latest info,
     * including share trades that are not yet persisted to database
     */
    public function deriveSharesTraded(): int
    {
        $progress = 0;
        foreach ($this->getShareTrades() as $shareTrade) {
            if (in_array($shareTrade->getStatus(), TradeStatus::countedStatuses())) {
                $progress += $shareTrade->getNumberOfShares();
            }
        }
        $this->sharesTraded = $progress;
        return $this->sharesTraded;
    }

    /**
     * Derived from aggregate field sharesTraded.
     * Lower bound of 0
     */
    public function getSharesAvailable(): int
    {
        return max(0, $this->numberOfShares - $this->sharesTraded);
    }
}
