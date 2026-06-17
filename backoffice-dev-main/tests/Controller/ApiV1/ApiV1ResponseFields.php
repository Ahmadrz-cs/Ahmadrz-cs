<?php

namespace App\Tests\Controller\ApiV1;

final class ApiV1ResponseFields
{
    /**
     * Asset
     */
    public const ASSET_STANDARD = [
        'id',
        'address',
        'alternate_name',
        'additional_type',
        'brief_desc',
        'company_number',
        'contact_point',
        'detail_desc',
        'display_name',
        'legal_name',
        'life_cycle_stage',
        'org_email',
        'sector',
        'tax_id',
        'telephone',
        'visibility',
        'name',
        'term_length',
        'term_start',
        'term_end',
        'term_remaining',
        'mangopay_user_id',
        'mangopay_wallet_id',
        'documents',
        'members',
        'fees',
        'custom',
        'info',
        'approved_at',
        'canceled_at',
        'created_at',
        'submitted_at',
        'updated_at',
        'user_full_name',
        'user_id',
    ];
    public const ASSET_LIGHT = [
        'id',
        'address',
        'alternate_name',
        'additional_type',
        'brief_desc',
        'company_number',
        'contact_point',
        'detail_desc',
        'display_name',
        'legal_name',
        'life_cycle_stage',
        'org_email',
        'sector',
        'tax_id',
        'telephone',
        'visibility',
        'name',
        'term_length',
        'term_start',
        'term_end',
        'term_remaining',
        'mangopay_user_id',
        'mangopay_wallet_id',
        'custom',
        'info',
        'approved_at',
        'canceled_at',
        'created_at',
        'submitted_at',
        'updated_at',
        'user_full_name',
        'user_id',
    ];

    public const ASSET_PRODUCT = [
        'id',
        'name',
        'companyName',
        'description',
        'pricePerShare',
        'numberOfShares',
        'sharesAvailable',
        'minimumInvestment',
        'type',
        'status',
        'statusOccuredAt',
        'termStart',
        'termEnd',
        'termRemaining',
        'termLength',
        'netProjectedIncome',
        'netProjectedYield',
        'featured',
        'buyRestricted',
        'sellRestricted',
        'visibility',
        'documents',
        'address',
        'fees',
        'createdAt',
        'updatedAt',
    ];

    public const ASSET_PRODUCT_ADDRESS = [
        'assetId',
        'address1',
        'address2',
        'address3',
        'city',
        'postCode',
        'country',
        'latitude',
        'longitude',
    ];

    /**
     * Offering
     */
    public const OFFERING_STANDARD = [
        'asset_id',
        'organization_id',
        'id',
        'name',
        'type',
        'additional_type',
        'category',
        'funding_goal',
        'life_cycle_stage',
        'external_commitments',
        'is_featured',
        'is_secondary_offering',
        'valuation',
        'equity_offered',
        'num_of_shares',
        'pricePerShare',
        'sell_investment',
        'term',
        'open_date',
        'close_date',
        'min_commit_user',
        'max_commit_user',
        'max_over_funding',
        'comments',
        'visibility',
        'currency',
        'investor_count',
        'investment_count',
        'amount_raised',
        'amount_percent',
        'raised_percent',
        'capital_outstanding',
        'primary_offering_id',
        'documents',
        'custom',
        'info',
        'created_at',
        'submitted_at',
        'published_at',
        'settled_at',
        'updated_at',
        'user_id',
        'max_commitment',
        'max_overfunding_amount',
        'min_commitment',
        'price_per_share',
    ];

    /**
     * Investment
     */
    public const INVESTMENT_STANDARD = [
        'capital_outstanding',
        'currency',
        'divested_amount',
        'divested_shares',
        'funding_goal',
        'interest_rate',
        'investment_amount',
        'life_cycle_stage',
        'life_cycle_stage_name',
        'number_of_shares',
        'id',
        'name',
        'term',
        'user_id',
        'user_email',
        'user_name',
        'visibility',
        'type',
        'custom',
        'info',
        'documents',
        'asset_id',
        'asset_name',
        'org_id',
        'org_name',
        'offering_id',
        'raised_percent',
        'offered_shares',
        'approved_at',
        'created_at',
        'settled_at',
        'is_settled',
        'updated_at',
    ];

    /**
     * Payout
     */
    public const PAYOUT_STANDARD = [
        'id',
        'additional_type',
        'assetId',
        'creditedUserId',
        'custom',
        'currency',
        'due_date',
        'investment_id',
        'payout_type',
        'payout_amount',
        'created_at',
        'updated_at',
        'user_id',
        'user_name',
    ];

    public const PAYOUT_MAPPED = [
        'id',
        'userId',
        'assetId',
        'assetName',
        'shares',
        'value',
        'type',
        'createdAt',
        'updatedAt',
    ];

