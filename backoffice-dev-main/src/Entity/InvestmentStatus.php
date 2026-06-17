<?php

namespace App\Entity;

use App\Entity\BaseEntity;
use App\Entity\Investment;
use App\Entity\Lifecycle\InvestmentLifecycle;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Workflow\Workflow;

/**
 * Class Status
 * @package App\Entity
 */
#[ORM\Table(name: 'investments_status')]
#[ORM\Entity]
// ForDBAL4 #[Gedmo\Loggable]
class InvestmentStatus extends BaseEntity
{
    /**
     * @var \DateTime
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $openOn;
    /**
     * @var boolean
     */
    #[ORM\Column(type: 'boolean')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $isOpen;
    /**
     * @var \DateTime
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $rejectedOn;
    /**
     * @var boolean
     */
    #[ORM\Column(type: 'boolean')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $isRejected;
    /**
     * @var \DateTime
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $approvedOn;
    /**
     * @var boolean
     */
    #[ORM\Column(type: 'boolean')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $isApproved;

    /**
     * @var \DateTime
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $withdrawnOn;
    /**
     * @var boolean
     */
    #[ORM\Column(type: 'boolean')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $isWithdrawn;

    /**
     * @var \DateTime
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $settledOn;

    /**
     * @return ?\DateTime
     */
    public function getSettledOn()
    {
        return $this->settledOn;
    }

    /**
     * @param \DateTime $settledOn
     */
    public function setSettledOn($settledOn)
    {
        $this->settledOn = $settledOn;
    }

    /**
     * @return boolean
     */
    public function isIsSettled()
    {
        return $this->isSettled;
    }

    /**
     * @param boolean $isSettled
     */
    public function setIsSettled($isSettled)
    {
        $this->isSettled = $isSettled;
    }

    /**
     * @var boolean
     */
    #[ORM\Column(type: 'boolean')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $isSettled;

    /**
     * @var string
     */
    #[ORM\Column]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $lifecycleStatus;

    /**
     * @var $investment
     */
    #[ORM\OneToOne(targetEntity: 'App\Entity\Investment', mappedBy: 'investmentStatus')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $investment;

    /**
     * @var boolean
     */
    #[ORM\Column(type: 'boolean')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $stampDutyPaid = false;

    public function __construct()
    {
        $this->isOpen = true;
        $this->isRejected = false;
        $this->isApproved = false;
        $this->isWithdrawn = false;
        $this->isSettled = false;
        $this->setLifecycleStatus(InvestmentLifecycle::getDefaultState());
    }

    /**
     * Set openOn
     *
     * @param \DateTime $openOn
     *
     * @return InvestmentStatus
     */
    public function setOpenOn($openOn)
    {
        $this->openOn = $openOn;
        return $this;
    }

    /**
     * Get openOn
     *
     * @return \DateTime
     */
    public function getOpenOn()
    {
        return $this->openOn;
    }

    /**
     * Set isOpen
     *
     * @param Boolean $isOpen
     *
     * @return InvestmentStatus
     */
    public function setIsopen($isOpen)
    {
        $this->isOpen = $isOpen;
        return $this;
    }

    /**
     * Get isOpen
     *
     * @return bool
     */
    public function getIsOpen()
    {
        return $this->isOpen;
    }

    /**
     * Set rejectedOn
     *
     * @param \DateTime $rejectedOn
     *
     * @return InvestmentStatus
     */
    public function setRejectedOn($rejectedOn)
    {
        $this->rejectedOn = $rejectedOn;
        return $this;
    }

    /**
     * Get rejectedOn
     *
     * @return \DateTime
     */
    public function getRejectedOn()
    {
        return $this->rejectedOn;
    }

    /**
     * Set isRejected
     *
     * @param Boolean $isRejected
     *
     * @return InvestmentStatus
     */
    public function setIsRejected($isRejected)
    {
        $this->isRejected = $isRejected;
        return $this;
    }

    /**
     * Get getIsRejected
     *
     * @return bool
     */
    public function getIsRejected()
    {
        return $this->isRejected;
    }

