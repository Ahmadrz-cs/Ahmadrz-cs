<?php

namespace App\Tests\Controller\ApiV2\Asset;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AssetCreatePermissionsTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<'asset creation'|'asset document creation', array{0: '/assets'|'/assets/1/documents', 1: array{0: 'asset:write'}}, mixed, void>
     */
    public static function assetEndpointScopeProvider(): \Generator
    {
        yield 'asset creation' => ['/assets', ['asset:write']];
        yield 'asset document creation' => ['/assets/1/documents', ['asset:write']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('assetEndpointScopeProvider')]
    public function testCreateAssetEndpointsAsAdminMissingScope(
        $endpoint,
        $requiredScopes,
    ): void {
        $scopes = array_diff($this->permittedScopes, $requiredScopes);
        $this->loginApiClientUser(self::USER_ADMIN, $scopes);
        $uri = self::API_PATH_PREFIX_V2 . $endpoint;
        $content = json_encode([]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCreateAssetAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V2 . '/assets';
        $content = json_encode([
            'name' => 'testCreateAsset',
            'numberOfShares' => '1000000',
            'pricePerShare' => '1.25',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCreateAssetAsPublic(): void
    {
        $this->loginApiClientPublic();
        $uri = self::API_PATH_PREFIX_V2 . '/assets';
        $content = json_encode([
            'name' => 'testCreateAsset',
            'numberOfShares' => '1000000',
            'pricePerShare' => '1.25',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCreateAssetDocumentAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V2 . '/assets/1/documents';
        $content = json_encode([
            'type' => 'image/jpeg',
            'fileName' => 'testCreateAssetDocumentAsRegUser.jpeg',
            'documentContent' => 'test',
            'tag' => 'test',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCreateAssetDocumentAsPublic(): void
    {
        $this->loginApiClientPublic();
        $uri = self::API_PATH_PREFIX_V2 . '/assets/1/documents';
        $content = json_encode([
            'type' => 'image/jpeg',
            'fileName' => 'testCreateAssetDocumentAsRegUser.jpeg',
            'documentContent' => 'test',
            'tag' => 'test',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
