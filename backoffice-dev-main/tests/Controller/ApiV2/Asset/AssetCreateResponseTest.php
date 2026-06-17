<?php

namespace App\Tests\Controller\ApiV2\Asset;

use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV2\ApiBase64Files;
use App\Tests\Controller\ApiV2\ApiResponseFields;
use Symfony\Component\HttpFoundation\Response;

class AssetCreateResponseTest extends FixtureWebTestCase
{
    public function testCreateAsset(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
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
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::ASSET_STANDARD;
        $actualFields = array_keys($apiResponse);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    }

    // public function testCreateAssetFieldsAll(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/assets';
    //     $content = json_encode([
    //         'name' => 'testCreateAssetFieldsAll',
    //         'numberOfShares' => '1000000',
    //         'pricePerShare' => '1.25',
    //         'type' => 'Residential',
    //         'status' => 'draft',
    //         'setupFee' => '0',
    //         'adminFee' => '50',
    //         'managementFee' => '10',
    //         'profitShare' => '15',
    //         'visibility' => '0',
    //         'companyNumber' => 'SPVT02187',
    //         'displayName' => 'test-Create-Asset-Fields-All',
    //         'address' => [
    //             'address1' => '12 Clarence Hold',
    //             'address2' => 'Camden',
    //             'address3' => '',
    //             'city' => 'London',
    //             'postCode' => 'DD3 8ES',
    //             'country' => 'United Kingdom',
    //             'region' => 'England',
    //             'latitude' => '53.951868',
    //             'longitude' => '-0.936572'
    //         ],
    //         'mangoPayUserId' => '21597406',
    //         'mangoPayWalletId' => '87743271',
    //         'additionalWallet' => '21958568'
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
    // Uncomment once endpoint is live
    // /**
    //  * @dataProvider docTypeProvider
    //  */
    // public function testCreateAssetDocumentFieldsType($type, $fileName, $documentContent): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/assets/1/documents';
    //     $content = json_encode([
    //         'type' => $type,
    //         'fileName' => $fileName,
    //         'documentContent' => $documentContent,
    //         'tag' => 'test'
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
    // /**
    //  * @dataProvider docTagProvider
    //  */
    // public function testCreateAssetDocumentFieldsTag($tag): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/assets/1/documents';
    //     $content = json_encode([
    //         'type' => 'image/jpg',
    //         'fileName' => 'tagTest.jpg',
    //         'documentContent' => ApiBase64Files::TEST_JPG,
    //         'tag' => $tag
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
    /**
     * @return string[][]
     *
     * @psalm-return array{jpeg: array{type: 'image/jpg', fileName: 'jpgTest.jpg', documentContent: string}, png: array{type: 'image/png', fileName: 'pngTest.jpg', documentContent: 'iVBORw0KGgoAAAANSUhEUgAAABkAAAAcCAYAAACUJBTQAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAAFiUAABYlAUlSJPAAAAClSURBVEhL7dWxDcMgEEDRS0pGYhl3HgTRMgNeBoZwS+0SWpxDuElMuCvc3ZMQCIovGnjVD3jYu8+PkgiLRFgkwjKMHMfRVzQppb76NYxs29YGRYwRrLXjED6Qd3LO1RhTvfd9514Ioa7r2uaRYQTNQpQA+htBoxA1gKYR9B3iBBD50yqlgHMOlFKw7zssywJa63460VJE142oN7jI98siERaJMACcjq79P7ZMhHAAAAAASUVORK5CYII='}, bmp: array{type: 'image/bmp', fileName: 'bmpTest.jpg', documentContent: string}, pdf: array{type: 'application/pdf', fileName: 'pdfTest.jpg', documentContent: 'JVBERi0xLgoxIDAgb2JqPDwvUGFnZXMgMiAwIFI+PmVuZG9iagoyIDAgb2JqPDwvS2lkc1szIDAgUl0vQ291bnQgMT4+ZW5kb2JqCjMgMCBvYmo8PC9QYXJlbnQgMiAwIFI+PmVuZG9iagp0cmFpbGVyIDw8L1Jvb3QgMSAwIFI+Pg=='}, doc: array{type: 'application/doc', fileName: 'docTest.doc', documentContent: string}, xlsx: array{type: 'application/xlsx', fileName: 'xlsxTest.xlsx', documentContent: string}}
     */
    public function docTypeProvider(): array
    {
        return [
            'jpeg' => [
                'type' => 'image/jpg',
                'fileName' => 'jpgTest.jpg',
                'documentContent' => ApiBase64Files::TEST_JPG,
            ],
            'png' => [
                'type' => 'image/png',
                'fileName' => 'pngTest.jpg',
                'documentContent' => ApiBase64Files::TEST_PNG,
            ],
            'bmp' => [
                'type' => 'image/bmp',
                'fileName' => 'bmpTest.jpg',
                'documentContent' => ApiBase64Files::TEST_BMP,
            ],
            'pdf' => [
                'type' => 'application/pdf',
                'fileName' => 'pdfTest.jpg',
                'documentContent' => ApiBase64Files::TEST_PDF,
            ],
            'doc' => [
                'type' => 'application/doc',
                'fileName' => 'docTest.doc',
                'documentContent' => ApiBase64Files::TEST_DOC,
            ],
            'xlsx' => [
                'type' => 'application/xlsx',
                'fileName' => 'xlsxTest.xlsx',
                'documentContent' => ApiBase64Files::TEST_XLSX,
            ],
        ];
    }

    /**
     * @return string[][]
     *
     * @psalm-return array{'read to activate': array{0: 'read_to_activate'}, logo: array{0: 'logo'}}
     */
    public function docTagProvider(): array
    {
        return [
            'read to activate' => ['read_to_activate'],
            'logo' => ['logo'],
        ];
    }
}
