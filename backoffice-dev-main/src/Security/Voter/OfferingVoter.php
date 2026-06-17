<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class OfferingVoter extends Voter
{
    public const CREATE_OFFERING = 'CAN_CREATE_OFFERING';
    public const READ_OFFERING = 'CAN_READ_OFFERING';
    public const UPDATE_OFFERING = 'CAN_UPDATE_OFFERING';

    public function __construct(
        private AccessDecisionManagerInterface $decisionManager,
    ) {}

    protected function supports($attribute, $subject): bool
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [
            self::CREATE_OFFERING,
            self::READ_OFFERING,
            self::UPDATE_OFFERING,
        ])) {
            return false;
        }

        // only vote on User objects inside this voter
        if (!$subject instanceof User) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(
        $attribute,
        $subject,
        TokenInterface $token,
    ): bool {
        switch ($attribute) {
            case self::CREATE_OFFERING:
                if ($this->decisionManager->decide($token, ['ROLE_OPERATIONS'])) {
                    return true;
                }
                break;
            case self::READ_OFFERING:
                if ($this->decisionManager->decide($token, ['ROLE_ANALYST'])) {
                    return true;
                }
                break;
            case self::UPDATE_OFFERING:
                if ($this->decisionManager->decide($token, ['ROLE_OPERATIONS'])) {
                    return true;
                }
                break;
        }

        return false;
    }
}
