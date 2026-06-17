<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 29/12/16
 * Time: 17:50
 */

namespace App\Entity;

use App\Entity\BaseEntity;
use App\Entity\User as BaseUser;
use App\Repository\TransactionRepository;
use App\Service\Util;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Entity for Transaction
 */
#[ORM\Table(name: 'transactions')]
#[ORM\Entity(repositoryClass: TransactionRepository::class)]
// ForDBAL4 #[Gedmo\Loggable]
class Transaction extends BaseEntity
{
    /**
     * @var int inv_id
     */
    #[ORM\Column(type: 'integer', nullable: true, options: ['default' => 0])]
    // ForDBAL4 #[Gedmo\Versioned]
    private $inv_id;

    /**
     * @var int creditor_id
     */
    #[ORM\Column(type: 'integer', nullable: true, options: ['default' => 0])]
    // ForDBAL4 #[Gedmo\Versioned]
    private $creditor_id;

    /**
     * @var int debitor_id
     */
    #[ORM\Column(type: 'integer', nullable: true, options: ['default' => 0])]
    // ForDBAL4 #[Gedmo\Versioned]
    private $debitor_id;

    /**
     * @var string debited_wallet_id
     */
    #[ORM\Column(type: 'string', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $debited_wallet_id;

    /**
     * @var string credited_wallet_id
     */
    #[ORM\Column(type: 'string', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $credited_wallet_id;

    /**
     * @var int offering_id
     */
    #[ORM\Column(type: 'integer', nullable: true, options: ['default' => 0])]
    // ForDBAL4 #[Gedmo\Versioned]
    private $offering_id;

    /**
     *
     * @var int $share_amount
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $share_amount;

    /**
     *
     * @var string $value_amount
     */
    #[ORM\Column(type: 'decimal', precision: 14, scale: 2, nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $value_amount;

    /**
     * @var string $fee_amount
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $fee_amount;

    /**
     * @var string $trans_type
     */
    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $trans_type;

    /**
     * @var string $currency
     */
    #[ORM\Column(type: 'string', length: 3, nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $currency;

    /**
     * @var string $comments
     */
    #[ORM\Column(type: 'string', length: 1024, nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $comments;

    /**
     *
     * @var string $payment_status
     */
    #[ORM\Column(type: 'string', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $payment_status;

    /**
     * @var $user
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $user;

    /**
     * @var string external_id
     */
    #[ORM\Column(type: 'string', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $external_id;

    #[ORM\OneToOne(mappedBy: 'transaction', cascade: ['persist', 'remove'])]
    private ?TradeOrder $tradeOrder = null;

    /**
     * @return string
     */
    public function getExternalId()
    {
        return $this->external_id;
    }

    /**
     * @param string $external_id
     */
    public function setExternalId($external_id)
    {
        $this->external_id = $external_id;
    }

    /**
     * @return string
     */
    public function getValueAmount()
    {
        return $this->value_amount;
    }

