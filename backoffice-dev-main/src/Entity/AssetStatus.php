<?php

namespace App\Entity;

use App\Entity\Asset;
use App\Entity\BaseEntity;
use App\Entity\Lifecycle\AssetLifecycle;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as JMS;

/**
 * Class Status
 * @package App\Entity
 */
#[ORM\Table(name: 'assets_status')]
#[ORM\Entity]
// ForDBAL4 #[Gedmo\Loggable]
class AssetStatus extends BaseEntity
{
    /**
     * @var \DateTime
     */
    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(type: 'datetime', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $draftOn;

    /**
     * @return \DateTime
     */
    public function getDraftOn()
    {
        return $this->draftOn;
    }

    /**
     * @return boolean
     */
    public function getIsDraft()
    {
        return $this->isDraft;
    }

    /**
     * @var boolean
     */
    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(type: 'boolean')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $isDraft;

    /**
     * @var \DateTime
     */
    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(type: 'datetime', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $archivedOn;

    /**
     * @var boolean
     */
    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(type: 'boolean')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $isArchived;

    /**
     * @var \DateTime
     */
    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(type: 'datetime', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $cancelledOn;

    /**
     * @var boolean
     */
    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(type: 'boolean')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $isCancelled;

    /**
     * @var \DateTime
     */
    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(type: 'datetime', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $submittedOn;

    /**
     * @var boolean
     */
    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(type: 'boolean')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $isSubmitted;

    /**
     * @var \DateTime
     */
    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(type: 'datetime', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $rejectedOn;

    /**
     * @var boolean
     */
    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(type: 'boolean')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $isRejected;

    /**
     * @var \DateTime
     */
    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(type: 'datetime', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $publishedOn;

    /**
     * @var boolean
     */
    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\Column(type: 'boolean')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $isPublished;

    /**
     * @var string
     */
    #[JMS\Groups(['minimum', 'standard'])]
    #[ORM\Column]
    protected $lifecycleStatus;

    /**
     * @var $asset
     */
    #[JMS\Groups(['admin'])]
    #[JMS\Exclude(if: "!is_granted('CAN_VIEW_ADMIN')")]
    #[ORM\OneToOne(targetEntity: 'App\Entity\Asset', mappedBy: 'assetStatus')]
    private $asset;

    public function __construct()
    {
        $this->isDraft = true;
        $this->isArchived = false;
        $this->isCancelled = false;
        $this->isSubmitted = false;
        $this->isRejected = false;
        $this->isPublished = false;

        $this->setLifecycleStatus(AssetLifecycle::getDefaultState());
    }

    /**
     * Set archivedOn
     *
     * @param \DateTime $archivedOn
     *
     * @return AssetStatus
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
     * @return AssetStatus
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
     * @return AssetStatus
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
     * @return AssetStatus
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
     * Set publishedOn
     *
     * @param \DateTime $publishedOn
     *
     * @return AssetStatus
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
     * @return AssetStatus
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
     * @return AssetStatus
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
     * @return AssetStatus
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
     * @return AssetStatus
     */
    public function setIsRejected($isRejected)
    {
        $this->isRejected = $isRejected;

        return $this;
    }

    /**
     * Set isPublished
     *
     * @param boolean $isPublished
     *
     * @return AssetStatus
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
     * @param mixed $lifecycleStatus
     * @return $this
     */
    public function setLifecycleStatus($lifecycleStatus)
    {
        //CV manage states as integer (probably we should have done the same)
        //this converts into a string
        if (is_int($lifecycleStatus)) {
            $lifecycleStatus = AssetLifecycle::intAsState($lifecycleStatus);
        }

        switch ($lifecycleStatus) {
            case AssetLifecycle::STATE_DRAFT:
                $this->draftOn = new \DateTime();
                $this->isDraft = true;
                break;

            case AssetLifecycle::STATE_ARCHIVED:
                $this->archivedOn = new \DateTime();
                $this->isArchived = true;
                $this->isDraft = false;
                break;

            case AssetLifecycle::STATE_CANCELLED:
                $this->cancelledOn = new \DateTime();
                $this->isCancelled = true;
                $this->isArchived = false;
                break;

            case AssetLifecycle::STATE_SUBMITTED:
                $this->submittedOn = new \DateTime();
                $this->isSubmitted = true;
                $this->isCancelled = false;
                break;

            case AssetLifecycle::STATE_REJECTED:
                $this->rejectedOn = new \DateTime();
                $this->isRejected = true;
                $this->isSubmitted = false;
                break;

            case AssetLifecycle::STATE_PUBLISHED:
                $this->publishedOn = new \DateTime();
                $this->isPublished = true;
                $this->isSubmitted = false;
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
        return AssetLifecycle::StateAsInt($this->lifecycleStatus);
    }
}
