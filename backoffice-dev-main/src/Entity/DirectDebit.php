<?php

namespace App\Entity;

use App\Entity\BaseEntity;
use App\Entity\User;
use App\Repository\DirectDebitRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Table(name: 'direct_debit')]
#[ORM\Entity(repositoryClass: DirectDebitRepository::class)]
// ForDBAL4 #[Gedmo\Loggable]
class DirectDebit extends BaseEntity
{
    /**
     * @var $user
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $user;

    /**
     * @var int
     */
    #[ORM\Column(name: 'mangopay_bank_account_Id', type: 'integer')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $mangopayBankaccountId;

    /**
     * @var int
     */
    #[ORM\Column(name: 'mangopay_mandate_Id', type: 'integer')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $mangopayMandateId;

    /**
     * @var string
     */
    #[ORM\Column(name: 'account_type', type: 'string', length: 2)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $accountType;

    /**
     * @var  \DateTime
     */
    #[ORM\Column(name: 'mandate_create_date', type: 'date')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $createDate;

    #[ORM\Column(name: 'direct_debit_active', type: 'boolean')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $directDebitActive;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 3)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $currency;

    /**
     * Amount to debit (in £GBP pennines)
     */
    #[ORM\Column(type: 'integer')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $amount;

    /**
     * @var string
     */
    #[ORM\Column(name: 'mandate_url', type: 'string')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $mandateUrl;

    /**
     * @var  \DateTime
     */
    #[ORM\Column(name: 'last_settlement_date', type: 'date', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $lastSettlementDate;

    /**
     * Get the value of currency
     *
     * @return  string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set the value of currency
     *
     * @param  string  $currency
     *
     * @return  self
     */
    public function setCurrency(string $currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get amount to debit (in £ pennines)
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set amount to debit (in £ pennines)
     *
     * @return  self
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get the value of user
     *
     * @return  $user
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set the value of user
     *
     * @param  $user  $user
     *
     * @return  self
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get the value of mangopayBankaccountId
     *
     * @return  int
     */
    public function getMangopayBankaccountId()
    {
        return $this->mangopayBankaccountId;
    }

    /**
     * Set the value of mangopayBankaccountId
     *
     * @param  int  $mangopayBankaccountId
     *
     * @return  self
     */
    public function setMangopayBankaccountId(int $mangopayBankaccountId)
    {
        $this->mangopayBankaccountId = $mangopayBankaccountId;

        return $this;
    }

    /**
     * Get the value of mangopayMandateId
     *
     * @return  int
     */
    public function getMangopayMandateId()
    {
        return $this->mangopayMandateId;
    }

    /**
     * Set the value of mangopayMandateId
     *
     * @param  int  $mangopayMandateId
     *
     * @return  self
     */
    public function setMangopayMandateId(int $mangopayMandateId)
    {
        $this->mangopayMandateId = $mangopayMandateId;

        return $this;
    }

    /**
     * Get the value of createDate
     *
     * @return  \DateTime
     */
    public function getCreateDate()
    {
        return $this->createDate;
    }

    /**
     * Set the value of createDate
     *
     * @param  \DateTime  $createDate
     *
     * @return  self
     */
    public function setCreateDate(\DateTime $createDate)
    {
        $this->createDate = $createDate;

        return $this;
    }

    /**
     * Get the value of directDebitActive
     */
    public function getDirectDebitActive()
    {
        return $this->directDebitActive;
    }

    /**
     * Set the value of directDebitActive
     *
     * @return  self
     */
    public function setDirectDebitActive($directDebitActive)
    {
        $this->directDebitActive = $directDebitActive;

        return $this;
    }

    /**
     * Get the value of accountType
     *
     * @return  string
     */
    public function getAccountType()
    {
        return $this->accountType;
    }

    /**
     * Set the value of accountType
     *
     * @param  string  $accountType
     *
     * @return  self
     */
    public function setAccountType(string $accountType)
    {
        $this->accountType = $accountType;

        return $this;
    }

    /**
     * Get the value of mandateUrl
     */
    public function getMandateUrl()
    {
        return $this->mandateUrl;
    }

    /**
     * Set the value of mandateUrl
     *
     * @return  self
     */
    public function setMandateUrl($mandateUrl)
    {
        $this->mandateUrl = $mandateUrl;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastSettlementDate()
    {
        return $this->lastSettlementDate;
    }

    /**
     * @param \DateTime $lastSettlementDate
     */
    public function setLastSettlementDate($lastSettlementDate)
    {
        $this->lastSettlementDate = $lastSettlementDate;
    }

    public function isDirectDebitActive(): ?bool
    {
        return $this->directDebitActive;
    }
}
