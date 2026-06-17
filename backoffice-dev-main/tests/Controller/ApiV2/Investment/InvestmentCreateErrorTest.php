<?php

namespace App\Tests\Controller\ApiV2\Investment;

use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV2\ApiBase64Files;
use Symfony\Component\HttpFoundation\Response;

class InvestmentCreateErrorTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<string, array{0: string}, mixed, void>
     */
    public function unsupportedMediaTypeProvider(): \Generator
    {
        yield 'form-data' => ['multipart/form-data'];
        yield 'text' => ['text/plain'];
        yield 'javascript' => ['application/javascript'];
        yield 'html' => ['text/html'];
    }

    /**
     * @psalm-return \Generator<'json'|'xml', array{0: 'application/json'|'application/xml'}, mixed, void>
     */
    public function supportedMediaTypeProvider(): \Generator
    {
        yield 'json' => ['application/json'];
        yield 'xml' => ['application/xml'];
    }

    public function testCreateInvestmentFieldsRequiredMissing(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/investments';
        $content = json_encode([
            'type' => 'Normal',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testCreateInvestmentFieldsTypeInvalid(): void
    {
        /**
         * "type" field is case sensitive
         * Permitted values can be found from App\Entity\Investment:getInvestmentTypes
         */
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(\App\Entity\Offering::class, [
            'status' => 'published',
        ]);
        $uri = self::API_PATH_PREFIX_V2 . '/investments';
        $content = json_encode([
            'offeringId' => $sample[0]->getId(),
            'numberOfShares' =>
                round(self::MIN_COMMIT / $sample[0]->getPricePerShare()) + 1,
            'type' => 'Prefunding',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testCreateInvestmentUserNotApproved(): void
    {
        $this->loginApiClientUser(self::USER_EMAIL_VERIFIED);
        $sample = $this->searchFixtures(\App\Entity\Offering::class, [
            'status' => 'published',
        ]);
        $uri = self::API_PATH_PREFIX_V2 . '/investments';
        $content = json_encode([
            'offeringId' => $sample[0]->getId(),
            'numberOfShares' =>
                round(self::MIN_COMMIT / $sample[0]->getPricePerShare()) + 1,
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testCreateInvestmentSharesExceeded(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(\App\Entity\Offering::class, [
            'status' => 'published',
        ]);
        $uri = self::API_PATH_PREFIX_V2 . '/investments';
        $content = json_encode([
            'offeringId' => $sample[0]->getId(),
            'numberOfShares' =>
                $sample[0]->getNoOfShares() - $sample[0]->getSharesSold() + 1,
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    // public function testCreateInvestmentInsufficientBalance(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $sample = $this->searchFixtures(
    //         \App\Entity\Offering::class,
    //         ['status' => 'published', 'name' => 'High share price offering 600k']
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/investments';
    //     $content = json_encode([
    //         'offeringId' => $sample[0]->getId(),
    //         'numberOfShares' => 1
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
    // public function testCreateInvestmentDocumentFieldsRequiredMissing(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $sample = $this->searchFixtures(
    //         \App\Entity\Investment::class,
    //         ["status" => 'settled']
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/investments/' . $sample[0]->getId() . '/documents';
    //     $content = json_encode([
    //         'tag' => 'test'
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
    // /**
    //  * @dataProvider unsupportedMediaTypeProvider
    //  */
    // public function testCreateInvestmentMediaTypeInvalid($mediaType): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/investments';
    //     $headers = [
    //         'CONTENT_TYPE' => $mediaType
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
    // }
    // /**
    //  * @dataProvider supportedMediaTypeProvider
    //  */
    // public function testCreateInvestmentFormatsInvalid($mediaType): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/investments';
    //     $headers = [
    //         'CONTENT_TYPE' => $mediaType
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
    // public function testCreateInvestmentNotExistsDocument(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/investments/-1/documents';
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
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
    // /**
    //  * @dataProvider unsupportedMediaTypeProvider
    //  */
    // public function testCreateInvestmentDocumentMediaTypeInvalid($mediaType): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $sample = $this->searchFixtures(
    //         \App\Entity\Investment::class,
    //         ["status" => 'settled']
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/investments/' . $sample[0]->getId() . '/documents';
    //     $content = json_encode([
    //         'type' => 'image/jpg',
    //         'fileName' => 'jpgTest.jpg',
    //         'documentContent' => ApiBase64Files::TEST_JPG,
    //         'tag' => 'test'
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => $mediaType
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
    // /**
    //  * @dataProvider supportedMediaTypeProvider
    //  */
    // public function testCreateInvestmentDocumentFormatsInvalid($mediaType): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $sample = $this->searchFixtures(
    //         \App\Entity\Investment::class,
    //         ["status" => 'settled']
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/investments/' . $sample[0]->getId() . '/documents';
    //     $content = json_encode([
    //         'type' => 'image/jpg',
    //         'fileName' => 'jpgTest.jpg',
    //         'documentContent' => ApiBase64Files::TEST_JPG,
    //         'tag' => 'test'
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => $mediaType
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
    // public function testCreateInvestmentDocumentInvalidFileExtension(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/investments/1/documents';
    //     $content = json_encode([
    //         'fileName' => 'shareCert.docx',
    //         'documentContent' => ApiBase64Files::TEST_PDF,
    //         'tag' => 'share_cert'
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
    // public function testCreateInvestmentDocumentInvalidMimeType(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/investments/1/documents';
    //     $content = json_encode([
    //         'fileName' => 'shareCert.png',
    //         'documentContent' => ApiBase64Files::TEST_DOC,
    //         'tag' => 'share_cert'
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
}
