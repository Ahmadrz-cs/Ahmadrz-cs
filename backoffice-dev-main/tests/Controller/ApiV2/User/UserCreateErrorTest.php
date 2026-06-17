<?php

namespace App\Tests\Controller\ApiV2\User;

use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV2\ApiBase64Files;
use Symfony\Component\HttpFoundation\Response;

class UserCreateErrorTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<'no lowercase'|'no numbers'|'no uppercase', array{0: 'VSQKS1ZQ'|'vsqks1zq'|'vsqkslzq'}, mixed, void>
     */
    public static function invalidPasswordProvider(): \Generator
    {
        yield 'no uppercase' => ['vsqks1zq'];
        yield 'no lowercase' => ['VSQKS1ZQ'];
        yield 'no numbers' => ['vsqkslzq'];
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

    #[\PHPUnit\Framework\Attributes\DataProvider('unsupportedMediaTypeProvider')]
    public function testCreateUserMediaTypeInvalid($mediaType): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/users';
        $headers = [
            'CONTENT_TYPE' => $mediaType,
        ];
        $this->client->request('POST', $uri, [], [], $headers);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
    }

    // #[\PHPUnit\Framework\Attributes\DataProvider('supportedMediaTypeProvider')]
    // public function testCreateUserFormatsValidEmptyRequest($mediaType): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/users';
    //     $headers = [
    //         'CONTENT_TYPE' => $mediaType
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    // }

    public function testCreateUserDuplicate(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/users';
        $content = json_encode([
            'email' => 'ben.auto@test.yielderverse.co.uk',
            'firstName' => 'Ben',
            'lastName' => 'Autotest',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidPasswordProvider')]
    public function testCreateUserWithInvalidPassword($password): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/users';
        $content = json_encode([
            'email' => 'lily.renoir@test.com',
            'firstName' => 'Lily',
            'lastName' => 'Renoir',
            'password' => $password,
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    // public function testCreateUserNotExistsDocument(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/users/-1/documents';
    //     $content = json_encode([
    //         'fileName' => 'proofOfId.pdf',
    //         'documentContent' => ApiBase64Files::TEST_PDF,
    //         'tag' => 'proof_of_identity'
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    // }

    public function testCreateUserDocumentInvalidFileExtension(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/users/1/documents';
        $content = json_encode([
            'fileName' => 'proofOfId.docx',
            'documentContent' => ApiBase64Files::TEST_PDF,
            'tag' => 'proof_of_identity',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testCreateUserDocumentInvalidMimeType(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/users/1/documents';
        $content = json_encode([
            'fileName' => 'proofOfId.png',
            'documentContent' => ApiBase64Files::TEST_DOC,
            'tag' => 'proof_of_identity',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    // public function testCreateUserBankwirePayinInvalidAmount(): void
    // {
    //     $this->loginApiClientUser(self::USER_REGULAR);
    //     $sample = $this->searchFixtures(
    //         \App\Entity\User::class,
    //         ["username" => "ben.auto@test.yielderverse.co.uk"],
    //         true
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/users/' . $sample[0] . '/payin';
    //     $content = json_encode([
    //         'amount' => 'random string'
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
}
