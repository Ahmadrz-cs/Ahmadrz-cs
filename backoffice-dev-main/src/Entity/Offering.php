<?php

/**
 * Created by PhpStorm.
 */

namespace App\Entity;

use App\Entity\BaseEntity;
use App\Entity\OfferingStatus;
use App\Repository\OfferingRepository;
use App\Service\Util;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as JMS;

#[JMS\ExclusionPolicy('all')]
#[ORM\Table(name: 'offerings')]
#[ORM\Entity(repositoryClass: OfferingRepository::class)]
#[ORM\HasLifecycleCallbacks]
// ForDBAL4 #[Gedmo\Loggable]
class Offering extends BaseEntity implements \JsonSerializable
{
    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[JMS\SerializedName('type')]
    #[ORM\Column(type: 'string', length: 50, nullable: false)]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $offeringType = 'retail';

    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(type: 'string', length: 50, nullable: false)]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $name;

    /**
     * @var string $additionalType
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $additionalType;

    /**
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $category;

    /**
     * @var float $fundingGoal
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true, options: [
        'default' => '0.00',
    ])]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $fundingGoal = 0;

    /**
     * @var float $externalCommitments
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(type: 'decimal', precision: 15, scale: 2, nullable: true, options: [
        'default' => '0.00',
    ])]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $externalCommitments = 0;

    /**
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(type: 'boolean', nullable: true, options: ['default' => false])]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $isFeatured = false;

    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[JMS\SerializedName('isSecondaryOffering')]
    #[ORM\Column(type: 'boolean', nullable: true, options: ['default' => false])]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $isSecondaryMrkt = false;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2, nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $valuation = 0;

    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(type: 'decimal', precision: 15, scale: 2, nullable: true, options: [
        'default' => '0.00',
    ])]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $equityOffered = 0;

    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[JMS\SerializedName('numberOfShares')]
    #[ORM\Column(type: 'integer', nullable: true, options: ['default' => 0])]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $noOfShares = 0;

    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true, options: [
        'default' => '0.00',
    ])]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $pricePerShare = 0;

    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[JMS\SerializedName('netAnnualYield')]
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $netRentProjected;

    /**
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $grossRentProjected;

    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[JMS\SerializedName('netTotalReturn')]
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $grossProjectReturn;

    #[ORM\Column(type: 'integer', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $offeringTerm;

    #[ORM\Column(type: 'datetime', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $openDate;

    #[ORM\Column(type: 'datetime', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $closeDate;

    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[JMS\SerializedName('minCommit')]
    #[ORM\Column(type: 'decimal', precision: 15, scale: 2, nullable: true, options: [
        'default' => '0.00',
    ])]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $minCommitUser = 0;

    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[JMS\SerializedName('maxCommit')]
    #[ORM\Column(type: 'decimal', precision: 15, scale: 2, nullable: true, options: [
        'default' => '0.00',
    ])]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $maxCommitUser = 0;

    #[ORM\Column(type: 'decimal', precision: 15, scale: 2, nullable: true, options: [
        'default' => '0.00',
    ])]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $maxOverFunding = 0;

    #[ORM\Column(type: 'integer', nullable: true, options: ['default' => 0])]
    protected $primaryOfferingId = 0;

    #[ORM\Column(type: 'string', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $comments;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Investment')]
    #[ORM\JoinColumn(name: 'inv_id', referencedColumnName: 'id')]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $sell_investment;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Asset', inversedBy: 'offerings')]
    #[ORM\JoinColumn(name: 'asset_id', referencedColumnName: 'id')]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $asset;

    #[ORM\OneToMany(
        targetEntity: 'App\Entity\OfferingAddFields',
        mappedBy: 'offering',
        cascade: ['persist'],
        orphanRemoval: true,
    )]
    protected $addFields;

    #[ORM\OneToMany(targetEntity: 'App\Entity\Investment', mappedBy: 'offering')]
    #[ORM\OrderBy(['createdAt' => 'ASC', 'id' => 'ASC'])]
    protected $investments;

    /**
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\OneToMany(
        targetEntity: 'App\Entity\OfferingDocuments',
        mappedBy: 'offering',
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    protected $documents;

    #[ORM\Column(type: 'integer', nullable: false, options: ['default' => 0])]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $visibility = 0;

    #[JMS\Exclude]
    #[ORM\OneToOne(
        targetEntity: 'App\Entity\OfferingStatus',
        cascade: ['all'],
        inversedBy: 'offering',
    )]
    protected $offeringStatus;

    #[ORM\Column(type: 'string', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $currency;

    /**
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $sharesSold = 0;

    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $transactionId = null;

    /**
     * @return integer
     */
    public function getPrimaryOfferingId()
    {
        return $this->primaryOfferingId;
    }

    /**
     * @param integer $primaryOfferingId
     */
    public function setPrimaryOfferingId($primaryOfferingId)
    {
        $this->primaryOfferingId = $primaryOfferingId;
    }

    /**
     * Set additionalType
     *
     * @param string $additionalType
     *
     * @return Offering
     */
    public function setAdditionalType($additionalType)
    {
        $this->additionalType = $additionalType;

        return $this;
    }

    /**
     * Get additionalType
     *
     * @return string
     */
    public function getAdditionalType()
    {
        return $this->additionalType;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Offering
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set category
     *
     * @param string $category
     *
     * @return Offering
     */
    public function setCategory($category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set fundingGoal
     *
     * @param string $fundingGoal
     *
     * @return Offering
     */
    public function setFundingGoal($fundingGoal)
    {
        $this->fundingGoal = $fundingGoal;

        return $this;
    }

    /**
     * Get fundingGoal
     *
     * @return string
     */
    public function getFundingGoal()
    {
        return $this->fundingGoal;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set asset
     *
     * @param \App\Entity\Asset $asset
     *
     * @return Offering
     */
    public function setAsset(?\App\Entity\Asset $asset = null)
    {
        $this->asset = $asset;

        return $this;
    }

    /**
     * Get asset
     *
     * @return \App\Entity\Asset
     */
    public function getAsset()
    {
        return $this->asset;
    }

    /**
     * Get asset id
     *
     * @return int|null
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[JMS\VirtualProperty]
    public function getAssetId()
    {
        if ($this->asset != null) {
            return $this->asset->getId();
        }
        return null;
    }

    /**
     * Set investment
     *
     * @param \App\Entity\Investment $investment
     *
     * @return Offering
     */
    public function setSellInvestment(?\App\Entity\Investment $investment = null)
    {
        $this->sell_investment = $investment;

        return $this;
    }

    /**
     * Get investment
     *
     * @return \App\Entity\Investment|null
     */
    public function getSellInvestment()
    {
        return $this->sell_investment;
    }

    /**
     * Add addField
     *
     * @param \App\Entity\OfferingAddFields $addField
     *
     * @return Offering
     */
    public function addAddField(\App\Entity\OfferingAddFields $addField)
    {
        $addField->setOffering($this);
        $this->addFields[] = $addField;

        return $this;
    }

    /**
     * Remove addField
     *
     * @param \App\Entity\OfferingAddFields $addField
     */
    public function removeAddField(\App\Entity\OfferingAddFields $addField)
    {
        $this->addFields->removeElement($addField);
    }

    /**
     * Get addFields
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAddFields()
    {
        return $this->addFields;
    }

    /**
     * Represents a string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getName() ?: '';
    }

    /**
     * Add investment
     *
     * @param \App\Entity\Investment $investment
     *
     * @return Offering
     */
    public function addInvestment(\App\Entity\Investment $investment)
    {
        $investment->setOffering($this);
        $this->investments[] = $investment;

        return $this;
    }

    /**
     * Remove investment
     *
     * @param \App\Entity\Investment $investment
     */
    public function removeInvestment(\App\Entity\Investment $investment)
    {
        $this->investments->removeElement($investment);
    }

    /**
     * Get investments
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getInvestments()
    {
        return $this->investments;
    }

    /**
     * Add document
     *
     * @param \App\Entity\OfferingDocuments $document
     *
     * @return Offering
     */
    public function addDocument(\App\Entity\OfferingDocuments $document)
    {
        $document->setOffering($this);
        $this->documents[] = $document;

        return $this;
    }

    /**
     * Remove document
     *
     * @param \App\Entity\OfferingDocuments $document
     */
    public function removeDocument(\App\Entity\OfferingDocuments $document)
    {
        $this->documents->removeElement($document);
    }

    /**
     * Get documents
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDocuments()
    {
        return $this->documents;
    }

    /**
     * Set externalCommitments
     *
     * @param string $externalCommitments
     *
     * @return Offering
     */
    public function setExternalCommitments($externalCommitments)
    {
        $this->externalCommitments = $externalCommitments;

        return $this;
    }

    /**
     * Get externalCommitments
     *
     * @return string
     */
    public function getExternalCommitments()
    {
        return $this->externalCommitments;
    }

    /**
     * Set isFeatured
     *
     * @param string $isFeatured
     *
     * @return Offering
     */
    public function setIsFeatured($isFeatured)
    {
        $this->isFeatured = $isFeatured;

        return $this;
    }

    /**
     * Get isFeatured
     *
     * @return bool
     */
    public function getIsFeatured()
    {
        return $this->isFeatured;
    }

    /**
     * Set isSecondaryMrkt
     *
     * @param boolean $isSecondaryMrkt
     *
     * @return Offering
     */
    public function setIsSecondaryMrkt($isSecondaryMrkt)
    {
        $this->isSecondaryMrkt = $isSecondaryMrkt;

        return $this;
    }

    /**
     * Get isSecondaryMrkt
     *
     * @return boolean
     */
    public function getIsSecondaryMrkt()
    {
        return $this->isSecondaryMrkt;
    }

    /**
     * Set valuation
     *
     * @param string $valuation
     *
     * @return Offering
     */
    public function setValuation($valuation)
    {
        $this->valuation = $valuation;

        return $this;
    }

    /**
     * Get valuation
     *
     * @return string
     */
    public function getValuation()
    {
        return $this->valuation;
    }

    /**
     * Set equityOffered
     *
     * @param string $equityOffered
     *
     * @return Offering
     */
    public function setEquityOffered($equityOffered)
    {
        $this->equityOffered = $equityOffered;

        return $this;
    }

    /**
     * Get equityOffered
     *
     * @return string
     */
    public function getEquityOffered()
    {
        return $this->equityOffered;
    }

    /**
     * Set noOfShares
     *
     * @param integer $noOfShares
     *
     * @return Offering
     */
    public function setNoOfShares($noOfShares)
    {
        $this->noOfShares = $noOfShares;

        return $this;
    }

    /**
     * Get noOfShares
     *
     * @return integer
     */
    public function getNoOfShares()
    {
        return $this->noOfShares;
    }

    /**
     * Set pricePerShare
     *
     * @param string $pricePerShare
     *
     * @return Offering
     */
    public function setPricePerShare($pricePerShare)
    {
        $this->pricePerShare = $pricePerShare;

        return $this;
    }

    /**
     * Get pricePerShare
     *
     * @return string
     */
    public function getPricePerShare()
    {
        return $this->pricePerShare;
    }

    /**
     * @return mixed
     */
    public function getGrossRentProjected()
    {
        return $this->grossRentProjected;
    }

    /**
     * @param mixed $grossRentProjected
     */
    public function setGrossRentProjected($grossRentProjected)
    {
        $this->grossRentProjected = $grossRentProjected;
    }

    /**
     * Set netRentProjected
     *
     * @param string $netRentProjected
     *
     * @return Offering
     */
    public function setNetRentProjected($netRentProjected)
    {
        $this->netRentProjected = $netRentProjected;

        return $this;
    }

    /**
     * Get netRentProjected
     *
     * @return string
     */
    public function getNetRentProjected()
    {
        return $this->netRentProjected;
    }

    /**
     * Set grossProjectReturn
     *
     * @param string $grossProjectReturn
     *
     * @return Offering
     */
    public function setGrossProjectReturn($grossProjectReturn)
    {
        $this->grossProjectReturn = $grossProjectReturn;

        return $this;
    }

    /**
     * Get grossProjectReturn
     *
     * @return string
     */
    public function getGrossProjectReturn()
    {
        return $this->grossProjectReturn;
    }

    /**
     * Set offeringTerm
     *
     * @param integer $offeringTerm
     *
     * @return Offering
     */
    public function setOfferingTerm($offeringTerm)
    {
        $this->offeringTerm = $offeringTerm;

        return $this;
    }

    /**
     * Get offeringTerm
     *
     * @return integer
     */
    public function getOfferingTerm()
    {
        return $this->offeringTerm;
    }

    public function getOfferingType(): ?string
    {
        return $this->offeringType;
    }

    public function setOfferingType(?string $offeringType): self
    {
        $this->offeringType = $offeringType;
        return $this;
    }

    /**
     * Get offeringTermInMonths
     *
     * @return integer
     */
    #[JMS\Expose]
    #[JMS\VirtualProperty]
    #[JMS\Groups(['standard', 'admin'])]
    #[JMS\SerializedName('term')]
    public function getOfferingTermInMonths()
    {
        return $this->offeringTerm * 12;
    }

    /**
     * Set openDate
     *
     * @param \DateTime $openDate
     *
     * @return Offering
     */
    public function setOpenDate($openDate)
    {
        $this->openDate = $openDate;

        return $this;
    }

    /**
     * Get openDate
     *
     * @return \DateTime
     */
    public function getOpenDate()
    {
        return $this->openDate;
    }

    /**
     * Set closeDate
     *
     * @param \DateTime $closeDate
     *
     * @return Offering
     */
    public function setCloseDate($closeDate)
    {
        $this->closeDate = $closeDate;

        return $this;
    }

    /**
     * Get closeDate
     *
     * @return \DateTime
     */
    public function getCloseDate()
    {
        return $this->closeDate;
    }

    /**
     * Set minCommitUser
     *
     * @param string $minCommitUser
     *
     * @return Offering
     */
    public function setMinCommitUser($minCommitUser)
    {
        $this->minCommitUser = $minCommitUser;

        return $this;
    }

    /**
     * Get minCommitUser
     *
     * @return string
     */
    public function getMinCommitUser()
    {
        return $this->minCommitUser;
    }

    /**
     * Set maxCommitUser
     *
     * @param string $maxCommitUser
     *
     * @return Offering
     */
    public function setMaxCommitUser($maxCommitUser)
    {
        $this->maxCommitUser = $maxCommitUser;

        return $this;
    }

    /**
     * Get maxCommitUser
     *
     * @return string
     */
    public function getMaxCommitUser()
    {
        return $this->maxCommitUser;
    }

    /**
     * Set maxOverFunding
     *
     * @param string $maxOverFunding
     *
     * @return Offering
     */
    public function setMaxOverFunding($maxOverFunding)
    {
        $this->maxOverFunding = $maxOverFunding;

        return $this;
    }

    /**
     * Get maxOverFunding
     *
     * @return string
     */
    public function getMaxOverFunding()
    {
        return $this->maxOverFunding;
    }

    /**
     * Set comments
     *
     * @param string $comments
     *
     * @return Offering
     */
    public function setComments($comments)
    {
        $this->comments = $comments;

        return $this;
    }

    /**
     * Get comments
     *
     * @return string
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Set visibility
     *
     * @param integer $visibility
     *
     *
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get visibility
     *
     * @return integer
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * @var $investor_count
     */
    // ForDBAL4 #[Gedmo\Versioned]
    protected $investor_count;

    public function getInvestorCount()
    {
        return $this->investor_count;
    }

    /**
     * @var $raised_amount
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[JMS\SerializedName('amountRaised')]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $raised_amount;

    public function getRaisedAmount()
    {
        return $this->raised_amount;
    }

    /**
     * @var $investment_count
     */
    // ForDBAL4 #[Gedmo\Versioned]
    protected $investment_count;

    public function getInvestmentCount()
    {
        return $this->investment_count;
    }

    /**
     * @var $raised_percent
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[JMS\SerializedName('raisedPercent')]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $raised_percent;

    public function getRaisedPercent()
    {
        return $this->raised_percent;
    }

    /**
     * @var $capital_outstanding
     */
    // ForDBAL4 #[Gedmo\Versioned]
    protected $capital_outstanding;

    #[ORM\OneToOne]
    private ?TradeOrder $tradeOrder = null;

    public function getCapitalOutstanding()
    {
        return $this->capital_outstanding;
    }

    public function getSharesSold(): int
    {
        return $this->sharesSold;
    }

    #[ORM\PostLoad]
    public function UpdateCalculateField(PostLoadEventArgs $event)
    {
        $offeringRepository = $event
            ->getObjectManager()
            ->getRepository(Offering::class);
        $aggregatedValues = $offeringRepository->findAggregatedOfferingValues($this);

        $this->investor_count = isset($aggregatedValues['totalInvestors'])
            ? $aggregatedValues['totalInvestors']
            : 0;
        $this->investment_count = isset($aggregatedValues['totalInvestments'])
            ? $aggregatedValues['totalInvestments']
            : 0;
        $this->raised_amount = isset($aggregatedValues['raisedAmount'])
            ? $aggregatedValues['raisedAmount'] + $this->getExternalCommitments()
            : $this->getExternalCommitments();
        $this->sharesSold = isset($aggregatedValues['sharesSold'])
            ? $aggregatedValues['sharesSold']
            : 0;

        $this->raised_amount = round($this->raised_amount, 2);
        if ($this->getFundingGoal() == 0) {
            $this->raised_percent = 0;
        } else {
            $this->raised_percent = ($this->raised_amount / $this->fundingGoal) * 100;
        }

        //@todo add the calculation for capital outstanding
    }

    public function setStatus(OfferingStatus $offeringStatus)
    {
        $this->offeringStatus = $offeringStatus;
        return $this;
    }

    /**
     * Get status
     *
     * @return \App\Entity\OfferingStatus
     */
    public function getStatus()
    {
        return $this->offeringStatus;
    }

    /**
     * @param string $lifecycleStatus
     * @return $this
     */
    public function setLifecycleStatus($lifecycleStatus)
    {
        $this->offeringStatus->setLifecycleStatus($lifecycleStatus);
        return $this;
    }

    /**
     * @return string
     */
    #[JMS\Expose]
    #[JMS\VirtualProperty]
    #[JMS\Groups(['minimum', 'standard', 'admin'])]
    #[JMS\SerializedName('status')]
    public function getLifecycleStatus()
    {
        return $this->offeringStatus->getLifecycleStatus();
    }

    #[JMS\Expose]
    #[JMS\VirtualProperty]
    #[JMS\Groups(['minimum', 'standard', 'admin'])]
    #[JMS\SerializedName('termRemaining')]
    public function getTermRemaining(): int
    {
        $now = new \DateTime();
        $createdAt = $this->getCreatedAt();
        $endDate = $createdAt->modify(
            '+' . $this->getOfferingTermInMonths() . 'months',
        );

        if ($endDate > $now) {
            $interval = $endDate->diff($now);

            if ($interval->d > 0) {
                return $interval->m + ($interval->y * 12) + 1;
            }

            return ($interval->y * 12) + $interval->m;
        }

        return 0;
    }

    public function __construct()
    {
        $this->isSecondaryMrkt = false;
        $this->offeringStatus = new OfferingStatus();
        $this->addFields = new ArrayCollection();
        $this->investments = new ArrayCollection();
        $this->documents = new ArrayCollection();
    }

    /**
     * To match ten twenty' API requirements, the custom fields need to be put in a array called custom
     * @todo This should be removed once we get to phase 2
     */
    public function getCustomJson($format = 'custom')
    {
        if ($format === 'custom') {
            return $result = [
                'net_rent_projected' => $this->netRentProjected,
                'gross_rent_projected_return' => $this->grossRentProjected,
                'gross_projected_return' => $this->grossProjectReturn,
            ];
        } else {
            return $result = [
                Util\Helper::convertInInfo(
                    'net_rent_projected',
                    $this->netRentProjected,
                ),
                Util\Helper::convertInInfo(
                    'gross_rent_projected_return',
                    $this->grossRentProjected,
                ),
                Util\Helper::convertInInfo(
                    'gross_projected_return',
                    $this->grossProjectReturn,
                ),
            ];
        }
    }

    public function isIsFeatured(): ?bool
    {
        return $this->isFeatured;
    }

    public function isIsSecondaryMrkt(): ?bool
    {
        return $this->isSecondaryMrkt;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function setTransactionId(?string $transactionId): static
    {
        $this->transactionId = $transactionId;

        return $this;
    }

    public function getOfferingStatus(): ?OfferingStatus
    {
        return $this->offeringStatus;
    }

    public function setOfferingStatus(?OfferingStatus $offeringStatus): self
    {
        $this->offeringStatus = $offeringStatus;

        return $this;
    }

    public function publicView()
    {
        return [
            'asset_id' => $this->getAsset()->getId(),
            'organization_id' => $this->getAsset()->getId(),
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->offeringType,
            'additional_type' => $this->additionalType,
            'category' => $this->category,
            'funding_goal' => $this->fundingGoal,
            'life_cycle_stage' => $this->offeringStatus->getLifecycleStatusAsInt(),

            'external_commitments' => $this->externalCommitments,
            'is_featured' => $this->isFeatured,
            ///////// HARD CODED TO ALWAYS RETURN RETURN related to issue #909
            ///removed the HARD CODING #913
            'is_secondary_offering' => $this->isSecondaryMrkt,
            ///////// HARD CODED TO ALWAYS RETURN RETURN related to issue #909

            'valuation' => $this->valuation,
            'equity_offered' => $this->equityOffered,
            'num_of_shares' => $this->noOfShares,
            'pricePerShare' => $this->pricePerShare,
            'currency' => $this->currency,

            /* relationships */
            'primary_offering_id' => $this->primaryOfferingId,
            'documents' => $this->documents->getValues(),

            /* custom fields */
            'custom' => $this->getCustomJson(),
            'info' => $this->getCustomJson('info'),

            'term' => $this->offeringTerm,
            'open_date' => Util\Helper::formatDate($this->openDate),
            'close_date' => Util\Helper::formatDate($this->closeDate),
            'min_commit_user' => $this->minCommitUser,
            'max_commit_user' => $this->maxCommitUser,
            'max_over_funding' => $this->maxOverFunding,
            'comments' => $this->comments,
            'visibility' => $this->visibility,

            /* calculated fields */
            'investor_count' => $this->investor_count,
            'investment_count' => $this->investment_count,
            'amount_raised' => $this->raised_amount,
            'amount_percent' => $this->raised_percent,
            'raised_percent' => $this->raised_percent,
            'capital_outstanding' => $this->capital_outstanding,

            /* audit */
            'created_at' => Util\Helper::formatDate($this->getStatus()->getCreatedAt()),
            'submitted_at' => Util\Helper::formatDate($this->getStatus()->getSubmittedOn()),
            'published_at' => Util\Helper::formatDate($this->getStatus()->getPublishedOn()),
            'settled_at' => Util\Helper::formatDate($this->getStatus()->getSettledOn()),
            'updated_at' => Util\Helper::formatDate($this->getStatus()->getUpdatedAt()),
            'user_id' => $this->getCreatedById(),

            'max_commitment' => $this->maxCommitUser,
            'max_overfunding_amount' => $this->maxOverFunding,
            'min_commitment' => $this->minCommitUser,
            'price_per_share' => $this->pricePerShare,
        ];
    }

    public function jsonSerialize(): mixed
    {
        if (!empty($this->getSellInvestment())) {
            $sell_inv = $this->getSellInvestment()->getId();
        } else {
            $sell_inv = null;
        }

        return [
            'asset_id' => $this->getAsset()->getId(),
            'organization_id' => $this->getAsset()->getId(),
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->offeringType,
            'additional_type' => $this->additionalType,
            'category' => $this->category,
            'funding_goal' => $this->fundingGoal,
            'life_cycle_stage' => $this->offeringStatus->getLifecycleStatusAsInt(),
            'external_commitments' => $this->externalCommitments,
            'is_featured' => $this->isFeatured,

            ///////// HARD CODED TO ALWAYS RETURN RETURN related to issue #909
            ///removed the HARD CODING #913
            'is_secondary_offering' => $this->isSecondaryMrkt,
            ///////// HARD CODED TO ALWAYS RETURN RETURN related to issue #909

            'valuation' => $this->valuation,
            'equity_offered' => $this->equityOffered,
            'num_of_shares' => $this->noOfShares,
            'pricePerShare' => $this->pricePerShare,
            'sell_investment' => $sell_inv,

            'term' => $this->offeringTerm,
            'open_date' => Util\Helper::formatDate($this->openDate),
            'close_date' => Util\Helper::formatDate($this->closeDate),
            'min_commit_user' => $this->minCommitUser,
            'max_commit_user' => $this->maxCommitUser,
            'max_over_funding' => $this->maxOverFunding,
            'comments' => $this->comments,
            'visibility' => $this->visibility,
            'currency' => $this->currency,

            /* calculated fields */
            'investor_count' => $this->investor_count,
            'investment_count' => $this->investment_count,
            'amount_raised' => $this->raised_amount,
            'amount_percent' => $this->raised_percent,
            'raised_percent' => $this->raised_percent,
            'capital_outstanding' => $this->capital_outstanding,

            /* relationships */
            'primary_offering_id' => $this->primaryOfferingId,
            'documents' => $this->documents->getValues(),

            /* custom fields */
            'custom' => $this->getCustomJson(),
            'info' => $this->getCustomJson('info'),

            /* audit */
            'created_at' => Util\Helper::formatDate($this->getStatus()->getCreatedAt()),
            'submitted_at' => Util\Helper::formatDate($this->getStatus()->getSubmittedOn()),
            'published_at' => Util\Helper::formatDate($this->getStatus()->getPublishedOn()),
            'settled_at' => Util\Helper::formatDate($this->getStatus()->getSettledOn()),
            'updated_at' => Util\Helper::formatDate($this->getStatus()->getUpdatedAt()),
            'user_id' => $this->getCreatedById(),

            'max_commitment' => $this->maxCommitUser,
            'max_overfunding_amount' => $this->maxOverFunding,
            'min_commitment' => $this->minCommitUser,
            'price_per_share' => $this->pricePerShare,
        ];
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
