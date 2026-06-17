<?php

namespace App\Entity;

use App\Entity\BaseEntity;
use App\Entity\Lifecycle\UserLifecycle;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Status
 * @package App\Entity
 */
#[JMS\ExclusionPolicy('all')]
#[ORM\Table(name: 'users_statuses')]
#[ORM\Entity]
// ForDBAL4 #[Gedmo\Loggable]
class Status extends BaseEntity
{
    /**
     * @var \DateTime
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $emailNotVerifiedOn;

    /**
     * @var boolean
     */
    #[ORM\Column(type: 'boolean')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $isEmailNotVerifed;

    /**
     * @var \DateTime
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $emailValidatedOn;

    /**
     * @var boolean
     */
    #[ORM\Column(type: 'boolean')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $isEmailValidated;

    /**
     * @var \DateTime
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $regCompletedOn;

    /**
     * @var boolean
     */
    #[ORM\Column(type: 'boolean')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $isRegCompleted;

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
    private $blockedOn;

    /**
     * @var boolean
     */
    #[ORM\Column(type: 'boolean')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $isBlocked;

    /**
     * @var boolean
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(type: 'boolean')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $isKycApproved;

    /**
     * @var \DateTime
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $kycStatusOn;

    /**
     * @var \DateTime
     */
    #[ORM\Column(type: 'boolean', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $acctMgmtStatusOn;

    /**
     * @var \DateTime
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $mangopayRegistrationOn;

    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $lifecycleStatus;

    /**
     * @var string
     */
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $lifecycleStatusComment;

    /**
     * @var User
     */
    #[ORM\OneToOne(targetEntity: 'App\Entity\User', mappedBy: 'status')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $user;

    public function __construct()
    {
        $this->isEmailNotVerifed = false;

        $this->isEmailValidated = false;

        $this->isRegCompleted = false;

        $this->isApproved = false;

        $this->isBlocked = false;

        $this->isKycApproved = false;

        $this->setLifecycleStatus(UserLifecycle::getDefaultState());
    }

