<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 13/12/16
 * Time: 12:46
 */

namespace App\Entity;

use App\Entity\BaseEntity;
use App\Entity\Investment;
use App\Entity\PayoutAddFields;
use App\Entity\User;
use App\Repository\PayoutRepository;
use App\Service\Util;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class Payout
 * @author Keesh
 * @package App\Entity
 */
#[JMS\ExclusionPolicy('all')]
#[JMS\Exclude(if: '!object.getUserId() || !object.getAssetId()')]
#[ORM\Table(name: 'payouts')]
#[ORM\Entity(repositoryClass: PayoutRepository::class)]
// ForDBAL4 #[Gedmo\Loggable]
class Payout extends BaseEntity implements \JsonSerializable
{
    /**
     *
     * @var string $additionalType
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(type: 'string', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $additionalType;

    /**
     * @var string $currency
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $currency;

    /**
     * @var integer $payoutType
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[JMS\SerializedName('type')]
    #[ORM\Column(type: 'integer', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $payoutType;

    /**
     * @var \DateTime $dueDate
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(type: 'datetime', nullable: false)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $dueDate;

    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[JMS\SerializedName('amount')]
    #[ORM\Column(type: 'decimal', precision: 15, scale: 2, nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $payoutAmount;

    /**
     * @var double $fee
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(type: 'decimal', precision: 15, scale: 2, nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $fee;

    /**
     * @var $addFields
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\OneToMany(
        targetEntity: 'App\Entity\PayoutAddFields',
        mappedBy: 'payout',
        cascade: ['persist'],
        orphanRemoval: true,
    )]
    protected $addFields;

    /**
     * @var string $transactionId
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(type: 'string', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $transactionId;

    /**
     * @var Asset $asset
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Asset')]
    #[ORM\JoinColumn(name: 'asset_id', referencedColumnName: 'id')]
    private $asset;

    /**
     * @var User $creditedUser
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
    #[ORM\JoinColumn(name: 'credited_user_id', referencedColumnName: 'id')]
    private $creditedUser;

    /**
     * @var Investment $investment
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Investment', inversedBy: 'payouts')]
    #[ORM\JoinColumn(name: 'investment_id', referencedColumnName: 'id')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $investment;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    // ForDBAL4 #[Gedmo\Versioned]
    private int $shareholding = 0;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dueDate = new \DateTime('first day of this month');
        $this->addFields = new ArrayCollection();
    }

    /**
     * Set payoutType
     *
     * @param integer $payoutType
     *
     * @return Payout
     */
    public function setPayoutType($payoutType)
    {
        $this->payoutType = $payoutType;

        return $this;
    }

    /**
     * Get payoutType
     *
     * @return integer
     */
    public function getPayoutType()
    {
        return $this->payoutType;
    }

    /**
     * Set payoutAmount
     *
     * @param string $payoutAmount
     *
     * @return Payout
     */
    public function setPayoutAmount($payoutAmount)
    {
        $this->payoutAmount = $payoutAmount;

        return $this;
    }

    /**
     * Get payoutAmount
     *
     * @return string
     */
    public function getPayoutAmount()
    {
        return $this->payoutAmount;
    }

    /**
     * Set currency
     *
     * @param string $currency
     *
     * @return Payout
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
     * Set investment
     *
     * @param \App\Entity\Investment $investment
     *
     * @return Payout
     */
    public function setInvestment(?\App\Entity\Investment $investment = null)
    {
        $this->investment = $investment;

        return $this;
    }

    /**
     * Get investment
     *
     * @return \App\Entity\Investment|null
     */
    public function getInvestment()
    {
        return $this->investment;
    }