    /**
     * Set approvedOn
     *
     * @param \DateTime $approvedOn
     *
     * @return InvestmentStatus
     */
    public function setApprovedOn($approvedOn)
    {
        $this->approvedOn = $approvedOn;
        return $this;
    }

    /**
     * Get approvedOn
     *
     * @return \DateTime
     */
    public function getApprovedOn()
    {
        return $this->approvedOn;
    }

    /**
     * Set isApproved
     *
     * @param boolean $isApproved
     *
     * @return InvestmentStatus
     */
    public function setisApproved($isApproved)
    {
        $this->isApproved = $isApproved;
        return $this;
    }

    /**
     * Get isApproved
     *
     * @return bool
     */
    public function getIsApproved()
    {
        return $this->isApproved;
    }

    /**
     * Set withdrawnOn
     *
     * @param \DateTime $withdrawnOn
     *
     * @return InvestmentStatus
     */
    public function setWithdrawnOn($withdrawnOn)
    {
        $this->withdrawnOn = $withdrawnOn;
        return $this;
    }

    /**
     * Get withdrawnOn
     *
     * @return \DateTime
     */
    public function getWithdrawnOn()
    {
        return $this->withdrawnOn;
    }

    /**
     * Set isWithdrawn
     *
     * @param boolean $isWithdrawn
     *
     * @return InvestmentStatus
     */
    public function setIsWithdrawn($isWithdrawn)
    {
        $this->isWithdrawn = $isWithdrawn;
        return $this;
    }

    /**
     * Get isWithdrawn
     *
     * @return bool
     */
    public function getIsWithdrawn()
    {
        return $this->isWithdrawn;
    }

    /**
     * @param string $lifecycleStatus
     * @return $this
     */
    public function setLifecycleStatus($lifecycleStatus)
    {
        //CV manage states as integer (probably we should have done the same)
        //this converts into a string
        if (is_int($lifecycleStatus)) {
            $lifecycleStatus = InvestmentLifecycle::intAsState($lifecycleStatus);
        }

        switch ($lifecycleStatus) {
            case InvestmentLifecycle::STATE_OPEN:
                $this->openOn = new \DateTime();
                $this->isOpen = true;
                $this->isRejected = false;
                $this->isApproved = false;
                $this->isWithdrawn = false;
                $this->isSettled = false;
                break;
            case InvestmentLifecycle::STATE_REJECTED:
                $this->rejectedOn = new \DateTime();
                $this->isOpen = false;
                $this->isRejected = true;
                $this->isApproved = false;
                $this->isWithdrawn = false;
                $this->isSettled = false;
                break;
            case InvestmentLifecycle::STATE_APPROVED:
                $this->approvedOn = new \DateTime();
                $this->isOpen = false;
                $this->isRejected = false;
                $this->isApproved = true;
                $this->isWithdrawn = false;
                $this->isSettled = false;
                break;
            case InvestmentLifecycle::STATE_WITHDRAWN:
                $this->withdrawnOn = new \DateTime();
                $this->isOpen = false;
                $this->isRejected = false;
                $this->isApproved = false;
                $this->isWithdrawn = true;
                $this->isSettled = false;
                break;
            case InvestmentLifecycle::STATE_SETTLED:
                $this->settledOn = new \DateTime();
                $this->isOpen = false;
                $this->isRejected = false;
                $this->isApproved = false;
                $this->isWithdrawn = false;
                $this->isSettled = true;
                break;
        }
        $this->lifecycleStatus = $lifecycleStatus;
        return $this;
    }

    /**
     * @return string
     */
    public function getLifecycleStatus()
    {
        return $this->lifecycleStatus;
    }

    /**
     * @return int
     */
    public function getLifecycleStatusAsInt()
    {
        return InvestmentLifecycle::StateAsInt($this->lifecycleStatus);
    }

    public function getStampDutyPaid(): bool
    {
        return $this->stampDutyPaid;
    }

    public function setStampDutyPaid(bool $stampDutyPaid): InvestmentStatus
    {
        $this->stampDutyPaid = $stampDutyPaid;

        return $this;
    }
}
