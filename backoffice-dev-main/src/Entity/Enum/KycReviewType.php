<?php

namespace App\Entity\Enum;

enum KycReviewType: string
{
    case Onboarding = 'onboarding';
    case Vip = 'vip';
    case Recurring = 'recurring';
    case Adhoc = 'adhoc';
}
