<?php

namespace App\Tests\Controller\ApiV2\Offering;

use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV2\ApiResponseFields;
use Symfony\Component\HttpFoundation\Response;

class OfferingGetResponseTest extends FixtureWebTestCase
{
    public function testGetOfferings(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/offerings';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::OFFERING_STANDARD;
        $actualFields = array_keys($apiResponse['data'][0]);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    }

    public function testGetSingleOffering(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/offerings/1';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::OFFERING_STANDARD;
        $actualFields = array_keys($apiResponse);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
        $this->assertEquals(1, $apiResponse['id']);
    }

    // public function testGetOfferingsQueryId(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings';
    //     $parameters = [
    //         'id' => implode(',', range(1, 8)),
    //     ];
    //     $this->client->request('GET', $uri, $parameters);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_OK);

    //     $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
    //     $expectedFields = [1, 2, 3, 4, 5, 6, 7, 8];
    //     $actualFields = array_map(function ($x) {
    //         return $x['id'];
    //     }, $apiResponse['data']);
    //     $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    //     $this->assertEquals(count($expectedFields), count($actualFields));
    // }

    // public function testGetOfferingsQueryFilters(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings';
    //     $parameters = [
    //         'id' => implode(',', range(1, 8)),
    //         'isFeatured' => 'true'
    //     ];
    //     $this->client->request('GET', $uri, $parameters);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_OK);

    //     $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
    //     $expectedFields = [2];
    //     $actualFields = array_map(function ($x) {
    //         return $x['id'];
    //     }, $apiResponse['data']);
    //     $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    //     $this->assertEquals(count($expectedFields), count($actualFields));
    // }

    // public function testGetOfferingsQueryViewAdmin(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings';
    //     $parameters = [
    //         'view' => 'admin'
    //     ];
    //     $this->client->request('GET', $uri, $parameters);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_OK);

    //     $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
    //     $expectedFields = ApiResponseFields::OFFERING_ADMIN;
    //     $actualFields = array_keys($apiResponse['data'][0]);
    //     $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    // }

    // public function testGetSingleOfferingQueryViewAdmin(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings/1';
    //     $parameters = [
    //         'view' => 'admin'
    //     ];
    //     $this->client->request('GET', $uri, $parameters);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_OK);

    //     $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
    //     $expectedFields = ApiResponseFields::OFFERING_ADMIN;
    //     $actualFields = array_keys($apiResponse);
    //     $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    //     $this->assertEquals(1, $apiResponse['id']);
    // }

    public function testGetOfferingDocuments(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $filter = $this->searchFixtures(
            \App\Entity\Offering::class,
            ['status' => 'published'],
            true,
        );
        $sample = $this->searchFixtures(\App\Entity\OfferingDocuments::class, [
            'offering' => $filter[0],
        ]);
        $uri = self::API_PATH_PREFIX_V2 . '/offerings/' . $filter[0] . '/documents';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::DOCUMENT_STANDARD;
        $actualFields = array_keys($apiResponse['data'][0]);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
        $this->assertMatchesRegularExpression(
            '~cloudfront.net~',
            $apiResponse['data'][0]['url'],
        );
    }

    public function testGetOfferingSingleDocument(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $filter = $this->searchFixtures(
            \App\Entity\Offering::class,
            ['status' => 'published'],
            true,
        );
        $sample = $this->searchFixtures(
            \App\Entity\OfferingDocuments::class,
            ['offering' => $filter[0]],
            true,
        );
        $uri =
            self::API_PATH_PREFIX_V2
            . '/offerings/'
            . $filter[0]
            . '/documents/'
            . $sample[0];
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::DOCUMENT_STANDARD;
        $actualFields = array_keys($apiResponse);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
        $this->assertEquals($sample[0], $apiResponse['id']);
        $this->assertMatchesRegularExpression('~cloudfront.net~', $apiResponse['url']);
    }

    public function testGetOfferingDocumentsQueryPagination(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $filter = $this->searchFixtures(
            \App\Entity\Offering::class,
            ['status' => 'published'],
            true,
        );
        $uri = self::API_PATH_PREFIX_V2 . '/offerings/' . $filter[0] . '/documents';
        $parameters = [
            'page' => 1,
            'limit' => 1,
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = $this->searchFixtures(
            \App\Entity\OfferingDocuments::class,
            [
                'offering' => $filter[0],
            ],
            true,
        );
        sort($expectedFields);
        $expectedFields = array_slice(
            $expectedFields,
            $parameters['limit'] * ($parameters['page'] - 1),
            $parameters['limit'],
        );
        $actualFields = array_map(function ($x) {
            return $x['id'];
        }, $apiResponse['data']);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
        $this->assertEquals(count($expectedFields), count($actualFields));
    }

    // public function testGetOfferingInvestments(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $filter = $this->searchFixtures(
    //         \App\Entity\Offering::class,
    //         ["status" => "published"],
    //         true
    //     );
    //     $sample = $this->searchFixtures(
    //         \App\Entity\Investment::class,
    //         ["offering" => $filter[0]]
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings/' . $filter[0] . '/investments';
    //     $this->client->request('GET', $uri);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    //     $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
    //     $expectedFields = ApiResponseFields::INVESTMENT_STANDARD;
    //     $actualFields = array_keys($apiResponse['data'][0]);
    //     $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    // }
    // public function testGetOfferingSingleInvestment(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $filter = $this->searchFixtures(
    //         \App\Entity\Offering::class,
    //         ["status" => "published"],
    //         true
    //     );
    //     $sample = $this->searchFixtures(
    //         \App\Entity\Investment::class,
    //         ["offering" => $filter[0]]
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings/' . $filter[0] . '/investments' . $sample[0];
    //     $this->client->request('GET', $uri);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    //     $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
    //     $expectedFields = ApiResponseFields::INVESTMENT_STANDARD;
    //     $actualFields = array_keys($apiResponse['data'][0]);
    //     $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    // }
}
