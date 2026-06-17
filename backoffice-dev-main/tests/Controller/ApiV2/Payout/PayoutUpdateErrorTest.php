<?php

namespace App\Tests\Controller\ApiV2\Payout;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\RequiresEnvironmentVariable('testApiV2', '1')]
class PayoutUpdateErrorTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<'amount'|'dueDate'|'type', array{0: array{dueDate?: 'now', type?: 3, amount?: '@!?'}}, mixed, void>
     */
    public static function invalidFieldProvider(): \Generator
    {
        yield 'type' => [['type' => 3]];
        yield 'amount' => [['amount' => '@!?']];
        yield 'dueDate' => [['dueDate' => 'now']];
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
    public static function supportedMediaTypeProvider(): \Generator
    {
        yield 'json' => ['application/json'];
        yield 'xml' => ['application/xml'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidFieldProvider')]
    public function testUpdatePayoutFieldsInvalid($fields): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/payouts/1';
        $content = json_encode($fields);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        // $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Not yet implemented, so method not allowed
        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    // #[\PHPUnit\Framework\Attributes\DataProvider('unsupportedMediaTypeProvider')]
    // public function testUpdatePayoutMediaTypeInvalid($mediaType): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/payouts/1';
    //     $content = json_encode([]);
    //     $headers = [
    //         'CONTENT_TYPE' => $mediaType
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
    // }
    // #[\PHPUnit\Framework\Attributes\DataProvider('supportedMediaTypeProvider')]
    // public function testUpdatePayoutFormatsInvalid($mediaType): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/payouts/1';
    //     $content = json_encode([]);
    //     $headers = [
    //         'CONTENT_TYPE' => $mediaType
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
    // public function testUpdatePayoutFieldsImmutable(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/payouts/1';
    //     $content = json_encode([
    //         'id' => 12
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
    // public function testUpdatePayoutFieldsUnknown(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/payouts/1';
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
