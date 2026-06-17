<?php

/**
 * Created by PhpStorm.
 */

namespace App\Entity;

use App\Entity\InvestmentStatus;
use App\Entity\Lifecycle\InvestmentLifecycle;
use App\Entity\Traits\TimestampableEntity;
use App\Entity\User;
use App\Repository\InvestmentRepository;
use App\Service\Util;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entity for Investment
 * @author Sayak Mukherjee <sayak.mukherjee@qtsin.net>
 */
#[JMS\ExclusionPolicy('all')]
#[ORM\Table(name: 'investments')]
#[ORM\Entity(repositoryClass: InvestmentRepository::class)]
#[ORM\HasLifecycleCallbacks]
// ForDBAL4 #[Gedmo\Loggable]
class Investment implements \JsonSerializable
{
    /**
     * Hook blameable behavior
     * updates createdBy, updatedBy fields
     */
    use BlameableEntity;

    /**
     * Hook timestampable behavior
     * updates createdAt, updatedAt fields
     */
    use TimestampableEntity;

    /**
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\Column(type: 'integer', nullable: true, options: ['default' => 0])]
    private $createdById;

    /**
     * @var int $visibility
     */
    #[ORM\Column(type: 'integer', nullable: false, options: ['default' => 0])]
    // ForDBAL4 #[Gedmo\Versioned]
    private $visibility = 0;

    /**
     * @var int for_sale
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[JMS\SerializedName('forSale')]
    #[ORM\Column(type: 'integer', nullable: false, options: ['default' => 0])]
    // ForDBAL4 #[Gedmo\Versioned]
    private $for_sale = 0;

    /**
     * @var string $name
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(type: 'string', length: 360, nullable: false)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $name = '';

    /**
     * @var integer $investmentValue
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $investmentValue;

    /**
     * @var integer $numberOfShares
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $numberOfShares;

    /**
     * @var string $currency
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $currency;

    /**
     * @var double $interestRate
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $interestRate;

    /**
     * @var integer $term
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $term;

    /**
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true, options: [
        'default' => '0.00',
    ])]
    // ForDBAL4 #[Gedmo\Versioned]
    private $orgPricePerShare;

    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[JMS\SerializedName('pricePerShare')]
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true, options: [
        'default' => '0.00',
    ])]
    // ForDBAL4 #[Gedmo\Versioned]
    private $PricePerShare;

    /**
     * @var integer $share_amount
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[JMS\SerializedName('numberOfShares')]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $share_amount;

    /**
     * @var string $transaction_id
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[JMS\SerializedName('transactionId')]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $transaction_id;

    /**
     * @var string $type
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    #[Assert\Choice(callback: 'getInvestmentTypes')]
    private $type = 'normal';

    /**
     * @var string $comments
     */
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $comments;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    // ForDBAL4 #[Gedmo\Versioned]
    private int $extraSharesDivested = 0;

