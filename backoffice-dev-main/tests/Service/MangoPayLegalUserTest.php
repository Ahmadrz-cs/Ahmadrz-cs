<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 06/12/16
 * Time: 14:55
 */

namespace App\Tests\Service;

use App\Entity\Address;
use App\Entity\Asset;
use App\Entity\AssetAddress;
use App\Entity\User;
use App\Service\MangoPay;
use App\Test\ExternalServiceWebTestCase;
use MangoPay\UserLegalSca;
use UnexpectedValueException;

class MangoPayLegalUserTest extends ExternalServiceWebTestCase
{
    public function testCanCreateLegalUser(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('salesforce_remote_tests');
        if (!$useRemoteTests) {
            $this->useMangopayServiceMock(self::MANGOPAY_CREATE_LEGAL_USER);
        }
        /** @var MangoPay $service */
        $service = static::getContainer()->get(MangoPay::class);

        //@todo should create client and register that on mangopay
        //@todo should create an asset on the fly for this test, the created user should be added at the point of contact
        $testAsset = $this->getTestAsset();

        $this->assertEquals(
            $testAsset->getContactPoint()->getUsername(),
            self::USER_SUPER_ADMIN,
        );
        $this->assertEquals($testAsset->getContactPoint()->getNationality(), 'GB');
        $this->assertEquals($testAsset->getContactPoint()->getPassportCountry(), 'GB');
        $testAsset->setOrgEmail('asset@emailaddress.co');

        $address = new AssetAddress();
        $address->setAddress1('street 1');
        $address->setAddress2('street 2');
        $address->setCity('city 1');
        $address->setRegion('region a');
        $address->setPostCode('E14 1AS');
        $address->setCountry('GB');

        $testAsset->getAddresses()->clear();
        $testAsset->addAddress($address);

        //The user must have address country set
        $userAddress = new Address();
        $userAddress->setAddress1('street 1');
        $userAddress->setCountry('GB');

        /** @var User $legaluser */
        $legaluser = $testAsset->getContactPoint();
        $legaluser->addAddress($userAddress);

        //now that we have a netural user for the asset on MP, create a
        //legal user for the asset
        /** @var UserLegalSca $mangoPayLegalUser */
        $mangoPayLegalUser = $service->createLegalUser($testAsset, $legaluser);

        $this->assertInstanceOf(UserLegalSca::class, $mangoPayLegalUser);
        $this->assertEquals('LEGAL', $mangoPayLegalUser->PersonType);
        $this->assertEquals('ORGANIZATION', $mangoPayLegalUser->LegalPersonType);
        $this->assertNotNull($mangoPayLegalUser->Id);
        $this->assertNotNull($mangoPayLegalUser->LegalRepresentative->FirstName);
        $this->assertNotNull($mangoPayLegalUser->LegalRepresentative->LastName);
        //@todo should also retrieve the Asset from the DB and confirm the mangopay id has been persisted

        // Check new MangoPay user differentiation fields
        $this->assertEquals('Owner', $mangoPayLegalUser->UserCategory);
        $this->assertTrue($mangoPayLegalUser->TermsAndConditionsAccepted);
        $this->assertNotNull($mangoPayLegalUser->HeadquartersAddress);
    }

