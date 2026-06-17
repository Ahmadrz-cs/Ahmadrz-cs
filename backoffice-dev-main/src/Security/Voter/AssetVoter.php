<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AssetVoter extends Voter
{
    public const CREATE_ASSET = 'CAN_CREATE_ASSET';
    public const READ_ASSET = 'CAN_READ_ASSET';
    public const UPDATE_ASSET = 'CAN_UPDATE_ASSET';

    public function __construct(
        private AccessDecisionManagerInterface $decisionManager,
    ) {}

    protected function supports($attribute, $subject): bool
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [
            self::CREATE_ASSET,
            self::READ_ASSET,
            self::UPDATE_ASSET,
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
            case self::CREATE_ASSET:
                if ($this->decisionManager->decide($token, ['ROLE_OPERATIONS'])) {
                    return true;
                }
                break;
            case self::READ_ASSET:
                if ($this->decisionManager->decide($token, ['ROLE_ANALYST'])) {
                    return true;
                }
                break;
            case self::UPDATE_ASSET:
                if ($this->decisionManager->decide($token, ['ROLE_OPERATIONS'])) {
                    return true;
                }
                break;
        }

        return false;
    }
}
