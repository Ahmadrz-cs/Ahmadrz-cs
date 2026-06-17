<?php

namespace App\Tests\Controller\ApiV1\Self;

use App\Entity\BankAccount;
use App\Entity\Enum\BankAccountHolderType;
use App\Entity\Enum\BankAccountStatus;
use App\Entity\Enum\BankAccountType;
use App\Entity\Enum\ScaStatus;
use App\Entity\User;
use App\Test\ExternalServiceWebTestCase;
use App\Tests\Controller\ApiV1\ApiV1ResponseFields;
use App\Tests\Controller\ApiV2\ApiBase64Files;
use Symfony\Component\HttpFoundation\Response;

class SelfPostResponseTest extends ExternalServiceWebTestCase
{
    public static function createDocTypeProvider(): \Generator
    {
        yield 'PNG' => ['test.png', 'image/png', ApiBase64Files::TEST_PNG];
        yield 'BMP' => ['test.bmp', 'image/bmp', ApiBase64Files::TEST_BMP];
        yield 'JPEG' => ['test.jpg', 'image/jpeg', ApiBase64Files::TEST_JPG];
        yield 'PDF' => ['test.pdf', 'application/pdf', ApiBase64Files::TEST_PDF];
        yield 'DOC' => [
            'test.doc',
            'application/vnd.ms-word',
            ApiBase64Files::TEST_DOC,
        ];
        yield 'XLSX' => [
            'test.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ApiBase64Files::TEST_XLSX,
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('createDocTypeProvider')]
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateSelfDocumentTypes(
        string $name,
        string $type,
        string $doc,
    ): void {
        $this->loginApiClientUser(self::USER_SUPER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/self/documents';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'tag' => 'proof_of_identity',
            'file_name' => $name,
            'file_type' => $type,
            'document_content' => $doc,
        ]);

        // Create the document
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        // Check the document has been saved
        $newDocId = $apiResponse['data']['document_id'];
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $documentIds = array_map(
            fn($item): int => $item['id'],
            $apiResponse['data']['list'],
        );
        $this->assertTrue(in_array($newDocId, $documentIds));
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateSelfMangoPayCardRegLink(): void
    {
        // Note that we don't store this information
        // It is used by the mangopayCards api call to register the credit card
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if (!$useRemoteTests) {
            $this->useMangopayServiceMock(self::MANGOPAY_CARD_REGISTRATION);
        }
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V1 . '/self/mangopayCardRegister';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'currency' => 'GBP',
            'card_type' => 'CB_VISA_MASTERCARD',
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $this->assertArrayHasKey('data', $apiResponse);
        $this->assertArrayHasKey('card_registration', $apiResponse['data']);
        $object = $apiResponse['data']['card_registration'];
        $this->assertArrayHasKey('id', $object);
        $this->assertArrayHasKey('access_key', $object);
        $this->assertArrayHasKey('preregistration_data', $object);
        $this->assertArrayHasKey('card_registration_url', $object);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateSelfMangopayCardValidation(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if (!$useRemoteTests) {
            $this->useMangopayServiceMock(self::MANGOPAY_CARD_VALIDATION);
        }
        $this->loginApiClientUser(self::USER_REGULAR);

        // Register the card first
        $uri = self::API_PATH_PREFIX_V1 . '/self/mangopayCardRegister';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'currency' => 'GBP',
            'card_type' => 'CB_VISA_MASTERCARD',
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        // Send verification request to the card processor
        if ($useRemoteTests) {
            //We only want to do the curl fetch when we are running remote tests
            $myVars =
                'data='
                . urlencode(
                    $apiResponse['data']['card_registration']['preregistration_data'],
                )
                . '&accessKeyRef='
                . urlencode($apiResponse['data']['card_registration']['access_key'])
                //    .'&returnURL='.urlencode('https://www.yoursite.com/mangopay-card-return')
                . '&cardNumber='
                . urlencode('3569990000000132')
                . '&cardExpirationDate='
                . urlencode('0119')
                . '&cardCvx123='
                . urlencode('123');

            $ch = curl_init(
                $apiResponse['data']['card_registration']['card_registration_url'],
            );
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $myVars);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-type: application/json']); // Assuming you're requesting JSON
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            $cardValidationData = curl_exec($ch);
            curl_close($ch);
        } else {
            $cardValidationData = 'testingLocallyOnly';
        }

        // Update the Mangopay registered card
        $uri = self::API_PATH_PREFIX_V1 . '/self/mangopayCards';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'data' => $cardValidationData,
            'card_registration_id' => $apiResponse['data']['card_registration']['id'],
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
        $this->assertArrayHasKey('card_id', $apiResponse['data']);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateSelfMangopayFundsByCard(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if (!$useRemoteTests) {
            $cardId = '19148323';
            $this->useMangopayServiceMock(self::MANGOPAY_CARD_PAYIN);
        } else {
            $this->fail('Remote tests not implemented yet');
        }
        $this->loginApiClientUser(self::USER_REGULAR);

        // Add funds via card
        $sample = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR,
        ])[0];
        $uri = self::API_PATH_PREFIX_V1 . "/self/mangopayCards/{$cardId}/payin";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'userId' => $sample->getId(),
            'amount' => 1000,
            'secureModeReturnUrl' => 'https://www.example.com/card-return',
            'ipAddress' => '1.1.1',
            'browserInfo' => [
                'acceptHeader' => 'example',
                'userAgent' => 'example',
                'language' => 'English',
                'screenWidth' => 10,
                'screenHeight' => 10,
                'colorDepth' => 100,
                'timeZoneOffset' => '+1',
                'javaEnabled' => true,
                'javascriptEnabled' => true,
            ],
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $this->assertEquals('success', $apiResponse['outcome']);
        $this->assertArrayHasKey('data', $apiResponse);
        $this->assertArrayHasKey('SecureModeRedirectURL', $apiResponse['data']);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateSelfMangopayKycCheck(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if (!$useRemoteTests) {
            $this->useMangopayServiceMock(self::MANGOPAY_KYC_CHECK);
        } else {
            $this->fail('Remote tests not implemented yet');
        }
        $this->loginApiClientUser(self::USER_REGULAR);

        // Trigger a kyc check based on existing user's information
        $uri = self::API_PATH_PREFIX_V1 . '/self/mangopayKycCheck';
        $this->client->request('POST', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $this->assertEquals('success', $apiResponse['outcome']);
        $this->assertArrayHasKey('data', $apiResponse);
        $this->assertArrayHasKey('kyc_id', $apiResponse['data']);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateSelfMangopayWallet(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if (!$useRemoteTests) {
            $this->useMangopayServiceMock(self::MANGOPAY_CREATE_WALLET);
        } else {
            $this->fail('Remote tests not implemented yet');
        }

        /** @var User $testUser */
        $testUser = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_REGULAR]);
        // Create only works if user does not have an existing MangopayUserId
        $testUser->setMangoPayWalletId(null);

        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V1 . '/self/mangopayWallets';
        $this->client->request('POST', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
        $this->assertEquals('success', $apiResponse['outcome']);

        // Check the new wallet has been created and the id saved to that user
        $sample = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR,
        ])[0];
        $this->assertEquals(
            $sample->getMangoPayWalletId(),
            $apiResponse['data']['mangopay_wallet_id'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    #[\PHPUnit\Framework\Attributes\Group('mangopay')]
    public function testCreateSelfBankAccountActivateDeactivateFlow(): void
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
        $this->entityManager->persist($bar);
        $this->entityManager->flush();

        $this->loginApiClientUser(self::USER_LOW_BALANCE);
        $uri =
            self::API_PATH_PREFIX_V1 . "/self/bank-accounts/{$bar->getId()}/activation";
        $this->client->request('POST', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::SCA_ACTION,
            array_keys($apiResponse),
        );
        // Refresh bank account registration from the database
        $bar = $this->entityManager
            ->getRepository(BankAccount::class)
            ->find($bar->getId());
        $this->assertEquals($apiResponse['id'], $bar->getId());
        $this->assertNotNull($bar->getProviderId());
        $this->assertEquals($apiResponse['providerId'], $bar->getProviderId());
        $this->assertEquals(BankAccountStatus::Approved->value, $apiResponse['status']);
        $this->assertNotEmpty($apiResponse['pendingUserAction']);
        // print_r($apiResponse);

        $uri =
            self::API_PATH_PREFIX_V1
            . "/self/bank-accounts/{$bar->getId()}/activation-outcome";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $requestBody = [
            'success' => true,
            'verify' => false,
        ];
        $content = json_encode($requestBody);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::BANK_ACCOUNT_REGISTRATION,
            array_keys($apiResponse),
        );
        $this->assertEquals(BankAccountStatus::Active->value, $apiResponse['status']);
        // Account details should be cleared on activation
        $this->assertEmpty($apiResponse['accountNumber']);
        $this->assertEmpty($apiResponse['bic']);
        // print_r($apiResponse);

        $uri = self::API_PATH_PREFIX_V1 . "/self/bank-accounts/{$bar->getId()}";
        $this->client->request('DELETE', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::BANK_ACCOUNT_REGISTRATION,
            array_keys($apiResponse),
        );
        $this->assertEquals(BankAccountStatus::Closed->value, $apiResponse['status']);

        // print_r($apiResponse);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateSelfBankAccountActivationOutcomeFail(): void
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
        $this->entityManager->persist($bar);
        $this->entityManager->flush();

        $this->loginApiClientUser(self::USER_LOW_BALANCE);
        $uri =
            self::API_PATH_PREFIX_V1
            . "/self/bank-accounts/{$bar->getId()}/activation-outcome";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $requestBody = [
            'success' => false,
            'verify' => false,
        ];
        $content = json_encode($requestBody);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::BANK_ACCOUNT_REGISTRATION,
            array_keys($apiResponse),
        );
        $this->assertEquals(BankAccountStatus::Closed->value, $apiResponse['status']);
        // Account details should be cleared on activation
        $this->assertEmpty($apiResponse['accountNumber']);
        $this->assertEmpty($apiResponse['bic']);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateSelfScaEnrollment(): void
    {
        $startTime = new \DateTime();
        $this->loginApiClientUser(self::USER_STAMP_DUTY);
        $uri = self::API_PATH_PREFIX_V1 . '/self/sca/enroll';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $this->client->request('POST', $uri, [], [], $headers);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::SCA_ENROLLMENT,
            array_keys($apiResponse),
        );

        // Check the redirectUrl has been provided by the mangopay API
        $this->assertStringContainsString(
            'https://sca.sandbox.mangopay.com/?token=',
            $apiResponse['PendingUserAction']['RedirectUrl'],
        );

        // Check Sca tracking fields have been updated
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy([
                'username' => self::USER_STAMP_DUTY,
            ]);
        $this->assertEquals(ScaStatus::Pending, $user->getScaStatus());
        // Use getTimestamp to get the time to the second rather than microsecond
        // PHP Datetime has microseconds, but microseconds are lost when we store in the database
        $this->assertGreaterThanOrEqual(
            $startTime->getTimestamp(),
            $user->getScaEnrolledAt()->getTimestamp(),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    #[\PHPUnit\Framework\Attributes\Group('mangopay')]
    public function testCreateSelfBankAccountMangopaySync(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR_2);
        // Make sure this user has no existing registrations
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy([
                'username' => self::USER_REGULAR_2,
            ]);
        $existingAccounts = $this->entityManager
            ->getRepository(BankAccount::class)
            ->findBy([
                'user' => $user->getId(), // can also just use the user object and not the id
            ]);
        $this->assertEmpty($existingAccounts);

        $uri = self::API_PATH_PREFIX_V1 . '/self/bank-accounts/mangopay-sync';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $requestBody = ['limit' => 1];
        $content = json_encode($requestBody);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $apiResponse);
        $apiResponse = $apiResponse[0];
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::BANK_ACCOUNT_REGISTRATION,
            array_keys($apiResponse),
        );
        // Check the bank account registration has been created in the database
        $expected = $this->entityManager
            ->getRepository(BankAccount::class)
            ->findOneBy([
                'user' => $user->getId(), // can also just use the user object and not the id
            ]);
        $this->assertNull($apiResponse['accountNumber']);
        $this->assertNull($apiResponse['bic']);
        $this->assertNotNull($apiResponse['id']);
        $this->assertNotNull($apiResponse['uuid']);
        $this->assertNotNull($apiResponse['displayName']);
        $this->assertNotNull($apiResponse['country']);
        $this->assertNotNull($apiResponse['providerId']);
        $this->assertEquals($expected->getId(), $apiResponse['id']);
        $this->assertEquals($expected->getUuid(), $apiResponse['uuid']);
        $this->assertEquals($expected->getDisplayName(), $apiResponse['displayName']);
        $this->assertEquals(
            $expected->getAccountNumber(),
            $apiResponse['accountNumber'],
        );
        $this->assertEquals($expected->getBankIdentifierCode(), $apiResponse['bic']);
        $this->assertEquals($expected->getCountry(), $apiResponse['country']);
        $this->assertEquals($expected->getProviderId(), $apiResponse['providerId']);
        $this->assertEquals($expected->getSTatus()->value, $apiResponse['status']);
        $this->assertEquals(BankAccountStatus::Active->value, $apiResponse['status']);

        // Subsequent syncs should return success but empty list (no new registrations created)
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEmpty($apiResponse);
    }
}
