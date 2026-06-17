<?php

namespace App\Entity;

use App\Entity\AssetFee;
use App\Entity\AssetStatus as LegacyAssetStatus;
use App\Entity\BaseEntity;
use App\Entity\Enum\AssetStatus;
use App\Entity\TaskTracker;
use App\Entity\User;
use App\Repository\AssetRepository;
use App\Service\Util;
use BcMath\Number;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Config\Resource\FileResource;

/**
 *
 * @author Sayak Mukherjee <sayak.mukherjee@qtsin.net>
 *
 * TODO change JMS Annotations to form of JMS\ rather than Expose etc.
 * TODO set ExlusionPolicy to all and use Expose rather than Exclude
 */
#[ORM\Table(name: 'assets')]
#[ORM\Entity(repositoryClass: AssetRepository::class)]
// ForDBAL4 #[Gedmo\Loggable]
class Asset extends BaseEntity implements \JsonSerializable
{
    public const DEFAULT_RELISTING_FEES = [
        0 => 10,
        300 => 15,
        800 => 40,
    ];

    private const int TRADE_VALUE_SCALE = 2;

    /**
     *
     * Core/Simple fields
     *
     */
    #[JMS\Groups(['minimum', 'standard', 'admin'])]
    #[ORM\Column(nullable: false)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $name = null;

    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $alternateName = null;

    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $displayName = null;

    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(type: 'text', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $briefDescription = null;

    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(type: 'text', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $detailedDesc = null;

    #[JMS\Groups(['minimum', 'standard', 'admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $companyNumber = null;

    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $legalName = null;

    #[JMS\Groups(['admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $additionalType = null;

    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $orgEmail = null;

    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $sector = null;

    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $taxId = null;

    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $telephone = null;

    #[JMS\Groups(['minimum', 'standard', 'admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $fundingGoal = null;

    /**
     * Can change to int in future if needed
     */
    #[JMS\Groups(['minimum', 'standard', 'admin'])]
    #[JMS\SerializedName('numberOfShares')]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $amountOfShares = null;

    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $setupFee = null;

    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $adminFee = null;

    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $managementFee = null;

    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $profitShare = null;

    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $stampDutyUser = null;

    #[JMS\Groups(['minimum', 'standard', 'admin'])]
    #[JMS\SerializedName('type')]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $assetType = null;

    /** Can change to int in future */
    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $investmentTerm = null;

    /** Usually a JSON string */
    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $grossRentalReturnPA = null;

    /**
     * Usually a JSON string
     */
    #[JMS\Groups(['admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $netRentalReturnPA = null;

    /** Usually a JSON string */
    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $grossCapitalAppreciation = null;

    /**
     * Usually a JSON string
     */
    #[JMS\Groups(['admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $netCapitalAppreciation = null;

    /** Usually a JSON string */
    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $netCapitalAppreciationYield = null;

    /** Usually a JSON string */
    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $pointsOfInterest = null;

    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $gross_yield = null;

    // #[JMS\Groups(['admin'])]
    // #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    // #[ORM\Column(type: 'boolean', nullable: true, options: ['default' => false])]
    // // ForDBAL4 #[Gedmo\Versioned]
    // private bool $blockedForSale = false;

    #[JMS\Groups(['minimum', 'standard', 'admin'])]
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $pricePerShare = null;

    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(type: 'integer', nullable: false, options: ['default' => 0])]
    // ForDBAL4 #[Gedmo\Versioned]
    private int $visibility = 0;

    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(nullable: true)]
    private ?string $mangoPayUserId = null;

    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $mangoPayWalletId = null;

    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $additional_wallet = null;

    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $depositWalletId = null;

    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $expensesWalletId = null;

    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $taxWalletId = null;

    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $distributionWalletId = null;

    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private ?string $treasuryWalletId = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $financialYearStart = null;

    /**
     *
     * Relational fields
     *
     */

