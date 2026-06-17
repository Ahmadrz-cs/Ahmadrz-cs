<?php

namespace App\Tests\Controller\ApiV1\Self;

use App\Entity\BankAccount;
use App\Entity\ContegoLog;
use App\Entity\Enum\BankAccountStatus;
use App\Entity\User;
use App\Test\ExternalServiceWebTestCase;
use App\Test\FixtureTestCase;
use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV1\ApiV1ResponseFields;
use Symfony\Component\HttpFoundation\Response;

class SelfGetResponseTest extends ExternalServiceWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetSelfIndex(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_ADMIN);
        $uri = FixtureWebTestCase::API_PATH_PREFIX_V1 . '/self';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        // Note that admins also get an is_admin key
        $object = $apiResponse['data']['user'];
        $this->assertEqualsCanonicalizing(
            array_merge(ApiV1ResponseFields::USER_SELF_EXTENDED, ['is_admin']),
            array_keys($object),
        );
        $this->assertNotNull($object['is_admin']);
        $this->assertIsInt($object['is_admin']);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetSelfIndexRegUser(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_VIP);
        $uri = FixtureWebTestCase::API_PATH_PREFIX_V1 . '/self';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        // Noe that admins also get an is_admin key
        $object = $apiResponse['data']['user'];
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::USER_SELF_EXTENDED,
            array_keys($object),
        );

        $this->assertArrayNotHasKey('is_admin', $object);
        $this->assertArrayHasKey('is_vip', $object);
        $this->assertNotNull($object['is_vip']);
        $this->assertIsInt($object['is_vip']);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    #[\PHPUnit\Framework\Attributes\Group('collection')]
    public function testGetSelfInvestmentsAsRegUser(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR);
        $uri = FixtureWebTestCase::API_PATH_PREFIX_V1 . '/self/investments';
        $parameters = [
            'offset' => 0,
            'limit' => 10,
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $objects = $apiResponse['data']['list'];
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::INVESTMENT_STANDARD,
            array_keys($objects[0]),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    #[\PHPUnit\Framework\Attributes\Group('collection')]
    public function testGetSelfInvestmentsAsAdmin(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_ADMIN);
        $uri = FixtureWebTestCase::API_PATH_PREFIX_V1 . '/self/investments';
        $parameters = [
            'offset' => 0,
            'limit' => 10,
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    #[\PHPUnit\Framework\Attributes\Group('collection')]
    public function testGetSelfOfferings(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_SUPER_ADMIN);
        $uri = FixtureWebTestCase::API_PATH_PREFIX_V1 . '/self/offerings';
        $parameters = [
            'offset' => 0,
            'limit' => 10,
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $objects = $apiResponse['data']['list'];
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::OFFERING_STANDARD,
            array_keys($objects[0]),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    #[\PHPUnit\Framework\Attributes\Group('collection')]
    public function testGetSelfDocuments(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR);
        $uri = FixtureWebTestCase::API_PATH_PREFIX_V1 . '/self/documents';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $this->assertArrayHasKey('outcome', $apiResponse);
        $this->assertArrayHasKey('data', $apiResponse);
        $this->assertArrayHasKey('status', $apiResponse);
        $objects = $apiResponse['data']['list'];
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::DOCUMENT_USER,
            array_keys($objects[0]),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetSelfContegoCheck(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if (!$useRemoteTests) {
            $this->useContegoServiceMock();
        }

        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR);
        $uri = FixtureWebTestCase::API_PATH_PREFIX_V1 . '/self/contegoCheck';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $this->assertEquals('success', $apiResponse['outcome']);
        $this->assertEquals('200', $apiResponse['status']);
        $this->assertArrayHasKey('data', $apiResponse);
        $this->assertArrayHasKey('ContegoScore', $apiResponse['data']);
        $this->assertArrayHasKey('score', $apiResponse['data']['ContegoScore']);
        $this->assertArrayHasKey('rag', $apiResponse['data']['ContegoScore']);
        $this->assertArrayHasKey('alerts', $apiResponse['data']['ContegoScore']);

        // Check contegoLog has been created for this user
        $filter = $this->searchFixtures(
            User::class,
            [
                'username' => FixtureTestCase::USER_REGULAR,
            ],
            true,
        )[0];

        /** @var ContegoLog $contegoLog */
        $contegoLog = $this->searchFixtures(ContegoLog::class, [
            'user' => $filter,
            'score' => ExternalServiceWebTestCase::KYC_TEST_SCORE,
        ])[0];
        $this->assertEquals(FixtureTestCase::USER_REGULAR, $contegoLog->getUser());
        $this->assertEquals(
            ExternalServiceWebTestCase::KYC_TEST_SCORE,
            $contegoLog->getKycScore(),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetSelfCompanyContegoCheck(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if (!$useRemoteTests) {
            $this->useContegoServiceMock();
        }

        // We will use the vendor user fixture who has a limited company with suitable address information
        $this->loginApiClientUser(FixtureTestCase::USER_VENDOR);
        $uri = FixtureWebTestCase::API_PATH_PREFIX_V1 . '/self/contegoCheckCompany';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $this->assertEquals('success', $apiResponse['outcome']);
        $this->assertEquals('200', $apiResponse['status']);
        $this->assertArrayHasKey('data', $apiResponse);
        $this->assertArrayHasKey('ContegoScore', $apiResponse['data']);
        $this->assertArrayHasKey('score', $apiResponse['data']['ContegoScore']);
        $this->assertArrayHasKey('rag', $apiResponse['data']['ContegoScore']);
        $this->assertArrayHasKey('alerts', $apiResponse['data']['ContegoScore']);

        // Check contegoLog has been created for this user
        $filter = $this->searchFixtures(
            User::class,
            [
                'username' => FixtureTestCase::USER_VENDOR,
            ],
            true,
        )[0];

        /** @var ContegoLog $contegoLog */
        $contegoLog = $this->searchFixtures(ContegoLog::class, [
            'user' => $filter,
            'score' => ExternalServiceWebTestCase::KYC_TEST_SCORE,
        ])[0];
        $this->assertEquals(FixtureTestCase::USER_VENDOR, $contegoLog->getUser());
        $this->assertEquals(
            ExternalServiceWebTestCase::KYC_TEST_SCORE,
            $contegoLog->getKycScore(),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetSelfComplianceStatus(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR);
        $uri = FixtureWebTestCase::API_PATH_PREFIX_V1 . '/self/checkComplianceStatus';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $this->assertArrayHasKey('complianceStatus', $apiResponse['data']);
        $this->assertArrayHasKey('mp_status', $apiResponse['data']['details']);
        $this->assertArrayHasKey('contego_status', $apiResponse['data']['details']);
        $this->assertArrayHasKey('payin_total', $apiResponse['data']['details']);
        $this->assertArrayHasKey('invest_total', $apiResponse['data']['details']);

        $this->assertArrayHasKey('has_been_approved', $apiResponse['data']['user']);
        $this->assertArrayHasKey('has_been_blocked', $apiResponse['data']['user']);
        $this->assertArrayHasKey('registration_complete', $apiResponse['data']['user']);
        $this->assertArrayHasKey('ob_step', $apiResponse['data']['user']);

        $this->assertArrayHasKey('term_service_accepted', $apiResponse['data']['user']);
        $this->assertArrayHasKey('gdpr_accepted', $apiResponse['data']['user']);
        $this->assertArrayHasKey('ob_step', $apiResponse['data']['user']);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    #[\PHPUnit\Framework\Attributes\Group('collection')]
    public function testGetSelfPayouts(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR);
        $uri = FixtureWebTestCase::API_PATH_PREFIX_V1 . '/self/payouts';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $this->assertArrayHasKey('outcome', $apiResponse);
        $this->assertArrayHasKey('data', $apiResponse);
        $this->assertArrayHasKey('status', $apiResponse);
        $objects = $apiResponse['data']['list'];
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::PAYOUT_STANDARD,
            array_keys($objects[0]),
        );

        $userId = $this->searchFixtures(
            User::class,
            [
                'username' => FixtureTestCase::USER_REGULAR,
            ],
            true,
        )[0];
        foreach ($objects as $payout) {
            if ($payout['creditedUserId']) {
                $this->assertEquals($userId, $payout['creditedUserId']);
                $this->assertNotEmpty($payout['assetId']);
            } else {
                $this->assertNotEmpty($payout['investment_id']);
                $this->assertEmpty($payout['assetId']);
                $this->assertEmpty($payout['creditedUserId']);
            }
        }
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    #[\PHPUnit\Framework\Attributes\Group('mangopay')]
    public function testGetSelfBankAccountSchema(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V1 . '/self/bank-accounts/schema';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::BANK_ACCOUNT_SCHEMA,
            array_keys($apiResponse),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    #[\PHPUnit\Framework\Attributes\Group('collection')]
    public function testGetSelfBankAccountRegistrations(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V1 . '/self/bank-accounts';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::BANK_ACCOUNT_REGISTRATION,
            array_keys($apiResponse[0]),
        );

        // Check that all the returned bank account registrations belong to the user
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy([
                'username' => self::USER_REGULAR,
            ]);
        $expected = $this->entityManager
            ->getRepository(BankAccount::class)
            ->findBy([
                'user' => $user->getId(), // can also just use the user object and not the id
            ]);
        foreach ($apiResponse as $registration) {
            $this->assertContains($registration['id'], $this->convertToIds(
                $expected,
                true,
            ));
        }
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    #[\PHPUnit\Framework\Attributes\Group('collection')]
    public function testGetSelfBankAccountRegistrationsFilterByStatus(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V1 . '/self/bank-accounts';
        $this->client->request('GET', $uri, ['status' => 'closed']);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::BANK_ACCOUNT_REGISTRATION,
            array_keys($apiResponse[0]),
        );

        // Check that all the returned bank account registrations belong to the user
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy([
                'username' => self::USER_REGULAR,
            ]);
        $expected = $this->entityManager
            ->getRepository(BankAccount::class)
            ->findBy([
                'user' => $user->getId(), // can also just use the user object and not the id
                'status' => BankAccountStatus::Closed,
            ]);
        foreach ($apiResponse as $registration) {
            $this->assertContains($registration['id'], $this->convertToIds(
                $expected,
                true,
            ));
        }
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    #[\PHPUnit\Framework\Attributes\Group('check')]
    public function testGetSelfBankAccountRegistrationSingle(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);

        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy([
                'username' => self::USER_REGULAR,
            ]);
        $sample = $this->entityManager
            ->getRepository(BankAccount::class)
            ->findOneBy([
                'user' => $user->getId(), // can also just use the user object and not the id
                'status' => BankAccountStatus::Closed,
            ]);

        $uri = self::API_PATH_PREFIX_V1 . "/self/bank-accounts/{$sample->getId()}";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::BANK_ACCOUNT_REGISTRATION,
            array_keys($apiResponse),
        );

        // Also try by Uuid
        $uri = self::API_PATH_PREFIX_V1 . "/self/bank-accounts/{$sample->getUuid()}";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::BANK_ACCOUNT_REGISTRATION,
            array_keys($apiResponse),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetSelfMangopayWalletScaNotRequired(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if (!$useRemoteTests) {
            $this->useMangopayServiceMock(ExternalServiceWebTestCase::MANGOPAY_VIEW_WALLET);
        } else {
            $this->fail('Remote tests not implemented yet');
        }

        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => FixtureTestCase::USER_REGULAR]);
        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR);
        $uri = FixtureWebTestCase::API_PATH_PREFIX_V1 . '/self/mangopay/wallet';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
        $this->assertEquals('success', $apiResponse['outcome']);
        $object = $apiResponse['data'];
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::WALLET_STANDARD,
            array_keys($object),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetSelfMangopayWalletScaRequired(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if (!$useRemoteTests) {
            $this->useMangopayServiceMock(ExternalServiceWebTestCase::MANGOPAY_VIEW_WALLET_SCA);
        } else {
            $this->fail('Remote tests not implemented yet');
        }

        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => FixtureTestCase::USER_REGULAR]);
        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR);
        $uri =
            FixtureWebTestCase::API_PATH_PREFIX_V1 . '/self/mangopay/wallet?sca=true';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $apiResponse['status']);
        $this->assertEquals('fail', $apiResponse['outcome']);
        $object = $apiResponse['data'];
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::WALLET_SCA_REQUIRED,
            array_keys($object),
        );
        $this->assertStringContainsString(
            'https://sca.sandbox.mangopay.com/?token=',
            $object['redirect_url'],
        );
        $this->assertEquals($user->getMangoPayWalletId(), $object['wallet_id']);
        $this->assertStringContainsString('SCA required', $object['user_message']);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    #[\PHPUnit\Framework\Attributes\Group('check')]
    public function testGetSelfMangopayWalletTransactionsScaNotRequired(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if (!$useRemoteTests) {
            $this->useMangopayServiceMock(ExternalServiceWebTestCase::MANGOPAY_LIST_TRANSACTIONS);
        } else {
            $this->fail('Remote tests not implemented yet');
        }

        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => FixtureTestCase::USER_REGULAR]);
        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR);
        $uri =
            FixtureWebTestCase::API_PATH_PREFIX_V1
            . '/self/mangopay/wallet/transactions';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
        $this->assertEquals('success', $apiResponse['outcome']);
        $objects = $apiResponse['data']['transactions'];
        $this->assertEmpty(array_diff(
            ApiV1ResponseFields::WALLET_TRANSACTION_STANDARD,
            array_keys($objects[0]),
        ));
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    #[\PHPUnit\Framework\Attributes\Group('check')]
    public function testGetSelfMangopayWalletTransactionsScaRequired(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if (!$useRemoteTests) {
            $this->useMangopayServiceMock(ExternalServiceWebTestCase::MANGOPAY_LIST_TRANSACTIONS_SCA);
        } else {
            $this->fail('Remote tests not implemented yet');
        }

        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => FixtureTestCase::USER_REGULAR]);
        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR);
        $uri =
            FixtureWebTestCase::API_PATH_PREFIX_V1
            . '/self/mangopay/wallet/transactions?sca=true';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $apiResponse['status']);
        $this->assertEquals('fail', $apiResponse['outcome']);
        $object = $apiResponse['data'];
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::WALLET_SCA_REQUIRED,
            array_keys($object),
        );
        $this->assertStringContainsString(
            'https://sca.sandbox.mangopay.com/?token=',
            $object['redirect_url'],
        );
        $this->assertEquals($user->getMangoPayWalletId(), $object['wallet_id']);
        $this->assertStringContainsString('SCA required', $object['user_message']);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    #[\PHPUnit\Framework\Attributes\Group('check')]
    public function testGetSelfMangopayPayin(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if (!$useRemoteTests) {
            $this->useMangopayServiceMock(ExternalServiceWebTestCase::MANGOPAY_VIEW_PAYIN);
        } else {
            $this->fail('Remote tests not implemented yet');
        }

        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => FixtureTestCase::USER_REGULAR]);
        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR);
        $uri =
            FixtureWebTestCase::API_PATH_PREFIX_V1
            . '/self/mangopay/payin/wt_0f44a630-454d-45ae-8de2-2389ea38f7bb';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
        $this->assertEquals('success', $apiResponse['outcome']);
        $object = $apiResponse['data'];
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::WALLET_TRANSACTION_PAYIN,
            array_keys($object),
        );
    }
}
