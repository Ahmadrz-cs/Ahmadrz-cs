<?php

namespace App\Entity;

use App\Entity\BaseEntity;
use App\Entity\Lifecycle\OfferingLifecycle;
use App\Entity\Offering;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Workflow\Workflow;

/**
 * Class Status
 * @package App\Entity
 */
#[ORM\Table(name: 'offerings_status')]
#[ORM\Entity]
// ForDBAL4 #[Gedmo\Loggable]
class OfferingStatus extends BaseEntity
{
    /**
     * @var \DateTime
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $draftedOn;

    /**
     * @var boolean
     */
    #[ORM\Column(type: 'boolean')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $isDraft;

    /**
     * @var \DateTime
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $archivedOn;

    /**
     * @var boolean
     */
    #[ORM\Column(type: 'boolean')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $isArchived;

    /**
     * @var \DateTime
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $cancelledOn;

    /**
     * @var boolean
     */
    #[ORM\Column(type: 'boolean')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $isCancelled;

    /**
     * @var \DateTime
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $submittedOn;

    /**
     * @var boolean
     */
    #[ORM\Column(type: 'boolean')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $isSubmitted;

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
    private $publishedOn;

    /**
     * @var boolean
     */
    #[ORM\Column(type: 'boolean')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $isPublished;

    /**
     * @var \DateTime
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $restrictedOn;

    /**
     * @var boolean
     */
    #[ORM\Column(type: 'boolean')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $isRestricted;

    /**
     * @var \DateTime
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $closedOn;

    /**
     * @var boolean
     */
    #[ORM\Column(type: 'boolean')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $isClosed;

    /**
     * @var \DateTime
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $settledOn;

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
     * @var $offering
     */
    #[ORM\OneToOne(targetEntity: 'App\Entity\Offering', mappedBy: 'offeringStatus')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $offering;

    public function __construct()
    {
        $this->isDraft = true;
        $this->isArchived = false;
        $this->isCancelled = false;
        $this->isSubmitted = false;
        $this->isRejected = false;
        $this->isPublished = false;
        $this->isApproved = false;
        $this->isClosed = false;
        $this->isSettled = false;
        $this->isRestricted = false;

        $this->setLifecycleStatus(OfferingLifecycle::getDefaultState());
    }

    /**
     * @return \DateTime
     */
    public function getDraftOn()
    {
        return $this->draftedOn;
    }

    /**
     * @return boolean
     */
    public function getIsDraft()
    {
        return $this->isDraft;
    }

    /**
     * Set archivedOn
     *
     * @param \DateTime $archivedOn
     *
     * @return OfferingStatus
     */
    public function setArchivedOn($archivedOn)
    {
        $this->archivedOn = $archivedOn;

        return $this;
    }

    /**
     * Get archivedOn
     *
     * @return \DateTime
     */
    public function getArchivedOn()
    {
        return $this->archivedOn;
    }

    /**
     * Set cancelledOn
     *
     * @param \DateTime $cancelledOn
     *
     * @return OfferingStatus
     */
    public function setCancelledOn($cancelledOn)
    {
        $this->cancelledOn = $cancelledOn;

        return $this;
    }

    /**
     * Get cancelledOn
     *
     * @return \DateTime
     */
    public function getCancelledOn()
    {
        return $this->cancelledOn;
    }

    /**
     * Set submittedOn
     *
     * @param \DateTime $submittedOn
     *
     * @return OfferingStatus
     */
    public function setSubmittedOn($submittedOn)
    {
        $this->submittedOn = $submittedOn;

        return $this;
    }

    /**
     * Get submittedOn
     *
     * @return \DateTime
     */
    public function getSubmittedOn()
    {
        return $this->submittedOn;
    }

    /**
     * Set rejectedOn
     *
     * @param \DateTime $rejectedOn
     *
     * @return OfferingStatus
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
     * Set approvedOn
     *
     * @param \DateTime $approvedOn
     *
     * @return OfferingStatus
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
     * Set publishedOn
     *
     * @param \DateTime $publishedOn
     *
     * @return OfferingStatus
     */
    public function setPublishedOn($publishedOn)
    {
        $this->publishedOn = $publishedOn;

        return $this;
    }

    /**
     * Get publishedOn
     *
     * @return \DateTime
     */
    public function getPublishedOn()
    {
        return $this->publishedOn;
    }

    /**
     * Set restrictedOn
     *
     * @param \DateTime $restrictedOn
     *
     * @return OfferingStatus
     */
    public function setRestrictedOn($restrictedOn)
    {
        $this->restrictedOn = $restrictedOn;

        return $this;
    }

    /**
     * Get restrictedOn
     *
     * @return \DateTime
     */
    public function getRestrictedOn()
    {
        return $this->restrictedOn;
    }

    /**
     * Set closedOn
     *
     * @param \DateTime $closedOn
     *
     * @return OfferingStatus
     */
    public function setClosedOn($closedOn)
    {
        $this->closedOn = $closedOn;

        return $this;
    }

    /**
     * Get closedOn
     *
     * @return \DateTime
     */
    public function getClosedOn()
    {
        return $this->closedOn;
    }

    /**
     * Set settledOn
     *
     * @param \DateTime $settledOn
     *
     * @return OfferingStatus
     */
    public function setSettledOn($settledOn)
    {
        $this->settledOn = $settledOn;

        return $this;
    }

    /**
     * Get settledOn
     *
     * @return \DateTime
     */
    public function getSettledOn()
    {
        return $this->settledOn;
    }

    /**
     * Get isArchived
     *
     * @return boolean
     */
    public function getIsArchived()
    {
        return $this->isArchived;
    }

