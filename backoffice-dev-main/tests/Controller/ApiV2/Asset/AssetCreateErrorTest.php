<?php

namespace App\Tests\Controller\ApiV2\Asset;

use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV2\ApiResponseFields;
use Symfony\Component\HttpFoundation\Response;

class AssetCreateErrorTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<string, array{0: string}, mixed, void>
     */
    public static function unsupportedMediaTypeProvider(): \Generator
    {
        yield 'form-data' => ['multipart/form-data'];
        yield 'text' => ['text/plain'];
        yield 'javascript' => ['application/javascript'];
        yield 'html' => ['text/html'];
    }

    /**
     * @psalm-return \Generator<'json'|'xml', array{0: 'application/json'|'application/xml'}, mixed, void>
     */
    public static function supportedMediaTypeProvider(): \Generator
    {
        yield 'json' => ['application/json'];
        yield 'xml' => ['application/xml'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('unsupportedMediaTypeProvider')]
    public function testCreateAssetMediaTypeInvalid($mediaType): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/assets';
        $headers = [
            'CONTENT_TYPE' => $mediaType,
        ];
        $this->client->request('POST', $uri, [], [], $headers);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
    }

    // #[\PHPUnit\Framework\Attributes\DataProvider('supportedMediaTypeProvider')]
    // public function testCreateAssetFormatsValidEmptyRequest($mediaType): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/assets';
    //     $headers = [
    //         'CONTENT_TYPE' => $mediaType
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    // }
    // /**
    //  * @dataProvider unsupportedMediaTypeProvider
    //  */
    // public function testCreateAssetDocumentMediaTypeInvalid($mediaType): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/assets/1/documents';
    //     $headers = [
    //         'CONTENT_TYPE' => $mediaType
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
    // }
    // /**
    //  * @dataProvider supportedMediaTypeProvider
    //  */
    // public function testCreateAssetDocumentFormatsInvalid($mediaType): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/assets/1/documents';
    //     $headers = [
    //         'CONTENT_TYPE' => $mediaType
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
    // public function testCreateAssetFieldsInvalid(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/assets';
    //     $content = json_encode([
    //         'name' => 'testCreateAsset',
    //         'numberOfShares' => '1000000',
    //         'pricePerShare' => '1.25',
    //         'erroneousField' => 'test'
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    //     $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
    //     $expectedFields = ApiResponseFields::ASSET_STANDARD;
    //     $actualFields = array_keys($apiResponse);
    //     $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    // }
    // Uncomment once endpoint fully implemented
    // should be getting 400 bad request
    // public function testCreateAssetFieldsRequiredMissing(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/assets';
    //     $content = json_encode([
    //         'numberOfShares' => '1000000',
    //         'pricePerShare' => '1.25',
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
    // Uncomment once endpoint fully implemented
    // public function testCreateAssetDocumentFieldsRequiredMissing(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/assets/1/documents';
    //     $content = json_encode([
    //         'type' => 'image/jpeg',
    //         'tag' => 'test'
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
    // public function testCreateAssetNotExistsDocument(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/assets/-1/documents';
    //     $content = json_encode([
    //         'type' => 'image/jpg',
    //         'fileName' => 'tagTest.jpg',
    //         'documentContent' => "",
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    // }
}
