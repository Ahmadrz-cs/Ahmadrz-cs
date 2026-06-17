<?php

namespace App\Tests\Controller\ApiV1\Investment;

use App\Entity\Investment;
use App\Entity\InvestmentDocuments;
use App\Entity\Lifecycle\InvestmentLifecycle;
use App\Entity\Payout;
use App\Entity\User;
use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV1\ApiV1ResponseFields;
use Symfony\Component\HttpFoundation\Response;

class InvestmentGetResponseTest extends FixtureWebTestCase
{
    /*
     * @group response
     */
    public function testGetSingleInvestment(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/investments/1';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $object = $apiResponse['data']['investment'];
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::INVESTMENT_STANDARD,
            array_keys($object),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    #[\PHPUnit\Framework\Attributes\Group('collection')]
    public function testGetInvestmentDocs(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $filter = $this->searchFixtures(InvestmentDocuments::class, [])[0];
        $sample = $this->searchFixtures(
            Investment::class,
            [
                'id' => $filter->getInvestment()->getId(),
            ],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/investments/{$sample}/documents";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $objects = $apiResponse['data']['list'];
        $this->assertGreaterThanOrEqual(1, count($objects));
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::DOCUMENT_INVESTMENT,
            array_keys($objects[0]),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    #[\PHPUnit\Framework\Attributes\Group('collection')]
    public function testGetInvestmentPayouts(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $filterUser = $this->searchFixtures(
            User::class,
            [
                'username' => self::USER_REGULAR,
            ],
            true,
        )[0];
        // deliberately searching for "legacy" payouts with investment relation
        $filter = $this->searchFixtures(Payout::class, [
            'user' => $filterUser,
        ])[0];
        $sample = $this->searchFixtures(
            Investment::class,
            [
                'id' => $filter->getInvestment()->getId(),
            ],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/investments/{$sample}/payouts";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $objects = $apiResponse['data']['list'];
        $this->assertGreaterThanOrEqual(1, count($objects));
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::PAYOUT_STANDARD,
            array_keys($objects[0]),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetInvestmentsDefault(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/investments';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $objects = $apiResponse['data']['list'];
        $this->assertGreaterThanOrEqual(1, count($objects));
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::INVESTMENT_STANDARD,
            array_keys($objects[0]),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('collection-query')]
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetInvestmentsQueryParameters(): void
    {
        $this->loginApiClientUser(self::USER_SUPER_ADMIN);
        $sample = [1, 3, 10, 12, 15, 18];
        $uri = self::API_PATH_PREFIX_V1 . '/investments';
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
    public function testGetInvestmentsFilters(): void
    {
        $this->loginApiClientUser(self::USER_SUPER_ADMIN);
        $expected = $this->searchFixtures(
            Investment::class,
            [
                'status' => [
                    InvestmentLifecycle::STATE_APPROVED,
                    InvestmentLifecycle::STATE_SETTLED,
                ],
            ],
            true,
        );
        $uri = self::API_PATH_PREFIX_V1 . '/investments';
        $parameters = [
            'sort' => implode(',', ['-id']),
            'status' => implode(',', [
                InvestmentLifecycle::STATE_APPROVED_INT,
                InvestmentLifecycle::STATE_SETTLED_INT,
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
}
