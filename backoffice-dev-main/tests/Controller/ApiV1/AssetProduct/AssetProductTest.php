<?php

namespace App\Tests\Controller\ApiV1\SelfPortfolio;

use App\Entity\Asset;
use App\Entity\Enum\AssetStatus;
use App\Entity\Enum\Visibility;
use App\Test\FixtureTestCase;
use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV1\ApiV1ResponseFields;
use Symfony\Component\HttpFoundation\Response;

class AssetProductTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetListFeaturedProducts(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR);
        $uri = FixtureWebTestCase::API_PATH_PREFIX_V1 . '/public/featured-products';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::ASSET_PRODUCT,
            array_keys($apiResponse['data'][0]),
        );
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::ASSET_PRODUCT_ADDRESS,
            array_keys($apiResponse['data'][0]['address']),
        );
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::RELATIONAL_DOCUMENT,
            array_keys($apiResponse['data'][0]['documents'][0]),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetListFeaturedProductsAsPublic(): void
    {
        // Check public users CAN use this route
        $uri = self::API_PATH_PREFIX_V1 . '/public/featured-products';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::ASSET_PRODUCT,
            array_keys($apiResponse['data'][0]),
        );
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::ASSET_PRODUCT_ADDRESS,
            array_keys($apiResponse['data'][0]['address']),
        );
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::RELATIONAL_DOCUMENT,
            array_keys($apiResponse['data'][0]['documents'][0]),
        );

        // Special characteristic of featured properties
        foreach ($apiResponse['data'] as $item) {
            $this->assertGreaterThanOrEqual(1, $item['featured']);
            $this->assertEquals(Visibility::Auto->value, $item['visibility']);
            $this->assertContains($item['status'], [
                AssetStatus::Active->value,
                AssetStatus::Closing->value,
            ]);
        }
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetListAssetProducts(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR);
        $uri = FixtureWebTestCase::API_PATH_PREFIX_V1 . '/asset-products';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::ASSET_PRODUCT,
            array_keys($apiResponse['data'][0]),
        );
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::ASSET_PRODUCT_ADDRESS,
            array_keys($apiResponse['data'][0]['address']),
        );
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::RELATIONAL_DOCUMENT,
            array_keys($apiResponse['data'][0]['documents'][0]),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetListAssetProductsAsPublic(): void
    {
        // Check public users cannot use this route
        $uri = FixtureWebTestCase::API_PATH_PREFIX_V1 . '/asset-products';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetAssetProduct(): void
    {
        $assetSample = $this->entityManager
            ->getRepository(Asset::class)
            ->findOneBy(['name' => 'Kolness by the Moor - Okehampton']);
        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR);
        $uri =
            FixtureWebTestCase::API_PATH_PREFIX_V1
            . "/asset-products/{$assetSample->getId()}";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::ASSET_PRODUCT,
            array_keys($apiResponse),
        );
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::ASSET_PRODUCT_ADDRESS,
            array_keys($apiResponse['address']),
        );
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::RELATIONAL_DOCUMENT,
            array_keys($apiResponse['documents'][0]),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetAssetProductDraftAsNonAdmin(): void
    {
        // Check public users cannot use this route
        $assetSample = $this->entityManager
            ->getRepository(Asset::class)
            ->findOneBy(['name' => 'Sagittarius Eystar - Horizon']);
        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR);
        $uri =
            FixtureWebTestCase::API_PATH_PREFIX_V1
            . "/asset-products/{$assetSample->getId()}";
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetAssetProductAsPublic(): void
    {
        // Check public users cannot use this route
        $assetSample = $this->entityManager
            ->getRepository(Asset::class)
            ->findOneBy(['name' => 'Kolness by the Moor - Okehampton']);
        $uri =
            FixtureWebTestCase::API_PATH_PREFIX_V1
            . "/asset-products/{$assetSample->getId()}";
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetListAssetProductSellOrders(): void
    {
        $asset = $this->entityManager
            ->getRepository(Asset::class)
            ->findOneBy(['name' => 'Kolness by the Moor - Okehampton']);

        $this->loginApiClientUser(FixtureTestCase::USER_VIP);
        $uri =
            FixtureWebTestCase::API_PATH_PREFIX_V1
            . "/asset-products/{$asset->getId()}/sell-orders";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::TRADE_ORDER,
            array_keys($apiResponse['data'][0]),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetListAssetProductSellOrdersExcludeOwn(): void
    {
        $asset = $this->entityManager
            ->getRepository(Asset::class)
            ->findOneBy(['name' => 'Kolness by the Moor - Okehampton']);

        // Login as the issuer - by default, excludeOwn filter is disable
        // Kolness by the Moor does not have secondary market listings, just the primary one
        $this->loginApiClientUser(FixtureTestCase::USER_SUPER_ADMIN);
        $uri =
            FixtureWebTestCase::API_PATH_PREFIX_V1
            . "/asset-products/{$asset->getId()}/sell-orders?excludeOwn=1";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(0, $apiResponse['data']);

        // If you query to not exclude, then you should be able to get the initial order again
        $uri =
            FixtureWebTestCase::API_PATH_PREFIX_V1
            . "/asset-products/{$asset->getId()}/sell-orders";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertGreaterThanOrEqual(0, count($apiResponse['data']));
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::TRADE_ORDER,
            array_keys($apiResponse['data'][0]),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetListAssetProductSellOrdersAsPublic(): void
    {
        $asset = $this->entityManager
            ->getRepository(Asset::class)
            ->findOneBy(['name' => 'Kolness by the Moor - Okehampton']);

        // Check public users cannot use this route
        $uri =
            FixtureWebTestCase::API_PATH_PREFIX_V1
            . "/asset-products/{$asset->getId()}/sell-orders";
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}
