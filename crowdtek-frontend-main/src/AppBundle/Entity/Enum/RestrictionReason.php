<?php

namespace AppBundle\Entity\Enum;

enum RestrictionReason: string
{
    case NotAuthenticated = 'not logged';
    case RegistrationIncomplete = 'account registration incomplete';
    case NotApproved = 'account not approved';
    case ScaEnrollment = 'Strong Customer Authentication (SCA) not setup';
    case IdentityVerification = 'identity verification pending';
    case ProfileIncomplete = 'profile actions pending'; // primarily for PS22/10 retroactive and periodic actions
    case CooloffElapsed = 'cooling off period not yet ended';
}
