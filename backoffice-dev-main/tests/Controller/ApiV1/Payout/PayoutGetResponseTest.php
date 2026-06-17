<?php

namespace App\Tests\Controller\ApiV1\Payout;

use App\Entity\Payout;
use App\Entity\User;
use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV1\ApiV1ResponseFields;

class PayoutGetResponseTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('collection-response')]
    public function testGetPayoutsDefault(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/payouts';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $objects = $apiResponse['data']['list'];
        $this->assertGreaterThanOrEqual(1, count($objects));
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::PAYOUT_STANDARD,
            array_keys($objects[0]),
        );
        $expected = count($this->searchFixtures(Payout::class, [], true));
        $this->assertEquals($expected, $apiResponse['data']['count']);

        // check default metadata
        $this->assertEquals(0, $apiResponse['data']['offset']);
        $this->assertEquals(10, $apiResponse['data']['limit']);
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('collection-query')]
    public function testGetPayoutsQueryParameters(): void
    {
        // Check response from get payouts with various query parameters
        $this->loginApiClientUser(self::USER_SUPER_ADMIN);
        $sample = $this->searchFixtures(
            Payout::class,
            [
                'payoutType' => 0,
            ],
            true,
        );
        $uri = self::API_PATH_PREFIX_V1 . '/payouts';
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
    public function testGetPayoutsFilters(): void
    {
        // Check response from get payouts with various query parameters
        $this->loginApiClientUser(self::USER_SUPER_ADMIN);
        $sample = $this->searchFixtures(
            Payout::class,
            [
                'payoutType' => 1,
            ],
            true,
        );
        $uri = self::API_PATH_PREFIX_V1 . '/payouts';
        $offset = 1;
        $limit = 3;
        $parameters = [
            'offset' => $offset,
            'limit' => $limit,
            'sort' => implode(',', ['-id']),
            'type' => implode(',', ['profitshare']),
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseIsSuccessful();

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals($offset, $apiResponse['data']['offset']);
        $this->assertEquals($limit, $apiResponse['data']['limit']);

        // check query parameters worked
        $actual = array_map(
            fn($item): int => $item['id'],
            $apiResponse['data']['list'],
        );
        $expected = array_slice(array_reverse($sample), $offset, $limit);
        $this->assertEqualsCanonicalizing($expected, $actual);
        foreach ($apiResponse['data']['list'] as $object) {
            $this->assertEquals(1, $object['payout_type']);
        }
        ;
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetPayoutsSelf(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V1 . '/self/payouts';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $objects = $apiResponse['data']['list'];
        $this->assertGreaterThanOrEqual(1, count($objects));
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::PAYOUT_STANDARD,
            array_keys($objects[0]),
        );

        $filter = $this->searchFixtures(
            User::class,
            [
                'username' => self::USER_REGULAR,
            ],
            true,
        )[0];
        $sample = count($this->searchFixtures(
            Payout::class,
            [
                'creditedUser' => $filter,
            ],
            true,
        ));
        $this->assertEquals($sample, $apiResponse['data']['count']);
    }
}
