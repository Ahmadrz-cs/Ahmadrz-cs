<?php

namespace App\Tests\Controller\ApiV2\Asset;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AssetGetErrorTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<string, array{0: string}, mixed, void>
     */
    public static function assetEndpointNotFoundProvider(): \Generator
    {
        yield 'asset misspelt' => ['/asset'];
        yield 'asset single' => ['/assets/-1'];
        // yield "asset offerings" => ["/assets/-1/offerings"];
        // yield "asset documents" => ["/assets/-1/documents"];
        yield 'asset exist document single' => ['/assets/1/documents/-1'];
        yield 'asset not exist document single' => ['/assets/-1/documents/-1'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('assetEndpointNotFoundProvider')]
    public function testGetAssetEndpointsNotExists($endpoint): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . $endpoint;
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // public function testGetAssetsQueryViewInvalid(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/assets';
    //     $parameters = [
    //         'view' => 'invalid'
    //     ];
    //     $this->client->request('GET', $uri, $parameters);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
    // public function testGetAssetsQueryFiltersInvalid(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/assets';
    //     $parameters = [
    //         'id' => 'a,b,c',
    //         'type' => 123
    //     ];
    //     $this->client->request('GET', $uri, $parameters);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
    // public function testGetAssetsQueryPaginationInvalid(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/assets';
    //     $parameters = $parameters = [
    //         'page' => 'one',
    //         'limit' => 'fifty'
    //     ];;
    //     $this->client->request('GET', $uri, $parameters);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
    // public function testGetSingleAssetQueryViewInvalid(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/assets/1';
    //     $parameters = [
    //         'view' => 'invalid'
    //     ];
    //     $this->client->request('GET', $uri, $parameters);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
}
