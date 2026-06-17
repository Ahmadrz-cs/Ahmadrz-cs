<?php

namespace App\Tests\Controller\ApiV1\User;

use App\Entity\Asset;
use App\Entity\Transaction;
use App\Entity\User;
use App\Test\ExternalServiceWebTestCase;
use App\Tests\Controller\ApiV1\ApiV1ResponseFields;
use Symfony\Component\HttpFoundation\Response;

class UserPostResponseTest extends ExternalServiceWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateUserMangoPayAccountRegister(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if (!$useRemoteTests) {
            $this->useMangopayServiceMock(self::MANGOPAY_CREATE_USER_SCA);
        } else {
            $this->fail('Remote tests not implemented yet');
        }

        /** @var User $testUser */
        $testUser = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_REGULAR]);
        // Create only works if user does not have an existing MangopayUserId
        $testUser->setMangoPayUserId(null);

        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR,
        ])[0];
        $uri =
            self::API_PATH_PREFIX_V1 . "/users/{$sample->getId()}/mangopayRegisterSca";
        $this->client->request('POST', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
        $this->assertEquals('success', $apiResponse['outcome']);

        // Check the new wallet has been created and the id saved to that user
        // Get the user again as it will be updated
        $sample = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR,
        ])[0];
        $this->assertEquals(
            $sample->getMangoPayUserId(),
            $apiResponse['data']['mangopay_id'],
        );
        $this->assertArrayHasKey('kyclevel', $apiResponse['data']);
        $this->assertNotEmpty($apiResponse['data']['kyclevel']);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateUserMangopayWalletRegister(): void
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
        $sample = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR,
        ])[0];
        $uri =
            self::API_PATH_PREFIX_V1
            . "/users/{$sample->getId()}/mangopayWalletRegister";
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
    public function testCreateUserBankWirePayin(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if (!$useRemoteTests) {
            $this->useMangopayServiceMock(self::MANGOPAY_BANKWIRE_PAYIN);
        } else {
            $this->fail('Remote tests not implemented yet');
        }
        $this->loginApiClientUser(self::USER_REGULAR);

        // Add funds via bankwire
        $sample = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR,
        ])[0];
        $uri =
            self::API_PATH_PREFIX_V1
            . "/users/{$sample->getId()}/mangopayWalletPayinBankWire/{$sample->getMangoPayWalletId()}";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'amount' => '10000',
            'currency' => 'GBP',
            'fee_amount' => '100',
            'user_wallet_id' => $sample->getMangoPayWalletId(),
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $this->assertEquals('success', $apiResponse['outcome']);
        $object = $apiResponse['data']['bank_account'];
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::WALLET_BANKWIRE_PAYIN,
            array_keys($object),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateUserTransfer(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if (!$useRemoteTests) {
            $this->useMangopayServiceMock(self::MANGOPAY_CREATE_TRANSFER);
        } else {
            $this->fail('Remote tests not implemented yet');
        }
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR,
        ])[0];
        $assetSample = $this->searchFixtures(Asset::class, [
            'name' => 'Clarence Hold A - Camden',
        ])[0];
        $transfer = [
            'amount' => '1000',
            'currency' => 'GBP',
            'fee_amount' => '100',
            'user_wallet_id' => $sample->getMangoPayWalletId(),
            'org_wallet_id' => $assetSample->getMangoPayWalletId(),
        ];
        $uri = self::API_PATH_PREFIX_V1 . "/users/{$sample->getId()}/mangopayTransfer";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode($transfer);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
        $this->assertEquals('success', $apiResponse['outcome']);

        $object = $apiResponse['data']['transfer'];
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::WALLET_TRANSFER,
            array_keys($object),
        );
        $this->assertEquals($transfer['amount'], $object['debited_funds']);
        $this->assertEquals(
            $transfer['amount'] - $transfer['fee_amount'],
            $object['credited_funds'],
        );

        // Transfers are also recorded in our own database
        $this->assertArrayHasKey('internal_transaction_id', $apiResponse['data']);
        $transactionRecord = $this->searchFixtures(Transaction::class, [
            'id' => $apiResponse['data']['internal_transaction_id'],
        ])[0];
        $this->assertEquals(
            $object['debited_funds'],
            $transactionRecord->getValueAmount(),
        );
        $this->assertEquals(
            $object['debited_funds'],
            $transactionRecord->getValueAmount(),
        );
        $this->assertEquals(100, $transactionRecord->getFeeAmount());
        $this->assertEquals(
            $sample->getMangoPayWalletId(),
            $transactionRecord->getDebitedWalletId(),
        );
        $this->assertEquals(
            $assetSample->getMangoPayWalletId(),
            $transactionRecord->getCreditedWalletId(),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testScenarioCombineTransferAndInvestment(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if (!$useRemoteTests) {
            $this->useMangopayServiceMock(self::MANGOPAY_CREATE_TRANSFER);
        } else {
            $this->fail('Remote tests not implemented yet');
        }
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR,
        ])[0];
        $assetSample = $this->searchFixtures(Asset::class, [
            'name' => 'Clarence Hold A - Camden',
        ])[0];
        $transfer = [
            'amount' => '1000',
            'currency' => 'GBP',
            'fee_amount' => '100',
            'user_wallet_id' => $sample->getMangoPayWalletId(),
            'org_wallet_id' => $assetSample->getMangoPayWalletId(),
        ];
        $uri = self::API_PATH_PREFIX_V1 . "/users/{$sample->getId()}/mangopayTransfer";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode($transfer);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
        $this->assertEquals('success', $apiResponse['outcome']);

        $object = $apiResponse['data']['transfer'];
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::WALLET_TRANSFER,
            array_keys($object),
        );

        $transaction = $this->searchFixtures(Transaction::class, [
            'id' => $apiResponse['data']['internal_transaction_id'],
        ])[0];

        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(
            \App\Entity\Offering::class,
            [
                'status' => \App\Entity\Lifecycle\OfferingLifecycle::STATE_PUBLISHED,
            ],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/offerings/$sample/investments";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'investment_amount' => 147.6,
            'info' => [
                'some_invest' => 'somewhere',
                'share_amount' => 123,
                'org_price_per_share' => 1.20,
                'transaction_id' => $transaction->getReferenceId(),
                'for_sale' => 1,
            ],
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $uri =
            self::API_PATH_PREFIX_V1
            . "/investments/{$apiResponse['data']['investment_id']}";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $object = $apiResponse['data']['investment']['custom'];
        $this->assertEquals(123, $object['share_amount']);
        $this->assertEquals(1.20, $object['org_price_per_share']);
        $this->assertEquals($transaction->getReferenceId(), $object['transaction_id']);
        $this->assertEquals(1, $object['for_sale']);
        // Unsupported keys not returned
        $this->assertArrayNotHasKey('some_invest', $object);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateUserUkBankAccount(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if (!$useRemoteTests) {
            $this->useMangopayServiceMock(self::MANGOPAY_CREATE_BANK_ACCOUNT_GB);
        } else {
            $this->fail('Remote tests not implemented yet');
        }

        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(
            User::class,
            ['username' => self::USER_REGULAR],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/users/{$sample}/bankaccounts/GB";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'account_number' => '70872490', //- the Bank Account's Account Number
            'sort_code' => '404784', //- the Bank Account's Sort Code, DONT USE '-' (dashes) HERE!
            'owner_name' => 'Jon Doe', // - the full name of the User who owns the Bank Account
            'address_line1' => '1 London Road',
            'address_line2' => '',
            'city' => 'London',
            'region' => '',
            'postcode' => 'E1 1RD',
            'country' => 'GB',
            'type' => 'GB',
            'owner_address' => 'Manchester House, 1 London Road, line3 , London, England, GB, E1', // - the full address of the User who owns the Bank Account
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
        $this->assertEquals('success', $apiResponse['outcome']);

        $object = $apiResponse['data']['bank_account'];
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::BANK_ACCOUNT_GB,
            array_keys($object),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateUserEuBankAccount(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if (!$useRemoteTests) {
            $this->useMangopayServiceMock(self::MANGOPAY_CREATE_BANK_ACCOUNT_IBAN);
        } else {
            $this->fail('Remote tests not implemented yet');
        }

        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(
            User::class,
            ['username' => self::USER_REGULAR],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/users/{$sample}/bankaccounts/IBAN";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'IBAN' => 'FR3020041010124530725S03383', //- the Bank Account's Account Number
            'BIC' => 'CRLYFRPP', //- the Bank Account's Sort Code, DONT USE '-' (dashes) HERE!
            'owner_name' => 'Jon Doe', // - the full name of the User who owns the Bank Account
            'address_line1' => '1 London Road',
            'address_line2' => '',
            'city' => 'London',
            'region' => '',
            'postcode' => 'E1 1RD',
            'country' => 'GB',
            'type' => 'IBAN',
            'owner_address' => 'Manchester House, 1 London Road, line3 , London, England, GB, E1', // - the full address of the User who owns the Bank Account
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
        $this->assertEquals('success', $apiResponse['outcome']);

        $object = $apiResponse['data']['bank_account'];
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::BANK_ACCOUNT_IBAN,
            array_keys($object),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateUserPayoutBankwire(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if (!$useRemoteTests) {
            $this->useMangopayServiceMock(self::MANGOPAY_BANKWIRE_PAYOUT);
        } else {
            $this->fail('Remote tests not implemented yet');
        }
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR,
        ])[0];

        if ($useRemoteTests) {
            // Get user's bank accounts if doing remote tests
            $uri = self::API_PATH_PREFIX_V1 . "/users/{$sample->getId()}/bankaccounts";
            $this->client->request('GET', $uri);
            $this->assertResponseIsSuccessful();
            $apiResponse = json_decode(
                $this->client->getResponse()->getContent(),
                true,
            );
            $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
            // Just pick the first one
            $walletBankAccountId = $apiResponse['data']['bank_accounts'][0]['id'];
        }

        // Withdraw funds via bankwire
        $uri =
            self::API_PATH_PREFIX_V1
            . "/users/{$sample->getId()}/mangopayWalletPayoutBankWire/{$sample->getMangoPayWalletId()}";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'amount' => '1000',
            'bank_account_id' => $walletBankAccountId ?? '1234',
            'currency' => 'GBP',
            'fee_amount' => '100',
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $this->assertEquals('success', $apiResponse['outcome']);
        $object = $apiResponse['data']['bank_account'];
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::WALLET_BANKWIRE_PAYOUT,
            array_keys($object),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateUserPayinUnregisteredCard(): void
    {
        // Direct card payin without pre-registered card
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if (!$useRemoteTests) {
            $this->useMangopayServiceMock(self::MANGOPAY_CARD_WEB_PAYIN);
        } else {
            $this->fail('Remote tests not implemented yet');
        }
        $this->loginApiClientUser(self::USER_REGULAR);

        // Add funds via card
        $sample = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR,
        ])[0];
        $uri =
            self::API_PATH_PREFIX_V1
            . "/users/{$sample->getId()}/mangopayWalletPayin/{$sample->getMangoPayWalletId()}";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'amount' => '5000',
            'currency' => 'GBP',
            'card_type' => 'CB_VISA_MASTERCARD',
            'callback_url' => 'https://example.com/callback',
            'fee_amount' => '0',
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $this->assertEquals('success', $apiResponse['outcome']);
        $this->assertArrayHasKey('data', $apiResponse);
        $this->assertArrayHasKey('RedirectURL', $apiResponse['data']);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateUserMangopayKycCheck(): void
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
        $sample = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR,
        ])[0];
        $uri = self::API_PATH_PREFIX_V1 . "/users/{$sample->getId()}/mangopayKycCheck";
        $this->client->request('POST', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $this->assertEquals('success', $apiResponse['outcome']);
        $this->assertArrayHasKey('data', $apiResponse);
        $this->assertArrayHasKey('kyc_id', $apiResponse['data']);
    }

    // /**
    //  * @group response
    //  */
    // public function testCreateUserDocument()
    // {
    //     // if fails run git status, check for deleted files and then git checkout -- web/apple-touch-icon.png
    //     $client = static::createAuthenticatedApiClient();
    //     /** @var User $currentUser */
    //     $currentUser = $this->getUser();
    //     $testDocumentName = 'Test Document Name';
    //     $testDocumentType = 'Test Document Type';
    //     $kernelRootDir = $client->getContainer()->getParameter('kernel.root_dir');
    //     //make sure we have a file to test loading up
    //     $sourcefile = $kernelRootDir . '/../web/uploads/documents/apple-touch-icon.png';
    //     $distfile = $kernelRootDir . '/../web/apple-touch-icon.png';
    //     copy($sourcefile, $distfile);
    //     $document = new UploadedFile(
    //         $kernelRootDir . '/../web/apple-touch-icon.png',
    //         'apple-touch-icon.png',
    //         'image/png',
    //         filesize($kernelRootDir . '/../web/apple-touch-icon.png')
    //     );
    //     $client->request(
    //         'POST',
    //         $this->getAPINetworkPath() . '/users/' . $currentUser->getId() . '/files',
    //         [
    //             'form'  => [
    //                 'name'  => $testDocumentName,
    //                 'type'  => $testDocumentType
    //             ]
    //         ],
    //         [
    //             'form'  => [
    //                 'file'  => $document
    //             ]
    //         ]
    //     );
    //     $this->assertEquals(Response::HTTP_CREATED, $client->getResponse()->getStatusCode());
    //     //bit of file clean up to put the file back for the next test run
    //     $sourcefile = $kernelRootDir . '/../web/uploads/documents/apple-touch-icon.png';
    //     $distfile = $kernelRootDir . '/../web/apple-touch-icon.png';
    //     copy($sourcefile, $distfile);
    // }
}