    #[JMS\Groups(['admin'])]
    #[JMS\VirtualProperty]
    public function getInvestmentId(): ?int
    {
        return $this->investment ? $this->investment->getId() : null;
    }

    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[JMS\VirtualProperty]
    public function getUserId(): ?int
    {
        if ($this->creditedUser) {
            return $this->creditedUser->getId();
        }

        if ($this->investment) {
            return $this->investment->getUser()->getId();
        }

        return null;
    }

    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[JMS\VirtualProperty]
    public function getAssetId(): ?int
    {
        if ($this->asset) {
            return $this->asset->getId();
        }

        if ($this->investment) {
            return $this->investment
                ->getOffering()
                ->getAsset()
                ->getId();
        }

        return null;
    }

    /**
     * Set dueDate
     *
     * @param \DateTime $dueDate
     *
     * @return Payout
     */
    public function setDueDate($dueDate)
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    /**
     * Get dueDate
     *
     * @return \DateTime
     */
    public function getDueDate()
    {
        return $this->dueDate;
    }

    /**
     * Set addtitionaType
     *
     * @param string $addtitionaType
     *
     * @return Payout
     */
    public function setAdditionalType($additionalType)
    {
        $this->additionalType = $additionalType;

        return $this;
    }

    /**
     * Get currency
     *
     * @return string
     */
    public function getAdditionalType()
    {
        return $this->additionalType;
    }

    /**
     * @return mixed
     */
    public function getFee()
    {
        return $this->fee;
    }

    /**
     * @param mixed $fee
     */
    public function setFee($fee)
    {
        $this->fee = $fee;
    }

    /**
     * Add addField
     *
     * @param \App\Entity\PayoutAddFields $addField
     *
     * @return Payout
     */
    public function addAddField(\App\Entity\PayoutAddFields $addField)
    {
        $addField->setPayout($this);
        $this->addFields[] = $addField;

        return $this;
    }

    /**
     * Remove addField
     *
     * @param \App\Entity\PayoutAddFields $addField
     */
    public function removeAddField(\App\Entity\PayoutAddFields $addField)
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

    public function getAsset(): ?Asset
    {
        return $this->asset;
    }

    public function setAsset(Asset $asset): Payout
    {
        $this->asset = $asset;

        return $this;
    }

    public function getCreditedUser(): ?User
    {
        return $this->creditedUser;
    }

    public function setCreditedUser(User $creditedUser): Payout
    {
        $this->creditedUser = $creditedUser;

        return $this;
    }

    public function getTransactionId()
    {
        return $this->transactionId;
    }

    public function setTransactionId(string $transactionId)
    {
        $this->transactionId = $transactionId;

        return $this;
    }

    public function getShareholding(): int
    {
        return $this->shareholding;
    }

    public function setShareholding(int $shareholding): self
    {
        $this->shareholding = $shareholding;
        return $this;
    }

    public function jsonSerialize(): mixed
    {
        if ($this->investment) {
            return [
                'id' => $this->id,
                'additional_type' => $this->additionalType,
                'assetId' => null,
                'creditedUserId' => null,
                'currency' => $this->currency,
                'custom' => ['fee' => $this->fee],
                'due_date' => Util\Helper::formatDate($this->dueDate),
                'investment_id' => $this->getInvestment()->getId(),
                'payout_type' => $this->payoutType,
                'payout_amount' => $this->payoutAmount,
                'created_at' => Util\Helper::formatDate($this->createdAt),
                'updated_at' => Util\Helper::formatDate($this->updatedAt),
                'user_id' => $this->getCreatedById(),
                'user_name' => $this->createdBy,
            ];
        } else {
            return [
                'id' => $this->id,
                'additional_type' => $this->additionalType,
                'assetId' => $this->asset->getId(),
                'creditedUserId' => $this->creditedUser->getId(),
                'currency' => $this->currency,
                'custom' => ['fee' => $this->fee],
                'due_date' => Util\Helper::formatDate($this->dueDate),
                'investment_id' => null,
                'payout_type' => $this->payoutType,
                'payout_amount' => $this->payoutAmount,
                'created_at' => Util\Helper::formatDate($this->createdAt),
                'updated_at' => Util\Helper::formatDate($this->updatedAt),
                'user_id' => $this->getCreatedById(),
                'user_name' => $this->createdBy,
            ];
        }
    }
}