    /** Unclear if this is used, could be due to incorrect mapping */
    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\OneToMany(
        targetEntity: 'App\Entity\Offering',
        mappedBy: 'asset',
        cascade: ['persist'],
    )]
    private Collection $offerings;

    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\OneToMany(
        targetEntity: 'App\Entity\AssetDocuments',
        mappedBy: 'asset',
        cascade: ['persist'],
    )]
    private Collection $documents;

    #[ORM\OneToMany(
        targetEntity: 'App\Entity\AssetAddress',
        mappedBy: 'asset',
        cascade: ['persist'],
    )]
    private Collection $addresses;

    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\OneToMany(
        targetEntity: 'App\Entity\AssetMember',
        mappedBy: 'asset',
        cascade: ['persist'],
    )]
    private Collection $members;

    #[ORM\OneToMany(
        targetEntity: 'App\Entity\AssetFee',
        mappedBy: 'asset',
        cascade: ['persist'],
        orphanRemoval: true,
    )]
    private Collection $fees;

    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
    private ?User $contactPoint = null;

    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\OneToMany(
        targetEntity: 'App\Entity\AssetAddFields',
        mappedBy: 'asset',
        cascade: ['persist'],
        orphanRemoval: true,
    )]
    private Collection $addFields;

    /**
     * @deprecated use currentStatus instead
     */
    #[JMS\Exclude]
    #[ORM\OneToOne(
        targetEntity: 'App\Entity\AssetStatus',
        cascade: ['all'],
        inversedBy: 'asset',
    )]
    private LegacyAssetStatus $assetStatus;

    #[ORM\OneToOne(cascade: ['persist'])]
    private ?TaskTracker $taskTracker = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 4, nullable: true)]
    private ?string $netProjectedYield = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $netProjectedIncome = null;

    /**
     * @var Collection<int, AssetStatusLog>
     */
    #[ORM\OneToMany(
        mappedBy: 'asset',
        targetEntity: AssetStatusLog::class,
        orphanRemoval: true,
        cascade: ['persist'],
    )]
    #[ORM\OrderBy(['occuredAt' => 'ASC', 'id' => 'ASC'])]
    private Collection $statusLogs;

    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $termStart = null;

    /**
     * @var Collection<int, TradeOrder>
     */
    #[ORM\OneToMany(
        mappedBy: 'asset',
        targetEntity: TradeOrder::class,
        orphanRemoval: true,
    )]
    private Collection $tradeOrders;

    #[ORM\Column(options: ['default' => false])]
    private bool $buyRestricted = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $sellRestricted = false;

    #[ORM\Column(options: ['default' => 0])]
    private int $featured = 0;

    /**
     * Aggregated field
     */
    private int $sharesAvailable = 0;

    #[ORM\Column(
        type: Types::NUMBER,
        precision: 15,
        scale: self::TRADE_VALUE_SCALE,
        nullable: true,
        options: ['default' => '100.00'],
    )]
    private ?Number $minimumInvestment = null;

    public function __construct()
    {
        $this->addresses = new ArrayCollection();
        $this->offerings = new ArrayCollection();
        $this->members = new ArrayCollection();
        $this->fees = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->addFields = new ArrayCollection();
        $this->assetStatus = new LegacyAssetStatus();
        $this->statusLogs = new ArrayCollection();
        $this->tradeOrders = new ArrayCollection();
        $this->minimumInvestment = new Number(100);
    }

    /**
     *
     * Core/Simple getter, setters, issers, hassers
     *
     */

    // Name is slightly unusual in that it must be set before persistence
    // Otherwise you get a doctrine not null constraint error
    // But the asset can still function before persistence if name is null
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setAdditionalType(?string $additionalType): self
    {
        $this->additionalType = $additionalType;

        return $this;
    }

    public function getAdditionalType(): ?string
    {
        return $this->additionalType;
    }

    public function setAlternateName(?string $alternateName): self
    {
        $this->alternateName = $alternateName;

        return $this;
    }

    public function getAlternateName(): ?string
    {
        return $this->alternateName;
    }

    public function setDisplayName(?string $displayName): self
    {
        $this->displayName = $displayName;

        return $this;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setBriefDescription(?string $briefDescription): self
    {
        $this->briefDescription = $briefDescription;

        return $this;
    }

    public function getBriefDescription(): ?string
    {
        return $this->briefDescription;
    }

    public function setDetailedDesc(?string $detailedDesc): self
    {
        $this->detailedDesc = $detailedDesc;

        return $this;
    }

    public function getDetailedDesc(): ?string
    {
        return $this->detailedDesc;
    }

    public function setCompanyNumber(?string $companyNumber): self
    {
        $this->companyNumber = $companyNumber;

        return $this;
    }

    public function getCompanyNumber(): ?string
    {
        return $this->companyNumber;
    }

    public function setLegalName(?string $legalName): self
    {
        $this->legalName = $legalName;

        return $this;
    }

    public function getLegalName(): ?string
    {
        return $this->legalName;
    }

    public function setOrgEmail(?string $orgEmail): self
    {
        $this->orgEmail = $orgEmail;

        return $this;
    }

    public function getOrgEmail(): ?string
    {
        return $this->orgEmail;
    }

    public function setSector(?string $sector): self
    {
        $this->sector = $sector;

        return $this;
    }

    public function getSector(): ?string
    {
        return $this->sector;
    }

    public function setTaxId(?string $taxId): self
    {
        $this->taxId = $taxId;

        return $this;
    }

    public function getTaxId(): ?string
    {
        return $this->taxId;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setFundingGoal(?string $fundingGoal): self
    {
        $this->fundingGoal = $fundingGoal;

        return $this;
    }

    public function getFundingGoal(): ?string
    {
        return $this->fundingGoal;
    }

    public function setAmountOfShares(string|int|null $amountOfShares): self
    {
        $this->amountOfShares = (string) $amountOfShares;

        return $this;
    }

    public function getAmountOfShares(): ?string
    {
        return $this->amountOfShares;
    }

    public function setSetupFee(?string $setupFee): self
    {
        $this->setupFee = $setupFee;

        return $this;
    }

    public function getSetupFee(): ?string
    {
        return $this->setupFee;
    }

    public function setAdminFee(?string $adminFee): self
    {
        $this->adminFee = $adminFee;

        return $this;
    }

    public function getAdminFee(): ?string
    {
        return $this->adminFee;
    }

    public function setManagementFee(?string $managementFee): self
    {
        $this->managementFee = $managementFee;

        return $this;
    }

    public function getManagementFee(): ?string
    {
        return $this->managementFee;
    }

    public function setProfitShare(?string $profitShare): self
    {
        $this->profitShare = $profitShare;

        return $this;
    }

    public function getProfitShare(): ?string
    {
        return $this->profitShare;
    }

    public function setStampDutyUser(?string $stampDutyUser): self
    {
        $this->stampDutyUser = $stampDutyUser;

        return $this;
    }

    public function getStampDutyUser(): ?string
    {
        return $this->stampDutyUser;
    }

    public function setAssetType(?string $assetType): self
    {
        $this->assetType = $assetType;

        return $this;
    }

    public function getAssetType(): ?string
    {
        return $this->assetType;
    }

    public function setInvestmentTerm(string|int|null $investmentTerm): self
    {
        $this->investmentTerm = (string) $investmentTerm;

        return $this;
    }

    public function getInvestmentTerm(): ?string
    {
        return $this->investmentTerm;
    }

    public function setGrossRentalReturnPA(?string $grossRentalReturnPA): self
    {
        $this->grossRentalReturnPA = $grossRentalReturnPA;

        return $this;
    }

    /**
     * @return string
     */
    public function getGrossRentalReturnPA(): ?string
    {
        // json string
        return $this->grossRentalReturnPA;
    }

    public function setNetRentalReturnPA(?string $netRentalReturnPA): self
    {
        $this->netRentalReturnPA = $netRentalReturnPA;

        return $this;
    }

    public function getNetRentalReturnPA(): ?string
    {
        return $this->netRentalReturnPA;
    }

    public function setGrossCapitalAppreciation(?string $grossCapitalAppreciation): self
    {
        $this->grossCapitalAppreciation = $grossCapitalAppreciation;

        return $this;
    }

    public function getGrossCapitalAppreciation(): ?string
    {
        return $this->grossCapitalAppreciation;
    }

    public function setNetCapitalAppreciation(?string $netCapitalAppreciation): self
    {
        $this->netCapitalAppreciation = $netCapitalAppreciation;

        return $this;
    }

    public function getNetCapitalAppreciation(): ?string
    {
        return $this->netCapitalAppreciation;
    }

    public function setNetCapitalAppreciationYield(?string $netCapitalAppreciationYield): self
    {
        $this->netCapitalAppreciationYield = $netCapitalAppreciationYield;

        return $this;
    }

    public function getNetCapitalAppreciationYield(): ?string
    {
        return $this->netCapitalAppreciationYield;
    }

    public function setPointsOfInterest(?string $pointsOfInterest): self
    {
        $this->pointsOfInterest = $pointsOfInterest;

        return $this;
    }

    public function getPointsOfInterest(): ?string
    {
        return $this->pointsOfInterest;
    }

    public function getGrossYield(): ?string
    {
        return $this->gross_yield;
    }

    public function setGrossYield(?string $gross_yield): void
    {
        $this->gross_yield = $gross_yield;
    }

    #[\Deprecated('Use setSellRestricted')]
    public function setBlockedForSale(?bool $blockedForSale): self
    {
        $this->setSellRestricted((bool) $blockedForSale);

        return $this;
    }

    #[\Deprecated('Use isSellRestricted')]
    public function getBlockedForSale(): bool
    {
        return $this->isSellRestricted();
    }

    public function setPricePerShare(?string $pricePerShare): self
    {
        $this->pricePerShare = $pricePerShare;

        return $this;
    }

    public function getPricePerShare(): ?string
    {
        return $this->pricePerShare;
    }

    public function setVisibility(int $visibility): self
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getVisibility(): int
    {
        return $this->visibility;
    }

    public function getMangoPayUserId(): ?string
    {
        return $this->mangoPayUserId;
    }

    public function setMangoPayUserId(?string $mangoPayUserId): void
    {
        $this->mangoPayUserId = $mangoPayUserId;
    }

    /** @deprecated use getHoldWalletId() instead */
    public function getMangoPayWalletId(): ?string
    {
        return $this->mangoPayWalletId;
    }

    /** @deprecated use setHoldWalletId() instead */
    public function setMangoPayWalletId(?string $mangoPayWalletId): void
    {
        $this->mangoPayWalletId = $mangoPayWalletId;
    }

    /** @deprecated use getSettlementWalletId() instead */
    public function getAdditionalWallet(): ?string
    {
        return $this->additional_wallet;
    }

    /** @deprecated use setSettlementWalletId() instead */
    public function setAdditionalWallet(?string $additional_wallet): void
    {
        $this->additional_wallet = $additional_wallet;
    }

    public function getHoldWalletId(): ?string
    {
        return $this->mangoPayWalletId;
    }

    public function setHoldWalletId(?string $holdWalletId): void
    {
        $this->mangoPayWalletId = $holdWalletId;
    }

    public function getSettlementWalletId(): ?string
    {
        return $this->additional_wallet;
    }

    public function setSettlementWalletId(?string $settlementWalletId): void
    {
        $this->additional_wallet = $settlementWalletId;
    }

    public function getMainWalletId(): ?string
    {
        return $this->additional_wallet;
    }

    public function setMainWalletId(?string $mainWalletId): void
    {
        $this->additional_wallet = $mainWalletId;
    }

    public function getDepositWalletId(): ?string
    {
        return $this->depositWalletId;
    }

    public function setDepositWalletId(?string $depositWalletId): void
    {
        $this->depositWalletId = $depositWalletId;
    }

    public function getExpensesWalletId(): ?string
    {
        return $this->expensesWalletId;
    }

    public function setExpensesWalletId(?string $expensesWalletId): void
    {
        $this->expensesWalletId = $expensesWalletId;
    }

    public function getTaxWalletId(): ?string
    {
        return $this->taxWalletId;
    }

    public function setTaxWalletId(?string $taxWalletId): void
    {
        $this->taxWalletId = $taxWalletId;
    }

    public function getDistributionWalletId(): ?string
    {
        return $this->distributionWalletId;
    }

    public function setDistributionWalletId(?string $distributionWalletId): void
    {
        $this->distributionWalletId = $distributionWalletId;
    }

    public function getTreasuryWalletId(): ?string
    {
        return $this->treasuryWalletId;
    }

    public function setTreasuryWalletId(?string $treasuryWalletId): void
    {
        $this->treasuryWalletId = $treasuryWalletId;
    }

    /**
     *
     * Relational getter, setters, issers, hassers
     *
     */

    public function addOffering(Offering $offering): self
    {
        // $offering->setAsset($this);
        $this->offerings[] = $offering;
        return $this;
    }

    public function removeOffering(Offering $offering): void
    {
        $this->offerings->removeElement($offering);
    }

    public function getOfferings(): Collection
    {
        return $this->offerings;
    }

    public function addAddress(AssetAddress $address): self
    {
        $address->setAsset($this);
        $this->addresses[] = $address;

        return $this;
    }

    public function removeAddress(AssetAddress $address): void
    {
        $this->addresses->removeElement($address);
    }

    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    public function addMember(AssetMember $member): self
    {
        $member->setAsset($this);
        $this->members[] = $member;

        return $this;
    }

    public function removeMember(AssetMember $member): void
    {
        $this->members->removeElement($member);
    }

    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addFee(AssetFee $fee): self
    {
        $this->fees->add($fee);
        $fee->setAsset($this);

        return $this;
    }

    public function removeFee(AssetFee $fee): void
    {
        $this->fees->removeElement($fee);
    }

    public function getFees(): Collection
    {
        return $this->fees;
    }

    /**
     * Useful for twig templates
     */
    public function getFeesAsKv(bool $sort = true): array
    {
        $feesKv = [];
        $sortedFees = $this->fees->toArray();
        if ($sort) {
            usort(
                $sortedFees,
                fn(AssetFee $a, AssetFee $b) => $a->getBand() <=> $b->getBand(),
            );
        }
        foreach ($sortedFees as $fee) {
            $feesKv[$fee->getBand()] = $fee->getFee();
        }
        return $feesKv;
    }

    public function addAddField(AssetAddFields $addField): self
    {
        $addField->setAsset($this);
        $this->addFields->add($addField);

        return $this;
    }

    public function removeAddField(AssetAddFields $addField): void
    {
        $this->addFields->removeElement($addField);
    }

    public function getAddFields(): Collection
    {
        return $this->addFields;
    }

    public function addDocument(AssetDocuments $document): self
    {
        $document->setAsset($this);
        $this->documents[] = $document;

        return $this;
    }

    public function removeDocument(AssetDocuments $document): void
    {
        $this->documents->removeElement($document);
    }

    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function setContactPoint(?User $contactPoint): self
    {
        $this->contactPoint = $contactPoint;

        return $this;
    }

    /**
     * Contact point is used to represent the issuer of the shares
     * Which is almost always superadmin
     * @return User|null
     */
    public function getContactPoint(): ?User
    {
        return $this->contactPoint;
    }

    public function setStatus(LegacyAssetStatus $assetStatus): self
    {
        $this->assetStatus = $assetStatus;
        return $this;
    }

    public function getStatus(): LegacyAssetStatus
    {
        return $this->assetStatus;
    }

    /**
     * Helper methods
     */

    public function __toString(): string
    {
        return $this->getName() ?: '';
    }

    public function isAMangoPayAsset()
    {
        return !empty($this->mangoPayUserId);
    }

    public function getAuthor(): ?AssetMember
    {
        /** @var AssetMember $member */
        foreach ($this->members as $member) {
            if ($member->getMembertype() == AssetMember::MEMBER_TYPE_AUTHOR) {
                return $member;
            }
        }

        return null;
    }

    public function setLifecycleStatus(string|int $lifecycleStatus): self
    {
        $this->assetStatus->setLifecycleStatus($lifecycleStatus);
        return $this;
    }

    #[JMS\Groups(['minimum', 'standard', 'admin'])]
    #[JMS\VirtualProperty]
    #[JMS\SerializedName('status')]
    #[ORM\Column(nullable: true)]
    public function getLifecycleStatus(): string
    {
        return $this->assetStatus->getLifecycleStatus();
    }

    /**
     * Special getter to return the most recent address which is deemed the main/current address
     */
    #[JMS\Groups(['standard', 'admin'])]
    #[JMS\VirtualProperty]
    #[JMS\SerializedName('address')]
    public function getMainAddress(): AssetAddress
    {
        $address = $this->getAddresses()->last();
        if ($address != null) {
            return $address;
        }

        return new AssetAddress();
    }

    public function getFeesGrouped(): array
    {
        // $fees = $this->fees->getValues();
        $feesGrouped = [];
        foreach ($this->getFees() as $fee) {
            $feesGrouped[$fee->getType()][$fee->getBand()] = $fee->getFee();
        }
        if (empty($feesGrouped)) {
            $feesGrouped = [
                'relisting' => self::DEFAULT_RELISTING_FEES,
            ];
        }
        ksort($feesGrouped);
        return $feesGrouped;
    }

    public function getAddedField(string $key): ?AssetAddFields
    {
        $fields = $this->getAddFields();
        if ($fields) {
            foreach ($fields as $assetAddField) {
                if ($assetAddField->getFieldKey() == $key) {
                    return $assetAddField;
                }
            }
        }
        return null;
    }

    /**
     * To match ten twenty' API requirements, the custom fields need to be put in a array called custom
     * @todo This should be removed once we get to phase 2
     */
    public function getCustomJson($format = 'custom'): array
    {
        if ($format === 'custom') {
            $result = [
                'funding_goal' => $this->fundingGoal,
                'amount_of_shares' => $this->amountOfShares,
                'setup_fee' => $this->setupFee,
                'admin_fee' => $this->adminFee,
                'management_fee' => $this->managementFee,
                'profit_share' => $this->profitShare,
                'stamp_duty_user' => $this->stampDutyUser,
                'asset_type' => $this->assetType,
                'gross_yield' => $this->gross_yield,
                'investment_term' => $this->investmentTerm,
                'gross_rental_return_pa' => $this->grossRentalReturnPA,
                'net_rental_return_pa' => $this->netRentalReturnPA,
                'gross_capital_appreciation' => $this->grossCapitalAppreciation,
                'net_capital_appreciation' => $this->netCapitalAppreciation,
                'net_capital_appreciation_yield' => 'TODO we dont have this field',
                /* @todo implement net_capital_appreciation_yield */
                'points_of_interest' => $this->pointsOfInterest,
                'price_per_share' => $this->pricePerShare,
                'blocked_for_sale' => $this->isSellRestricted(),
                'spv_1_wallet_id' => $this->additional_wallet,
            ];
        } else {
            $result = [
                Util\Helper::convertInInfo('funding_goal', $this->fundingGoal),
                Util\Helper::convertInInfo('amount_of_shares', $this->amountOfShares),
                Util\Helper::convertInInfo('setup_fee', $this->setupFee),
                Util\Helper::convertInInfo('admin_fee', $this->adminFee),
                Util\Helper::convertInInfo('management_fee', $this->managementFee),
                Util\Helper::convertInInfo('profit_share', $this->profitShare),
                Util\Helper::convertInInfo('stamp_duty_user', $this->stampDutyUser),
                Util\Helper::convertInInfo('asset_type', $this->assetType),
                Util\Helper::convertInInfo('gross_yield', $this->gross_yield),
                Util\Helper::convertInInfo('investment_term', $this->investmentTerm),
                Util\Helper::convertInInfo(
                    'gross_rental_return_pa',
                    $this->grossRentalReturnPA,
                ),
                Util\Helper::convertInInfo(
                    'net_rental_return_pa',
                    $this->netRentalReturnPA,
                ),
                Util\Helper::convertInInfo(
                    'gross_capital_appreciation',
                    $this->grossCapitalAppreciation,
                ),
                Util\Helper::convertInInfo(
                    'net_capital_appreciation',
                    $this->netCapitalAppreciation,
                ),
                Util\Helper::convertInInfo(
                    'net_capital_appreciation_yield',
                    'TODO we dont have this field',
                ),
                /* @todo implement net_capital_appreciation_yield */
                Util\Helper::convertInInfo(
                    'points_of_interest',
                    $this->pointsOfInterest,
                ),
                Util\Helper::convertInInfo('price_per_share', $this->pricePerShare),
                Util\Helper::convertInInfo(
                    'blocked_for_sale',
                    $this->isSellRestricted(),
                ),
                Util\Helper::convertInInfo('spv_1_wallet_id', $this->additional_wallet),
            ];
            foreach ($this->addFields as $af) {
                $result[] = Util\Helper::convertInInfo(
                    $af->getFieldKey(),
                    $af->getValue(),
                );
            }
        }

        return $result;
    }

    public function lightView(): array
    {
        return [
            'id' => $this->id,
            'address' => $this->getMainAddress(),
            'alternate_name' => $this->alternateName,
            'additional_type' => $this->additionalType,
            'brief_desc' => $this->briefDescription,
            'company_number' => $this->companyNumber,
            'contact_point' => $this->contactPoint,
            'detail_desc' => $this->detailedDesc,
            'display_name' => $this->displayName,
            'legal_name' => $this->legalName,
            'life_cycle_stage' => $this->assetStatus->getLifecycleStatusAsInt(),
            'org_email' => $this->orgEmail,
            'sector' => $this->sector,
            'tax_id' => $this->taxId,
            'telephone' => $this->telephone,
            'visibility' => $this->visibility,
            'name' => $this->name,
            'term_length' => $this->investmentTerm,
            'term_start' => Util\Helper::formatDate($this->termStart),
            'term_end' => Util\Helper::formatDate($this->getTermEnd()),
            'term_remaining' => $this->getTermRemaining(),

            /* mangopay */
            'mangopay_user_id' => $this->mangoPayUserId,
            'mangopay_wallet_id' => $this->mangoPayWalletId,

            /* custom fields */
            'custom' => $this->getCustomJson(),
            'info' => $this->getCustomJson('info'),

            /* audit */
            'approved_at' => Util\Helper::formatDate(
                $this->getStatus()->getCreatedAt(),
            ),
            'canceled_at' => Util\Helper::formatDate(
                $this->getStatus()->getCancelledOn(),
            ),
            'created_at' => Util\Helper::formatDate($this->getStatus()->getCreatedAt()),
            'submitted_at' => Util\Helper::formatDate(
                $this->getStatus()->getSubmittedOn(),
            ),
            'updated_at' => Util\Helper::formatDate($this->getStatus()->getUpdatedAt()),
            'user_full_name' => $this->createdBy,
            'user_id' => $this->getCreatedById(),
        ];
    }

    public function publicView(): array
    {
        return [
            'id' => $this->id,
            'address' => $this->getMainAddress(),
            'alternate_name' => $this->alternateName,
            'additional_type' => $this->additionalType,
            'brief_desc' => $this->briefDescription,
            'company_number' => $this->companyNumber,
            'contact_point' => $this->contactPoint,
            'detail_desc' => $this->detailedDesc,
            'display_name' => $this->displayName,
            'legal_name' => $this->legalName,
            'life_cycle_stage' => $this->assetStatus->getLifecycleStatusAsInt(),
            'org_email' => $this->orgEmail,
            'sector' => $this->sector,
            'tax_id' => $this->taxId,
            'telephone' => $this->telephone,
            'visibility' => $this->visibility,
            'name' => $this->name,
            'term_length' => $this->investmentTerm,
            'term_start' => Util\Helper::formatDate($this->termStart),
            'term_end' => Util\Helper::formatDate($this->getTermEnd()),
            'term_remaining' => $this->getTermRemaining(),

            /* relationships */
            'documents' => $this->documents->getValues(),
            'members' => $this->members->getValues(),
            // 'fees' => $this->fees->getValues(),
            'fees' => $this->getFeesGrouped(),

            /* custom fields */
            'custom' => $this->getCustomJson(),
            'info' => $this->getCustomJson('info'),

            /* audit */
            /* @todo need to put this back in one Approved state is added to workflow */
            'approved_at' => Util\Helper::formatDate(
                $this->getStatus()->getCreatedAt(),
            ),
            'canceled_at' => Util\Helper::formatDate(
                $this->getStatus()->getCancelledOn(),
            ),
            'created_at' => Util\Helper::formatDate($this->getStatus()->getCreatedAt()),
            'submitted_at' => Util\Helper::formatDate(
                $this->getStatus()->getSubmittedOn(),
            ),
            'updated_at' => Util\Helper::formatDate($this->getStatus()->getUpdatedAt()),
            'user_full_name' => $this->createdBy,
            'user_id' => $this->getCreatedById(),
        ];
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->id,
            'address' => $this->getMainAddress(),
            'alternate_name' => $this->alternateName,
            'additional_type' => $this->additionalType,
            'brief_desc' => $this->briefDescription,
            'company_number' => $this->companyNumber,
            'contact_point' => $this->contactPoint,
            'detail_desc' => $this->detailedDesc,
            'display_name' => $this->displayName,
            'legal_name' => $this->legalName,
            'life_cycle_stage' => $this->assetStatus->getLifecycleStatusAsInt(),
            'org_email' => $this->orgEmail,
            'sector' => $this->sector,
            'tax_id' => $this->taxId,
            'telephone' => $this->telephone,
            'visibility' => $this->visibility,
            'name' => $this->name,
            'term_length' => $this->investmentTerm,
            'term_start' => Util\Helper::formatDate($this->termStart),
            'term_end' => Util\Helper::formatDate($this->getTermEnd()),
            'term_remaining' => $this->getTermRemaining(),

            /* mangopay */
            'mangopay_user_id' => $this->mangoPayUserId,
            'mangopay_wallet_id' => $this->mangoPayWalletId,

            /* relationships */
            'documents' => $this->documents->getValues(),
            'members' => $this->members->getValues(),
            // 'fees' => $this->fees->getValues(),
            'fees' => $this->getFeesGrouped(),

            /* custom fields */
            'custom' => $this->getCustomJson(),
            'info' => $this->getCustomJson('info'),

            /* audit */
            'approved_at' => Util\Helper::formatDate(
                $this->getStatus()->getCreatedAt(),
            ),
            'canceled_at' => Util\Helper::formatDate(
                $this->getStatus()->getCancelledOn(),
            ),
            'created_at' => Util\Helper::formatDate($this->getStatus()->getCreatedAt()),
            'submitted_at' => Util\Helper::formatDate(
                $this->getStatus()->getSubmittedOn(),
            ),
            'updated_at' => Util\Helper::formatDate($this->getStatus()->getUpdatedAt()),
            'user_full_name' => $this->createdBy,
            'user_id' => $this->getCreatedById(),
        ];
    }

    #[\Deprecated('Use isSellRestricted')]
    public function isBlockedForSale(): ?bool
    {
        return $this->isSellRestricted();
    }

    public function getAssetStatus(): ?LegacyAssetStatus
    {
        return $this->assetStatus;
    }

    public function setAssetStatus(?LegacyAssetStatus $assetStatus): self
    {
        $this->assetStatus = $assetStatus;

        return $this;
    }

    public function getFinancialYearStart(): ?\DateTimeInterface
    {
        return $this->financialYearStart;
    }

    public function setFinancialYearStart(?\DateTimeInterface $financialYearStart): self
    {
        if ($financialYearStart !== null) {
            $financialYearStart = \DateTime::createFromInterface($financialYearStart);
        }
        $this->financialYearStart = $financialYearStart;

        return $this;
    }

    public function getTaskTracker(): ?TaskTracker
    {
        return $this->taskTracker;
    }

    public function setTaskTracker(?TaskTracker $taskTracker): self
    {
        $this->taskTracker = $taskTracker;

        return $this;
    }

    public function getNetProjectedYield(): ?string
    {
        return $this->netProjectedYield;
    }

    public function setNetProjectedYield(?string $netProjectedYield): static
    {
        $this->netProjectedYield = $netProjectedYield;

        return $this;
    }

    public function getNetProjectedIncome(): ?string
    {
        return $this->netProjectedIncome;
    }

    public function setNetProjectedIncome(?string $netProjectedIncome): static
    {
        $this->netProjectedIncome = $netProjectedIncome;

        return $this;
    }

    public function getCurrentStatus(): AssetStatus
    {
        // This method should be just "getStatus"
        // But need to deprecate and remove legacy lifecycleStatus first
        return $this->statusLogs->isEmpty()
            ? AssetStatus::Draft
            : $this->statusLogs->last()->getStatus();
    }

    public function getCurrentStatusLog(): ?AssetStatusLog
    {
        return $this->statusLogs->isEmpty() ? null : $this->statusLogs->last();
    }

    /**
     * @return Collection<int, AssetStatusLog>
     */
    public function getStatusLogs(): Collection
    {
        return $this->statusLogs;
    }

    public function addStatusLog(AssetStatusLog $assetStatusLog): static
    {
        if (!$this->statusLogs->contains($assetStatusLog)) {
            $this->statusLogs->add($assetStatusLog);
            $assetStatusLog->setAsset($this);
        }

        return $this;
    }

    public function removeStatusLog(AssetStatusLog $assetStatusLog): static
    {
        if ($this->statusLogs->removeElement($assetStatusLog)) {
            // set the owning side to null (unless already changed)
            if ($assetStatusLog->getAsset() === $this) {
                $assetStatusLog->setAsset(null);
            }
        }

        return $this;
    }

    /**
     * Helper method to add chosen status as new status log with current time
     *
     * For more complex status updates, e.g. custom times and notes, use addStatusLog
     *
     * This method should be just "setStatus", but need to deprecate legacyStatus first
     */
    public function setCurrentStatus(AssetStatus $status): static
    {
        $statusLog = new AssetStatusLog($this, $status);
        $this->addStatusLog($statusLog);
        return $this;
    }

    public function getTermStart(): ?\DateTimeInterface
    {
        return $this->termStart;
    }

    public function setTermStart(?\DateTimeInterface $termStart): static
    {
        if ($termStart !== null) {
            $termStart = \DateTime::createFromInterface($termStart);
        }
        $this->termStart = $termStart;

        return $this;
    }

    /**
     * Virtual property termLength derived from investmentTerm
     */
    #[JMS\Groups(['minimum', 'standard', 'admin'])]
    #[JMS\VirtualProperty]
    public function getTermLength(): ?int
    {
        if ($this->investmentTerm) {
            return intval($this->investmentTerm);
        }
        return null;
    }

    /**
     * Virtual property termEnd derived from termStart and investmentTerm
     */
    #[JMS\Groups(['standard', 'admin'])]
    #[JMS\VirtualProperty]
    public function getTermEnd(): ?\DateTimeInterface
    {
        if ($this->termStart && $this->investmentTerm) {
            $start = \DateTime::createFromInterface($this->termStart);
            return $start->modify("+{$this->investmentTerm} months");
        }
        return null;
    }

    /**
     * Virtual property termRemaining (in months) derived from termStart and investmentTerm
     */
    #[JMS\Groups(['minimum', 'standard', 'admin'])]
    #[JMS\VirtualProperty]
    public function getTermRemaining(): ?int
    {
        $termEnd = $this->getTermEnd();
        if ($termEnd) {
            if ($termEnd <= new \DateTime()) {
                return 0;
            }
            $interval = $termEnd->diff(new \DateTime());
            return ($interval->y * 12) + $interval->m;
        }
        return null;
    }

    /**
     * @return Collection<int, TradeOrder>
     */
    public function getTradeOrders(): Collection
    {
        return $this->tradeOrders;
    }

    public function addTradeOrder(TradeOrder $tradeOrder): static
    {
        if (!$this->tradeOrders->contains($tradeOrder)) {
            $this->tradeOrders->add($tradeOrder);
            $tradeOrder->setAsset($this);
        }

        return $this;
    }

    public function removeTradeOrder(TradeOrder $tradeOrder): static
    {
        if ($this->tradeOrders->removeElement($tradeOrder)) {
            // set the owning side to null (unless already changed)
            if ($tradeOrder->getAsset() === $this) {
                $tradeOrder->setAsset(null);
            }
        }

        return $this;
    }

    public function isBuyRestricted(): bool
    {
        return $this->buyRestricted;
    }

    public function setBuyRestricted(bool $buyRestricted): static
    {
        $this->buyRestricted = $buyRestricted;

        return $this;
    }

    public function isSellRestricted(): bool
    {
        return $this->sellRestricted;
    }

    public function setSellRestricted(bool $sellRestricted): static
    {
        $this->sellRestricted = $sellRestricted;

        return $this;
    }

    public function getTradingStatus(): string
    {
        return match (true) {
            $this->sellRestricted && $this->buyRestricted => 'Closed',
            $this->sellRestricted && !$this->buyRestricted => 'Buy Only',
            !$this->sellRestricted && $this->buyRestricted => 'Sell Only',
            !$this->sellRestricted && !$this->buyRestricted => 'Open',
        };
    }

    public function getFeatured(): int
    {
        return $this->featured;
    }

    public function setFeatured(int $featured): static
    {
        $this->featured = $featured;

        return $this;
    }

    public function getMinimumInvestment(): ?Number
    {
        return $this->minimumInvestment;
    }

    public function setMinimumInvestment(int|string|Number|null $minimumInvestment): static
    {
        if ($minimumInvestment === null) {
            $this->minimumInvestment = $minimumInvestment;
        } else {
            if (!$minimumInvestment instanceof Number) {
                $minimumInvestment = new Number($minimumInvestment);
            }

            // Scale should match what database will store
            $this->minimumInvestment = $minimumInvestment->round(self::TRADE_VALUE_SCALE);
        }

        return $this;
    }

    /**
     * Aggregate field not persisted
     * Populated on postLoad via AssetAggregateListener
     */
    public function setSharesAvailable(int $sharesAvailable): static
    {
        $this->sharesAvailable = $sharesAvailable;

        return $this;
    }

    /**
     * Aggregate field not persisted
     */
    public function getSharesAvailable(): int
    {
        return $this->sharesAvailable;
    }
}
