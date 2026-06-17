<?php

namespace App\Tests\Controller\ApiV2;

final class ApiResponseFields
{
    /**
     * Asset
     */
    public const ASSET_MIN = [
        'id',
        'status',
        'name',
        'companyNumber',
        'fundingGoal',
        'numberOfShares',
        'type',
        'pricePerShare',
        'termLength',
        'termRemaining',
        'createdAt',
        'updatedAt',
    ];
    public const ASSET_STANDARD = [
        'id',
        'name',
        'companyNumber',
        'type',
        'displayName',
        'fundingGoal',
        'numberOfShares',
        'setupFee',
        'adminFee',
        'managementFee',
        'profitShare',
        'pricePerShare',
        'termLength',
        'termStart',
        'termEnd',
        'termRemaining',
        'status',
        'createdAt',
        'updatedAt',
        'address',
        'documents',
    ];
    public const ASSET_ADMIN = [
        'id',
        'createdAt',
        'updatedAt',
        'createdBy',
        'status',
        'name',
        'briefDescription',
        'companyNumber',
        'displayName',
        'fundingGoal',
        'numberOfShares',
        'setupFee',
        'adminFee',
        'managementFee',
        'profitShare',
        'type',
        'pricePerShare',
        'termLength',
        'termStart',
        'termEnd',
        'termRemaining',
        'stampDutyUser',
        'investmentTerm',
        // 'blockedForSale',
        'members',
        'contactPoint',
        'addFields',
        'documents',
        'visibility',
        'mangoPayUserId',
        'mangoPayWalletId',
        'additional_wallet',
        'offerings',
        'address',
    ];

    /**
     * Offering
     */
    public const OFFERING_STANDARD = [
        'id',
        'createdAt',
        'updatedAt',
        'assetId',
        'name',
        'fundingGoal',
        'externalCommitments',
        'isFeatured',
        'numberOfShares',
        'pricePerShare',
        'netAnnualYield',
        'netTotalReturn',
        'term',
        'minCommit',
        'maxCommit',
        'documents',
        'status',
        'amountRaised',
        'raisedPercent',
        'sharesSold',
        'termRemaining',
        'type',
    ];
    public const OFFERING_ADMIN = [
        'id',
        'createdBy',
        'assetId',
        'name',
        'category',
        'fundingGoal',
        'externalCommitments',
        'isFeatured',
        'isSecondaryOffering',
        'equityOffered',
        'numberOfShares',
        'pricePerShare',
        'netAnnualYield',
        'netTotalReturn',
        'term',
        'minCommit',
        'maxCommit',
        'primaryOfferingId',
        'documents',
        'status',
        'amountRaised',
        'raisedPercent',
        'sharesSold',
        'termRemaining',
        'type',
    ];

    /**
     * Investment
     */
    public const INVESTMENT_STANDARD = [
        'id',
        'assetId',
        'offeringId',
        'userId',
        'type',
        'numberOfShares',
        'pricePerShare',
        'investmentValue',
        'status',
        'transactionId',
        'documents',
        'metadata',
        'currency',
        'createdAt',
        'updatedAt',
    ];
    public const INVESTMENT_ADMIN = [];

    /**
     * Payout
     */
    public const PAYOUT_STANDARD = [
        'id',
        'userId',
        'assetId',
        'type',
        'amount',
        'currency',
        'dueDate',
        'createdAt',
        'updatedAt',
    ];
    public const PAYOUT_ADMIN = [];

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
    public const USER_ADMIN = [];

    /**
     * Payin
     */
    public const BANKWIRE_PAYIN_STANDARD = [
        'wireReference',
        'bankAccount',
        'amount',
        'currency',
    ];

    public const BANKWIRE_PAYIN_BANK_ACCOUNT = [
        'ownerName',
        'address',
        'iban',
        'bic',
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
    public const DOCUMENT_STANDARD = [
        'id',
        'createdAt',
        'updatedAt',
        'type',
        'description',
        'fileName',
        'tag',
        'url',
    ];

    /**
     * Wallet
     */
    public const WALLET_STANDARD = [
        'id',
        'creationDate',
        'currency',
        'balance',
    ];
}
