<?php

namespace App\Tests\Service\Util;

use App\Service\Util\Helper;

class MpAccountHandlingTest extends \PHPUnit\Framework\TestCase
{
    protected $processedGbAccount = [
        'account_number' => '10028459',
        'sort_code' => '180268',
        'id' => '34560880',
        'type' => 'GB',
        'active' => true,
        'owner_name' => 'Ben',
        'created_at' => 1512468013,
    ];
    protected $processedIbanAccount = [
        'account_number' => 'FR7630004000031234567890143',
        'id' => '34560880',
        'type' => 'IBAN',
        'active' => true,
        'owner_name' => 'Ben',
        'created_at' => 1512468013,
    ];
    protected $processedOtherAccount = [
        'id' => '34560880',
        'type' => 'OTHER',
        'active' => true,
        'owner_name' => 'Ben',
        'created_at' => 1512468013,
    ];

    public function testGbAccountWithDetails(): void
    {
        $mpaccount = [
            'UserId' => '12345678',
            'Type' => 'GB',
            'OwnerName' => 'Ben',
            'OwnerAddress' => [
                'AddressLine1' => '52 Wood Lane',
                'AddressLine2' => null,
                'City' => 'BAGILLT',
                'Region' => null,
                'PostalCode' => 'CH6 2NL',
                'Country' => 'GB',
            ],
            'Details' => [
                'AccountNumber' => '10028459',
                'SortCode' => '180268',
            ],
            'Active' => true,
            'Id' => '34560880',
            'Tag' => null,
            'CreationDate' => 1512468013,
        ];
        $processedAccount = Helper::handleMangopayBankAccounts($mpaccount);
        $this->assertEmpty(array_diff_assoc(
            $this->processedGbAccount,
            $processedAccount,
        ));
    }

    public function testGbAccount(): void
    {
        $mpaccount = [
            'OwnerAddress' => [
                'AddressLine1' => '52 Wood Lane',
                'AddressLine2' => null,
                'City' => 'BAGILLT',
                'Region' => null,
                'PostalCode' => 'CH6 2NL',
                'Country' => 'GB',
            ],
            'UserId' => '12345678',
            'Type' => 'GB',
            'OwnerName' => 'Ben',
            'AccountNumber' => '10028459',
            'SortCode' => '180268',
            'Active' => true,
            'Id' => '34560880',
            'Tag' => null,
            'CreationDate' => 1512468013,
        ];
        $processedAccount = Helper::handleMangopayBankAccounts($mpaccount);
        $this->assertEmpty(array_diff_assoc(
            $this->processedGbAccount,
            $processedAccount,
        ));
    }

    public function testIbanAccountWithDetails(): void
    {
        $mpaccount = [
            'UserId' => '12345678',
            'Type' => 'IBAN',
            'OwnerName' => 'Ben',
            'OwnerAddress' => [
                'AddressLine1' => '52 Wood Lane',
                'AddressLine2' => null,
                'City' => 'BAGILLT',
                'Region' => null,
                'PostalCode' => 'CH6 2NL',
                'Country' => 'GB',
            ],
            'Details' => [
                'IBAN' => 'FR7630004000031234567890143',
                'BIC' => 'CRLYFRPP',
            ],
            'Active' => true,
            'Id' => '34560880',
            'Tag' => null,
            'CreationDate' => 1512468013,
        ];
        $processedAccount = Helper::handleMangopayBankAccounts($mpaccount);
        $this->assertEmpty(array_diff_assoc(
            $this->processedIbanAccount,
            $processedAccount,
        ));
    }

    public function testIbanAccount(): void
    {
        $mpaccount = [
            'OwnerAddress' => [
                'AddressLine1' => '52 Wood Lane',
                'AddressLine2' => null,
                'City' => 'BAGILLT',
                'Region' => null,
                'PostalCode' => 'CH6 2NL',
                'Country' => 'GB',
            ],
            'UserId' => '12345678',
            'Type' => 'IBAN',
            'OwnerName' => 'Ben',
            'IBAN' => 'FR7630004000031234567890143',
            'BIC' => 'CRLYFRPP',
            'Active' => true,
            'Id' => '34560880',
            'Tag' => null,
            'CreationDate' => 1512468013,
        ];
        $processedAccount = Helper::handleMangopayBankAccounts($mpaccount);
        $this->assertEmpty(array_diff_assoc(
            $this->processedIbanAccount,
            $processedAccount,
        ));
    }

    public function testOtherAccountWithDetails(): void
    {
        $mpaccount = [
            'UserId' => '12345678',
            'Type' => 'OTHER',
            'OwnerName' => 'Ben',
            'OwnerAddress' => [
                'AddressLine1' => '52 Wood Lane',
                'AddressLine2' => null,
                'City' => 'BAGILLT',
                'Region' => null,
                'PostalCode' => 'CH6 2NL',
                'Country' => 'GB',
            ],
            'Details' => [
                'AccountNumber' => '11696419',
                'BIC' => 'CRLYFRPP',
            ],
            'Active' => true,
            'Id' => '34560880',
            'Tag' => null,
            'CreationDate' => 1512468013,
        ];
        $processedAccount = Helper::handleMangopayBankAccounts($mpaccount);
        $this->assertEmpty(array_diff_assoc(
            $this->processedOtherAccount,
            $processedAccount,
        ));
    }

    public function testOtherAccount(): void
    {
        $mpaccount = [
            'OwnerAddress' => [
                'AddressLine1' => '52 Wood Lane',
                'AddressLine2' => null,
                'City' => 'BAGILLT',
                'Region' => null,
                'PostalCode' => 'CH6 2NL',
                'Country' => 'GB',
            ],
            'UserId' => '12345678',
            'Type' => 'OTHER',
            'OwnerName' => 'Ben',
            'AccountNumber' => '11696419',
            'BIC' => 'CRLYFRPP',
            'Active' => true,
            'Id' => '34560880',
            'Tag' => null,
            'CreationDate' => 1512468013,
        ];
        $processedAccount = Helper::handleMangopayBankAccounts($mpaccount);
        $this->assertEmpty(array_diff_assoc(
            $this->processedOtherAccount,
            $processedAccount,
        ));
    }
}
