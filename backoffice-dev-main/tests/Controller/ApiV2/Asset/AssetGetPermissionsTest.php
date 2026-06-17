<?php

namespace App\Tests\Controller\ApiV2\Asset;

use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV2\ApiResponseFields;
use Symfony\Component\HttpFoundation\Response;

class AssetGetPermissionsTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<string, array{0: string, 1: 200|403}, mixed, void>
     */
    public static function nonAdminAssetStatusProvider(): \Generator
    {
        yield 'draft asset' => ['draft', Response::HTTP_FORBIDDEN];
        yield 'submitted asset' => ['submitted', Response::HTTP_FORBIDDEN];
        yield 'approved asset' => ['approved', Response::HTTP_FORBIDDEN];
        yield 'published asset' => ['published', Response::HTTP_OK];
        yield 'restricted asset' => ['restricted', Response::HTTP_FORBIDDEN];
        yield 'archived asset' => ['archived', Response::HTTP_FORBIDDEN];
        yield 'cancelled asset' => ['cancelled', Response::HTTP_FORBIDDEN];
    }

    /**
     * @psalm-return \Generator<string, array{0: string, 1: array{0: 'asset:read', 1?: 'offering:read'}}, mixed, void>
     */
    public static function assetEndpointScopeProvider(): \Generator
    {
        yield 'asset collection' => ['/assets', ['asset:read']];
        yield 'asset single' => ['/assets/1', ['asset:read']];
        yield 'asset offerings' => [
            '/assets/1/offerings',
            ['asset:read', 'offering:read'],
        ];
        yield 'asset documents' => ['/assets/1/documents', ['asset:read']];
        yield 'asset document single' => ['/assets/1/documents/1', ['asset:read']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('assetEndpointScopeProvider')]
    public function testGetAssetEndpointsAsAdminMissingScope(
        $endpoint,
        $requiredScopes,
    ): void {
        $scopes = array_diff($this->permittedScopes, $requiredScopes);
        $this->loginApiClientUser(self::USER_ADMIN, $scopes);
        $uri = self::API_PATH_PREFIX_V2 . $endpoint;
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGetAssetsAsPublic(): void
    {
        $this->loginApiClientPublic();
        $uri = self::API_PATH_PREFIX_V2 . '/assets';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testGetAssetsAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V2 . '/assets';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('nonAdminAssetStatusProvider')]
    public function testGetSingleAssetAsPublic($status, $expectedStatusCode): void
    {
        $this->loginApiClientPublic();
        $sample = $this->searchFixtures(\App\Entity\Asset::class, [
            'status' => $status,
        ]);
        $uri = self::API_PATH_PREFIX_V2 . '/assets/' . $sample[0]->getId();
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame($expectedStatusCode);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('nonAdminAssetStatusProvider')]
    public function testGetSingleAssetAsRegUser($status, $expectedStatusCode): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(\App\Entity\Asset::class, [
            'status' => $status,
        ]);
        $uri = self::API_PATH_PREFIX_V2 . '/assets/' . $sample[0]->getId();
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame($expectedStatusCode);
    }

    public function testGetAssetsViewAdminAsPublic(): void
    {
        $this->loginApiClientPublic();
        $uri = self::API_PATH_PREFIX_V2 . '/assets';
        $parameters = [
            'view' => 'admin',
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::ASSET_STANDARD;
        $actualFields = array_keys($apiResponse['data'][0]);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    }

    public function testGetAssetsViewAdminAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V2 . '/assets';
        $parameters = [
            'view' => 'admin',
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::ASSET_STANDARD;
        $actualFields = array_keys($apiResponse['data'][0]);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    }

    public function testGetSingleAssetViewAdminAsPublic(): void
    {
        $this->loginApiClientPublic();
        $sample = $this->searchFixtures(\App\Entity\Asset::class, [
            'status' => 'published',
        ]);
        $uri = self::API_PATH_PREFIX_V2 . '/assets/' . $sample[0]->getId();
        $parameters = [
            'view' => 'admin',
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::ASSET_STANDARD;
        $actualFields = array_keys($apiResponse);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    }

    public function testGetSingleAssetViewAdminAsRegUser(): void
    {
        $this->loginApiClientPublic();
        $sample = $this->searchFixtures(\App\Entity\Asset::class, [
            'status' => 'published',
        ]);
        $uri = self::API_PATH_PREFIX_V2 . '/assets/' . $sample[0]->getId();
        $parameters = [
            'view' => 'admin',
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::ASSET_STANDARD;
        $actualFields = array_keys($apiResponse);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    }

    public function testGetAssetOfferingsAsPublic(): void
    {
        $this->loginApiClientPublic();
        $filter = $this->searchFixtures(
            \App\Entity\Asset::class,
            ['status' => 'published'],
            true,
        );
        $sample = $this->searchFixtures(\App\Entity\Offering::class, [
            'asset' => $filter[0],
            'status' => 'published',
        ]);
        $uri = self::API_PATH_PREFIX_V2 . '/assets/' . $filter[0] . '/offerings';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(count($sample), count($apiResponse['data']));
    }

    public function testGetAssetOfferingsAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $filter = $this->searchFixtures(
            \App\Entity\Asset::class,
            ['status' => 'published'],
            true,
        );
        $sample = $this->searchFixtures(\App\Entity\Offering::class, [
            'asset' => $filter[0],
            'status' => 'published',
        ]);
        $uri = self::API_PATH_PREFIX_V2 . '/assets/' . $filter[0] . '/offerings';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(count($sample), count($apiResponse['data']));
    }

    public function testGetAssetDocumentsAsPublic(): void
    {
        $this->loginApiClientPublic();
        $sample = $this->searchFixtures(\App\Entity\Asset::class, [
            'status' => 'published',
        ]);
        $uri =
            self::API_PATH_PREFIX_V2 . '/assets/' . $sample[0]->getId() . '/documents';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testGetAssetDocumentsAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(\App\Entity\Asset::class, [
            'status' => 'published',
        ]);
        $uri =
            self::API_PATH_PREFIX_V2 . '/assets/' . $sample[0]->getId() . '/documents';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testGetAssetSingleDocumentAsPublic(): void
    {
        $this->loginApiClientPublic();
        $filter = $this->searchFixtures(
            \App\Entity\Asset::class,
            ['status' => 'published'],
            true,
        );
        $sample = $this->searchFixtures(\App\Entity\AssetDocuments::class, [
            'asset' => $filter,
        ]);
        $uri =
            self::API_PATH_PREFIX_V2
            . '/assets/'
            . $sample[0]->getAsset()->getId()
            . '/documents/'
            . $sample[0]->getId();
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testGetAssetSingleDocumentAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $filter = $this->searchFixtures(
            \App\Entity\Asset::class,
            ['status' => 'published'],
            true,
        );
        $sample = $this->searchFixtures(\App\Entity\AssetDocuments::class, [
            'asset' => $filter,
        ]);
        $uri =
            self::API_PATH_PREFIX_V2
            . '/assets/'
            . $sample[0]->getAsset()->getId()
            . '/documents/'
            . $sample[0]->getId();
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    // All the commented out tests below require the bug to be fixed first

    public function testGetAssetNotPublishedDocumentsAsPublic(): void
    {
        $this->loginApiClientPublic();
        $sample = $this->searchFixtures(\App\Entity\Asset::class, [
            'status' => 'draft',
        ]);
        $uri =
            self::API_PATH_PREFIX_V2 . '/assets/' . $sample[0]->getId() . '/documents';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGetAssetNotPublishedDocumentsAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(\App\Entity\Asset::class, [
            'status' => 'draft',
        ]);
        $uri =
            self::API_PATH_PREFIX_V2 . '/assets/' . $sample[0]->getId() . '/documents';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGetAssetNotPublishedSingleDocumentsAsPublic(): void
    {
        $this->loginApiClientPublic();
        $sample = $this->searchFixtures(\App\Entity\Asset::class, [
            'status' => 'draft',
        ]);
        $uri =
            self::API_PATH_PREFIX_V2
            . '/assets/'
            . $sample[0]->getId()
            . '/documents/1';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGetAssetNotPublishedSingleDocumentsAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(\App\Entity\Asset::class, [
            'status' => 'draft',
        ]);
        $uri =
            self::API_PATH_PREFIX_V2
            . '/assets/'
            . $sample[0]->getId()
            . '/documents/1';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGetAssetNotPublishedOfferingsAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(\App\Entity\Asset::class, [
            'status' => 'draft',
        ]);
        $uri =
            self::API_PATH_PREFIX_V2 . '/assets/' . $sample[0]->getId() . '/offerings';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