    /**
     * Set isArchived
     *
     * @param boolean $isArchived
     *
     * @return OfferingStatus
     */
    public function setIsArchived($isArchived)
    {
        $this->isArchived = $isArchived;

        return $this;
    }

    /**
     * Get isCancelled
     *
     * @return boolean
     */
    public function getIsCancelled()
    {
        return $this->isCancelled;
    }

    /**
     * Set isCancelled
     *
     * @param boolean $isCancelled
     *
     * @return OfferingStatus
     */
    public function setIsCancelled($isCancelled)
    {
        $this->isCancelled = $isCancelled;

        return $this;
    }

    /**
     * Get isSubmitted
     *
     * @return boolean
     */
    public function getIsSubmitted()
    {
        return $this->isSubmitted;
    }

    /**
     * Set isSubmitted
     *
     * @param boolean $isSubmitted
     *
     * @return OfferingStatus
     */
    public function setIsSubmitted($isSubmitted)
    {
        $this->isSubmitted = $isSubmitted;

        return $this;
    }

    /**
     * Get isRejected
     *
     * @return boolean
     */
    public function getIsRejected()
    {
        return $this->isRejected;
    }

    /**
     * Set isRejected
     *
     * @param boolean $isRejected
     *
     * @return OfferingStatus
     */
    public function setIsRejected($isRejected)
    {
        $this->isRejected = $isRejected;

        return $this;
    }

    /**
     * Get isApproved
     *
     * @return boolean
     */
    public function getIsApproved()
    {
        return $this->isApproved;
    }

    /**
     * Set isApproved
     *
     * @param boolean $isApproved
     *
     * @return OfferingStatus
     */
    public function setIsApproved($isApproved)
    {
        $this->isApproved = $isApproved;

        return $this;
    }

    /**
     * Set isPublished
     *
     * @param boolean $isPublished
     *
     * @return OfferingStatus
     */
    public function setIsPublished($isPublished)
    {
        $this->isPublished = $isPublished;

        return $this;
    }

    /**
     * Get isPublished
     *
     * @return boolean
     */
    public function getIsPublished()
    {
        return $this->isPublished;
    }

    /**
     * Set isRestricted
     *
     * @param boolean $isRestricted
     *
     * @return OfferingStatus
     */
    public function setIsRestricted($isRestricted)
    {
        $this->isRestricted = $isRestricted;

        return $this;
    }

    /**
     * Get isRestricted
     *
     * @return boolean
     */
    public function getIsRestricted()
    {
        return $this->isRestricted;
    }

    /**
     * Get isClosed
     *
     * @return boolean
     */
    public function getIsClosed()
    {
        return $this->isClosed;
    }

    /**
     * Set isClosed
     *
     * @param boolean $isClosed
     *
     * @return OfferingStatus
     */
    public function setIsClosed($isClosed)
    {
        $this->isClosed = $isClosed;

        return $this;
    }

    /**
     * Get isSettled
     *
     * @return boolean
     */
    public function getIsSettled()
    {
        return $this->isSettled;
    }

    /**
     * Set isSettled
     *
     * @param boolean $isSettled
     *
     * @return OfferingStatus
     */
    public function setIsSettled($isSettled)
    {
        $this->isSettled = $isSettled;

        return $this;
    }

    /**
     * Set user
     *
     * @return OfferingStatus
     */
    public function setOffering(?Offering $offering = null)
    {
        $this->offering = $offering;

        return $this;
    }

    /**
     * @return User
     */
    public function getOffering()
    {
        return $this->offering;
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
            $lifecycleStatus = OfferingLifecycle::intAsState($lifecycleStatus);
        } else {
            $lifecycleStatus = strtolower($lifecycleStatus);
        }

        switch ($lifecycleStatus) {
            case OfferingLifecycle::STATE_DRAFT:
                $this->draftedOn = new \DateTime();
                $this->isDraft = true;
                break;
            /*            case OfferingLifecycle::STATE_ARCHIVED:
             * $this->archivedOn   = new \DateTime();
             * $this->isArchived  = true;
             * $this->isDraft = false;
             * break;
             */
            case OfferingLifecycle::STATE_CANCELLED:
                $this->cancelledOn = new \DateTime();
                $this->isCancelled = true;
                $this->isArchived = false;
                break;
            case OfferingLifecycle::STATE_SUBMITTED:
                $this->submittedOn = new \DateTime();
                $this->isSubmitted = true;
                $this->isCancelled = false;
                break;
            case OfferingLifecycle::STATE_REJECTED:
                $this->rejectedOn = new \DateTime();
                $this->isRejected = true;
                $this->isSubmitted = false;
                break;
            case OfferingLifecycle::STATE_APPROVED:
                $this->approvedOn = new \DateTime();
                $this->isApproved = true;
                $this->isRejected = false;
                break;
            case OfferingLifecycle::STATE_PUBLISHED:
                $this->publishedOn = new \DateTime();
                $this->isPublished = true;
                $this->isApproved = false;
                break;
            case OfferingLifecycle::STATE_RESTRICTED:
                $this->restrictedOn = new \DateTime();
                $this->isRestricted = true;
                $this->isApproved = false;
                break;
            case OfferingLifecycle::STATE_CLOSED:
                $this->closedOn = new \DateTime();
                $this->isClosed = true;
                $this->isPublished = false;
                break;
            case OfferingLifecycle::STATE_SETTELED:
                $this->settledOn = new \DateTime();
                $this->isSettled = true;
                $this->isClosed = false;
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
        return OfferingLifecycle::StateAsInt($this->lifecycleStatus);
    }
}
