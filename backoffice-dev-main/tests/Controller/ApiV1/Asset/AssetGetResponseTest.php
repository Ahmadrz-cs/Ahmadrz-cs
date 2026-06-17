<?php

namespace App\Tests\Controller\ApiV1\Asset;

use App\Entity\Asset;
use App\Entity\AssetDocuments;
use App\Entity\Lifecycle\AssetLifecycle;
use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV1\ApiV1ResponseFields;

class AssetGetResponseTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetSingleAsset(): void
    {
        $this->loginApiClientUser(self::USER_SUPER_ADMIN);
        $sample = $this->searchFixtures(
            Asset::class,
            [
                'status' => AssetLifecycle::STATE_PUBLISHED,
            ],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/assets/$sample";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $object = $apiResponse['data']['organization'];
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::ASSET_STANDARD,
            array_keys($object),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    #[\PHPUnit\Framework\Attributes\Group('single-query')]
    public function testGetSingleAssetQueryParameters(): void
    {
        $this->loginApiClientUser(self::USER_SUPER_ADMIN);
        $sample = $this->searchFixtures(
            Asset::class,
            [
                'status' => AssetLifecycle::STATE_PUBLISHED,
            ],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/assets/$sample";
        $parameters = ['light' => 'true'];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseIsSuccessful();

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $object = $apiResponse['data']['organization'];
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::ASSET_LIGHT,
            array_keys($object),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('response')]
    #[\PHPUnit\Framework\Attributes\Group('relation')]
    public function testGetAssetOfferings(): void
    {
        $this->loginApiClientUser(self::USER_SUPER_ADMIN);
        $sample = $this->searchFixtures(
            Asset::class,
            [
                'companyNumber' => 'SPVTMultiState',
            ],
            true,
        )[0];

        $uri = self::API_PATH_PREFIX_V1 . "/assets/$sample/offerings";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $objects = $apiResponse['data']['list'];
        $this->assertGreaterThanOrEqual(2, count($objects));
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::OFFERING_STANDARD,
            array_keys($objects[0]),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetAssetDocuments(): void
    {
        $this->loginApiClientUser(self::USER_SUPER_ADMIN);
        $sample = $this->searchFixtures(AssetDocuments::class, [])[0];
        $uri =
            self::API_PATH_PREFIX_V1
            . '/assets/'
            . $sample->getAsset()->getId()
            . '/documents';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $objects = $apiResponse['data']['list'];
        $this->assertGreaterThanOrEqual(1, count($objects));
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::DOCUMENT_ASSET,
            array_keys($objects[0]),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetAssets(): void
    {
        $this->loginApiClientUser(self::USER_SUPER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/assets';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $objects = $apiResponse['data']['list'];
        $this->assertGreaterThanOrEqual(1, count($objects));
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::ASSET_STANDARD,
            array_keys($objects[0]),
        );
        // check default metadata
        $this->assertGreaterThan(0, $apiResponse['data']['count']);
        $this->assertEquals(0, $apiResponse['data']['offset']);
        $this->assertEquals(10, $apiResponse['data']['limit']);
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('response')]
    #[\PHPUnit\Framework\Attributes\Group('collection-query')]
    public function testGetAssetsQueryParameters(): void
    {
        $this->loginApiClientUser(self::USER_SUPER_ADMIN);
        $sample = $this->searchFixtures(
            Asset::class,
            [
                'status' => AssetLifecycle::STATE_PUBLISHED,
            ],
            true,
        );
        $uri = self::API_PATH_PREFIX_V1 . '/assets';
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
    #[\PHPUnit\Framework\Attributes\Group('response')]
    #[\PHPUnit\Framework\Attributes\Group('collection-query')]
    public function testGetAssetsFilters(): void
    {
        /**
         * Check combinatorial filtering
         */
        $this->loginApiClientUser(self::USER_SUPER_ADMIN);
        $sample1 = $this->searchFixtures(
            Asset::class,
            [
                'status' => AssetLifecycle::STATE_PUBLISHED,
                'type' => 'Residential',
            ],
            true,
        );
        $sample2 = $this->searchFixtures(
            Asset::class,
            [
                'status' => AssetLifecycle::STATE_RESTRICTED,
                'type' => 'Residential',
            ],
            true,
        );
        $uri = self::API_PATH_PREFIX_V1 . '/assets';
        $parameters = [
            'sort' => implode(',', ['-id']),
            'status' => implode(',', [
                AssetLifecycle::STATE_RESTRICTED_INT,
                AssetLifecycle::STATE_PUBLISHED_INT,
            ]),
            'type' => implode(',', ['residential']),
            'id' => implode(',', array_merge($sample1, $sample2)),
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseIsSuccessful();

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expected = array_unique(array_merge($sample1, $sample2));
        rsort($expected);
        $expected = array_slice($expected, 0, 10); // default offset and limit of 0 and 10

        // check query parameters worked
        $actual = array_map(
            fn($item): int => $item['id'],
            $apiResponse['data']['list'],
        );
        $this->assertEqualsCanonicalizing($expected, $actual);
    }
}
