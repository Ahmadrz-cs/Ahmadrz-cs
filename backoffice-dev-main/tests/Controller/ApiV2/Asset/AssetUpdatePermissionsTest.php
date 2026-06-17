<?php

namespace App\Tests\Controller\ApiV2\Asset;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AssetUpdatePermissionsTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<'asset document update'|'asset update', array{0: '/assets/1'|'/assets/1/documents/1', 1: array{0: 'asset:write'}}, mixed, void>
     */
    public static function assetEndpointScopeProvider(): \Generator
    {
        yield 'asset update' => ['/assets/1', ['asset:write']];
        yield 'asset document update' => ['/assets/1/documents/1', ['asset:write']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('assetEndpointScopeProvider')]
    public function testUpdateAssetEndpointsAsAdminMissingScope(
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

        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUpdateAssetAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V2 . '/assets/1';
        $content = json_encode([]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUpdateAssetAsPublic(): void
    {
        $this->loginApiClientPublic();
        $uri = self::API_PATH_PREFIX_V2 . '/assets/1';
        $content = json_encode([]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUpdateAssetDocumentAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V2 . '/assets/1/documents/1';
        $content = json_encode([]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUpdateAssetDocumentAsPublic(): void
    {
        $this->loginApiClientPublic();
        $uri = self::API_PATH_PREFIX_V2 . '/assets/1/documents/1';
        $content = json_encode([]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
