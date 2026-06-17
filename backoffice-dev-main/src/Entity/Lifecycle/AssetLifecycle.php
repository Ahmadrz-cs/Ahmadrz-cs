<?php

namespace App\Entity\Lifecycle;

use App\Entity\Asset;
use App\Entity\User;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @deprecated Use AssetStatusLog for record of status changes instead
 */
class AssetLifecycle
{
    // Possible states
    // as integer
    //"0 = Draft, 1 = Submitted, 2 = Rejected, 3 = Approved, 4 = Restricted, 5 = Published, 6 = Archived, 7 = Canceled"
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

    public const STATE_ARCHIVED = 'archived';
    public const STATE_ARCHIVED_INT = 6;

    public const STATE_CANCELLED = 'cancelled';
    public const STATE_CANCELLED_INT = 7;

    // Supported transitions
    public const TRANSITION_ARCHIVAL = 'archival';
    public const TRANSITION_CANCELLATION = 'cancellation';
    public const TRANSITION_SUBBMISSION = 'submission';
    public const TRANSITION_APPROVAL = 'approval';
    public const TRANSITION_REJECTION = 'rejection';
    public const TRANSITION_ASSETPUBLISHING = 'asset_publishing';
    public const DRAFT_TO_ARCHIVAL = 'draft_to_archival';
    public const DRAFT_TO_CANCELLED = 'draft_to_cancelled';
    public const DRAFT_TO_SUBMIT = 'draft_to_submit';
    public const SUBMIT_TO_APPROVE = 'submit_to_approve';

    protected WorkflowInterface $workflow;

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
     * @param User $user The subject
     * @param string $transition The transition to apply. Check App\User for possible values
     * @param bool $persist Whether to persist the subject after applying transition
     * @return \Symfony\Component\Workflow\Marking The applied marking
     */
    public function applyTransition(Asset $asset, $transition, $persist = false)
    {
        return $this->workflow->apply($asset, $transition);
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

            case self::STATE_PUBLISHED_INT:
                $asValue = self::STATE_PUBLISHED;
                break;

            case self::STATE_ARCHIVED_INT:
                $asValue = self::STATE_ARCHIVED;
                break;

            case self::STATE_CANCELLED_INT:
                $asValue = self::STATE_CANCELLED;
                break;

            case self::STATE_SUBMITTED_INT:
                $asValue = self::STATE_SUBMITTED;
                break;

            case self::STATE_REJECTED_INT:
                $asValue = self::STATE_REJECTED;
                break;
            case self::STATE_RESTRICTED_INT:
                $asValue = self::STATE_RESTRICTED;
                break;
            case self::STATE_APPROVED_INT:
                $asValue = self::STATE_APPROVED;
                break;
        }
        return $asValue;
    }

    public static function StateAsInt($lifecycleStatus)
    {
        switch ($lifecycleStatus) {
            case self::STATE_DRAFT:
                $asInt = self::STATE_DRAFT_INT;
                break;

            case self::STATE_PUBLISHED:
                $asInt = self::STATE_PUBLISHED_INT;
                break;

            case self::STATE_ARCHIVED:
                $asInt = self::STATE_ARCHIVED_INT;
                break;

            case self::STATE_CANCELLED:
                $asInt = self::STATE_CANCELLED_INT;
                break;

            case self::STATE_SUBMITTED:
                $asInt = self::STATE_SUBMITTED_INT;
                break;

            case self::STATE_REJECTED:
                $asInt = self::STATE_REJECTED_INT;
                break;
            case self::STATE_RESTRICTED:
                $asInt = self::STATE_RESTRICTED_INT;
                break;
            case self::STATE_APPROVED:
                $asInt = self::STATE_APPROVED_INT;
                break;
        }
        return $asInt;
    }
}
