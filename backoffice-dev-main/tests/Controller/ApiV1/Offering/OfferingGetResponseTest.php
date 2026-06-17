<?php

namespace App\Tests\Controller\ApiV1\Offering;

use App\Entity\Investment;
use App\Entity\Lifecycle\OfferingLifecycle;
use App\Entity\Offering;
use App\Entity\OfferingDocuments;
use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV1\ApiV1ResponseFields;
use Symfony\Component\HttpFoundation\Response;

class OfferingGetResponseTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('response')]
    #[\PHPUnit\Framework\Attributes\Group('collection')]
    public function testGetOfferings(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/offerings';
        $this->client->request('GET', $uri);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $objects = $apiResponse['data']['list'];
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::OFFERING_STANDARD,
            array_keys($objects[0]),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('resposne')]
    public function testGetOfferingSingle(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/offerings/1';
        $this->client->request('GET', $uri);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::OFFERING_STANDARD,
            array_keys($apiResponse['data']['offering']),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetOfferingInvestments(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(Investment::class, []);
        $sampleOffering = $sample[0]->getOffering()->getId();
        $uri = self::API_PATH_PREFIX_V1 . "/offerings/$sampleOffering/investments";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
        $this->assertEquals(0, $apiResponse['data']['offset']);
        $this->assertEquals(10, $apiResponse['data']['limit']);
        // Curiously, regular users can see other people's investments...
        $this->assertLessThan(count($sample), $apiResponse['data']['count']);

        $objects = $apiResponse['data']['list'];
        $this->assertGreaterThanOrEqual(1, count($objects));
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::INVESTMENT_STANDARD,
            array_keys($objects[0]),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    #[\PHPUnit\Framework\Attributes\Group('collection')]
    public function testGetOfferingDocs(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(OfferingDocuments::class, [])[0];
        $sampleId = $sample->getOffering()->getId();
        $uri = self::API_PATH_PREFIX_V1 . "/offerings/$sampleId/documents";
        $this->client->request('GET', $uri);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $objects = $apiResponse['data']['list'];
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::DOCUMENT_OFFERING,
            array_keys($objects[0]),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('collection-query')]
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetOfferingsQueryParameters(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(
            Offering::class,
            [
                'status' => OfferingLifecycle::STATE_PUBLISHED,
            ],
            true,
        );
        $uri = self::API_PATH_PREFIX_V1 . '/offerings';
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
        $this->assertEquals($limit, $apiResponse['data']['count']);

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
    public function testGetOfferingsFilters(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/offerings';

        // Check the sort and term query parameter
        $term = 5;
        $expected = $this->searchFixtures(
            Offering::class,
            [
                'offeringTerm' => $term,
            ],
            true,
        );
        $parameters = [
            'sort' => implode(',', ['-id']),
            'term' => $term,
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseIsSuccessful();

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        rsort($expected);
        $expected = array_slice($expected, 0, 10); // default offset and limit of 0 and 10
        $actual = array_map(
            fn($item): int => $item['id'],
            $apiResponse['data']['list'],
        );
        $this->assertEqualsCanonicalizing($expected, $actual);

        // Check the status query parameter
        $sample1 = $this->searchFixtures(
            Offering::class,
            [
                'status' => OfferingLifecycle::STATE_APPROVED,
            ],
            true,
        );
        $sample2 = $this->searchFixtures(
            Offering::class,
            [
                'status' => OfferingLifecycle::STATE_PUBLISHED,
            ],
            true,
        );
        $status = [
            OfferingLifecycle::STATE_APPROVED_INT,
            OfferingLifecycle::STATE_PUBLISHED_INT,
        ];
        $parameters = [
            'status' => implode(',', $status),
            'sort' => implode(',', ['-id']),
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseIsSuccessful();

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expected = array_merge($sample1, $sample2);
        rsort($expected);
        // Filtering for status happens on the first 10 AFTER doctrine retrieves
        // Bit weird, but limitation of legacy code
        $expected = array_slice($expected, 0, count($apiResponse['data']['list']));
        $actual = array_map(
            fn($item): int => $item['id'],
            $apiResponse['data']['list'],
        );
        $this->assertEqualsCanonicalizing($expected, $actual);
    }
}