    /**
     * @param string $value_amount
     */
    public function setValueAmount($value_amount)
    {
        $this->value_amount = $value_amount;
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
    public function getFeeAmount()
    {
        return $this->fee_amount;
    }

    /**
     * @param string $fee_amount
     */
    public function setFeeAmount($fee_amount)
    {
        $this->fee_amount = $fee_amount;
    }

    /**
     * @return string
     */
    public function getTransType()
    {
        return $this->trans_type;
    }

    /**
     * @param string $trans_type
     */
    public function setTransType($trans_type)
    {
        $this->trans_type = $trans_type;
    }

    /**
     * @return int
     */
    public function getInvId()
    {
        return $this->inv_id;
    }

    /**
     * @param int $inv_id
     */
    public function setInvId($inv_id)
    {
        $this->inv_id = $inv_id;
    }

    /**
     * @return int
     */
    public function getCreditorId()
    {
        return $this->creditor_id;
    }

    /**
     * @param int $creditor_id
     */
    public function setCreditorId($creditor_id)
    {
        $this->creditor_id = $creditor_id;
    }

    /**
     * @return int
     */
    public function getDebitorId()
    {
        return $this->debitor_id;
    }

    /**
     * @param int $debitor_id
     */
    public function setDebitorId($debitor_id)
    {
        $this->debitor_id = $debitor_id;
    }

    /**
     * @return string
     */
    public function getDebitedWalletId()
    {
        return $this->debited_wallet_id;
    }

    /**
     * @param string $debited_wallet_id
     */
    public function setDebitedWalletId($debited_wallet_id)
    {
        $this->debited_wallet_id = $debited_wallet_id;
    }

    /**
     * @return string
     */
    public function getCreditedWalletId()
    {
        return $this->credited_wallet_id;
    }

    /**
     * @param string $credited_wallet_id
     */
    public function setCreditedWalletId($credited_wallet_id)
    {
        $this->credited_wallet_id = $credited_wallet_id;
    }

    /**
     * @return int
     */
    public function getOfferingId()
    {
        return $this->offering_id;
    }

    /**
     * @param int $offering_id
     */
    public function setOfferingId($offering_id)
    {
        $this->offering_id = $offering_id;
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

    /**
     * Set user
     *
     * @param \App\Entity\User $user
     *
     * @return Transaction
     */
    public function setUser(?\App\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPaymentStatus()
    {
        return $this->payment_status;
    }

    /**
     * @param mixed $payment_status
     */
    public function setPaymentStatus($payment_status)
    {
        $this->payment_status = $payment_status;
    }

    /**
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param mixed $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * @return mixed
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * @param mixed $comments
     */
    public function setComments($comments)
    {
        $this->comments = $comments;
    }

    // Getter and setter aliases

    public function getType(): ?string
    {
        return $this->trans_type;
    }

    public function setType(string $type): void
    {
        $this->trans_type = $type;
    }

    public function getReferenceId(): ?string
    {
        return $this->external_id;
    }

    public function setReferenceId(string $referenceId): void
    {
        $this->external_id = $referenceId;
    }

    public function getCreditUserId(): ?int
    {
        return $this->creditor_id;
    }

    public function setCreditUserId(?int $userId): void
    {
        $this->creditor_id = $userId;
    }

    public function getDebitUserId(): ?int
    {
        return $this->debitor_id;
    }

    public function setDebitUserId(?int $userId): void
    {
        $this->debitor_id = $userId;
    }

    public function getCreditResourceId(): ?string
    {
        return $this->credited_wallet_id;
    }

    public function setCreditResourceId(string $creditResourceId): void
    {
        $this->credited_wallet_id = $creditResourceId;
    }

    public function getDebitResourceId(): ?string
    {
        return $this->debited_wallet_id;
    }

    public function setDebitResourceId(string $debitResourceId): void
    {
        $this->debited_wallet_id = $debitResourceId;
    }

    public function getAmount(): ?string
    {
        return $this->value_amount;
    }

    public function setAmount(string $amount): void
    {
        $this->value_amount = $amount;
    }

    public function getFee(): ?string
    {
        return $this->fee_amount ?? '0';
    }

    public function setFee(?string $amount): void
    {
        $this->fee_amount = $amount ?? '0';
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->id,
            'external_id' => $this->external_id,
            'creditor_id' => $this->creditor_id,
            'debitor_id' => $this->debitor_id,
            'credited_wallet_id' => $this->credited_wallet_id,
            'debited_wallet_id' => $this->debited_wallet_id,
            'currency' => $this->currency,
            'offering_id' => $this->offering_id,
            'share_amount' => $this->share_amount,
            'value_amount' => $this->value_amount,
            'fee_amount' => $this->fee_amount,
            'trans_type' => $this->trans_type,
            'comments' => $this->comments,
            'payment_status' => $this->payment_status,

            'created_at' => Util\Helper::formatDate($this->createdAt),
            'updated_at' => Util\Helper::formatDate($this->updatedAt),
            'user_id' => $this->getCreatedById(),
            'user_name' => $this->createdBy,
        ];
    }

    public function getTradeOrder(): ?TradeOrder
    {
        return $this->tradeOrder;
    }

    public function setTradeOrder(?TradeOrder $tradeOrder): static
    {
        // unset the owning side of the relation if necessary
        if ($tradeOrder === null && $this->tradeOrder !== null) {
            $this->tradeOrder->setTransaction(null);
        }

        // set the owning side of the relation if necessary
        if ($tradeOrder !== null && $tradeOrder->getTransaction() !== $this) {
            $tradeOrder->setTransaction($this);
        }

        $this->tradeOrder = $tradeOrder;

        return $this;
    }
}
