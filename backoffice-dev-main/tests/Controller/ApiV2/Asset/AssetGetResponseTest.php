<?php

namespace App\Tests\Controller\ApiV2\Asset;

use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV2\ApiResponseFields;

class AssetGetResponseTest extends FixtureWebTestCase
{
    public function testGetAssets(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/assets';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::ASSET_STANDARD;
        $actualFields = array_keys($apiResponse['data'][0]);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    }

    public function testGetAssetsViewMin(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/assets';
        $parameters = [
            'view' => 'minimum',
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseIsSuccessful();

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::ASSET_MIN;
        $actualFields = array_keys($apiResponse['data'][0]);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    }

    // Uncomment when view admin implemented
    // public function testGetAssetsViewAdmin()
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/assets';
    //     $parameters = [
    //         'view' => 'admin'
    //     ];
    //     $this->client->request('GET', $uri, $parameters);
    //     $this->assertResponseIsSuccessful();

    //     $apiResponse = json_decode($this->client->getResponse()->getContent(), true);

    //     // 1.primary field checks
    //     $expectedFields = ApiResponseFields::ASSET_ADMIN;
    //     $actualFields = array_keys($apiResponse['data'][0]);
    //     $this->assertEqualsCanonicalizing($expectedFields, $actualFields);

    //     // 2.sub field checks
    //     // missing fixtures for 2.b.contactPoint and 2.c.addFields to test

    //     // 2a member checks
    //     $this->assertArrayHasKey('id', $apiResponse['data'][0]['members'][0]);

    //     // 2d doc checks
    //     $expectedFields = ApiResponseFields::DOCUMENT_STANDARD;
    //     $actualFields = array_keys($apiResponse['data'][0]['documents'][0]);
    //     $this->assertEqualsCanonicalizing($expectedFields, $actualFields);

    //     // 2e offering checks
    //     $expectedFields = ApiResponseFields::OFFERING_STANDARD;
    //     $actualFields = array_keys($apiResponse['data'][0]['offerings'][0]);
    //     $this->assertEqualsCanonicalizing($expectedFields, $actualFields);

    //     // 2f address checks
    //     $expectedFields = ApiResponseFields::ADDRESS_ASSET;
    //     $actualFields = array_keys($apiResponse['data'][0]['addresses'][0]);
    //     $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    // }

    public function testGetAssetsQueryFilters(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/assets';
        $parameters = [
            'id' => implode(',', range(1, 9)),
            'type' => 'Commercial',
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseIsSuccessful();

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = $this->searchFixtures(
            \App\Entity\Asset::class,
            ['id' => range(1, 9), 'type' => ['Commercial']],
            true,
        );
        $actualFields = array_map(function ($x) {
            return $x['id'];
        }, $apiResponse['data']);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
        $this->assertEquals(count($expectedFields), count($actualFields));
    }

    public function testGetAssetsQueryPagination(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/assets';
        $parameters = [
            'page' => 2,
            'limit' => 2,
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseIsSuccessful();

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = $this->searchFixtures(\App\Entity\Asset::class, [], true);
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

    public function testGetAssetsDocumentsQueryPagination(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $filter = $this->searchFixtures(
            \App\Entity\Asset::class,
            ['status' => 'published'],
            true,
        );
        $uri = self::API_PATH_PREFIX_V2 . '/assets/' . $filter[0] . '/documents';
        $parameters = [
            'page' => 1,
            'limit' => 2,
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseIsSuccessful();

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = $this->searchFixtures(
            \App\Entity\AssetDocuments::class,
            [
                'asset' => $filter[0],
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

    public function testGetAssetOfferingsQueryPagination(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $filter = $this->searchFixtures(
            \App\Entity\Asset::class,
            ['status' => 'published'],
            true,
        );
        $uri = self::API_PATH_PREFIX_V2 . '/assets/' . $filter[0] . '/offerings';
        $parameters = [
            'page' => 1,
            'limit' => 2,
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseIsSuccessful();

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = $this->searchFixtures(
            \App\Entity\Offering::class,
            ['asset' => $filter[0]],
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

    public function testGetSingleAsset(): void
    {
        // tests regular view param by default

        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(\App\Entity\Asset::class, [], true);
        $uri = self::API_PATH_PREFIX_V2 . '/assets/' . $sample[0];
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::ASSET_STANDARD;
        $actualFields = array_keys($apiResponse);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
        $this->assertEquals(1, $apiResponse['id']);
    }

    public function testGetSingleAssetViewMinimum(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/assets/1';
        $parameters = [
            'view' => 'minimum',
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseIsSuccessful();

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::ASSET_MIN;
        $actualFields = array_keys($apiResponse);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    }

    // Uncomment when admin view implemented
    // public function testGetSingleAssetViewAdmin(): void
    // {
    //     // tests admin view param
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/assets/1';
    //     $parameters = [
    //         'view' => 'admin'
    //     ];
    //     $this->client->request('GET', $uri, $parameters);
    //     $this->assertResponseIsSuccessful();

    //     // 1.primary field checks
    //     $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
    //     $expectedFields = ApiResponseFields::ASSET_ADMIN;
    //     $actualFields = array_keys($apiResponse);
    //     $this->assertEqualsCanonicalizing($expectedFields, $actualFields);

    //     // 2.sub field checks
    //     // missing fixtures for 2.b.contactPoint and 2.c.addFields to test

    //     // 2.a.member checks
    //     $this->assertArrayHasKey('id', $apiResponse['members'][0]);

    //     // 2.d.doc checks
    //     $expectedFields = ApiResponseFields::DOCUMENT_STANDARD;
    //     $actualFields = array_keys($apiResponse['documents'][0]);
    //     $this->assertEqualsCanonicalizing($expectedFields, $actualFields);

    //     // 2.e.offering checks
    //     $expectedFields = ApiResponseFields::OFFERING_STANDARD;
    //     $actualFields = array_keys($apiResponse['offerings'][0]);
    //     $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    // }

    public function testGetAssetOfferings(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $filter = $this->searchFixtures(
            \App\Entity\Asset::class,
            ['status' => 'published'],
            true,
        );
        $sample = $this->searchFixtures(\App\Entity\Offering::class, [
            'asset' => $filter[0],
        ]);
        $uri = self::API_PATH_PREFIX_V2 . '/assets/' . $filter[0] . '/offerings';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::OFFERING_STANDARD;
        $actualFields = array_keys($apiResponse['data'][0]);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
        $this->assertEquals(count($sample), count($apiResponse['data']));
    }

    public function testGetAssetDocuments(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $filter = $this->searchFixtures(
            \App\Entity\Asset::class,
            ['status' => 'published'],
            true,
        );
        $sample = $this->searchFixtures(\App\Entity\AssetDocuments::class, [
            'asset' => $filter[0],
        ]);
        $uri = self::API_PATH_PREFIX_V2 . '/assets/' . $filter[0] . '/documents';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::DOCUMENT_STANDARD;
        $actualFields = array_keys($apiResponse['data'][0]);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
        $this->assertEquals(count($sample), count($apiResponse['data']));
        $this->assertMatchesRegularExpression(
            '~cloudfront.net~',
            $apiResponse['data'][0]['url'],
        );
    }

    public function testGetAssetSingleDocument(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $filter = $this->searchFixtures(
            \App\Entity\Asset::class,
            ['status' => 'published'],
            true,
        );
        $sample = $this->searchFixtures(
            \App\Entity\AssetDocuments::class,
            ['asset' => $filter[0]],
            true,
        );
        $uri =
            self::API_PATH_PREFIX_V2
            . '/assets/'
            . $filter[0]
            . '/documents/'
            . $sample[0];
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::DOCUMENT_STANDARD;
        $actualFields = array_keys($apiResponse);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
        $this->assertEquals($sample[0], $apiResponse['id']);
        $this->assertMatchesRegularExpression('~cloudfront.net~', $apiResponse['url']);
    }
}
