<?php

namespace App\Tests\Controller\ApiV1\User;

use App\Entity\Lifecycle\UserLifecycle;
use App\Entity\User;
use App\Test\ExternalServiceWebTestCase;
use App\Test\FixtureTestCase;
use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV1\ApiV1ResponseFields;
use Symfony\Component\HttpFoundation\Response;

class UserGetResponseTest extends ExternalServiceWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('collection-response')]
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetUsersDefault(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_ADMIN);
        $uri = FixtureWebTestCase::API_PATH_PREFIX_V1 . '/users';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $objects = $apiResponse['data']['list'];
        // Check default pagination fields
        $this->assertEquals(0, $apiResponse['data']['offset']);
        $this->assertEquals(10, $apiResponse['data']['limit']);
        // Check results content
        $this->assertGreaterThan(1, $objects);
        if (array_key_exists('is_admin', $objects[0])) {
            $expected = array_merge(ApiV1ResponseFields::USER_EXTENDED, ['is_admin']);
        }
        $this->assertEqualsCanonicalizing(
            $expected ?? ApiV1ResponseFields::USER_EXTENDED,
            array_keys($objects[0]),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetUserSingleAsAdmin(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_ADMIN);
        $sample = $this->searchFixtures(User::class, [
            'username' => FixtureTestCase::USER_REGULAR,
        ])[0];
        $uri = FixtureWebTestCase::API_PATH_PREFIX_V1 . "/users/{$sample->getId()}";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
        $this->assertEquals('success', $apiResponse['outcome']);

        $object = $apiResponse['data']['user'];
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::USER_EXTENDED,
            array_keys($object),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('collection-query')]
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetUsersQueryParameters(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_ADMIN);
        $sample = [1, 3, 10, 12, 15, 18];
        $uri = FixtureWebTestCase::API_PATH_PREFIX_V1 . '/users';
        $offset = 1;
        $limit = 3;
        $parameters = [
            'offset' => $offset,
            'limit' => $limit,
            'sort' => implode(',', ['-id', 'updatedAt']),
            'id' => implode(',', $sample),
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseIsSuccessful();

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals($offset, $apiResponse['data']['offset']);
        $this->assertEquals($limit, $apiResponse['data']['limit']);
        $this->assertGreaterThanOrEqual($limit, $apiResponse['data']['count']);

        // check query parameters worked
        $actual = array_map(
            fn($item): int => $item['id'],
            $apiResponse['data']['list'],
        );
        $expected = array_slice(array_reverse($sample), $offset, $limit);
        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('collection-query')]
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetUsersFilters(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_SUPER_ADMIN);
        $expected = $this->searchFixtures(
            User::class,
            ['status' => [UserLifecycle::STATE_APPROVED]],
            true,
        );
        $uri = FixtureWebTestCase::API_PATH_PREFIX_V1 . '/users';
        $parameters = [
            'sort' => implode(',', ['-id']),
            'status' => implode(',', [
                UserLifecycle::getConvertedLifecycleStatus(UserLifecycle::STATE_APPROVED),
            ]),
            'id' => implode(',', $expected),
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseIsSuccessful();

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        rsort($expected);
        $expected = array_slice($expected, 0, 10); // default offset and limit of 0 and 10
        $actual = array_map(function ($x) {
            return $x['id'];
        }, $apiResponse['data']['list']);
        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetUserFieldIsAdmin(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_ADMIN);
        $sample = $this->searchFixtures(
            User::class,
            ['username' => FixtureTestCase::USER_ADMIN],
            true,
        )[0];
        $uri = FixtureWebTestCase::API_PATH_PREFIX_V1 . "/users/{$sample}";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $object = $apiResponse['data']['user'];
        $this->assertEqualsCanonicalizing(
            array_merge(ApiV1ResponseFields::USER_EXTENDED, ['is_admin']),
            array_keys($object),
        );
        $this->assertEquals('1', $object['is_admin']);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetUserFieldIsAdminNotExists(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_ADMIN);
        $sample = $this->searchFixtures(
            User::class,
            ['username' => FixtureTestCase::USER_REGULAR],
            true,
        )[0];
        $uri = FixtureWebTestCase::API_PATH_PREFIX_V1 . "/users/{$sample}";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $object = $apiResponse['data']['user'];
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::USER_EXTENDED,
            array_keys($object),
        );
        $this->assertArrayNotHasKey('is_admin', $object);
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetUserMangoPayWallets(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if (!$useRemoteTests) {
            $this->useMangopayServiceMock(ExternalServiceWebTestCase::MANGOPAY_LIST_WALLETS);
        } else {
            $this->fail('Remote tests not implemented yet');
        }

        $this->loginApiClientUser(FixtureTestCase::USER_ADMIN);
        $sample = $this->searchFixtures(
            User::class,
            ['username' => FixtureTestCase::USER_REGULAR],
            true,
        )[0];
        $uri =
            FixtureWebTestCase::API_PATH_PREFIX_V1 . "/users/{$sample}/mangopayWallets";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
        $this->assertEquals('success', $apiResponse['outcome']);

        $objects = $apiResponse['data']['wallets'];
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::WALLET_STANDARD,
            array_keys($objects[0]),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetUserMangopayBankAccounts(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if (!$useRemoteTests) {
            $this->useMangopayServiceMock(ExternalServiceWebTestCase::MANGOPAY_LIST_BANK_ACCOUNTS);
        } else {
            $this->fail('Remote tests not implemented yet');
        }

        $this->loginApiClientUser(FixtureTestCase::USER_ADMIN);
        $sample = $this->searchFixtures(
            User::class,
            ['username' => FixtureTestCase::USER_REGULAR],
            true,
        )[0];
        $uri = FixtureWebTestCase::API_PATH_PREFIX_V1 . "/users/{$sample}/bankaccounts";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
        $this->assertEquals('success', $apiResponse['outcome']);

        $objects = $apiResponse['data']['bank_accounts'];
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::BANK_ACCOUNT_STANDARD,
            array_keys($objects[0]),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('response')]
    #[\PHPUnit\Framework\Attributes\Group('check')]
    public function testGetUserWalletTransactions(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if (!$useRemoteTests) {
            $this->useMangopayServiceMock(ExternalServiceWebTestCase::MANGOPAY_LIST_TRANSACTIONS);
        } else {
            $this->fail('Remote tests not implemented yet');
        }

        $this->loginApiClientUser(FixtureTestCase::USER_ADMIN);
        $sample = $this->searchFixtures(User::class, [
            'username' => FixtureTestCase::USER_REGULAR,
        ])[0];
        $uri =
            FixtureWebTestCase::API_PATH_PREFIX_V1
            . "/users/{$sample->getId()}/mangopayWallets/{$sample->getMangoPayWalletId()}/transactions";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
        $this->assertEquals('success', $apiResponse['outcome']);

        $objects = $apiResponse['data']['transactions'];
        // Mangopay changed their record as of 2025-06-19, so just check the minimum fields are there
        $this->assertEmpty(array_diff(
            ApiV1ResponseFields::WALLET_TRANSACTION_STANDARD,
            array_keys($objects[0]),
        ));

        // $this->assertEqualsCanonicalizing(
        //     ApiV1ResponseFields::WALLET_TRANSACTION_STANDARD,
        //     array_keys($objects[0])
        // );
    }
}
