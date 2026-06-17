<?php

namespace App\Tests\Controller\ApiV2\Asset;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AssetUpdateErrorTest extends FixtureWebTestCase
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
    public function testUpdateAssetMediaTypeInvalid($mediaType): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/assets/1';
        $headers = [
            'CONTENT_TYPE' => $mediaType,
        ];
        $this->client->request('PATCH', $uri, [], [], $headers);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
    }

    // #[\PHPUnit\Framework\Attributes\DataProvider('supportedMediaTypeProvider')]
    // public function testUpdateAssetFormatsValidEmptyRequest($mediaType): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/assets/1';
    //     $headers = [
    //         'CONTENT_TYPE' => $mediaType
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    // }
    // /**
    //  * @dataProvider unsupportedMediaTypeProvider
    //  */
    // public function testUpdateAssetDocumentMediaTypeInvalid($mediaType): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $sample = $this->searchFixtures(\App\Entity\AssetDocuments::class, []);
    //     $uri = self::API_PATH_PREFIX_V2 . '/assets/1/documents/1';
    //     $headers = [
    //         'CONTENT_TYPE' => $mediaType
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
    // }
    // /**
    //  * @dataProvider supportedMediaTypeProvider
    //  */
    // public function testUpdateAssetDocumentFormatsInvalid($mediaType): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/assets/1/documents/1';
    //     $headers = [
    //         'CONTENT_TYPE' => $mediaType
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
    // public function testUpdateSingleAssetNotExists(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/assets/-1';
    //     $content = json_encode([]);
    //     $headers = [
    //         "CONTENT_TYPE" => "application/json"
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    // }
    // public function testUpdateAssetSingleDocumentNotExists(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/assets/1/documents/-1';
    //     $content = json_encode([]);
    //     $headers = [
    //         "CONTENT_TYPE" => "application/json"
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    // }
    // public function testUpdateAssetFieldsImmutable(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/assets/1';
    //     $content = json_encode([
    //         "name" => "asset name updated",
    //         "id" => 1
    //     ]);
    //     $headers = [
    //         "CONTENT_TYPE" => "application/json"
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
    // public function testUpdateAssetFieldsUnknown(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/assets/1';
    //     $content = json_encode([
    //         "name" => "asset name updated",
    //         "biscuit" => "test unknown field"
    //     ]);
    //     $headers = [
    //         "CONTENT_TYPE" => "application/json"
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
    // public function testUpdateAssetFieldsInvalidType(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/assets/1';
    //     $content = json_encode([
    //         "name" => "asset name updated",
    //         "numberOfShares" => "test alphabet in numerical field"
    //     ]);
    //     $headers = [
    //         "CONTENT_TYPE" => "application/json"
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
}