    #[ORM\OneToMany(
        targetEntity: 'App\Entity\InvestmentAddFields',
        mappedBy: 'investment',
        cascade: ['persist'],
        orphanRemoval: true,
    )]
    private $addFields;

    /**
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\OneToMany(
        targetEntity: 'App\Entity\InvestmentDocuments',
        mappedBy: 'investment',
        cascade: ['persist'],
    )]
    private $documents;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\User', inversedBy: 'investments')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $user;

    /**
     * @var $offering
     */
    #[ORM\ManyToOne(
        targetEntity: 'App\Entity\Offering',
        inversedBy: 'investments',
        cascade: ['persist'],
    )]
    #[ORM\JoinColumn(name: 'off_id', referencedColumnName: 'id')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $offering;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|\App\Entity\Payout[]
     * @var $payouts
     */
    #[ORM\OneToMany(
        targetEntity: 'App\Entity\Payout',
        mappedBy: 'investment',
        cascade: ['persist'],
    )]
    private $payouts;

    #[ORM\OneToOne(
        targetEntity: 'App\Entity\InvestmentStatus',
        cascade: ['all'],
        inversedBy: 'investment',
    )]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $investmentStatus;

    /**
     * Constructor
     */
    public function __construct()
    {
        $payouts[] = new ArrayCollection();
        $this->investmentStatus = new InvestmentStatus();
        $this->addFields = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->payouts = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getShareAmount()
    {
        return $this->share_amount;
    }

    /**
     * @param int $share_amount
     */
    public function setShareAmount($share_amount)
    {
        $this->share_amount = $share_amount;
    }

    /**
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transaction_id;
    }

    /**
     * @param string $transaction_id
     */
    public function setTransactionId($transaction_id)
    {
        $this->transaction_id = $transaction_id;
    }

    /**
     * @return mixed
     */
    public function getCapitalOutstanding()
    {
        return $this->capital_outstanding;
    }

    /**
     * @param mixed $capital_outstanding
     */
    public function setCapitalOutstanding($capital_outstanding)
    {
        $this->capital_outstanding = $capital_outstanding;
    }

    /**
     * Add payout
     *
     * @param \App\Entity\Payout $payout
     *
     * @return Investment
     */
    public function addPayout(\App\Entity\Payout $payout)
    {
        $payout->setInvestment($this);
        $this->payouts[] = $payout;

        return $this;
    }

    /**
     * Remove payout
     *
     * @param \App\Entity\Payout $payout
     */
    public function removePayout(\App\Entity\Payout $payout)
    {
        $this->payouts->removeElement($payout);
    }

    /**
     * Get payouts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPayouts()
    {
        return $this->payouts;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Investment
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
     * @return Investment
     */
    public function setUser(?User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \App\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[JMS\VirtualProperty]
    public function getUserId(): int
    {
        return $this->user->getId();
    }

    /**
     * Set offering
     *
     * @param \App\Entity\Offering $offering
     *
     * @return Investment
     */
    public function setOffering(?\App\Entity\Offering $offering = null)
    {
        $this->offering = $offering;

        return $this;
    }

    /**
     * Get offering
     *
     * @return \App\Entity\Offering
     */
    public function getOffering()
    {
        return $this->offering;
    }

    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[JMS\VirtualProperty]
    public function getOfferingId(): int
    {
        return $this->offering->getId();
    }

    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[JMS\VirtualProperty]
    public function getAssetId(): int
    {
        return $this->offering->getAsset()->getId();
    }

    /**
     * Represents a string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->generateName() ?: '';
    }

    public function generateName()
    {
        if (!isset($this->offering)) {
            $uniq_name = $this->getUser()->getEmail();
        } else {
            $uniq_name =
                $this->getUser()->getEmail()
                . ' :Inv Id: '
                . $this->id
                . ' :Offering: '
                . $this->getOffering()->getName();
        }

        $this->setName($uniq_name);

        return $uniq_name;
    }

    /**
     * Add addField
     *
     * @param \App\Entity\InvestmentAddFields $addField
     *
     * @return Investment
     */
    public function addAddField(\App\Entity\InvestmentAddFields $addField)
    {
        $addField->setInvestment($this);
        $this->addFields[] = $addField;

        return $this;
    }

    /**
     * Remove addField
     *
     * @param \App\Entity\InvestmentAddFields $addField
     */
    public function removeAddField(\App\Entity\InvestmentAddFields $addField)
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
     * Add document
     *
     * @param \App\Entity\InvestmentDocuments $document
     *
     * @return Investment
     */
    public function addDocument(\App\Entity\InvestmentDocuments $document)
    {
        $document->setInvestment($this);
        $this->documents[] = $document;

        return $this;
    }

    /**
     * Remove document
     *
     * @param \App\Entity\InvestmentDocuments $document
     */
    public function removeDocument(\App\Entity\InvestmentDocuments $document)
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
     * Set investmentValue
     *
     * @param string $investmentValue
     *
     * @return Investment
     */
    public function setInvestmentValue($investmentValue)
    {
        $this->investmentValue = $investmentValue;

        return $this;
    }

    /**
     * Get investmentValue
     *
     * @return integer
     */
    public function getInvestmentValue()
    {
        return $this->investmentValue;
    }

    /**
     * Set numberOfShares
     *
     * @param integer $numberOfShares
     *
     * @return Investment
     */
    public function setNumberOfShares($numberOfShares)
    {
        $this->numberOfShares = $numberOfShares;

        return $this;
    }

    /**
     * Get numberOfShares
     *
     * @return integer
     */
    public function getNumberOfShares()
    {
        return $this->numberOfShares;
    }

    /**
     * Set currency
     *
     * @param string $currency
     *
     * @return Investment
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set interestRate
     *
     * @param string $interestRate
     *
     * @return Investment
     */
    public function setInterestRate($interestRate)
    {
        $this->interestRate = $interestRate;

        return $this;
    }

    /**
     * Get interestRate
     *
     * @return string
     */
    public function getInterestRate()
    {
        return $this->interestRate;
    }

    /**
     * Set term
     *
     * @param integer $term
     *
     * @return Investment
     */
    public function setTerm($term)
    {
        $this->term = $term;

        return $this;
    }

    /**
     * Get term
     *
     * @return integer
     */
    public function getTerm()
    {
        return $this->term;
    }

    /**
     * Set orgPricePerShare
     *
     * @param string $orgPricePerShare
     *
     * @return Investment
     */
    public function setOrgPricePerShare($orgPricePerShare)
    {
        $this->orgPricePerShare = $orgPricePerShare;

        return $this;
    }

    /**
     * Get orgPricePerShare
     *
     * @return string
     */
    public function getOrgPricePerShare()
    {
        return $this->orgPricePerShare;
    }

    /**
     * Set PricePerShare
     *
     * @param string $PricePerShare
     *
     * @return Investment
     */
    public function setPricePerShare($PricePerShare)
    {
        $this->PricePerShare = $PricePerShare;

        return $this;
    }

    /**
     * Get PricePerShare
     *
     * @return string
     */
    public function getPricePerShare()
    {
        return $this->PricePerShare;
    }

    /**
     * Set comments
     *
     * @param string $comments
     *
     * @return Investment
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

    public function getExtraSharesDivested(): int
    {
        return $this->extraSharesDivested;
    }

    public function setExtraSharesDivested(int $extraSharesDivested): self
    {
        $this->extraSharesDivested = $extraSharesDivested;
        return $this;
    }

    /**
     * Set visibility
     *
     * @param integer $visibility
     *
     * @return Investment
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
     * Set for_sale
     *
     * @param integer $for_sale
     *
     * @return Investment
     */
    public function setForSale($for_sale)
    {
        $this->for_sale = $for_sale;

        return $this;
    }

    /**
     * Get for_sale
     *
     * @return integer
     */
    public function getForSale()
    {
        return $this->for_sale;
    }

    /**
     * @var $capital_outstanding
     */
    private $capital_outstanding;

    public function getCaptialOutstanding()
    {
        return $this->capital_outstanding;
    }

    /**
     * @var float $divested_amount
     */
    private $divested_amount;

    public function getDivestedAmount()
    {
        return $this->divested_amount;
    }

    /**
     * @var int $divested_shares
     */
    private $divested_shares;

    public function getDivestedShares()
    {
        return $this->divested_shares;
    }

    private $offered_shares;

    #[ORM\OneToOne]
    private ?TradeOrder $tradeOrder = null;

    #[ORM\OneToOne]
    private ?ShareTrade $shareTrade = null;

    public function getOfferedShares()
    {
        return $this->offered_shares;
    }

    #[ORM\PostLoad]
    public function UpdateCalculateField(PostLoadEventArgs $event)
    {
        /** @var InvestmentRepository $repository */
        $repository = $event->getObjectManager()->getRepository(self::class);

        $this->capital_outstanding = $repository->getCapitalOutstanding($this->getId());

        $offered_results = $repository->getOfferedValues($this->getId());

        $this->offered_shares = $offered_results['totaloffered_shares'];

        //calculated the divested amounts
        if (isset($this->id) && isset($this->offering)) {
            $divested_results = $repository->getDivestedValues(
                $this->getId(),
                $this->getOffering()->getId(),
            );
            $this->divested_amount = $divested_results['totaldivested_amount'];

            $this->divested_shares = $divested_results['totaldivested_shares'];
        }

        if ('prefunding' == $this->getType()) {
            foreach ($this->addFields as $af) {
                if ('capitalRepaid' == $af->getFieldKey()) {
                    $this->divested_shares = (int) $af->getFieldValue();
                    $this->divested_amount =
                        $this->divested_shares
                        * $this->getOffering()->getAsset()->getPricePerShare();
                    break;
                }
            }
        }
        $this->divested_shares += $this->extraSharesDivested;
        $this->divested_amount += $this->extraSharesDivested * $this->orgPricePerShare;
    }

    /**
     * Set status
     *
     * @param investmentStatus $investmentStatus
     * @return investment
     */
    public function setStatus(InvestmentStatus $investmentStatus)
    {
        $this->investmentStatus = $investmentStatus;
        return $this;
    }

    /**
     * Get status
     *
     * @return \App\Entity\InvestmentStatus
     */
    public function getStatus()
    {
        return $this->investmentStatus;
    }

    /**
     * @param string $lifecycleStatus
     * @return $this
     */
    public function setLifecycleStatus($lifecycleStatus)
    {
        $this->investmentStatus->setLifecycleStatus($lifecycleStatus);
        return $this;
    }

    /**
     * @return string
     */
    #[JMS\Expose]
    #[JMS\VirtualProperty]
    #[JMS\Groups(['standard', 'admin'])]
    #[JMS\SerializedName('status')]
    public function getLifecycleStatus()
    {
        return $this->investmentStatus->getLifecycleStatus();
    }

    /**
     * Set type
     * @param string $type
     * @return Investment
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     * @return string
     */
    public function getType()
    {
        return $this->type ?? 'normal';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setCreatedById(int $createdById): void
    {
        $this->createdById = $createdById;
    }

    public function getCreatedById(): int
    {
        return $this->createdById;
    }

    public static function getInvestmentTypes()
    {
        return [
            'normal',
            'off-market',
            'prefunding',
        ];
    }

    public function updateAddedField(string $key, string $value): void
    {
        $fields = $this->getAddFields();
        if ($fields) {
            foreach ($fields as &$investmendAddField) {
                if ($investmendAddField->getFieldKey() == $key) {
                    $investmendAddField->setFieldValue($value);
                    return;
                }
            }
        }
        //if return has not been called, add the field as it does not exist.
        $additionalField = new \App\Entity\InvestmentAddFields();
        $additionalField->setFieldKey($key);
        $additionalField->setFieldValue($value);
        $this->addAddField($additionalField);
    }

    public function getAddedField(string $key): ?\App\Entity\InvestmentAddFields
    {
        $fields = $this->getAddFields();
        if ($fields) {
            foreach ($fields as $investmendAddField) {
                if ($investmendAddField->getFieldKey() == $key) {
                    return $investmendAddField;
                }
            }
        }
        return null;
    }

    #[JMS\Expose]
    #[JMS\VirtualProperty]
    #[JMS\Groups(['standard', 'admin'])]
    public function getMetadata(): array
    {
        $metadata = [];
        foreach ($this->addFields as $kvPair) {
            $metadata[$kvPair->getFieldKey()] = $kvPair->getFieldValue();
        }
        return $metadata;
    }

    /**
     * To match ten twenty' API requirements, the custom fields need to be put in a array called custom
     * @todo This should be removed once we get to phase 2
     * @param $format
     */
    public function getCustomJson($format = 'custom')
    {
        if ($format === 'custom') {
            return $result = [
                'share_amount' => $this->share_amount,
                'org_price_per_share' => $this->orgPricePerShare,
                'price_per_share' => $this->PricePerShare,
                'transaction_id' => $this->transaction_id,
                'for_sale' => $this->for_sale,
            ];
        } else {
            return $result = [
                Util\Helper::convertInInfo('share_amount', $this->getShareAmount()),
                Util\Helper::convertInInfo(
                    'org_price_per_share',
                    $this->getOrgPricePerShare(),
                ),
                Util\Helper::convertInInfo(
                    'price_per_share',
                    $this->getPricePerShare(),
                ),
                Util\Helper::convertInInfo('transaction_id', $this->getTransactionId()),
                Util\Helper::convertInInfo('for_sale', $this->getForSale()),
            ];
        }
    }

    public function jsonSerialize(): mixed
    {
        return [
            'capital_outstanding' => $this->capital_outstanding,
            'currency' => $this->currency,
            'divested_amount' => $this->divested_amount,
            'divested_shares' => $this->divested_shares,
            'funding_goal' => $this->getOffering()->getFundingGoal(),

            'interest_rate' => $this->interestRate,
            'investment_amount' => $this->investmentValue,
            'life_cycle_stage' => $this->investmentStatus->getLifecycleStatusAsInt(),
            'life_cycle_stage_name' => $this->getLifecycleStatus(),
            'number_of_shares' => $this->share_amount,
            'id' => $this->id,
            'name' => $this->name,

            'term' => $this->getOffering()->getOfferingTerm(),
            'user_id' => $this->getUser()->getId(),
            'user_email' => $this->getUser()->getEmail(),
            'user_name' => $this->getUser()->getUsername(),
            'visibility' => $this->visibility,
            'type' => $this->type,

            /* relationships */
            'custom' => $this->getCustomJson(),
            'info' => $this->getCustomJson('info'),
            'documents' => $this->documents->getValues(),

            'asset_id' => $this->getOffering()->getAsset()->getId(),
            'asset_name' => $this->getOffering()->getAsset()->getName(),
            'org_id' => $this->getOffering()->getAsset()->getId(),
            'org_name' => $this->getOffering()->getAsset()->getName(),
            'offering_id' => $this->getOffering()->getId(),

            /* calculated fields */
            'raised_percent' => $this->getOffering()->getRaisedPercent(),
            'offered_shares' => $this->getOfferedShares(),

            /* audit */
            /* @todo need to put this back in once Settled state is added to workflow */
            'approved_at' => Util\Helper::formatDate($this->getStatus()->getApprovedOn()),
            'created_at' => Util\Helper::formatDate($this->getStatus()->getCreatedAt()),
            'settled_at' => Util\Helper::formatDate($this->getStatus()->getSettledOn()),
            'is_settled' => $this->getStatus()->isIsSettled(),
            'updated_at' => Util\Helper::formatDate($this->getStatus()->getUpdatedAt()),
        ];
    }

    public function getInvestmentStatus(): ?InvestmentStatus
    {
        return $this->investmentStatus;
    }

    public function setInvestmentStatus(?InvestmentStatus $investmentStatus): self
    {
        $this->investmentStatus = $investmentStatus;

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
