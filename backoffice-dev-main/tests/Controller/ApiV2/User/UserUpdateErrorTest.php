<?php

namespace App\Tests\Controller\ApiV2\User;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserUpdateErrorTest extends FixtureWebTestCase
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
    public function testUpdateUserMediaTypeInvalid($mediaType): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/users/1';
        $headers = [
            'CONTENT_TYPE' => $mediaType,
        ];
        $this->client->request('PATCH', $uri, [], [], $headers);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
    }

    // Can technically patch with empty content
    // #[\PHPUnit\Framework\Attributes\DataProvider('supportedMediaTypeProvider')]
    // public function testUpdateUserFormatsInvalid($mediaType): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/users/1';
    //     $headers = [
    //         'CONTENT_TYPE' => $mediaType
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers, content: json_encode([]));
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }

    // /**
    //  * @dataProvider unsupportedMediaTypeProvider
    //  */
    // public function testUpdateUserDocumentMediaTypeInvalid($mediaType): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/users/1/documents/1';
    //     $headers = [
    //         'CONTENT_TYPE' => $mediaType
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
    // }

    // /**
    //  * @dataProvider supportedMediaTypeProvider
    //  */
    // public function testUpdateUserDocumentFormatsInvalid($mediaType): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/users/1/documents/1';
    //     $headers = [
    //         'CONTENT_TYPE' => $mediaType
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }

    public function testUpdateUserNotExists(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/users/-1';
        $content = json_encode([]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // public function testUpdateUserDocumentNotExists(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/users/-1/documents/-1';
    //     $content = json_encode([]);
    //     $headers = [
    //         "CONTENT_TYPE" => "application/json"
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    // }

    // public function testUpdateUserFieldsImmutable(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/users/1';
    //     $content = json_encode([
    //         "id" => 99
    //     ]);
    //     $headers = [
    //         "CONTENT_TYPE" => "application/json"
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }

    // public function testUpdateUserFieldsUnknown(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/users/1';
    //     $content = json_encode([
    //         "biscuit" => "test unknown field"
    //     ]);
    //     $headers = [
    //         "CONTENT_TYPE" => "application/json"
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }

    // public function testUpdateUserFieldsInvalidType(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/users/1';
    //     $content = json_encode([
    //         "mobilePhone" => "test alphabet in numerical field"
    //     ]);
    //     $headers = [
    //         "CONTENT_TYPE" => "application/json"
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }

    public function testUpdateUserFieldEmailInvalid(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/users/1';
        $content = json_encode([
            'email' => 'email with the at and has spaces',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
