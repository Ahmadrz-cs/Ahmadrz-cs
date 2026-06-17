<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 30/12/16
 * Time: 22:50
 */

namespace App\Entity;

use App\Entity\BaseEntity;
use App\Repository\WalletRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as JMS;

/**
 * Wallet class
 * @author Keesh
 */
#[JMS\ExclusionPolicy('all')]
#[ORM\Table(name: 'wallets')]
#[ORM\Entity(repositoryClass: WalletRepository::class)]
#[ORM\HasLifecycleCallbacks]
// ForDBAL4 #[Gedmo\Loggable]
class Wallet extends BaseEntity implements \JsonSerializable
{
    /**
     * @var $user
     */
    #[ORM\OneToOne(targetEntity: 'App\Entity\User', mappedBy: 'wallet')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $user;

    /**
     * @var string $currency
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(nullable: false)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $currency;

    /**
     *
     * @var double $free_balance
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[JMS\SerializedName('freeBalance')]
    #[ORM\Column(type: 'decimal', precision: 10, nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $free_balance;

    /**
     *
     * @var double $balance
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(type: 'decimal', precision: 10, nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $balance;

    /**
     *
     * @var double $committed_balance
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[JMS\SerializedName('committedBalance')]
    #[ORM\Column(type: 'decimal', precision: 10, nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $committed_balance;

    /**
     * Set committed_balance
     *
     * @param double $committed_balance
     *
     * @return Wallet
     */
    public function setCommittedBalance($committed_balance)
    {
        $this->committed_balance = $committed_balance;

        return $this;
    }

    /**
     * Get committed_balance
     *
     * @return double
     */
    public function getCommittedBalance()
    {
        return $this->committed_balance;
    }

    /**
     * Set balance
     *
     * @param double $balance
     *
     * @return Wallet
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * Get balance
     *
     * @return double
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * Set free_balance
     *
     * @param double $free_balance
     *
     * @return Wallet
     */
    public function setFreeBalance($free_balance)
    {
        $this->free_balance = $free_balance;

        return $this;
    }

    /**
     * Get free_balance
     *
     * @return double
     */
    public function getFreeBalance()
    {
        return $this->free_balance;
    }

    /**
     * Set currency
     *
     * @param string $currency
     *
     * @return Wallet
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
     * Set user
     *
     * @param \App\Entity\User $user
     *
     * @return Wallet
     */
    public function setUser(?\App\Entity\User $user = null)
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

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->id,
            'currency' => $this->currency,
            'balance' => $this->balance,
            'freebalance' => $this->free_balance,
            'committedbalance' => $this->committed_balance,
            'user_id' => $this->getUser()->getId(),
            'user_name' => $this->getUser()->getUsername(),
        ];
    }

    /**
     * Represents a string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return (
            'Balance = '
            . ($this->getBalance() ?: '')
            . '| Free = '
            . $this->getFreeBalance()
            . '| Committed = '
            . $this->getCommittedBalance()
            ?: ''
        );
    }
}
