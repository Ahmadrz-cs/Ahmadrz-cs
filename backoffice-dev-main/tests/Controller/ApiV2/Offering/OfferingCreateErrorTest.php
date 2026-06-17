<?php

namespace App\Tests\Controller\ApiV2\Offering;

use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV2\ApiBase64Files;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Response;

class OfferingCreateErrorTest extends FixtureWebTestCase
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

    /**
     * @psalm-return \Generator<string, array{0: string}, mixed, void>
     */
    public static function nonPublishedAssetStatusProvider(): \Generator
    {
        yield 'draft asset' => ['draft'];
        yield 'submitted asset' => ['submitted'];
        yield 'approved asset' => ['approved'];
        yield 'restricted asset' => ['restricted'];
        yield 'archived asset' => ['archived'];
        yield 'cancelled asset' => ['cancelled'];
    }

    public function testCreateOfferingAssetNotExists(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/offerings';
        $content = json_encode([
            'name' => 'testCreateOffering',
            'assetId' => -1,
            'numberOfShares' => '1000',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    // public function testCreateOfferingNotExistsDocument(): void
    // {
    //     $this->loginApiClientPublic();
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings/-1/documents';
    //     $content = json_encode([
    //         'type' => 'image/jpg',
    //         'fileName' => 'jpgTest.jpg',
    //         'documentContent' => ApiBase64Files::TEST_JPG,
    //         'tag' => 'test'
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    // }

    #[\PHPUnit\Framework\Attributes\DataProvider('unsupportedMediaTypeProvider')]
    public function testCreateOfferingMediaTypeInvalid($mediaType): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/offerings';
        $headers = [
            'CONTENT_TYPE' => $mediaType,
        ];
        $this->client->request('POST', $uri, [], [], $headers);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
    }

    // #[\PHPUnit\Framework\Attributes\DataProvider('supportedMediaTypeProvider')]
    // public function testCreateOfferingFormatsValidEmptyRequest($mediaType): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings';
    //     $headers = [
    //         'CONTENT_TYPE' => $mediaType
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    // }

    // /**
    //  * @dataProvider unsupportedMediaTypeProvider
    //  */
    // public function testCreateOfferingDocumentMediaTypeInvalid($mediaType): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings/1/documents';
    //     $headers = [
    //         'CONTENT_TYPE' => $mediaType
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
    // }
    // /**
    //  * @dataProvider supportedMediaTypeProvider
    //  */
    // public function testCreateOfferingDocumentFormatsInvalid($mediaType): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings/1/documents';
    //     $headers = [
    //         'CONTENT_TYPE' => $mediaType
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
    // currently returns 500(HTTP_INTERNAL_SERVER_ERROR) open to change
    // public function testCreateOfferingFieldsAllMissing(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings';
    //     $content = json_encode([]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
    // currently returns 500(HTTP_INTERNAL_SERVER_ERROR) open to change
    // public function testCreateOfferingFieldsRequiredMissing(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings';
    //     $content = json_encode([
    //         'netTotalReturn' => 'required fields missing test'
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
    #[\PHPUnit\Framework\Attributes\DataProvider('nonPublishedAssetStatusProvider')]
    public function testCreateOfferingFieldsAssetNotPublished($status): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(\App\Entity\Asset::class, [
            'status' => $status,
        ]);
        $uri = self::API_PATH_PREFIX_V2 . '/offerings';
        $content = json_encode([
            'name' => 'testCreateOffering',
            'assetId' => $sample[0]->getId(),
            'numberOfShares' => '1000',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
