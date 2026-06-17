<?php

namespace App\Entity\Lifecycle;

use App\Entity\Investment;
use App\Entity\User;
use Symfony\Component\Workflow\WorkflowInterface;

class InvestmentLifecycle
{
    // Possible states
    //"life_cycle_stage": "0 = Open, 1 = Rejected, 2 = Approved, 3 = Withdrawn, 4 = Settled",

    public const STATE_OPEN = 'open';
    public const STATE_OPEN_INT = 0;
    public const STATE_REJECTED = 'rejected';
    public const STATE_REJECTED_INT = 1;
    public const STATE_APPROVED = 'approved';
    public const STATE_APPROVED_INT = 2;
    public const STATE_WITHDRAWN = 'withdrawn';
    public const STATE_WITHDRAWN_INT = 3;
    public const STATE_SETTLED = 'settled';
    public const STATE_SETTLED_INT = 4;

    // Supported transitions
    public const TRANSITION_ARCHIVAL = 'rejection';
    public const TRANSITION_OPEN_APPROVAL = 'open_to_approval';
    public const TRANSITION_OPEN_WITHDRAWN = 'open_to_withdrawn';
    public const TRANSITION_OPEN_REJECTION = 'open_to_rejection';
    public const TRANSITION_APPROVAL_OPEN = 'approval_to_open';
    public const TRANSITION_APPROVED_REJECTED = 'approve_to_rejection';
    public const TRANSITION_REJECTED_APPROVED = 'reject_to_approval';
    public const TRANSITION_APPROVE_WITHDRAWN = 'approve_to_withdrawn';
    public const TRANSACTION_APPROVE_SETTLED = 'approve_to_settled';

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
        return self::STATE_OPEN;
    }

    /**
     * Generic function to apply a self transition
     *
     * @param User $user The subject
     * @param string $transition The transition to apply. Check App\User for possible values
     * @param bool $persist Whether to persist the subject after applying transition
     * @return \Symfony\Component\Workflow\Marking The applied marking
     */
    public function applyTransition(
        Investment $investment,
        $transition,
        $persist = false,
    ) {
        return $this->workflow->apply($investment, $transition);
    }

    /**
     * Convert the integer code for state into name
     * @param int $lifecycleStatus
     */
    public static function intAsState($lifecycleStatus)
    {
        switch ($lifecycleStatus) {
            case self::STATE_OPEN_INT:
                $asValue = self::STATE_OPEN;
                break;

            case self::STATE_REJECTED_INT:
                $asValue = self::STATE_REJECTED;
                break;

            case self::STATE_APPROVED_INT:
                $asValue = self::STATE_APPROVED;
                break;

            case self::STATE_WITHDRAWN_INT:
                $asValue = self::STATE_WITHDRAWN;
                break;

            case self::STATE_SETTLED_INT:
                $asValue = self::STATE_SETTLED;
                break;
        }
        return $asValue;
    }

    public static function StateAsInt($lifecycleStatus)
    {
        switch ($lifecycleStatus) {
            case self::STATE_OPEN:
                $asInt = self::STATE_OPEN_INT;
                break;

            case self::STATE_REJECTED:
                $asInt = self::STATE_REJECTED_INT;
                break;

            case self::STATE_APPROVED:
                $asInt = self::STATE_APPROVED_INT;
                break;

            case self::STATE_WITHDRAWN:
                $asInt = self::STATE_WITHDRAWN_INT;
                break;

            case self::STATE_SETTLED:
                $asInt = self::STATE_SETTLED_INT;
                break;
        }
        return $asInt;
    }
}
