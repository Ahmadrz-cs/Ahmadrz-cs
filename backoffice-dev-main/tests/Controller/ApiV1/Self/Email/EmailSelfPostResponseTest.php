<?php

namespace App\Tests\Controller\ApiV1\Self\Email;

use App\Entity\BankAccount;
use App\Entity\Enum\ActionRequest;
use App\Entity\Enum\BankAccountHolderType;
use App\Entity\Enum\BankAccountStatus;
use App\Entity\Enum\BankAccountType;
use App\Entity\User;
use App\Test\MailcatcherTestCase;
use App\Tests\Controller\ApiV1\ApiV1ResponseFields;
use Symfony\Component\HttpFoundation\Response;

class EmailSelfPostResponseTest extends MailcatcherTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('email')]
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateSelfChangePassword(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V1 . '/self/changePassword';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'current_password' => self::USER_PASSWORD_STANDARD,
            'new_password' => self::USER_PASSWORD_STANDARD . '011235',
            'new_password_confirm' => self::USER_PASSWORD_STANDARD . '011235',
        ]);

        // Request password change
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        // Check an email confirmation has been sent
        $message = $this->getMessages()[0];
        $this->assertEquals('Password Reset Confirmation', $message->subject);
        $this->assertEquals('<noreply@yielders.co.uk>', $message->sender);
        $this->assertContains('<' . self::USER_REGULAR . '>', $message->recipients);

        // Verify that you can use new password to login
        // Don't actually need to logout to issue a new access_token
        // $this->client->request('GET', self::OAUTH2_PATH_LOGOUT, ['continue_url' => 'https://www.example.com']);
        $this->sendLoginRequest(
            self::USER_REGULAR,
            self::USER_PASSWORD_STANDARD . '011235',
            [],
        );
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($response['access_token']);
    }

    #[\PHPUnit\Framework\Attributes\Group('email')]
    #[\PHPUnit\Framework\Attributes\Group('response')]
    #[\PHPUnit\Framework\Attributes\Group('mangopay')]
    public function testCreateSelfBankAccountRegistrations(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V1 . '/self/bank-accounts';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $requestBody = [
            'country' => 'GB',
            // "accountNumber" => (string) mt_rand(10102020, 99990000),
            // "bic" => (string) mt_rand(101100, 990088),
            // Using valid bank details should pass Mangopay validation
            // Note that bank account numbers use a modulus check, so it's not random
            // https://www.vocalink.com/tools/modulus-checking/
            'accountNumber' => '41066722',
            'bic' => '040004',
            'accountHolderType' => 'personal',
        ];
        $content = json_encode($requestBody);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::BANK_ACCOUNT_REGISTRATION,
            array_keys($apiResponse),
        );

        // Check the bank account registration has been created in the database
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy([
                'username' => self::USER_REGULAR,
            ]);
        $expected = $this->entityManager
            ->getRepository(BankAccount::class)
            ->findOneBy([
                'user' => $user->getId(), // can also just use the user object and not the id
                'accountNumber' => $requestBody['accountNumber'],
                'bankIdentifierCode' => $requestBody['bic'],
            ]);
        $this->assertEquals($expected->getId(), $apiResponse['id']);
        $this->assertEquals($expected->getDisplayName(), $apiResponse['displayName']);
        $this->assertEquals('GBP GB _ 6722', $apiResponse['displayName']);
        $this->assertEquals('41066722', $apiResponse['accountNumber']);
        $this->assertEquals('040004', $apiResponse['bic']);
        $this->assertEquals('GB', $apiResponse['country']);
        $this->assertNull($apiResponse['providerId']);
        $this->assertEquals($expected->getId(), $apiResponse['id']);

        // Check an email confirmation has been sent
        $messages = $this->getMessages();
        $this->assertCount(2, $messages);
        // Customer email
        $this->assertEquals(
            'Your bank account registration has been received',
            $messages[0]->subject,
        );
        $this->assertEquals('<noreply@yielders.co.uk>', $messages[0]->sender);
        $this->assertContains('<' . self::USER_REGULAR . '>', $messages[0]->recipients);
        // BizOps team email
        $this->assertEquals(
            'Bank account registration ready for review',
            $messages[1]->subject,
        );
        $this->assertEquals('<noreply@yielders.co.uk>', $messages[1]->sender);
        $this->assertContains(
            '<' . $_ENV['MAILER_TEAM_ADDRESS'] . '>',
            $messages[1]->recipients,
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateSelfBankAccountActionCompletion(): void
    {
        // We'll create an approved bank account first
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy([
                'username' => self::USER_LOW_BALANCE,
            ]);
        $bar = new BankAccount();
        $bar->setUser($user);
        $bar->setStatus(BankAccountStatus::Pending);
        $bar->setCountry('GB');
        $bar->setAccountHolderType(BankAccountHolderType::Personal);
        $bar->setAccountType(BankAccountType::GB);
        $bar->setAccountNumber('55779911');
        $bar->setBankIdentifierCode('200000');
        $bar->setDescription('APIv1 activate bank account registration automated test');
        $bar->setMetadata(['actionRequests' => [ActionRequest::ProofAddress]]);
        $this->entityManager->persist($bar);
        $this->entityManager->flush();

        $this->loginApiClientUser(self::USER_LOW_BALANCE);
        $uri =
            self::API_PATH_PREFIX_V1
            . "/self/bank-accounts/{$bar->getId()}/action-completion";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $requestBody = [
            'actionRequests' => [ActionRequest::ProofAddress],
        ];
        $content = json_encode($requestBody);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::BANK_ACCOUNT_REGISTRATION,
            array_keys($apiResponse),
        );
        // The action should be cleared
        $this->assertEmpty($apiResponse['metadata']);

        // BizOps should be notified once all actionRequests are cleared
        $messages = $this->getMessages();
        $this->assertCount(1, $messages);
        $this->assertEquals(
            'Bank account registration ready for review',
            $messages[0]->subject,
        );
        $this->assertEquals('<noreply@yielders.co.uk>', $messages[0]->sender);
        $this->assertContains(
            '<' . $_ENV['MAILER_TEAM_ADDRESS'] . '>',
            $messages[0]->recipients,
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateSelfBankAccountActionCompletionPartial(): void
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
        $bar->setAccountHolderType(BankAccountHolderType::Personal);
        $bar->setAccountType(BankAccountType::GB);
        $bar->setAccountNumber('55779911');
        $bar->setBankIdentifierCode('200000');
        $bar->setDescription('APIv1 activate bank account registration automated test');
        $bar->setMetadata(['actionRequests' => [
            ActionRequest::ProofAddress,
            ActionRequest::ProofId,
        ]]);
        $this->entityManager->persist($bar);
        $this->entityManager->flush();

        $this->loginApiClientUser(self::USER_LOW_BALANCE);
        $uri =
            self::API_PATH_PREFIX_V1
            . "/self/bank-accounts/{$bar->getId()}/action-completion";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $requestBody = [
            'actionRequests' => [ActionRequest::ProofAddress],
        ];
        $content = json_encode($requestBody);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::BANK_ACCOUNT_REGISTRATION,
            array_keys($apiResponse),
        );
        // The requested action should be cleared, others remain
        $this->assertEqualsCanonicalizing(
            ['actionRequests' => [ActionRequest::ProofId->value]],
            $apiResponse['metadata'],
        );

        // No emails should be sent to BizOps if there are still outstanding actionRequests
        $messages = $this->getMessages();
        $this->assertCount(0, $messages);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateSelfBankAccountActionCompletionAlreadyPassedReview(): void
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
        $bar->setAccountHolderType(BankAccountHolderType::Personal);
        $bar->setAccountType(BankAccountType::GB);
        $bar->setAccountNumber('55779911');
        $bar->setBankIdentifierCode('200000');
        $bar->setDescription('APIv1 activate bank account registration automated test');
        $bar->setMetadata(['actionRequests' => [ActionRequest::ProofAddress]]);
        $this->entityManager->persist($bar);
        $this->entityManager->flush();

        $this->loginApiClientUser(self::USER_LOW_BALANCE);
        $uri =
            self::API_PATH_PREFIX_V1
            . "/self/bank-accounts/{$bar->getId()}/action-completion";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $requestBody = [
            'actionRequests' => [ActionRequest::ProofAddress],
        ];
        $content = json_encode($requestBody);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::BANK_ACCOUNT_REGISTRATION,
            array_keys($apiResponse),
        );
        // The action should be cleared
        $this->assertEmpty($apiResponse['metadata']);

        // No emails should be sent for registrations that don't need a review, e.g. approved or active accounts
        $messages = $this->getMessages();
        $this->assertCount(0, $messages);
    }
}
