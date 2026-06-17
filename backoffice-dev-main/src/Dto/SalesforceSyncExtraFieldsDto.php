<?php

namespace App\Dto;

/**
 * Additional Salesforce Contact field names that we may want to sync
 * But where the data isn't usually found in the User entity
 */
readonly class SalesforceSyncExtraFieldsDto
{
    public function __construct(
        public ?string $MPWalletBalance__c = null,
        public ?string $InvestmentValue__c = null,
        public ?string $LastDateOfInvestment__c = null,
        public ?string $AssetName__c = null,
    ) {}
}
