<?php

namespace App\Entity\Lifecycle;

use App\Entity\Offering;
use App\Entity\User;
use Symfony\Component\Workflow\WorkflowInterface;

class OfferingLifecycle
{
    // Possible states
    //0 = Draft, 1 = Submitted, 2 = Rejected, 3 = Approved,
    //4 = Restricted, 5 = Published, 6 = Live, 7 = Closing, 8 = Settled, 9 = Canceled"
    public const STATE_DRAFT = 'draft';
    public const STATE_DRAFT_INT = 0;

    public const STATE_SUBMITTED = 'submitted';
    public const STATE_SUBMITTED_INT = 1;

    public const STATE_REJECTED = 'rejected';
    public const STATE_REJECTED_INT = 2;

    public const STATE_APPROVED = 'approved';
    public const STATE_APPROVED_INT = 3;

    public const STATE_RESTRICTED = 'restricted';
    public const STATE_RESTRICTED_INT = 4;

    public const STATE_PUBLISHED = 'published';
    public const STATE_PUBLISHED_INT = 5;

    public const STATE_LIVE = 'live';
    public const STATE_LIVE_INT = 6;

    public const STATE_CLOSED = 'closed';
    public const STATE_CLOSED_INT = 7;

    public const STATE_SETTELED = 'settled';
    public const STATE_SETTELED_INT = 8;

    public const STATE_CANCELLED = 'cancelled';
    public const STATE_CANCELLED_INT = 9;

    // Supported transitions
    public const TRANSITION_ARCHIVAL = 'archival';
    public const TRANSITION_CANCELLATION = 'cancellation';
    public const TRANSITION_SUBBMISSION = 'submission';
    public const TRANSITION_APPROVAL = 'approval';
    public const TRANSITION_REJECTION = 'rejection';
    public const TRANSITION_OFFERINGPUBLISHING = 'offering_publishing';
    public const TRANSITION_CLOSURE = 'closure';
    public const TRANSITION_SETTLEMENT = 'settlement';
    public const DRAFT_TO_ARCHIVAL = 'draft_to_archival';
    public const DRAFT_TO_SUBMIT = 'draft_to_submit';
    public const DRAFT_TO_CANCEL = 'draft_to_cancel';
    public const SUBMIT_TO_ARCHIVAL = 'submit_to_archival';
    public const SUBMIT_TO_REJECTION = 'submit_to_rejection';

    protected $workflow;

    public function __construct(WorkflowInterface $workflow)
    {
        $this->workflow = $workflow;
    }

    /**
     * @return string
     */
    public static function getDefaultState()
    {
        return self::STATE_DRAFT;
    }

    /**
     * Generic function to apply a self transition
     *
     * @param User $Offering The subject
     * @param string $transition The transition to apply. Check App\User for possible values
     * @param bool $persist Whether to persist the subject after applying transition
     * @return \Symfony\Component\Workflow\Marking The applied marking
     */
    public function applyTransition(Offering $Offering, $transition, $persist = false)
    {
        return $this->workflow->apply($Offering, $transition);
    }

    /**
     * Convert the integer code for state into name
     * @param int $lifecycleStatus
     */
    public static function intAsState($lifecycleStatus)
    {
        switch ($lifecycleStatus) {
            case self::STATE_DRAFT_INT:
                $asValue = self::STATE_DRAFT;
                break;
            case self::STATE_SUBMITTED_INT:
                $asValue = self::STATE_SUBMITTED;
                break;
            case self::STATE_REJECTED_INT:
                $asValue = self::STATE_REJECTED;
                break;
            case self::STATE_APPROVED_INT:
                $asValue = self::STATE_APPROVED;
                break;
            case self::STATE_RESTRICTED_INT:
                $asValue = self::STATE_RESTRICTED;
                break;
            case self::STATE_PUBLISHED_INT:
                $asValue = self::STATE_PUBLISHED;
                break;
            case self::STATE_LIVE_INT:
                $asValue = self::STATE_LIVE;
                break;
            case self::STATE_CLOSED_INT:
                $asValue = self::STATE_CLOSED;
                break;
            case self::STATE_SETTELED_INT:
                $asValue = self::STATE_SETTELED;
                break;
            case self::STATE_CANCELLED_INT:
                $asValue = self::STATE_CANCELLED;
                break;
        }
        return $asValue;
    }

    /**
     * Convert the Status name into integer code
     * @param int $lifecycleStatus
     */
    public static function StateAsInt($lifecycleStatus)
    {
        switch ($lifecycleStatus) {
            case self::STATE_DRAFT:
                $asInt = self::STATE_DRAFT_INT;
                break;
            case self::STATE_SUBMITTED:
                $asInt = self::STATE_SUBMITTED_INT;
                break;
            case self::STATE_REJECTED:
                $asInt = self::STATE_REJECTED_INT;
                break;
            case self::STATE_APPROVED:
                $asInt = self::STATE_APPROVED_INT;
                break;
            case self::STATE_RESTRICTED:
                $asInt = self::STATE_RESTRICTED_INT;
                break;
            case self::STATE_PUBLISHED:
                $asInt = self::STATE_PUBLISHED_INT;
                break;
            case self::STATE_LIVE:
                $asInt = self::STATE_LIVE_INT;
                break;
            case self::STATE_CLOSED:
                $asInt = self::STATE_CLOSED_INT;
                break;
            case self::STATE_SETTELED:
                $asInt = self::STATE_SETTELED_INT;
                break;
            case self::STATE_CANCELLED:
                $asInt = self::STATE_CANCELLED_INT;
                break;
        }
        return $asInt;
    }
}
