<?php

namespace App\Tests\Controller\ApiV1\User;

use App\Controller\ApiV1\Response\ErrorResponse;
use App\Entity\User;
use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserPostErrorTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testCreateUserTransferFieldAmountMissing(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR,
        ])[0];
        $transfer = [
            'currency' => 'GBP',
            'fee_amount' => '100',
            'user_wallet_id' => $sample->getMangoPayWalletId(),
            'org_wallet_id' => 21986022,
        ];
        $uri = self::API_PATH_PREFIX_V1 . "/users/{$sample->getId()}/mangopayTransfer";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode($transfer);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $apiResponse['status']);
        $this->assertEquals('fail', $apiResponse['outcome']);
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_INSUFFICIENT_PARAMS];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testCreateUserFieldEmailAlreadyExists(): void
    {
        $uri = self::API_PATH_PREFIX_V1 . '/public/users';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'email' => self::USER_REGULAR,
            'url' => 'http://example.com/verifyme',
            'first_name' => 'Franklin',
            'last_name' => 'Hall',
            'username' => self::USER_REGULAR,
            'password' => self::USER_PASSWORD_STANDARD,
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $apiResponse['status']);
        $this->assertEquals('fail', $apiResponse['outcome']);
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_USER_EMAIL_ALREADY_EXISTS];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testCreateUserFieldEmailMissing(): void
    {
        $uri = self::API_PATH_PREFIX_V1 . '/public/users';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'url' => 'http://example.com/verifyme',
            'first_name' => 'Franklin',
            'last_name' => 'Hall',
            'username' => 'abc' . self::USER_REGULAR,
            'password' => self::USER_PASSWORD_STANDARD,
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $apiResponse['status']);
        $this->assertEquals('fail', $apiResponse['outcome']);
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_USER_MISSING_EMAIL];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testCreateUserFieldPasswordMissing(): void
    {
        $uri = self::API_PATH_PREFIX_V1 . '/public/users';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'email' => 'abc' . self::USER_REGULAR,
            'url' => 'http://example.com/verifyme',
            'first_name' => 'Franklin',
            'last_name' => 'Hall',
            'username' => 'abc' . self::USER_REGULAR,
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $apiResponse['status']);
        $this->assertEquals('fail', $apiResponse['outcome']);
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_USER_MISSING_PASSWORD];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testCreateUserFieldVerifyUrlMissing(): void
    {
        $uri = self::API_PATH_PREFIX_V1 . '/public/users';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'email' => 'abc' . self::USER_REGULAR,
            'first_name' => 'Franklin',
            'last_name' => 'Hall',
            'username' => 'abc' . self::USER_REGULAR,
            'password' => self::USER_PASSWORD_STANDARD,
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $apiResponse['status']);
        $this->assertEquals('fail', $apiResponse['outcome']);
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_USER_MISSING_VERIFY_URL];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
    }

    public static function invalidPasswordProvider(): \Generator
    {
        yield 'Only numbers' => ['123456789'];
        yield 'Only lower alphas' => ['uajgyhafbjafw'];
        yield 'Only upper alphas' => ['YUPIKAIWUQ'];
        yield 'Too short' => ['a1X!'];

        // yield 'Unsupported white space' => ['a1X! A441feiy  a']; // We seem to support them
        // yield 'Unsupported special characters' => ['£$%£$%GGFDsupGFDG£1$%$DG']; // We seem to support them
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidPasswordProvider')]
    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testCreateSelfDocumentTypes(string $password): void
    {
        $uri = self::API_PATH_PREFIX_V1 . '/public/users';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'email' => 'abc' . self::USER_REGULAR,
            'password' => $password,
            'url' => 'http://example.com/verifyme',
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $apiResponse['status']);
        $this->assertEquals('fail', $apiResponse['outcome']);
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_USER_PASSWORD_STRENGTH];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
    }
}