    /**
     * @param string $lifecycleStatus
     * @return $this
     */
    public function setLifecycleStatus($lifecycleStatus)
    {
        switch ($lifecycleStatus) {
            case UserLifecycle::STATE_EMAIL_NOT_VERIFIED:
                $this->isEmailNotVerifed = true;
                $this->emailNotVerifiedOn = new \DateTime();
                $this->lifecycleStatus = $lifecycleStatus;
                break;

            case UserLifecycle::STATE_EMAIL_VERIFIED:
                $this->isEmailValidated = true;
                $this->isEmailNotVerifed = false;
                $this->emailValidatedOn = new \DateTime();
                $this->lifecycleStatus = $lifecycleStatus;
                break;

            case UserLifecycle::STATE_REGISTRATION_COMPLETE:
                $this->isRegCompleted = true;
                $this->regCompletedOn = new \DateTime();
                $this->lifecycleStatus = $lifecycleStatus;
                break;

            case UserLifecycle::STATE_APPROVED:
                $this->approvedOn = new \DateTime();
                $this->isApproved = true;
                $this->isBlocked = false;
                $this->lifecycleStatus = $lifecycleStatus;
                break;

            case UserLifecycle::STATE_BLOCKED:
                /* Life cycle change when user are in approve and
                 * registration complete state*/
                if (in_array(
                    $this->lifecycleStatus,
                    [
                        UserLifecycle::STATE_APPROVED,
                        UserLifecycle::STATE_REGISTRATION_COMPLETE,
                    ],
                )) {
                    $this->isBlocked = true;
                    $this->isApproved = false;
                } else {
                    //Life cycle change when user are in othere states
                    $this->blockedOn = new \DateTime();
                }
                $this->lifecycleStatus = $lifecycleStatus;
                break;
            case UserLifecycle::STATE_MANGOPAY_REGISTERED:
                $this->mangopayRegistrationOn = new \DateTime();
                $this->lifecycleStatus = $lifecycleStatus;
                break;
        }
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
     * @return boolean
     */
    public function getIsApproved()
    {
        return $this->isApproved;
    }

    /**
     * @param boolean $isApproved
     */
    public function setIsApproved($isApproved)
    {
        $this->isApproved = $isApproved;
    }

    /**
     * Set emailValidatedOn
     *
     * @param \DateTime $emailNotVerifiedOn
     *
     * @return Status
     */
    public function setEmailNotVerifiedOn($emailNotVerifiedOn)
    {
        $this->emailNotVerifiedOn = $emailNotVerifiedOn;

        return $this;
    }

    /**
     * Get emailNotVerifiedOn
     *
     * @return \DateTime
     */
    public function getEmailNotVerifiedOn()
    {
        return $this->emailNotVerifiedOn;
    }

    /**
     * Set isEmailNotVerifed
     *
     * @param boolean $isEmailNotVerifed
     *
     * @return Status
     */
    public function setIsEmailNotVerifed($isEmailNotVerifed)
    {
        $this->isEmailNotVerifed = $isEmailNotVerifed;

        return $this;
    }

    /**
     * Get isEmailNotVerifed
     *
     * @return boolean
     */
    public function getIsEmailNotVerifed()
    {
        return $this->isEmailNotVerifed;
    }

    /**
     * Set emailValidatedOn
     *
     * @param \DateTime $emailValidatedOn
     *
     * @return Status
     */
    public function setEmailValidatedOn($emailValidatedOn)
    {
        $this->emailValidatedOn = $emailValidatedOn;

        return $this;
    }

    /**
     * Get emailValidatedOn
     *
     * @return \DateTime
     */
    public function getEmailValidatedOn()
    {
        return $this->emailValidatedOn;
    }

    /**
     * Set regCompletedOn
     *
     * @param \DateTime $regCompletedOn
     *
     * @return Status
     */
    public function setRegCompletedOn($regCompletedOn)
    {
        $this->regCompletedOn = $regCompletedOn;

        return $this;
    }

    /**
     * Get regCompletedOn
     *
     * @return \DateTime
     */
    public function getRegCompletedOn()
    {
        return $this->regCompletedOn;
    }

    /**
     * Set approvedOn
     *
     * @param \DateTime $approvedOn
     *
     * @return Status
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
     * Set isBlocked
     *
     * @param boolean $isBlocked
     *
     * @return Status
     */
    public function setIsBlocked($isBlocked)
    {
        $this->isBlocked = $isBlocked;

        return $this;
    }

    /**
     * Get isBlocked
     *
     * @return boolean
     */
    public function getIsBlocked()
    {
        return $this->isBlocked;
    }

    /**
     * Set blockedOn
     *
     * @param \DateTime $blockedOn
     *
     * @return Status
     */
    public function setBlockedOn($blockedOn)
    {
        $this->blockedOn = $blockedOn;

        return $this;
    }

    /**
     * Get blockedOn
     *
     * @return \DateTime
     */
    public function getBlockedOn()
    {
        return $this->blockedOn;
    }

    /**
     * Set isRegCompleted
     *
     * @param boolean $isRegCompleted
     *
     * @return Status
     */
    public function setIsRegCompleted($isRegCompleted)
    {
        $this->isRegCompleted = $isRegCompleted;

        return $this;
    }

    /**
     * Get isRegCompleted
     *
     * @return boolean
     */
    public function getIsRegCompleted()
    {
        return $this->isRegCompleted;
    }

    /**
     * Set isEmailValidated
     *
     * @param boolean $isEmailValidated
     *
     * @return Status
     */
    public function setIsEmailValidated($isEmailValidated)
    {
        $this->isEmailValidated = $isEmailValidated;

        return $this;
    }

    /**
     * Get isEmailValidated
     *
     * @return boolean
     */
    public function getIsEmailValidated()
    {
        return $this->isEmailValidated;
    }

    // Compatibility methods to match lifecycle status terminology
    public function getIsEmailVerified()
    {
        return $this->isEmailValidated;
    }

    public function getEmailVerifiedOn()
    {
        return $this->emailValidatedOn;
    }

    public function getIsRegistrationComplete()
    {
        return $this->isRegCompleted;
    }

    public function getRegistrationCompleteOn()
    {
        return $this->regCompletedOn;
    }

    /**
     * Set isKycApproved
     *
     * @param boolean $isKycApproved
     *
     * @return Status
     */
    public function setIsKycApproved($isKycApproved)
    {
        $this->isKycApproved = $isKycApproved;

        return $this;
    }

    /**
     * Get isKycApproved
     *
     * @return boolean
     */
    public function getIsKycApproved()
    {
        return $this->isKycApproved;
    }

    /**
     * Set kycStatusOn
     *
     * @param boolean $kycStatusOn
     *
     * @return Status
     */
    public function setKycStatusOn($kycStatusOn)
    {
        $this->kycStatusOn = $kycStatusOn;

        return $this;
    }

    /**
     * Get kycStatusOn
     *
     * @return boolean
     */
    public function getKycStatusOn()
    {
        return $this->kycStatusOn;
    }

    /**
     * Set acctMgmtStatusOn
     *
     * @param boolean $acctMgmtStatusOn
     *
     * @return Status
     */
    public function setAcctMgmtStatusOn($acctMgmtStatusOn)
    {
        $this->acctMgmtStatusOn = $acctMgmtStatusOn;

        return $this;
    }

    /**
     * Get acctMgmtStatusOn
     *
     * @return \DateTime
     */
    public function getAcctMgmtStatusOn()
    {
        return $this->acctMgmtStatusOn;
    }

    /**
     * Set user
     *
     * @param \App\Entity\User $user
     *
     * @return Status
     */
    public function setUser(?\App\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param $mangopayRegistrationOn
     * @return $this
     */
    public function setMangopayRegistrationOn($mangopayRegistrationOn)
    {
        $this->mangopayRegistrationOn = $mangopayRegistrationOn;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getMangopayRegistrationOn()
    {
        return $this->mangopayRegistrationOn;
    }

    /**
     * @param $comment string
     * @return $this
     */
    public function setLifecycleStatusComment($comment)
    {
        $this->lifecycleStatusComment = $comment;

        return $this;
    }

    /**
     * @return string
     */
    public function getLifecycleStatusComment()
    {
        return $this->lifecycleStatusComment;
    }
}