    /**
     * User
     */
    public const USER_STANDARD = [
        'id',
        'username',
        'email',
        'gender',
        'lastLogin',
        'status',
        'address',
        'firstName',
        'lastName',
        'nationality',
        'mobilePhone',
        'phone',
        'dateOfBirth',
        'isVIP',
        'createdAt',
        'updatedAt',
    ];

    public const USER_EXTENDED = [
        'image',
        'additional_name',
        'additional_type',
        'affiliate_code',
        'email',
        'username',
        'external_reference_id',
        'family_name',
        'full_name',
        'given_name',
        'honorific_prefix',
        'honorific_suffix',
        'id',
        'job_title',
        'last_login_at',
        'location',
        'referral_code',
        'address',
        'sector',
        'tagline',
        'visibility',
        'phone_verified',
        'email_verified',
        'has_been_approved',
        'has_been_blocked',
        'registration_complete',
        'term_service_accepted',
        'gdpr_accepted',
        'ob_step',
        'mifid_status',
        'created_at',
        'updated_at',
        'is_vip',
    ];

    public const USER_SELF_EXTENDED = [
        'image',
        'additional_name',
        'additional_type',
        'affiliate_code',
        'biography',
        'birth_country',
        'birth_date',
        'birth_place',
        'driving_license_number',
        'email',
        'username',
        'external_reference_id',
        'family_name',
        'full_name',
        'gender',
        'given_name',
        'honorific_prefix',
        'honorific_suffix',
        'id',
        'income_range',
        'job_title',
        'last_login_at',
        'location',
        // "mangopay_card_id",
        'mangopay_user_id',
        'mangopay_wallet_id',
        'has_mangopay_wallet_id',
        'nationality',
        'passport_country',
        // "passport_expiry",
        // "passport_number",
        // "password_expired",
        'phone_1',
        'phone_2',
        'mobile',
        'referral_code',
        'sector',
        'tagline',
        'tax_id',
        'time_zone',
        'visibility',
        'web_site',
        'address',
        'bank_accounts',
        'info',
        'documents',
        'organizations',
        'phone_verified',
        'email_verified',
        'has_been_approved',
        'has_been_blocked',
        'registration_complete',
        'term_service_accepted',
        'gdpr_accepted',
        'ob_step',
        'mifid_status',
        'onboarding_profile',
        'open_kyc_reviews',
        'sca_status',
        'sca_enrolled_at',
        'bank_accounts_synced_at',
        'account_status',
        'created_at',
        'updated_at',
        'is_vip',
    ];

    /**
     * Address
     */
    public const ADDRESS_ASSET = [
        'address1',
        'city',
        'postCode',
        'country',
        'latitude',
        'longitude',
    ];
    public const ADDRESS_USER = [
        'address1',
        'city',
        'postCode',
        'country',
    ];

    /**
     * Document
     */
    public const DOCUMENT_BASE = [
        'id',
        'file_alias',
        'file_description',
        'file_name',
        'file_type',
        'tag',
        'url',
        'document_url',
        'created_at',
        'updated_at',
        'user_id',
    ];
    public const DOCUMENT_ASSET = [...self::DOCUMENT_BASE, 'asset_document_id'];
    public const DOCUMENT_OFFERING = [...self::DOCUMENT_BASE, 'offering_document_id'];
    public const DOCUMENT_INVESTMENT = [
        ...self::DOCUMENT_BASE,
        'investment_document_id',
    ];
    public const DOCUMENT_USER = [
        ...self::DOCUMENT_BASE,
        'document_content',
        'asset_document_id',
    ]; // potential incorrect key here

    public const RELATIONAL_DOCUMENT = [
        'id',
        'relationLinkId',
        'relationId',
        'filename',
        'description',
        'type',
        'tag',
        'path',
        'url',
        'createdAt',
        'updatedAt',
    ];

    /**
     * Wallet
     */
    public const WALLET_STANDARD = [
        'id',
        'tag',
        'creation_date',
        'description',
        'currency',
        'balance',
    ];

    public const WALLET_SCA_REQUIRED = [
        'user_message',
        'developer_message',
        'wallet_id',
        'redirect_url',
    ];

    public const BANK_ACCOUNT_STANDARD = [
        'id',
        'account_number',
        'sort_code',
        'active',
        'owner_name',
        'created_at',
        'type',
    ];

    public const BANK_ACCOUNT_GB = [
        'id',
        'account_number',
        'sort_code',
        'owner_name',
        'created_at',
        'type',
    ];

    public const BANK_ACCOUNT_IBAN = [
        'id',
        'IBAN',
        'BIC',
        'owner_name',
        'created_at',
        'type',
    ];

