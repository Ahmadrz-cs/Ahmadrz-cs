<?php

namespace App\Tests\Controller\ApiV1\Self;

use App\Controller\ApiV1\Response\ErrorResponse;
use App\Entity\BankAccount;
use App\Entity\Enum\BankAccountStatus;
use App\Entity\Enum\BankAccountType;
use App\Entity\User;
use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class SelfPostErrorTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testUpdatePasswordCurrentPasswordIncorrect(): void
    {
        // Cannot change password if you are unable to confirm your current one correctly
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V1 . '/self/changePassword';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'current_password' => self::USER_PASSWORD_STANDARD . 'abc',
            'new_password' => self::USER_PASSWORD_STANDARD . '011235',
            'new_password_confirm' => self::USER_PASSWORD_STANDARD . '011235',
        ]);

        // Request password change
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_NOT_ACCEPTABLE, $apiResponse['status']);

        $this->assertEquals('fail', $apiResponse['outcome']);
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_USER_CURRENT_PASSWORD_INVALID];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testUpdatePasswordNewPasswordMisMatch(): void
    {
        // Cannot change password if the new password does not match the confirmation
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V1 . '/self/changePassword';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'current_password' => self::USER_PASSWORD_STANDARD,
            'new_password' => self::USER_PASSWORD_STANDARD . '011235',
            'new_password_confirm' => self::USER_PASSWORD_STANDARD . '011235811',
        ]);

        // Request password change
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_NOT_ACCEPTABLE, $apiResponse['status']);

        $this->assertEquals('fail', $apiResponse['outcome']);
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_USER_CONFIRM_PASSWORD_NOT_MACHING];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testUpdatePasswordNewPasswordSameAsCurrent(): void
    {
        // If password is the same as before, no point changing
        // This is arguably a bit leaky...
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V1 . '/self/changePassword';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'current_password' => self::USER_PASSWORD_STANDARD,
            'new_password' => self::USER_PASSWORD_STANDARD,
            'new_password_confirm' => self::USER_PASSWORD_STANDARD,
        ]);

        // Request password change
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_NOT_ACCEPTABLE, $apiResponse['status']);

        $this->assertEquals('fail', $apiResponse['outcome']);
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_USER_PASSWORD_MACHING_WITH_CURRENT_PASSWORD];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testUpdatePasswordCurrentPasswordMissing(): void
    {
        // Must provide current password to update
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V1 . '/self/changePassword';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'current_password' => '',
            'new_password' => self::USER_PASSWORD_STANDARD . '011235',
            'new_password_confirm' => self::USER_PASSWORD_STANDARD . '011235',
        ]);

        // Request password change
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_NOT_ACCEPTABLE, $apiResponse['status']);

        $this->assertEquals('fail', $apiResponse['outcome']);
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_USER_CURRENT_PASSWORD_MISSING];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testUpdatePasswordNewPasswordConfirmMissing(): void
    {
        // Must provide new password confirmation
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V1 . '/self/changePassword';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'current_password' => 'self::USER_PASSWORD_STANDARD',
            'new_password' => self::USER_PASSWORD_STANDARD . '011235',
            'new_password_confirm' => '',
        ]);

        // Request password change
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_NOT_ACCEPTABLE, $apiResponse['status']);

        $this->assertEquals('fail', $apiResponse['outcome']);
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_USER_CONFIRM_PASSWORD_MISSING];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testUpdatePasswordNewPasswordMissing(): void
    {
        // Must provide new password
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V1 . '/self/changePassword';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'current_password' => 'self::USER_PASSWORD_STANDARD',
            'new_password' => '',
            'new_password_confirm' => self::USER_PASSWORD_STANDARD . '011235',
        ]);

        // Request password change
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_NOT_ACCEPTABLE, $apiResponse['status']);

        $this->assertEquals('fail', $apiResponse['outcome']);
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_USER_NEW_PASSWORD_MISSING];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testUpdatePasswordAllFieldsMissing(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V1 . '/self/changePassword';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'current_password' => 'self::USER_PASSWORD_STANDARD',
            'new_password' => '',
            'new_password_confirm' => self::USER_PASSWORD_STANDARD . '011235',
        ]);

        // Request password change
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_NOT_ACCEPTABLE, $apiResponse['status']);

        $this->assertEquals('fail', $apiResponse['outcome']);
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_USER_NEW_PASSWORD_MISSING];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testCreateUserVerificationEmailAlreadyVerified(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V1 . '/self/resendVerificationEmail';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'url' => 'http://example.com/verifyme',
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $apiResponse['status']);
        $this->assertEquals('fail', $apiResponse['outcome']);
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_USER_ALREADY_VERIFIED_EMAIL];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('error')]
    #[\PHPUnit\Framework\Attributes\Group('mangopay')]
    #[\PHPUnit\Framework\Attributes\Group('check')]
    public function testCreateSelfBankAccountRegistrationsDuplicate(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V1 . '/self/bank-accounts';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $requestBody = [
            'country' => 'GB',
            'accountNumber' => (string) mt_rand(10102020, 99990000),
            'bic' => (string) mt_rand(111100, 990088),
            'accountHolderType' => 'personal',
        ];
        $content = json_encode($requestBody);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();

        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('already exists', $apiResponse['detail']);
    }

    #[\PHPUnit\Framework\Attributes\Group('error')]
    #[\PHPUnit\Framework\Attributes\Group('check')]
    public function testPostSelfBankAccountActivationNotApproved(): void
    {
        // We'll create an approved bank account first
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy([
                'username' => self::USER_REGULAR,
            ]);
        $bar = new BankAccount();
        $bar->setUser($user);
        $bar->setStatus(BankAccountStatus::Validated);
        $bar->setCountry('GB');
        $bar->setAccountType(BankAccountType::GB);
        $bar->setAccountNumber('55779911');
        $bar->setBankIdentifierCode('200000');
        $bar->setDescription('APIv1 activate bank account registration automated test');
        $this->entityManager->persist($bar);
        $this->entityManager->flush();

        $this->loginApiClientUser(self::USER_REGULAR);
        $uri =
            self::API_PATH_PREFIX_V1 . "/self/bank-accounts/{$bar->getId()}/activation";
        $this->client->request('POST', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString(
            'only activate approved',
            $apiResponse['detail'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testPostAndDeleteSelfBankAccountOtherUser(): void
    {
        // We'll create an approved bank account first
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy([
                'username' => self::USER_LOW_BALANCE,
            ]);
        $bar = new BankAccount();
        $bar->setUser($user);
        $bar->setStatus(BankAccountStatus::Approved);
        $bar->setCountry('GB');
        $bar->setAccountType(BankAccountType::GB);
        $bar->setAccountNumber('55779911');
        $bar->setBankIdentifierCode('200000');
        $bar->setDescription('APIv1 activate bank account registration automated test');
        $this->entityManager->persist($bar);
        $this->entityManager->flush();

        $this->loginApiClientUser(self::USER_REGULAR);
        $uri =
            self::API_PATH_PREFIX_V1 . "/self/bank-accounts/{$bar->getId()}/activation";
        $this->client->request('POST', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString(
            'only access your own',
            $apiResponse['detail'],
        );

        $uri =
            self::API_PATH_PREFIX_V1
            . "/self/bank-accounts/{$bar->getId()}/activation-outcome";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $requestBody = [
            'success' => true,
            'verify' => false,
        ];
        $content = json_encode($requestBody);
        $this->client->request(
            method: 'POST',
            uri: $uri,
            server: $headers,
            content: $content,
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString(
            'only access your own',
            $apiResponse['detail'],
        );

        $uri = self::API_PATH_PREFIX_V1 . "/self/bank-accounts/{$bar->getId()}";
        $this->client->request('DELETE', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString(
            'only access your own',
            $apiResponse['detail'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testCreateSelfScaEnrollmentMissingMangopayUserId(): void
    {
        $this->loginApiClientUser(self::USER_EMAIL_UNVERIFIED);
        $uri = self::API_PATH_PREFIX_V1 . '/self/sca/enroll';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $this->client->request('POST', $uri, [], [], $headers);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_MANGOPAY_USER_MISSING_ID];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
    }
}
