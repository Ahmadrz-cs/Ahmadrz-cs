<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EditUserPermissionsVoter extends Voter
{
    public const EDIT_PERMISSION = 'CAN_EDIT_PERMISSION';
    public const EDIT_USER = 'CAN_EDIT_USER';
    public const EDIT_STATUS = 'CAN_EDIT_USER_STATUS';

    public function __construct(
        private AccessDecisionManagerInterface $decisionManager,
    ) {}

    protected function supports($attribute, $subject): bool
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [
            self::EDIT_PERMISSION,
            self::EDIT_USER,
            self::EDIT_STATUS,
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
        $userToken = new UsernamePasswordToken($subject, 'none', $subject->getRoles());

        switch ($attribute) {
            case self::EDIT_PERMISSION:
                if ($this->decisionManager->decide($token, ['ROLE_SUPER_ADMIN'])) {
                    return true;
                } elseif ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
                    if ($this->decisionManager->decide($userToken, [
                        'ROLE_SUPER_ADMIN',
                    ])) {
                        return false;
                    }
                    return true;
                } elseif ($this->decisionManager->decide($token, ['ROLE_OPERATIONS'])) {
                    return false;
                } elseif ($this->decisionManager->decide($token, ['ROLE_ANALYST'])) {
                    return false;
                }
                break;
            case self::EDIT_USER:
                if ($this->decisionManager->decide($token, ['ROLE_SUPER_ADMIN'])) {
                    return true;
                } elseif ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
                    if ($this->decisionManager->decide($userToken, [
                        'ROLE_SUPER_ADMIN',
                    ])) {
                        return false;
                    }
                    return true;
                } elseif ($this->decisionManager->decide($token, ['ROLE_OPERATIONS'])) {
                    if ($this->decisionManager->decide($userToken, ['ROLE_ADMIN'])) {
                        return false;
                    }
                    return true;
                } elseif ($this->decisionManager->decide($token, ['ROLE_ANALYST'])) {
                    return false;
                }
                break;
            case self::EDIT_STATUS:
                if ($this->decisionManager->decide($token, ['ROLE_OPERATIONS'])) {
                    return true;
                }
                break;
        }
        return false;
    }
}
