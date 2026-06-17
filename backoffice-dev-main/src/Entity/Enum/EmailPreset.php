<?php

namespace App\Entity\Enum;

enum EmailPreset: string
{
    /**
     * Should follow computer style naming convention of least specific to most specific
     */

    case UserRegistrationNew = 'user_registration_new';
    case UserVipApplicationCreate = 'user_vip_application_create';
    case UserVipApplicationApprove = 'user_vip_application_approve';
    case UserVipApplicationReject = 'user_vip_application_reject';
    case UserOnboardingComplete = 'user_onboarding_complete';
    case UserOnboardingReview = 'user_onboarding_review';
    case UserKycApprove = 'user_kyc_approve';
    case UserKycFail = 'user_kyc_fail';
    case UserSecurityMfaEmailCode = 'user_security_mfa_email_code';
    case UserSecurityPasswordForgot = 'user_security_password_forgot';
    case UserSecurityPasswordReset = 'user_security_password_reset';

    case InvestmentCreate = 'investment_create';
    case InvestmentWithdraw = 'investment_withdraw';

    case ListingSellCreate = 'listing_sell_create';
    case ListingSellPublish = 'listing_sell_publish';
    case ListingSellReject = 'list_sell_reject';

    case PayinBankwireRequest = 'payin_bankwire_request';

    case MonthendDivestment = 'monthend_divestment';
    case MonthendDividend = 'monthend_diviend';
    case MonthendStatement = 'monthend_statement';
}
