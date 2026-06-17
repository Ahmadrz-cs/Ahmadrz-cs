<?php

namespace App\Tests\Controller\ApiV2\Investment;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class InvestmentUpdateErrorTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<'investment document not exists'|'investment not exists document not exists'|'investment not exists', array{0: '/investments/-1'|'/investments/-1/documents/-1'|'/investments/1/documents/-1'}, mixed, void>
     */
    public static function investmentEndpointsNotExistsProvider(): \Generator
    {
        yield 'investment not exists' => ['/investments/-1'];
        yield 'investment document not exists' => ['/investments/1/documents/-1'];
        yield 'investment not exists document not exists' => [
            '/investments/-1/documents/-1',
        ];
    }

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
    public function supportedMediaTypeProvider(): \Generator
    {
        yield 'json' => ['application/json'];
        yield 'xml' => ['application/xml'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider(
        'investmentEndpointsNotExistsProvider',
    )]
    public function testUpdateInvestmentEndpointsNotExists($endpoint): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . $endpoint;
        $content = json_encode([]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('unsupportedMediaTypeProvider')]
    public function testUpdateInvestmentMediaTypeInvalid($mediaType): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/investments/1';
        $content = json_encode([]);
        $headers = [
            'CONTENT_TYPE' => $mediaType,
        ];
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
    }

    // /**
    //  * @dataProvider supportedMediaTypeProvider
    //  */
    // public function testUpdateInvestmentFormatsInvalid($mediaType): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/investments/1';
    //     $content = json_encode([]);
    //     $headers = [
    //         'CONTENT_TYPE' => $mediaType
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
    // /**
    //  * @dataProvider unsupportedMediaTypeProvider
    //  */
    // public function testUpdateInvestmentDocumentMediaTypeInvalid($mediaType): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $sample = $this->searchFixtures(
    //         \App\Entity\Investment::class,
    //         ["status" => 'settled'],
    //         true
    //     );
    //     $filter = $this->searchFixtures(
    //         \App\Entity\InvestmentDocuments::class,
    //         ["investment" => $sample],
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/investments/' . $filter[0]->getInvestment()->getId() . '/documents/' . $filter[0]->getId();
    //     $content = json_encode([
    //         'tag' => 'test'
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => $mediaType
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
    // }
    // /**
    //  * @dataProvider supportedMediaTypeProvider
    //  */
    // public function testUpdateInvestmentDocumentFormatsInvalid($mediaType): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $sample = $this->searchFixtures(
    //         \App\Entity\Investment::class,
    //         ["status" => 'settled'],
    //         true
    //     );
    //     $filter = $this->searchFixtures(
    //         \App\Entity\InvestmentDocuments::class,
    //         ["investment" => $sample],
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/investments/' . $filter[0]->getInvestment()->getId() . '/documents/' . $filter[0]->getId();
    //     $content = json_encode([
    //         'tag' => 'test'
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => $mediaType
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
    // public function testUpdateInvestmentFieldsImmutable(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/investments/1';
    //     $content = json_encode([
    //         'id' => '999'
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
    // public function testUpdateInvestmentFieldsUnknown(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/investments/1';
    //     $content = json_encode([
    //         'biscuit' => 'cookie'
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
}
