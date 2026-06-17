<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class InvestmentVoter extends Voter
{
    public const CREATE_INVESTMENT = 'CAN_CREATE_INVESTMENT';
    public const READ_INVESTMENT = 'CAN_READ_INVESTMENT';
    public const UPDATE_INVESTMENT = 'CAN_UPDATE_INVESTMENT';

    public function __construct(
        private AccessDecisionManagerInterface $decisionManager,
    ) {}

    protected function supports($attribute, $subject): bool
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [
            self::CREATE_INVESTMENT,
            self::READ_INVESTMENT,
            self::UPDATE_INVESTMENT,
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
            case self::CREATE_INVESTMENT:
                if ($this->decisionManager->decide($token, ['ROLE_OPERATIONS'])) {
                    return true;
                }
                break;
            case self::READ_INVESTMENT:
                if ($this->decisionManager->decide($token, ['ROLE_ANALYST'])) {
                    return true;
                }
                break;
            case self::UPDATE_INVESTMENT:
                if ($this->decisionManager->decide($token, ['ROLE_OPERATIONS'])) {
                    return true;
                }
                break;
        }

        return false;
    }
}
