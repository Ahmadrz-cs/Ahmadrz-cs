<?php

namespace App\Tests\Controller\ApiV1\Self;

use App\Entity\Enum\KycReviewStatus;
use App\Entity\Enum\KycReviewType;
use App\Entity\Enum\ScaStatus;
use App\Entity\KycReview;
use App\Entity\User;
use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV1\ApiV1ResponseFields;
use Symfony\Component\HttpFoundation\Response;

class SelfPatchResponseTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testPatchSelfFieldsCountryHandling(): void
    {
        /**
         * Specific testcase for testing the management of
         * - country codes
         * - checkout
         * - address country
         * - nationality
         * - birth country
         * - passport country
         */
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V1 . '/self';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'nationality' => 'United Kingdom',
            'passport_country' => 'Germany',
            'birth_country' => 'Italy',
            'address' => [
                'building' => 'One Market Plaza',
                'street_address' => 'One M',
                'address3' => 'One Market Plaza3',
                'city' => 'San Francisco',
                'postcode' => '13222',
                'country' => 'France',
                'region' => 'USA',
            ],
        ]);
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $sample = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR,
        ])[0];
        $this->assertEquals($sample->getId(), $apiResponse['data']['user_id']);

        // Check the DB stores the country code, rather than the whole name
        $this->assertEquals('FR', $sample->getMainAddress()->getCountry());
        $this->assertEquals('GB', $sample->getNationality());
        $this->assertEquals('DE', $sample->getPassportCountry());
        $this->assertEquals('IT', $sample->getBirthCountry());

        // But you do get the country name from the API
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $object = $apiResponse['data']['user'];
        $this->assertEquals('France', $object['address']['country']);
        $this->assertEquals('United Kingdom', $object['nationality']);
        $this->assertEquals('Germany', $object['passport_country']);
        $this->assertEquals('Italy', $object['birth_country']);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testUpdateSelfFieldAddress(): void
    {
        // Also test for issue 1158, test that patching a user address doesn't add a duplicate

        /** @var User $userBefore */
        $userBefore = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR,
        ])[0];
        $addressCountBefore = $userBefore->getAddresses()->count();
        $this->assertSame(1, $addressCountBefore);

        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V1 . '/self';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'address' => [
                'building' => 'One Market Plaza',
                'street_address' => 'Spear Tower',
                'address3' => 'One Market Plaza3',
                'city' => 'San Francisco',
                'postcode' => '13222',
                'country' => 'France',
                'region' => 'South of France',
            ],
        ]);
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        // Check that the number of addresses is still the same (i.e. only 1)
        /** @var User $userAfter */
        $userAfter = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR,
        ])[0];
        $addressCountAfter1 = $userAfter->getAddresses()->count();
        $this->assertSame($addressCountBefore, $addressCountAfter1);

        // Check that the address has actually changed (rather than being appended)
        $this->assertNotEquals(
            $userBefore->getMainAddress()->getAddress1(),
            $userAfter->getMainAddress()->getAddress1(),
        );
        $this->assertNotEquals(
            $userBefore->getMainAddress()->getAddress2(),
            $userAfter->getMainAddress()->getAddress2(),
        );
        $this->assertNotEquals(
            $userBefore->getMainAddress()->getAddress3(),
            $userAfter->getMainAddress()->getAddress3(),
        );
        $this->assertNotEquals(
            $userBefore->getMainAddress()->getCity(),
            $userAfter->getMainAddress()->getCity(),
        );
        $this->assertNotEquals(
            $userBefore->getMainAddress()->getPostCode(),
            $userAfter->getMainAddress()->getPostCode(),
        );
        $this->assertNotEquals(
            $userBefore->getMainAddress()->getCountry(),
            $userAfter->getMainAddress()->getCountry(),
        );
        $this->assertNotEquals(
            $userBefore->getMainAddress()->getRegion(),
            $userAfter->getMainAddress()->getRegion(),
        );

        // Check the mapping of field name to db column is correct
        // e.g. building maps to address1
        $this->assertEquals(
            'One Market Plaza',
            $userAfter->getMainAddress()->getAddress1(),
        );
        $this->assertEquals('Spear Tower', $userAfter->getMainAddress()->getAddress2());
        $this->assertEquals(
            'One Market Plaza3',
            $userAfter->getMainAddress()->getAddress3(),
        );
        $this->assertEquals('San Francisco', $userAfter->getMainAddress()->getCity());
        $this->assertEquals('13222', $userAfter->getMainAddress()->getPostCode());
        $this->assertEquals('FR', $userAfter->getMainAddress()->getCountry()); // Note the db has the country code not name!
        $this->assertEquals(
            'South of France',
            $userAfter->getMainAddress()->getRegion(),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testUpdateSelfFieldSpecificCountryCodes(): void
    {
        /**
         * Specific testcase for testing the management specific country codes
         * - Palestinian Territories
         * - Syria
         */
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V1 . '/self';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'passport_country' => 'Palestinian Territories',
            'birth_country' => 'Syria',
        ]);
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $sample = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR,
        ])[0];
        $this->assertEquals($sample->getId(), $apiResponse['data']['user_id']);

        // Check the DB stores the country code, rather than the whole name
        $this->assertEquals('PS', $sample->getPassportCountry());
        $this->assertEquals('SY', $sample->getBirthCountry());

        // Check how the API converts the country code back into the country name
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $object = $apiResponse['data']['user'];
        $this->assertEquals('Palestinian Territories', $object['passport_country']);
        $this->assertEquals('Syrian Arab Republic', $object['birth_country']);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testUpdateSelfFieldsTopLevel(): void
    {
        /**
         * Any fields that are at the top level of the user object (no nesting)
         * - Personal info fields
         * - Some status fields
         */
        $fieldsToChange = [
            'given_name' => 'Athena',
            'family_name' => 'Franklin',
            'additional_name' => 'Heather',
            'email' => 'athena.auto@test.yielderverse.co.uk',
            'honorific_prefix' => 'Ms',
            'phone_1' => '072228881111',
            'phone_2' => '070000000008',
            'mobile' => '070000000008',
            'referral_code' => 'phpland',
            'gdpr_accepted' => 0,
            'ob_step' => 3,
            'mifid_status' => 1,
        ];
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V1 . '/self';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode($fieldsToChange);
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $object = $apiResponse['data']['user'];
        foreach ($fieldsToChange as $key => $value) {
            $this->assertEquals($value, $object[$key]);
        }
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testUpdateSelfFieldsNestedFields(): void
    {
        /**
         * Any fields that are in nested structures
         * - Info (additional fields)
         * - Company fields
         * - Compliance
         * - Top yielder
         */
        $fieldsToChange = [
            'info' => [
                'cxb_worth_investor' => true,
                'cxb_sophisticated_investor' => true,
                'cxb_restricted_investor' => true,
                'cxb_ltd_company_investor' => true,
                'always_go_up' => 'Yes',
                'income_every_month' => 'Yes',
                'never_exit' => 'Yes',
                'words_of_your_own' => 'top yielder message',
                'corporate_investor' => true,

                // 'company_registered_address_1' => 'Lane No 1', // Not used
                // 'registration_country' => 'India', // Not used
                // 'business_nature' => 'Windmill Parts', // Not used
                // 'building_name' => '1745', // Not used
                'company_directors' => 'Mr directors',
                'company_beneficial_owners' => 'Mr Owner',
                'company_website' => 'www.example.com',
                'operating_address' => 'Lane No 1, RD Avenue',
                'operating_postcode' => '77D78',

                // Questionnaire example fields
                'Q1' => '100',
                'Q2' => 'Maybe',
                'Q3' => 'Yes',
                'Q4' => 'No',
            ],
        ];
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V1 . '/self';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode($fieldsToChange);
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $object = $apiResponse['data']['user'];

        $flattenedInfo = [];
        foreach ($object['info'] as $field) {
            $flattenedInfo[$field['type']] = $field['value'];
        }
        foreach ($fieldsToChange['info'] as $key => $value) {
            $this->assertEquals($value, $flattenedInfo[$key]);
        }
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testUpdateSelfFieldsContegoGreenObComplete(): void
    {
        // Auto-enrollment works if your (Contego/Northrow) KYC is GREEN
        $this->loginApiClientUser(self::USER_REG_KYC_GREEN);
        $uri = self::API_PATH_PREFIX_V1 . '/self';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode(['ob_step' => 5]);

        // Check that the user has not yet finished onboarding
        $this->client->request('GET', $uri);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $object = $apiResponse['data']['user'];
        $this->assertNotEquals(5, $object['ob_step']);
        $this->assertEquals(false, $object['has_been_approved']);
        $this->assertEquals(false, $object['registration_complete']);

        // Attempt to auto-enroll by updating ob_step to "onboarding complete" (5)
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        // Check the user is now updated with auto-enrollment complete
        $this->client->request('GET', $uri);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $object = $apiResponse['data']['user'];
        $this->assertEquals(5, $object['ob_step']);
        $this->assertEquals(true, $object['has_been_approved']);
        $this->assertEquals(true, $object['registration_complete']);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testUpdateSelfFieldsContegoAmberObComplete(): void
    {
        // Auto-enrollment does NOT work if your (Contego/Northrow) KYC is GREEN
        $this->loginApiClientUser(self::USER_REG_KYC_AMBER);
        $uri = self::API_PATH_PREFIX_V1 . '/self';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode(['ob_step' => 5]);

        // Check that the user has not yet finished onboarding
        $this->client->request('GET', $uri);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $object = $apiResponse['data']['user'];
        $this->assertNotEquals(5, $object['ob_step']);
        $this->assertEquals(false, $object['has_been_approved']);
        $this->assertEquals(false, $object['registration_complete']);

        // Attempt to auto-enroll by updating ob_step to "onboarding complete" (5)
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        // Check the user is now updated, but auto-enrollment not completed
        $this->client->request('GET', $uri);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $object = $apiResponse['data']['user'];
        $this->assertEquals(5, $object['ob_step']);
        $this->assertEquals(false, $object['has_been_approved']);
        $this->assertEquals(false, $object['registration_complete']);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testUpdateSelfKycReview(): void
    {
        $user = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR,
        ])[0];
        $this->loginApiClientUser(self::USER_REGULAR);

        // Create a new KycReview for the user
        $kycReview = new KycReview(KycReviewType::Adhoc, $user);
        $kycReview->setStatus(KycReviewStatus::PendingSubjectAction);
        $this->entityManager->persist($kycReview);
        $this->entityManager->flush();
        $this->assertNotEmpty($kycReview->getId());

        $uri = self::API_PATH_PREFIX_V1 . "/self/kyc-reviews/{$kycReview->getId()}";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $requestBody = [
            'status' => 'ready',
        ];
        $content = json_encode($requestBody);
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::KYC_REVIEW,
            array_keys($apiResponse),
        );

        // Check API response
        $this->assertEquals(KycReviewStatus::Ready->value, $apiResponse['status']);
        // Refresh the option from the database and check db record (as a double check)
        $kycReview = $this->entityManager
            ->getRepository(KycReview::class)
            ->find($kycReview->getId());
        $this->assertEquals(KycReviewStatus::Ready, $kycReview->getStatus());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('scaStatusProvider')]
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testPatchSelfScaStatus(ScaStatus $status): void
    {
        $this->loginApiClientUser(self::USER_STAMP_DUTY);
        $uri = self::API_PATH_PREFIX_V1 . '/self/sca/status';
        $headers = ['CONTENT_TYPE' => 'application/json'];

        $requestBody = [
            'status' => $status->value,
        ];
        $content = json_encode($requestBody);
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::SCA_STATUS,
            array_keys($apiResponse),
        );
        $this->assertEquals($status->value, $apiResponse['scaStatus']);
        // Confirm the user has been updated
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy([
                'username' => self::USER_STAMP_DUTY,
            ]);
        $this->assertEquals($status, $user->getScaStatus());
    }

    public static function scaStatusProvider(): \Generator
    {
        yield 'inactive' => [ScaStatus::Inactive];
        yield 'pending in progress' => [ScaStatus::Pending];
        yield 'active' => [ScaStatus::Active];
    }
}