    public const WALLET_TRANSACTION_STANDARD = [
        'Id',
        'AuthorId',
        'CreationDate',
        'CreditedFunds',
        'CreditedUserId',
        'CreditedWalletId',
        'DebitedFunds',
        'DebitedWalletId',
        'ExecutionDate',
        'Fees',
        'Nature',
        'ResultCode',
        'ResultMessage',
        'Status',
        'Tag',
        'Type',
    ];

    public const WALLET_TRANSACTION_PAYIN = [
        'id',
        'tag',
        'payment_type',
        'execution_type',
        'creation_date',
        'execution_date',
        'status',
        'result_code',
        'result_message',
        'amount',
        'currency',
    ];

    public const WALLET_BANKWIRE_PAYIN = [
        'owner_name',
        'IBAN',
        'BIC',
        'wire_reference',
        'type',
    ];

    public const WALLET_BANKWIRE_PAYOUT = [
        'created_at',
        'author_id',
        'amount',
        'fees',
        'type',
        'sort_code',
    ];

    public const WALLET_TRANSFER = [
        'id',
        'creation_date',
        'author_id',
        'credited_user_id',
        'debited_funds',
        'credited_funds',
        'status',
        'type',
        'execution_date',
        'nature',
        'result_message',
        'pending_user_action',
    ];

    public const BANK_ACCOUNT_SCHEMA = [
        'accountNumber',
        'bic',
    ];

    public const BANK_ACCOUNT_REGISTRATION = [
        'id',
        'uuid',
        'userId',
        'country',
        'currency',
        'accountHolderType',
        'accountNumber',
        'bic',
        'status',
        'displayName',
        'providerId',
        'method',
        'description',
        'metadata',
        'createdAt',
        'updatedAt',
    ];

    public const SCA_ENROLLMENT = [
        'PendingUserAction',
    ];

    public const SCA_STATUS = [
        'scaStatus',
        'scaEnrolledAt',
    ];

    public const SCA_ACTION = [
        'id',
        'object',
        'status',
        'providerId',
        'providerStatus',
        'pendingUserAction',
    ];

    public const SCA_OUTCOME = [
        'id',
        'object',
        'status',
        'providerId',
        'success',
    ];

    public const KYC_REVIEW = [
        'id',
        'status',
        'decision',
        'notes',
        'identityReview',
        'addressReview',
        'countryReview',
        'kycProviderReview',
        'dueDiligenceLevelReview',
        'kycSurveyReview',
        'transactionsReview',
        'reviewType',
        'subjectId',
        'reviewedById',
    ];

    /**
     * Onboarding
     */

    public const USER_ONBOARDING_PROFILE = [
        'cooloffEnd',
        'cooloffAccepted',
        'riskWarningAccepted',
        'category',
        'categoryReviewedAt',
        'assessmentPassed',
        'assessmentAttempts',
        'assessmentAttemptedAt',
    ];

    public const USER_ASSESSMENT = [
        'id',
        'userId',
        'passed',
        'complete',
        'expiry',
        'notes',
        'responses',
    ];

    public const USER_CATEGORISATION = [
        'id',
        'userId',
        'category',
        'details',
        'notes',
        'verified',
    ];

    public const QUESTION = [
        'id',
        'questionType',
        'section',
        'content',
        'active',
        'locked',
        'choices',
    ];

    public const QUESTION_CHOICE = [
        'id',
        'question',
        'content',
        'active',
        'correct',
    ];

    /**
     * Share trade system
     */

    public const TRADE_ORDER = [
        'id',
        'uuid',
        'assetId',
        'assetName',
        'userId',
        'pricePerShare',
        'numberOfShares',
        'minimumShares',
        'maximumShares',
        'sharesTraded',
        'sharesAvailable',
        'fees',
        'taxes',
        'status',
        'statusOccuredAt',
        'direction',
        'notes',
        'type',
        'createdAt',
        'updatedAt',
    ];

    public const SHARE_TRADE = [
        'id',
        'uuid',
        'assetId',
        'assetName',
        'sellerId',
        'buyerId',
        'pricePerShare',
        'numberOfShares',
        'tradeValue',
        'status',
        'statusOccuredAt',
        'type',
        'createdAt',
        'updatedAt',
    ];

    /**
     * Portfolio
     */

    public const USER_PORTFOLIO = [
        'userId',
        'value',
        'dividends',
        'capitalGains',
        'positions',
    ];

    public const USER_PORTFOLIO_POSITION = [
        'assetId',
        'assetName',
        'assetYield',
        'assetTermRemaining',
        'averagePrice',
        'shares',
        'value',
        'dividends',
        'capitalGains',
        'buyShares',
        'buyValue',
        'sellShares',
        'sellValue',
        'sharesAvailable',
    ];
}
