<?php

namespace App\Tests\Controller\ApiV1\Scenario\Email;

use App\Controller\ApiV1\Response\ErrorResponse;
use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use App\Test\MailcatcherTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use SymfonyCasts\Bundle\ResetPassword\Generator\ResetPasswordTokenGenerator;

class UserPasswordResetTest extends MailcatcherTestCase
{
    public const FORGOT_URL = 'http://example.com';
    public const NETWORK_NAME = 'yielders';

    #[\PHPUnit\Framework\Attributes\Group('email')]
    public function testForgotPassword(): void
    {
        $newPassword = 'NewPassword12!';
        $sample = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR,
        ])[0];
        $this->clientSendForgotPasswordRequest($sample);
        $this->assertResponseIsSuccessful();

        // Check key parts of password reset email are correct
        $message = $this->getMessages()[0];
        $messageContent = $this->getMessageInFormat($message->id, 'html');
        $this->assertEquals('Password Reset', $message->subject);
        $this->assertEquals('<noreply@yielders.co.uk>', $message->sender);
        $this->assertContains('<' . self::USER_REGULAR . '>', $message->recipients);
        $this->assertStringContainsString(self::FORGOT_URL, $messageContent);

        // Change the password
        $this->clientSendResetPasswordRequest(
            $newPassword,
            $newPassword,
            $this->extraPasswordResetToken($messageContent),
        );
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
        $this->assertArrayHasKey('user_id', $apiResponse['data']);
        $this->assertEquals($sample->getId(), $apiResponse['data']['user_id']);

        // Should be able to login with new password
        $this->sendLoginRequest(self::USER_REGULAR, $newPassword);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('access_token', $apiResponse);
        $this->assertNotEmpty($apiResponse['access_token']);

        // But not with old password
        $this->sendLoginRequest(self::USER_REGULAR, self::USER_PASSWORD_STANDARD);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error_description', $apiResponse);
        $this->assertEquals(
            'The user credentials were incorrect.',
            $apiResponse['error_description'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('email')]
    public function testForgotPasswordIgnore(): void
    {
        // User should not be obliged to change their password if somebody initiated it

        $sample = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR,
        ])[0];
        $this->clientSendForgotPasswordRequest($sample);
        $this->assertResponseIsSuccessful();

        // Can still login with current password
        $this->sendLoginRequest(self::USER_REGULAR, self::USER_PASSWORD_STANDARD);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('access_token', $apiResponse);
        $this->assertNotEmpty($apiResponse['access_token']);
    }

    #[\PHPUnit\Framework\Attributes\Group('email')]
    public function testForgotPasswordExpiredToken(): void
    {
        $sample = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR,
        ])[0];

        // Create a deliberately expired reset request
        /** @var ResetPasswordTokenGenerator $tokenGenerator */
        $tokenGenerator = static::getContainer()->get(
            'symfonycasts.reset_password.token_generator',
        );
        // Use the current time minus 10 seconds for the expiredAt time
        // This should be plenty to ensure the reset request is expired during the test
        $expiresAt = new \DateTime();
        $expiresAt->sub(new \DateInterval('PT10S'));
        // Convert to immutable datetime to satisfy type checks
        $expiresAt = \DateTimeImmutable::createFromMutable($expiresAt);
        // The user identifier must be a string to match the bundle's expectations!
        // If it's an int the hash will be different!
        $tokenComponents = $tokenGenerator->createToken(
            $expiresAt,
            (string) $sample->getId(),
        );
        $passwordResetRequest = new ResetPasswordRequest(
            $sample,
            $expiresAt,
            $tokenComponents->getSelector(),
            $tokenComponents->getHashedToken(),
        );
        $this->entityManager->persist($passwordResetRequest);
        $this->entityManager->flush();

        // Change the password
        $newPassword = 'NewPassword12!';
        $this->clientSendResetPasswordRequest(
            $newPassword,
            $newPassword,
            $tokenComponents->getPublicToken(),
        );

        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_USER_TOKEN_EXPIRED];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
        $this->assertEquals($expectedResponse['http'], $apiResponse['status']);
        $this->assertEquals('fail', $apiResponse['outcome']);
    }

    #[\PHPUnit\Framework\Attributes\Group('email')]
    public function testForgotPasswordIncorrectToken(): void
    {
        $newPassword = 'NewPassword12!';
        $sample = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR,
        ])[0];
        $this->clientSendForgotPasswordRequest($sample);
        $this->assertResponseIsSuccessful();

        $this->clientSendResetPasswordRequest($newPassword, $newPassword, 'a');

        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_USER_NOT_FOUND];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
        $this->assertEquals($expectedResponse['http'], $apiResponse['status']);
        $this->assertEquals('fail', $apiResponse['outcome']);
    }

    #[\PHPUnit\Framework\Attributes\Group('email')]
    public function testResetPasswordNotRequested(): void
    {
        $newPassword = 'NewPassword12!';

        // Send password reset request without having previously said you have forgotten your password
        $this->clientSendResetPasswordRequest($newPassword, $newPassword, 'a');

        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_USER_NOT_FOUND];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
        $this->assertEquals($expectedResponse['http'], $apiResponse['status']);
        $this->assertEquals('fail', $apiResponse['outcome']);
    }

    #[\PHPUnit\Framework\Attributes\Group('email')]
    public function testForgotPasswordPasswordMismatch(): void
    {
        $newPassword = 'NewPassword12!';
        $sample = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR,
        ])[0];
        $this->clientSendForgotPasswordRequest($sample);
        $this->assertResponseIsSuccessful();

        // Check key parts of password reset email are correct
        $message = $this->getMessages()[0];
        $messageContent = $this->getMessageInFormat($message->id, 'html');
        $this->assertEquals('Password Reset', $message->subject);
        $this->assertEquals('<noreply@yielders.co.uk>', $message->sender);
        $this->assertContains('<' . self::USER_REGULAR . '>', $message->recipients);
        $this->assertStringContainsString(self::FORGOT_URL, $messageContent);

        // Change the password
        $this->clientSendResetPasswordRequest(
            $newPassword,
            $newPassword . 'extrabits',
            $this->extraPasswordResetToken($messageContent),
        );
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_USER_PASSWORD_DONT_MATCH];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
        $this->assertEquals($expectedResponse['http'], $apiResponse['status']);
        $this->assertEquals('fail', $apiResponse['outcome']);
    }

    #[\PHPUnit\Framework\Attributes\Group('email')]
    public function testForgotPasswordUserNotExist(): void
    {
        $uri = self::API_PATH_PREFIX_V1 . '/public/forgotPassword';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'network' => self::NETWORK_NAME,
            'url' => self::FORGOT_URL,
            'email' => 'nobody@here.com',
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);

        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_USER_NOT_FOUND];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
        $this->assertEquals($expectedResponse['http'], $apiResponse['status']);
        $this->assertEquals('fail', $apiResponse['outcome']);
    }

    private function extraPasswordResetToken(string $emailBody): string
    {
        // Use the Symfony DOMCrawler to parse the email and access the reset password link
        $crawler = new Crawler($emailBody);
        // The XPath should extract the following element
        // <a href="http://yielders.co.uk?user_id=9&amp;secret=e2226fb66ca8fe79fb20">Reset Password</a>
        // Then we extract the href attribute
        // The base URL will vary based on the url sent during the forgot password request
        $resetUrl = $crawler
            ->filterXPath('//*[text() = "Reset Password"]')
            ->attr('href');
        $resetUrlParts = explode('token=', $resetUrl);
        if (count($resetUrlParts) != 2) {
            $this->fail('Unable to parse reset link: ' . $resetUrl);
        }
        return $resetUrlParts[1];
    }

    private function clientSendForgotPasswordRequest(User $user): void
    {
        $uri = self::API_PATH_PREFIX_V1 . '/public/forgotPassword';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'network' => self::NETWORK_NAME,
            'url' => self::FORGOT_URL,
            'email' => $user->getEmail(),
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
    }

    private function clientSendResetPasswordRequest(
        string $password,
        string $passwordConfirm,
        string $token,
    ): void {
        $uri = self::API_PATH_PREFIX_V1 . '/public/resetPassword';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'token' => $token,
            'password' => $password,
            'password_confirm' => $passwordConfirm,
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
    }
}
