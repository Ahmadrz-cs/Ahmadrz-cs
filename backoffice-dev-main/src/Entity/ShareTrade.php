<?php

namespace App\Entity;

use App\Entity\Enum\TradeStatus;
use App\Entity\Traits\AssociationBlameableEntity;
use App\Repository\ShareTradeRepository;
use BcMath\Number;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ShareTradeRepository::class)]
class ShareTrade
{
    use AssociationBlameableEntity;
    use TimestampableEntity;

    private const int PRICE_SCALE = 6;
    private const int TRADE_VALUE_SCALE = 2;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, ShareTradeStatusLog>
     */
    #[ORM\OneToMany(
        mappedBy: 'shareTrade',
        targetEntity: ShareTradeStatusLog::class,
        orphanRemoval: true,
        cascade: ['persist'],
    )]
    #[ORM\OrderBy(['occuredAt' => 'ASC', 'id' => 'ASC'])]
    private Collection $statusLogs;

    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private ?Uuid $uuid = null;

    #[ORM\Column(nullable: false, options: ['default' => false])]
    private bool $derived = true;

    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'buyTrades')]
        #[ORM\JoinColumn(nullable: false)]
        private ?TradeOrder $buyOrder = null,
        #[ORM\ManyToOne(inversedBy: 'sellTrades')]
        #[ORM\JoinColumn(nullable: false)]
        private ?TradeOrder $sellOrder = null,
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
        #[ORM\Column(
            type: Types::NUMBER,
            precision: 15,
            scale: self::TRADE_VALUE_SCALE,
            nullable: false,
            options: ['default' => '0.00'],
        )]
        private ?Number $tradeValue = null,
    ) {
        $this->uuid = Uuid::v7();
        $this->statusLogs = new ArrayCollection();

        // Set scale to match what will be stored in database
        $this->pricePerShare = $this->pricePerShare->round(self::PRICE_SCALE);

        // If constructor tradeValue is given, tradeValue is manually initialised/set (not derived)
        // If constructor tradeValue is not given, initialise it with the derived tradeValue
        if ($tradeValue === null) {
            $this->deriveTradeValue();
        } else {
            $this->tradeValue = $this->tradeValue->round(self::TRADE_VALUE_SCALE);
            $this->derived = false;
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBuyOrder(): ?TradeOrder
    {
        return $this->buyOrder;
    }

    public function setBuyOrder(?TradeOrder $buyOrder): static
    {
        $this->buyOrder = $buyOrder;

        return $this;
    }

    public function getSellOrder(): ?TradeOrder
    {
        return $this->sellOrder;
    }

    public function setSellOrder(?TradeOrder $sellOrder): static
    {
        $this->sellOrder = $sellOrder;

        return $this;
    }

    public function getNumberOfShares(): ?int
    {
        return $this->numberOfShares;
    }

    public function setNumberOfShares(int $numberOfShares): static
    {
        $this->numberOfShares = $numberOfShares;
        if ($this->derived) {
            $this->deriveTradeValue();
        }

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
        // Scale should match what database will store
        $this->pricePerShare = $pricePerShare->round(self::PRICE_SCALE);
        if ($this->derived) {
            $this->deriveTradeValue();
        }

        return $this;
    }

    /**
     * @return Collection<int, ShareTradeStatusLog>
     */
    public function getStatusLogs(): Collection
    {
        return $this->statusLogs;
    }

    public function addStatusLog(ShareTradeStatusLog $statusLog): static
    {
        if (!$this->statusLogs->contains($statusLog)) {
            $this->statusLogs->add($statusLog);
            $statusLog->setShareTrade($this);
        }

        return $this;
    }

    public function removeStatusLog(ShareTradeStatusLog $statusLog): static
    {
        if ($this->statusLogs->removeElement($statusLog)) {
            // set the owning side to null (unless already changed)
            if ($statusLog->getShareTrade() === $this) {
                $statusLog->setShareTrade(null);
            }
        }

        return $this;
    }

    public function getStatus(): TradeStatus
    {
        return $this->statusLogs->isEmpty()
            ? TradeStatus::Draft
            : $this->statusLogs->last()->getStatus();
    }

    /**
     * Helper method to add chosen status as new status log with current time
     *
     * For more complex status updates, e.g. custom times and notes, use addStatusLog
     */
    public function setStatus(TradeStatus $status): static
    {
        $statusLog = new ShareTradeStatusLog($this, $status);
        $this->addStatusLog($statusLog);
        return $this;
    }

    public function getCurrentStatusLog(): ?ShareTradeStatusLog
    {
        return $this->statusLogs->isEmpty() ? null : $this->statusLogs->last();
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

    public function isDerived(): bool
    {
        return $this->derived;
    }

    public function getTradeValue(): Number
    {
        if ($this->derived) {
            $this->deriveTradeValue();
        }
        return $this->tradeValue;
    }

    public function setTradeValue(int|string|Number $tradeValue): static
    {
        if (!$tradeValue instanceof Number) {
            $tradeValue = new Number($tradeValue);
        }
        $this->tradeValue = $tradeValue->round(self::TRADE_VALUE_SCALE);
        $this->derived = false;

        return $this;
    }

    public function deriveTradeValue(): static
    {
        $tradeValue = $this->pricePerShare->mul($this->numberOfShares);
        $this->tradeValue = $tradeValue->round(self::TRADE_VALUE_SCALE);
        $this->derived = true;

        return $this;
    }
}
