<?php

namespace App\Tests\Controller\ApiV2\Offering;

use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV2\ApiBase64Files;
use App\Tests\Controller\ApiV2\ApiResponseFields;
use Symfony\Component\HttpFoundation\Response;

class OfferingCreateResponseTest extends FixtureWebTestCase
{
    public function testCreateOffering(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(\App\Entity\Asset::class, [
            'status' => 'published',
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
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::OFFERING_STANDARD;
        $actualFields = array_keys($apiResponse);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    }

    public function testCreateOfferingFieldsAll(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(
            \App\Entity\Asset::class,
            ['status' => 'published'],
            true,
        );
        $uri = self::API_PATH_PREFIX_V2 . '/offerings';
        $content = json_encode([
            'assetId' => $sample[0],
            'numberOfShares' => 100,
            'name' => 'testCreateOfferingFieldsAll offering',
            'pricePerShare' => '1',
            'minCommit' => '100',
            'status' => 'draft',
            'isFeatured' => false,
            'externalCommitments' => '0',
            'netAnnualYield' => '0',
            'netTotalReturn' => '0',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::OFFERING_STANDARD;
        $actualFields = array_keys($apiResponse);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    }

    // public function testCreateOfferingRelisted(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $sample = $this->searchFixtures(
    //         \App\Entity\Asset::class,
    //         ["status" => 'published']
    //     );
    //     $filter = $this->searchFixtures(
    //         \App\Entity\Investment::class,
    //         ["assetId" => $sample[0]->getId()]
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings';
    //     $content = json_encode([
    //         'name' => 'testCreateOffering',
    //         'assetId' => $sample[0]->getId(),
    //         'isSecondaryMarket' => true,
    //         'numberOfShares' => '1000',
    //         'investmentId' => $filter[0]->getId(),
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    //     $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
    //     $expectedFields = ApiResponseFields::OFFERING_STANDARD;
    //     $actualFields = array_keys($apiResponse);
    //     $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    // }
    // /**
    //  * @dataProvider docTypeProvider
    //  */
    // public function testCreateOfferingDocument($type, $fileName, $documentContent): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings/1/documents';
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
    //     $expectedFields = ApiResponseFields::OFFERING_STANDARD;
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
}
