<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DocumentVoter extends Voter
{
    public const CREATE_DOC = 'CAN_CREATE_DOC';
    public const READ_DOC = 'CAN_READ_DOC';
    public const UPDATE_DOC = 'CAN_UPDATE_DOC';
    public const DELETE_DOC = 'CAN_DELETE_DOC';

    public function __construct(
        private AccessDecisionManagerInterface $decisionManager,
    ) {}

    protected function supports($attribute, $subject): bool
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [
            self::CREATE_DOC,
            self::READ_DOC,
            self::UPDATE_DOC,
            self::DELETE_DOC,
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
            case self::CREATE_DOC:
                if ($this->decisionManager->decide($token, ['ROLE_OPERATIONS'])) {
                    return true;
                }
                break;
            case self::READ_DOC:
                if ($this->decisionManager->decide($token, ['ROLE_ANALYST'])) {
                    return true;
                }
                break;
            case self::UPDATE_DOC:
                if ($this->decisionManager->decide($token, ['ROLE_OPERATIONS'])) {
                    return true;
                }
                break;
            case self::DELETE_DOC:
                if ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
                    return true;
                }
                break;
        }

        return false;
    }
}
