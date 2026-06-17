<?php

namespace App\Entity\Lifecycle;

use App\Entity\Lifecycle\LifecycleInterface;
use App\Entity\User;
use Symfony\Component\Workflow\WorkflowInterface;

class UserLifecycle implements LifecycleInterface
{
    // Possible states
    public const STATE_EMAIL_NOT_VERIFIED = 'email_not_verified';
    public const STATE_EMAIL_VERIFIED = 'email_verified';
    public const STATE_REGISTRATION_COMPLETE = 'registration_complete';
    public const STATE_APPROVED = 'approved';
    public const STATE_BLOCKED = 'blocked';
    public const STATE_MANGOPAY_REGISTERED = 'mangopay_registered';

    // Supported transitions
    public const TRANSITION_EMAIL_VERIFICATION = 'email_not_verified_to_email_verified';
    public const TRANSITION_REGISTRATION_COMPLETE = 'email_verified_to_registration_complete';
    public const TRANSITION_APPROVE = 'registration_complete_to_approve';
    public const TRANSITION_REGISTRATION_TO_BLOCK = 'registration_to_block';
    public const TRANSITION_APPROVE_TO_BLOCK = 'approved_to_block';
    public const TRANSITION_MANGOPAY_REGISTRATION = 'mangopay_registration';

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
        return self::STATE_EMAIL_NOT_VERIFIED;
    }

    /**
     * Generic function to apply a self transition
     *
     * @param User $user The subject
     * @param string $transition The transition to apply. Check App\User for possible values
     * @param bool $persist Whether to persist the subject after applying transition
     * @return \Symfony\Component\Workflow\Marking The applied marking
     */
    public function applyTransition(User $user, $transition, $persist = false)
    {
        return $this->workflow->apply($user, $transition);
    }

    public static function getConvertedLifecycleStatus($lifecycleStatus)
    {
        if (!empty($lifecycleStatus)) {
            switch ($lifecycleStatus) {
                case UserLifecycle::STATE_EMAIL_NOT_VERIFIED:
                    $lifecycleStatusValue = 1;
                    break;

                case UserLifecycle::STATE_EMAIL_VERIFIED:
                    $lifecycleStatusValue = 2;
                    break;

                case UserLifecycle::STATE_REGISTRATION_COMPLETE:
                    $lifecycleStatusValue = 3;
                    break;

                case UserLifecycle::STATE_APPROVED:
                    $lifecycleStatusValue = 4;
                    break;

                case UserLifecycle::STATE_BLOCKED:
                    $lifecycleStatusValue = 5;
                    break;

                case UserLifecycle::STATE_MANGOPAY_REGISTERED:
                    $lifecycleStatusValue = 6;
                    break;
            }

            if (!empty($lifecycleStatusValue)) {
                return $lifecycleStatusValue;
            }
        }

        return false;
    }
}
