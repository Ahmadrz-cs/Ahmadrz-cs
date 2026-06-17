<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{
    public const VERIFY_EMAIL = 'verify_email';
    public const ADD_FUNDS = 'add_funds';
    public const GET_WALLET = 'get_wallet';

    public function __construct(
        private AccessDecisionManagerInterface $decisionManager,
    ) {}

    protected function supports($attribute, $subject): bool
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [
            self::VERIFY_EMAIL,
            self::ADD_FUNDS,
            self::GET_WALLET,
        ])) {
            return false;
        }

        // only vote on `User` objects
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
            case self::VERIFY_EMAIL:
                return $this->canVerifyEmail($token);
                break;
            case self::ADD_FUNDS:
                return $this->canAddFunds($token, $subject);
                break;
            case self::GET_WALLET:
                return $this->canGetWallet($token, $subject);
                break;
        }

        return false;
    }

    private function canVerifyEmail(TokenInterface $token)
    {
        if ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }
    }

    private function canAddFunds(TokenInterface $token, User $user)
    {
        if ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }
        if ($token->getUser()->getUserIdentifier()) {
            if ($token->getUser()->getUserIdentifier() == $user->getUserIdentifier()) {
                return true;
            }
        }

        return false;
    }

    private function canGetWallet(TokenInterface $token, User $user)
    {
        if ($token->getUser()->getUserIdentifier()) {
            if ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
                return true;
            }
            if ($token->getUser()->getUserIdentifier() == $user->getUserIdentifier()) {
                return true;
            }
        }

        return false;
    }
}