    public function testCanNotCreateLegalUserValidationFailures(): void
    {
        /** @var MangoPay $service */
        $service = static::getContainer()->get(MangoPay::class);

        $testAsset = $this->getTestAsset();

        $address = new AssetAddress();
        $address->setAddress1('street 1');
        $address->setCountry('GB');

        //The user must have address country set
        $userAddress = new Address();
        $userAddress->setAddress1('street 1');
        $userAddress->setCountry('GB');

        if ($testAsset->getAddresses()->count() > 1) {
            $testAsset->removeAddress($testAsset->getAddresses()->first());
        }

        $testAsset->addAddress($address);

        /** @var User $legaluser */
        $legaluser = $testAsset->getContactPoint();
        $legaluser->addAddress($userAddress);

        //setup the asset for this test - Fail for missing point of contact
        $testAsset->setContactPoint(null);

        $testAsset->setOrgEmail('blah@blah.com');

        $emess = null;

        try {
            $service->createLegalUser($testAsset, $legaluser); // we expect an Exception here
        } catch (UnexpectedValueException $e) {
            $emess = $e->getMessage();
        }

        $this->assertEquals('Asset PointofContact can not be empty', $emess);

        //setup the asset for this test - Fail for missing address
        if ($testAsset->getAddresses()->count() > 0) {
            $testAsset->getAddresses()->clear();
        }
        $this->assertEquals($testAsset->getAddresses()->count(), 0);

        $testAsset->setContactPoint($legaluser);

        $emess = null;
        try {
            $service->createLegalUser($testAsset, $legaluser);
        } catch (\Exception $e) {
            $emess = $e->getMessage();
        }

        $this->assertEquals('Address cannot be empty', $emess);
        //setup the asset for this test - Fail for missing address1
        $address->setAddress1(null);
        $testAsset->addAddress($address);

        $testAsset->setContactPoint($legaluser);

        $emess = null;
        try {
            $service->createLegalUser($testAsset, $legaluser);
        } catch (\Exception $e) {
            $emess = $e->getMessage();
        }

        $this->assertEquals($emess, 'Address line 1 cannot be empty');

        //setup the asset for this test - Fail for missing City
        $testAsset->getAddresses()->clear();
        $address->setAddress1('street1');
        $address->setCity(null);
        $testAsset->addAddress($address);

        $testAsset->setContactPoint($legaluser);

        $emess = null;
        try {
            $service->createLegalUser($testAsset, $legaluser);
        } catch (\Exception $e) {
            $emess = $e->getMessage();
        }

        $this->assertEquals($emess, 'City cannot be empty');

        //setup the asset for this test - Fail for missing Region
        $testAsset->getAddresses()->clear();
        $address->setAddress1('street1');
        $address->setCity('city');
        $address->setRegion(null);
        $testAsset->addAddress($address);

        $testAsset->setContactPoint($legaluser);

        // Removed requirement for region to be a must have
        /*$emess = null;
         * try {
         * $service->createLegalUser($testAsset, $legaluser );
         * } catch (\Exception $e) { $emess = $e->getMessage(); }
         *
         * $this->assertEquals($emess, 'Region cannot be empty');
         *
         * //setup the asset for this test - Fail for missing PostCode
         * $testAsset->getAddresses()->clear();
         * $address->setAddress1('street1');
         * $address->setCity('city');
         * $address->setRegion('region');
         * $address->setPostCode(null);
         * $testAsset->addAddress($address);
         *
         * $testAsset->setContactPoint($legaluser);
         */

        $emess = null;
        try {
            $service->createLegalUser($testAsset, $legaluser);
        } catch (\Exception $e) {
            $emess = $e->getMessage();
        }

        $this->assertEquals($emess, 'PostCode cannot be empty');

        //setup the asset for this test - Fail for missing Country
        $testAsset->getAddresses()->clear();
        $address->setAddress1('street1');
        $address->setCity('city');
        $address->setRegion('region');
        $address->setPostCode('E14 2ED');
        $address->setCountry(null);
        $testAsset->addAddress($address);

        $testAsset->setContactPoint($legaluser);

        $emess = null;
        try {
            $service->createLegalUser($testAsset, $legaluser);
        } catch (\Exception $e) {
            $emess = $e->getMessage();
        }

        $this->assertEquals($emess, 'Country cannot be empty');

        //setup the asset for this test - Fail for missing org email
        $testAsset->getAddresses()->clear();
        $address->setAddress1('street1');
        $address->setCity('city');
        $address->setRegion('region');
        $address->setPostCode('E14 2ED');
        $address->setCountry('GB');
        $testAsset->addAddress($address);

        $testAsset->setOrgEmail(null);

        $emess = null;
        try {
            $service->createLegalUser($testAsset, $legaluser);
        } catch (\Exception $e) {
            $emess = $e->getMessage();
        }

        $this->assertEquals($emess, 'Asset OrgEmail can not be empty');
    }

    /**
     * @return null|object
     */
    private function getTestAsset(): ?object
    {
        /**
         * Tweak to get the relevant asset
         */
        $contactPointId = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_SUPER_ADMIN]);
        $randomAsset = $this->entityManager
            ->getRepository(Asset::class)
            ->findOneBy(['contactPoint' => $contactPointId]);
        // $randomAsset = $this->entityManager->getRepository(Asset::class)
        //     ->find(13);
        return $randomAsset;
    }
}
