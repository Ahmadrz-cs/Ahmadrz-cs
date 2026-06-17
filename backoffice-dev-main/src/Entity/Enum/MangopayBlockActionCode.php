<?php

namespace App\Entity\Enum;

/**
 * Useful until Mangopay provide their action codes in the SDK
 */
enum MangopayBlockActionCode: string
{
    case FraudulentBehaviour = '008701';
    case ComplianceIssue = '008702';
    case GeneralIssue = '008703';
    case AccountDeleted = '008704';
    case InvalidIdDoc = '008705';
    case PoliticallyExposedPerson = '008710';
    case NewIdDocRequired = '008711';
    case NewLegalPersonType = '008712';
    case NewAddressDocRequired = '008714';
    case KycVerificationRequired = '008715';
}
