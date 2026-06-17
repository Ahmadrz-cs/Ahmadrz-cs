<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ViewUserPermissionsVoter extends Voter
{
    public const SUPER_ADMIN_VIEW = 'SUPER_ADMIN_VIEW_PERMISSIONS';
    public const ADMIN_VIEW = 'ADMIN_VIEW_PERMISSIONS';
    public const OPS_VIEW = 'OPS_VIEW_PERMISSIONS';
    public const VIEW = 'VIEW_PERMISSIONS';

    public function __construct(
        private Security $security,
    ) {}

    protected function supports($attribute, $subject): bool
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [
            self::SUPER_ADMIN_VIEW,
            self::ADMIN_VIEW,
            self::OPS_VIEW,
            self::VIEW,
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
            case self::SUPER_ADMIN_VIEW:
                if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
                    return true;
                }

                break;
            case self::ADMIN_VIEW:
                if ($this->security->isGranted('ROLE_ADMIN')) {
                    return true;
                }
                break;
            case self::OPS_VIEW:
                if ($this->security->isGranted('ROLE_OPERATIONS')) {
                    return true;
                }
                break;
            case self::VIEW:
                if ($this->security->isGranted('ROLE_ANALYST')) {
                    return true;
                }
                break;
        }
        return false;
    }
}
