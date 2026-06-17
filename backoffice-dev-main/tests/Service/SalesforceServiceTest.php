<?php

namespace App\Tests\Service;

use App\Service\SalesforceService;
use App\Test\ExternalServiceWebTestCase;

class SalesforceServiceTest extends ExternalServiceWebTestCase
{
    public function testCreateNewUserObject()
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('salesforce_remote_tests');
        if (!$useRemoteTests) {
            $this->useSalesforceServiceMock();
        }
        /** @var SalesforceService $service */
        $service = static::getContainer()->get(SalesforceService::class);

        $newUserDetails = [
            'LastName' => 'Davies',
            'firstname__c' => 'Yorran',
            'lastname__c' => 'Davies',
            'username__c' => 'yorrandavies@test.yielderverse.co.uk',
        ];
        $response = $service->create(self::SALESFORCE_OBJECT_NAME, $newUserDetails);
        $rspcode = $response->getStatusCode();
        $rspbody = json_decode($response->getBody(), true);
        $this->assertEquals(201, $rspcode);
        $this->assertArrayHasKey('id', $rspbody); // we only care about the id, other values not used
        return $rspbody['id'];
    }

    #[\PHPUnit\Framework\Attributes\Depends('testCreateNewUserObject')]
    public function testUpdateUserObject($salesforce_id): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('salesforce_remote_tests');
        if (!$useRemoteTests) {
            $this->useSalesforceServiceMock();
        }
        /** @var SalesforceService $service */
        $service = static::getContainer()->get(SalesforceService::class);

        $extraUserDetails = [
            'IsEmailValidated__c' => 1,
            'gender__c' => 'MALE',
            'honoricPrefix__c' => 'Mr',
            'jobTitle__c' => null,
            'address1__c' => 'A road name',
            'address2__c' => null,
            'address3__c' => null,
            'city__c' => 'London',
            'postCode__c' => 'E1 2PT',
            'country__c' => 'United Kingdom',
            'nationality__c' => 'United Kingdom',
            'mobile__c' => '07123456789',
            'phone1__c' => '02081234567',
            'birthDate__c' => '1986-04-02T00:00:00+01:00',
            // 'mangoPayUserId__c' => 123456,
            // 'mangoPayWalletId__c' => 123456,
            'isVIP__c' => 0,
            //'occupation__c' => $user['email_verified'],
            'referralCode__c' => 'Flyer',
            'gdpr_accepted__c' => 1,
            'cxbWorthInvestor__c' => 0,
            'cxbSophisticatedInvestor__c' => 1,
            'cxbRestrictedUser__c' => 0,
            'cxbLtdCompInvestor__c' => 0,
            'wordsOfOwn__c' => null,
            'IsApproved__c' => 0,
            'isRegCompleted__c' => 0,
            'isBlocked__c' => 0,
        ];

        $response = $service->update(
            self::SALESFORCE_OBJECT_NAME,
            $salesforce_id,
            $extraUserDetails,
        );
        $rspcode = $response->getStatusCode();
        $this->assertEquals(204, $rspcode);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testCreateNewUserObject')]
    public function testRetrieveUserObject($salesforce_id): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('salesforce_remote_tests');
        if (!$useRemoteTests) {
            $this->useSalesforceServiceMock();
        }
        /** @var SalesforceService $service */
        $service = static::getContainer()->get(SalesforceService::class);

        $response = $service->retrieve(self::SALESFORCE_OBJECT_NAME, $salesforce_id);
        $rspcode = $response->getStatusCode();
        $rspbody = json_decode($response->getBody(), true);
        $this->assertEquals(200, $rspcode);
        $this->assertEquals(
            self::SALESFORCE_OBJECT_NAME,
            $rspbody['attributes']['type'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Depends('testCreateNewUserObject')]
    public function testDeleteUserObject($salesforce_id): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('salesforce_remote_tests');
        if (!$useRemoteTests) {
            $this->useSalesforceServiceMock();
        }
        /** @var SalesforceService $service */
        $service = static::getContainer()->get(SalesforceService::class);

        $response = $service->delete(self::SALESFORCE_OBJECT_NAME, $salesforce_id);
        $rspcode = $response->getStatusCode();
        $this->assertEquals(204, $rspcode);
    }
}
