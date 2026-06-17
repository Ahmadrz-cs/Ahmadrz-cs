<?php

namespace App\Tests\Controller\ApiV2\Payout;

use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV2\ApiResponseFields;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\RequiresEnvironmentVariable('testApiV2', '1')]
class PayoutGetResponseTest extends FixtureWebTestCase
{
    public function testGetPayouts(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/payouts';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::PAYOUT_STANDARD;
        $actualFields = array_keys($apiResponse['data'][0]);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    }

    public function testGetSinglePayout(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(\App\Entity\Payout::class, [], true);
        $uri = self::API_PATH_PREFIX_V2 . '/payouts/' . $sample[0];
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::PAYOUT_STANDARD;
        $actualFields = array_keys($apiResponse);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
        $this->assertEquals(1, $apiResponse['id']);
    }

    public function testGetPayoutsQueryIdVariantInvestment(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $filter = $this->searchFixtures(\App\Entity\Investment::class, [], true);
        $sample = $this->searchFixtures(
            \App\Entity\Payout::class,
            ['investment' => $filter],
            true,
        );
        $expected = array_rand(array_flip($sample), 3);
        $uri = self::API_PATH_PREFIX_V2 . '/payouts';
        $parameters = [
            'id' => implode(',', $expected),
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $actual = array_map(function ($x) {
            return $x['id'];
        }, $apiResponse['data']);
        $this->assertEqualsCanonicalizing($expected, $actual);
        $this->assertEquals(count($expected), count($actual));
    }

    public function testGetPayoutsQueryIdVariantShareholding(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $filter = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => 'ben.auto@test.yielderverse.co.uk'],
            true,
        );
        $sample = $this->searchFixtures(
            \App\Entity\Payout::class,
            ['creditedUser' => $filter],
            true,
        );
        $expected = array_rand(array_flip($sample), 2);
        $uri = self::API_PATH_PREFIX_V2 . '/payouts';
        $parameters = [
            'id' => implode(',', $expected),
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $actual = array_map(function ($x) {
            return $x['id'];
        }, $apiResponse['data']);
        $this->assertEqualsCanonicalizing($expected, $actual);
        $this->assertEquals(count($expected), count($actual));
    }
}
