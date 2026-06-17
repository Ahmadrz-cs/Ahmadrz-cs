<?php

namespace App\Tests\Service;

use App\Dto\BrowserInfoDTO;
use App\Dto\CardPayinDTO;
use App\Entity\Asset;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Investment;
use App\Entity\Offering;
use App\Entity\TradeOrder;
use App\Entity\User;
use App\Exception\ApiException;
use App\Service\MangoPay;
use App\Service\PaymentService;
use App\Service\Util\Helper;
use App\Test\ExternalServiceWebTestCase;
use App\Test\Util\EntityIdTestUtil;
use BcMath\Number;
use MangoPay\Client;
use MangoPay\TransactionStatus;
use MangoPay\Transfer;
use MangoPay\UserNaturalSca;
use MangoPay\Wallet;
use Psr\Log\LoggerInterface;

class MangoPayTest extends ExternalServiceWebTestCase
{
    public function testMangopayConfig(): void
    {
        $service = static::getContainer()->get(MangoPay::class);
        $config = $service->getMangoPayApi()->Config;
        // Should by default be in sandbox mode
        $this->assertStringContainsString('sandbox', $config->BaseUrl);
    }

    public function testCanGetClient(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if ($useRemoteTests) {
            /** @var MangoPay $service */
            $service = static::getContainer()->get(MangoPay::class);

            /** @var Client $client */
            $client = $service->getClient();
            $this->assertInstanceOf(Client::class, $client);
            $this->assertEquals($_ENV['MANGOPAY_ID'], $client->ClientId);
        } else {
            // We don't want to contact mango pay so we skip this test
            $this->assertTrue(true);
        }
    }

    public function testCreateMangoPayNaturalUserSca(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('salesforce_remote_tests');
        if (!$useRemoteTests) {
            $this->useMangopayServiceMock(self::MANGOPAY_CREATE_USER_SCA);
        }
        /** @var MangoPay $service */
        $service = static::getContainer()->get(MangoPay::class);

        /** @var User $testUser */
        $testUser = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_REGULAR]);
        // Create only works if user does not have an existing MangopayUserId
        $testUser->setMangoPayUserId(null);

        /** @var UserNaturalSca $mangoPayUser */
        $mangoPayUser = $service->createNaturalUser($testUser);

        $this->assertInstanceOf(UserNaturalSca::class, $mangoPayUser);
        if (!$useRemoteTests) {
            $this->assertNotNull($mangoPayUser->FirstName);
            $this->assertNotNull($mangoPayUser->LastName);
            $this->assertNotNull($mangoPayUser->Nationality);
            $this->assertNotNull($mangoPayUser->CountryOfResidence);
            $this->assertNotNull($mangoPayUser->Email);
            $this->assertNotNull($mangoPayUser->Tag);
            $this->assertNotNull($mangoPayUser->Id);
        } else {
            $this->assertEquals($testUser->getFirstname(), $mangoPayUser->FirstName);
            $this->assertEquals($testUser->getLastname(), $mangoPayUser->LastName);
            $this->assertEquals(
                $testUser->getNationality(),
                $mangoPayUser->Nationality,
            );
            $this->assertEquals(
                $testUser->getPassportCountry(),
                $mangoPayUser->CountryOfResidence,
            );
            $this->assertEquals($testUser->getEmail(), $mangoPayUser->Email);
            $this->assertEquals($testUser->getId(), $mangoPayUser->Tag);
            $this->assertEquals(
                Helper::preparePhoneNumber($testUser->getPhone1()),
                $mangoPayUser->PhoneNumber,
            );
        }
        $this->assertNotNull($mangoPayUser->Id);

        // Check new MangoPay user differentiation fields
        $this->assertEquals('Owner', $mangoPayUser->UserCategory);
        $this->assertTrue($mangoPayUser->TermsAndConditionsAccepted);
    }

    public function testCreateMangoPayWallet(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('salesforce_remote_tests');
        if (!$useRemoteTests) {
            $this->useMangopayServiceMock(self::MANGOPAY_CREATE_WALLET);
        }
        /** @var MangoPay $service */
        $service = static::getContainer()->get(MangoPay::class);

        /** @var User $testUser */
        $testUser = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_REGULAR]);
        // Create only works if user does not have an existing MangopayWalletId
        $testUser->setMangoPayWalletId(null);

        /** @var \MangoPay\Wallet */
        $mangoPayUserWallet = $service->createUserWallet($testUser);

        // Check mangopaywalletid looks good
        $this->assertNotNull($mangoPayUserWallet->Id);
    }

    public function testCreateInvestmentTransfer(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('salesforce_remote_tests');

        $user = EntityIdTestUtil::setEntityId(new User(), 5146);
        $user->setMangoPayUserId('user_m_01JVCBBBVNSJYSW61R1W28TEST');
        $user->setMangoPayWalletId('wlt_m_01HW3EFRH8GM978NHCM8ZGTEST');
        $asset = new Asset();
        $asset->setName('Test Investment Asset');
        $asset->setCompanyNumber('SPVTEST50007821');
        $asset->setHoldWalletId('wlt_m_01HW3DD8S6MFPYGVC0FPBHTEST');
        $offering = new Offering();
        $offering->setAsset($asset);
        $investment = EntityIdTestUtil::setEntityId(new Investment(), 13681);
        $investment->setOffering($offering);
        $investment->setUser($user);
        $investment->setInvestmentValue('1065.88');

        $expectedTag = 'Transfer: wlt_m_01HW3EFRH8GM978NHCM8ZGTEST to wlt_m_01HW3DD8S6MFPYGVC0FPBHTEST;AstName:Test Investment Asset;AstCode:SPVTEST50007821;Type:Investment';

        if (!$useRemoteTests) {
            $mangopayServiceMock = $this->createMangopayServiceMock([
                'executeMangopayTransfersCreate',
            ]);
            $this->client->disableReboot();
            // Override the MangoPayService with our mock in the service container
            static::getContainer()->set(MangoPay::class, $mangopayServiceMock);

            $transfer = new Transfer();
            $debit = new \MangoPay\Money();
            $debit->Amount = 106588;
            $debit->Currency = 'GBP';
            $fee = new \MangoPay\Money();
            $fee->Amount = 0;
            $fee->Currency = 'GBP';
            $transfer->DebitedFunds = $debit;
            $transfer->Fees = $fee;
            $transfer->AuthorId = $user->getMangoPayUserId();
            $transfer->DebitedWalletId = $user->getMangoPayWalletId();
            $transfer->CreditedWalletId = $asset->getHoldWalletId();
            $transfer->ScaContext = 'USER_PRESENT';
            $transfer->Tag = $expectedTag;

            $mangopayServiceMock
                ->expects($this->once())
                ->method('executeMangopayTransfersCreate')
                // Make sure the transfer created withjin the method matches the expected transfer we made above
                ->with($transfer)
                ->willReturn($transfer);
        }
        /** @var MangoPay $service */
        $service = static::getContainer()->get(MangoPay::class);

        $actual = $service->createInvestmentTransfer(
            $investment,
            $investment->getInvestmentValue(),
            true,
        );

        $this->assertEquals($user->getMangoPayUserId(), $actual->AuthorId);
        $this->assertEquals($user->getMangoPayWalletId(), $actual->DebitedWalletId);
        $this->assertEquals($asset->getHoldWalletId(), $actual->CreditedWalletId);
        $this->assertEquals(106588, $actual->DebitedFunds->Amount);
        $this->assertEquals('GBP', $actual->DebitedFunds->Currency);
        $this->assertEquals($expectedTag, $actual->Tag);
        $this->assertEquals('USER_PRESENT', $actual->ScaContext);
    }

    public function testCreateRelistingFeeTransfer(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('salesforce_remote_tests');

        $user = EntityIdTestUtil::setEntityId(new User(), 5146);
        $user->setMangoPayUserId('user_m_01JVCBBBVNSJYSW61R1W28TEST');
        $user->setMangoPayWalletId('wlt_m_01HW3EFRH8GM978NHCM8ZGTEST');
        $asset = new Asset();
        $asset->setName('Test Investment Asset');
        $asset->setCompanyNumber('SPVTEST50007821');
        $asset->setHoldWalletId('wlt_m_01HW3DD8S6MFPYGVC0FPBHTEST');
        $offering = new Offering();
        $offering->setAsset($asset);
        $investment = EntityIdTestUtil::setEntityId(new Investment(), 13681);
        $investment->setOffering($offering);
        $investment->setUser($user);
        $investment->setInvestmentValue('1065.88');
        $offering->setSellInvestment($investment);

        $expectedTag = 'Transfer: wlt_m_01HW3EFRH8GM978NHCM8ZGTEST to wlt_m_01HW3DD8S6MFPYGVC0FPBHTEST;Type:Relisting Fee';

        if (!$useRemoteTests) {
            $mangopayServiceMock = $this->createMangopayServiceMock([
                'executeMangopayTransfersCreate',
            ]);
            $this->client->disableReboot();
            // Override the MangoPayService with our mock in the service container
            static::getContainer()->set(MangoPay::class, $mangopayServiceMock);

            $transfer = new Transfer();
            $debit = new \MangoPay\Money();
            $debit->Amount = 4000;
            $debit->Currency = 'GBP';
            $fee = new \MangoPay\Money();
            $fee->Amount = 0;
            $fee->Currency = 'GBP';
            $transfer->DebitedFunds = $debit;
            $transfer->Fees = $fee;
            $transfer->AuthorId = $user->getMangoPayUserId();
            $transfer->DebitedWalletId = $user->getMangoPayWalletId();
            $transfer->CreditedWalletId = $asset->getHoldWalletId();
            $transfer->ScaContext = 'USER_PRESENT';
            $transfer->Tag = $expectedTag;

            $mangopayServiceMock
                ->expects($this->once())
                ->method('executeMangopayTransfersCreate')
                // Make sure the transfer created withjin the method matches the expected transfer we made above
                ->with($transfer)
                ->willReturn($transfer);
        }
        /** @var MangoPay $service */
        $service = static::getContainer()->get(MangoPay::class);

        $actual = $service->createRelistingFeeTransfer($offering, 40, true);

        $this->assertEquals($user->getMangoPayUserId(), $actual->AuthorId);
        $this->assertEquals($user->getMangoPayWalletId(), $actual->DebitedWalletId);
        $this->assertEquals($asset->getHoldWalletId(), $actual->CreditedWalletId);
        $this->assertEquals(4000, $actual->DebitedFunds->Amount);
        $this->assertEquals('GBP', $actual->DebitedFunds->Currency);
        $this->assertEquals($expectedTag, $actual->Tag);
        $this->assertEquals('USER_PRESENT', $actual->ScaContext);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('createTradeOrderTransferProvider')]
    #[\PHPUnit\Framework\Attributes\Group('check')]
    public function testCreateTradeOrderTransfer(
        TradeDirection $direction,
        string $tagType,
    ): void {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('salesforce_remote_tests');

        $user = EntityIdTestUtil::setEntityId(new User(), 5146);
        $user->setMangoPayUserId('user_m_01JVCBBBVNSJYSW61R1W28TEST');
        $user->setMangoPayWalletId('wlt_m_01HW3EFRH8GM978NHCM8ZGTEST');
        $asset = new Asset();
        $asset->setName('Test Investment Asset');
        $asset->setCompanyNumber('SPVTEST50007821');
        $asset->setHoldWalletId('wlt_m_01HW3DD8S6MFPYGVC0FPBHTEST');
        $tradeOrder = new TradeOrder(
            direction: $direction,
            asset: $asset,
            user: $user,
            numberOfShares: 100,
            pricePerShare: new Number('1.12'),
            type: TradeOrderType::Market,
        );

        $expectedTag = "AstName:Test Investment Asset;AstCode:SPVTEST50007821;Type:{$tagType}";

        if (!$useRemoteTests) {
            $mangopayServiceMock = $this->createMangopayServiceMock([
                'executeMangopayTransfersCreate',
            ]);
            $this->client->disableReboot();
            // Override the MangoPayService with our mock in the service container
            static::getContainer()->set(MangoPay::class, $mangopayServiceMock);

            $transfer = new Transfer();
            $debit = new \MangoPay\Money();
            $debit->Amount = 106588;
            $debit->Currency = 'GBP';
            $fee = new \MangoPay\Money();
            $fee->Amount = 0;
            $fee->Currency = 'GBP';
            $transfer->DebitedFunds = $debit;
            $transfer->Fees = $fee;
            $transfer->AuthorId = $user->getMangoPayUserId();
            $transfer->DebitedWalletId = $user->getMangoPayWalletId();
            $transfer->CreditedWalletId = $asset->getHoldWalletId();
            $transfer->ScaContext = 'USER_PRESENT';
            $transfer->Tag = $expectedTag;

            $mangopayServiceMock
                ->expects($this->once())
                ->method('executeMangopayTransfersCreate')
                // Make sure the transfer created withjin the method matches the expected transfer we made above
                ->with($transfer)
                ->willReturn($transfer);
        }
        /** @var MangoPay $service */
        $service = static::getContainer()->get(MangoPay::class);
        $actual = $service->createTradeOrderTransfer($tradeOrder, '1065.88', true);

        $this->assertEquals($user->getMangoPayUserId(), $actual->AuthorId);
        $this->assertEquals($user->getMangoPayWalletId(), $actual->DebitedWalletId);
        $this->assertEquals($asset->getHoldWalletId(), $actual->CreditedWalletId);
        $this->assertEquals(106588, $actual->DebitedFunds->Amount);
        $this->assertEquals('GBP', $actual->DebitedFunds->Currency);
        $this->assertEquals($expectedTag, $actual->Tag);
        $this->assertEquals('USER_PRESENT', $actual->ScaContext);
    }

    public static function createTradeOrderTransferProvider(): \Generator
    {
        yield 'Buy - investment' => [TradeDirection::Buy, 'Investment'];
        yield 'Sell - relisting' => [TradeDirection::Sell, 'Relisting'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('unsupportedTradeOrderProvider')]
    public function testCreateTradeOrderTransferException(TradeOrderType $orderType): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 5146);
        $asset = new Asset();
        $tradeOrder = new TradeOrder(
            direction: TradeDirection::Buy,
            asset: $asset,
            user: $user,
            numberOfShares: 100,
            pricePerShare: new Number('1.12'),
            type: $orderType,
        );

        $this->expectExceptionMessage('create transfer for order of type');
        $this->expectException(\InvalidArgumentException::class);

        /** @var MangoPay $service */
        $service = static::getContainer()->get(MangoPay::class);
        $service->createTradeOrderTransfer($tradeOrder, '1065.88', true);
    }

    public static function unsupportedTradeOrderProvider(): \Generator
    {
        foreach (array_udiff(
            TradeOrderType::cases(),
            TradeOrderType::tradingBuyTypes(),
            fn($r1, $r2) => $r1->value <=> $r2->value,
        ) as $type) {
            yield "Order type {$type->value}" => [$type];
        }
    }

    public function testMangopayCreateUserBankAccounts(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('salesforce_remote_tests');
        if (!$useRemoteTests) {
            $this->useMangopayServiceMock(self::MANGOPAY_CREATE_BANK_ACCOUNT_GB);
        }
        /** @var MangoPay $service */
        $service = static::getContainer()->get(MangoPay::class);

        /** @var User $testUser */
        $testUser = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_REGULAR]);

        //Create a bank account for this user
        //We need to post some json data in the request body
        $data = new \stdClass();
        $data->account_number = '70872490'; //- the Bank Account's Account Number
        $data->sort_code = '404784'; //- the Bank Account's Sort Code, DONT USE '-' HERE!
        $data->owner_name = 'Jon Doe'; // - the full name of the User who owns the Bank Account
        $data->address_line1 = '1 London Road';
        $data->address_line2 = '';
        $data->city = 'London';
        $data->region = '';
        $data->postcode = 'E1 1RD';
        $data->country = 'GB';
        $data->owner_address = 'Manchester House, 1 London Road, line3, London, England, GB, E1';
        $data->type = 'GB';

        /** @var \MangoPay\BankAccount $mangopayBankAccount */
        $mangopayBankAccount = $service->createMangopayUserBankAccount(
            $testUser,
            $data,
        );
        $this->assertNotNull($mangopayBankAccount->Details->AccountNumber);
    }

    public function testMangopayGetUserBankAccounts(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('salesforce_remote_tests');
        if (!$useRemoteTests) {
            $this->useMangopayServiceMock(self::MANGOPAY_LIST_BANK_ACCOUNTS);
        }
        /** @var MangoPay $service */
        $service = static::getContainer()->get(MangoPay::class);

        /** @var User $testUser */
        $testUser = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_REGULAR]);

        // Now we can go and get the actual bank account and test
        /** @var \MangoPay\BankAccount[] $bankAccounts */
        $bankAccounts = $service->getUserbankaccounts($testUser);

        //NOTE the response is an array of $bankAccounts
        if (!$useRemoteTests) {
            $this->assertNotNull($bankAccounts[0]->UserId);
        } else {
            $this->assertEquals(
                $testUser->getMangoPayUserId(),
                $bankAccounts[0]->UserId,
            );
        }
        $this->assertNotNull($bankAccounts[0]->Details->AccountNumber);
    }

    public function testGetMangoPayUserWalletTransactions(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('salesforce_remote_tests');
        if (!$useRemoteTests) {
            $this->useMangopayServiceMock(self::MANGOPAY_LIST_TRANSACTIONS);
        }
        /** @var MangoPay $service */
        $service = static::getContainer()->get(MangoPay::class);

        /** @var User $testUser */
        $testUser = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_REGULAR]);

        /**
         * Check page and page size behaviour
         * Check logic that determines whether to use single or multi page execute
         */

        /** @var \MangoPay\Transaction[]  $transactions */
        $transactions = $service->getUserMangoPayWalletTransactions($testUser, 1, 2);
        /** @var \MangoPay\Transaction[] $transactionsRange */
        $transactionsRange = $service->getUserMangoPayWalletTransactions(
            $testUser,
            0,
            1,
            (int) $transactions[0]->CreationDate + 1,
            (int) $transactions[1]->CreationDate + 10000,
        );
        //NOTE the response is an array of $transactions
        $this->assertNotNull($transactions[0]->AuthorId);
        $this->assertNotNull($transactions[0]->Status);
        $this->assertLessThanOrEqual(2, count($transactions)); // at most 2 transactions
        $this->assertGreaterThan(
            (int) $transactions[0]->CreationDate + 1,
            (int) $transactionsRange[0]->CreationDate,
        );
        $this->assertLessThan(
            (int) $transactions[0]->CreationDate + 10000,
            (int) $transactionsRange[0]->CreationDate,
        );
    }

    public function testMangoPayDeactivateCard(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('salesforce_remote_tests');
        if (!$useRemoteTests) {
            $this->useMangopayServiceMock(self::MANGOPAY_CARD_DEACTIVATE);
        }
        /** @var MangoPay $service */
        $service = static::getContainer()->get(MangoPay::class);

        /** @var \MangoPay\Card $response */
        // $response = $service->deactivateCard($cardData['data']['card_id'] ?? "1234");
        // Don't have custom test cardData response anymore
        $response = $service->deactivateCard('1234');

        $this->assertNotNull($response->ExpirationDate);
        $this->assertNotNull($response->Alias);
        $this->assertNotNull($response->CardProvider);
        $this->assertNotNull($response->UserId);
        $this->assertNotNull($response->CardType);
        $this->assertNotNull($response->Product);
        $this->assertNotNull($response->Country);
        $this->assertNotNull($response->Currency);
        $this->assertEquals(false, $response->Active);
    }

    public function testBuildTransferObj(): void
    {
        /** @var Mangopay $service */
        $service = static::getContainer()->get(MangoPay::class);
        $tag = 'AstName:Clarence Hold A - Camden;AstCode:SPVAF00011;Type:Investment';
        $transfer = $service->buildTransferObj('000', '111', '222', 50.55, 0, $tag);

        $finalTag = 'Transfer: 111 to 222;' . $tag;
        $this->assertEquals('000', $transfer->AuthorId);
        $this->assertEquals('111', $transfer->DebitedWalletId);
        $this->assertEquals('222', $transfer->CreditedWalletId);
        $this->assertSame(5055, $transfer->DebitedFunds->Amount);
        $this->assertEquals(0, $transfer->Fees->Amount);
        $this->assertEquals($finalTag, $transfer->Tag);
    }

    public function testCreateUserWalletDefault(): void
    {
        $serviceMock = $this
            ->getMockBuilder(MangoPay::class)
            ->setConstructorArgs([
                static::getContainer()->get(\Psr\Log\LoggerInterface::class),
                static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class),
                static::getContainer()->get(\App\Service\Manager\AssetManager::class),
                static::getContainer()->get(\App\Service\DocumentService::class),
                static::getContainer()->get(\App\Service\MailerService::class),
                null,
                null,
                null,
                '',
                '',
                '',
                'sandbox',
            ])
            ->onlyMethods([
                'executeMangoPayWalletCreate',
                'isUserValidForMangoPay',
                'hasUserMangoPayId',
                'handleMangopayResponse',
            ])
            ->getMock();
        $serviceMock->method('isUserValidForMangoPay')->willReturn(true);
        $serviceMock->method('hasUserMangoPayId')->willReturn(true);

        /** @var User $user */
        $user = EntityIdTestUtil::setEntityId(new User(), 24);
        $user->setMangoPayUserId('42824');

        $sampleWallet = new Wallet();
        $sampleWallet->Owners = [$user->getMangoPayUserId()];
        $sampleWallet->Description = 'Yielders investor main wallet';
        $sampleWallet->Currency = 'GBP';
        $sampleWallet->Tag = $user->getId();
        $serviceMock
            ->expects($this->once())
            ->method('executeMangoPayWalletCreate')
            ->with($sampleWallet);

        /** @var Mangopay $serviceMock */
        $serviceMock->createUserWallet($user);
    }

    public function testCreateUserWalletCustomDescription(): void
    {
        $serviceMock = $this
            ->getMockBuilder(MangoPay::class)
            ->setConstructorArgs([
                static::getContainer()->get(\Psr\Log\LoggerInterface::class),
                static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class),
                static::getContainer()->get(\App\Service\Manager\AssetManager::class),
                static::getContainer()->get(\App\Service\DocumentService::class),
                static::getContainer()->get(\App\Service\MailerService::class),
                null,
                null,
                null,
                '',
                '',
                '',
                'sandbox',
            ])
            ->onlyMethods([
                'executeMangoPayWalletCreate',
                'isUserValidForMangoPay',
                'hasUserMangoPayId',
                'handleMangopayResponse',
            ])
            ->getMock();
        $serviceMock->method('isUserValidForMangoPay')->willReturn(true);
        $serviceMock->method('hasUserMangoPayId')->willReturn(true);

        /** @var User $user */
        $user = EntityIdTestUtil::setEntityId(new User(), 24);
        $user->setMangoPayUserId('42824');

        $sampleWallet = new Wallet();
        $sampleWallet->Owners = [$user->getMangoPayUserId()];
        $sampleWallet->Description = 'Custom Description';
        $sampleWallet->Currency = 'GBP';
        $sampleWallet->Tag = $user->getId();

        $serviceMock
            ->expects($this->once())
            ->method('executeMangoPayWalletCreate')
            ->with($sampleWallet);

        /** @var Mangopay $serviceMock */
        $serviceMock->createUserWallet($user, 'Custom Description');
    }

    public function testGetRateLimit(): void
    {
        /** @var Mangopay $service */
        $service = static::getContainer()->get(MangoPay::class);
        $actual = $service->getRateLimits();
        $this->assertIsArray($actual);
        $this->assertEmpty($actual);
    }

    public function testBuildTransferPayment(): void
    {
        /** @var Mangopay $service */
        $service = static::getContainer()->get(MangoPay::class);

        $asset = new \App\Entity\Asset();
        $asset->setName('Clarence Hold A - Camden');
        $asset->setCompanyNumber('SPVAF00011');
        $asset->setAdditionalWallet('abcd6789');
        $payee = new User();
        $payee->setMangoPayWalletId('mnop4567');
        $metadata =
            'AstName:Clarence Hold A - Camden;AstCode:SPVAF00011;Type:'
            . PaymentService::TYPE_INVESTMENT_EXIT
            . ';';
        $transfer = $service->buildTransferPayment(
            $asset,
            $payee,
            'hijk2345',
            50.55,
            PaymentService::TYPE_INVESTMENT_EXIT,
        );

        $expectedTag = 'Transfer: abcd6789 to mnop4567;' . $metadata;
        $this->assertEquals('hijk2345', $transfer->AuthorId);
        $this->assertEquals('abcd6789', $transfer->DebitedWalletId);
        $this->assertEquals('mnop4567', $transfer->CreditedWalletId);
        $this->assertEquals(5055, $transfer->DebitedFunds->Amount);
        $this->assertEquals(0, $transfer->Fees->Amount);
        $this->assertEquals($expectedTag, $transfer->Tag);
    }

    public function testBuildTransferPaymentCustomDebitWallet(): void
    {
        /** @var Mangopay $service */
        $service = static::getContainer()->get(MangoPay::class);

        $asset = new \App\Entity\Asset();
        $asset->setName('Clarence Hold A - Camden');
        $asset->setCompanyNumber('SPVAF00011');
        $asset->setAdditionalWallet('abcd6789');
        $payee = new User();
        $payee->setMangoPayWalletId('mnop4567');
        $metadata =
            'AstName:Clarence Hold A - Camden;AstCode:SPVAF00011;Type:'
            . PaymentService::TYPE_INVESTMENT_EXIT
            . ';';
        $debitWalletId = bin2hex(random_bytes(8));
        $transfer = $service->buildTransferPayment(
            $asset,
            $payee,
            'hijk2345',
            50.55,
            PaymentService::TYPE_INVESTMENT_EXIT,
            $debitWalletId,
        );

        $expectedTag = "Transfer: $debitWalletId to mnop4567;" . $metadata;
        $this->assertEquals('hijk2345', $transfer->AuthorId);
        $this->assertEquals($debitWalletId, $transfer->DebitedWalletId);
        $this->assertEquals('mnop4567', $transfer->CreditedWalletId);
        $this->assertEquals(5055, $transfer->DebitedFunds->Amount);
        $this->assertEquals(0, $transfer->Fees->Amount);
        $this->assertEquals($expectedTag, $transfer->Tag);
    }

    public function testCreateTransferPayment(): void
    {
        $serviceMock = $this
            ->getMockBuilder(MangoPay::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['executeMangopayTransfersCreate'])
            ->getMock();

        $asset = new Asset();
        $asset->setName('Clarence Hold A - Camden');
        $asset->setCompanyNumber('SPVAF00011');
        $asset->setAdditionalWallet('abcd6789');
        $payee = new User();
        $payee->setMangoPayWalletId('mnop4567');
        $assetUserId = bin2hex(random_bytes(8));
        $amount = 50.55;
        $transferType = PaymentService::TYPE_DIVIDEND;

        $builtTransfer = $serviceMock->buildTransferPayment(
            $asset,
            $payee,
            $assetUserId,
            $amount,
            $transferType,
        );
        $builtTransfer->ScaContext = 'USER_NOT_PRESENT';

        $serviceMock = $this
            ->getMockBuilder(MangoPay::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['executeMangopayTransfersCreate'])
            ->getMock();

        /**
         * Should be sending a transfer request to Mangopay that is
         * - Identical to what is constructed by buildTransferPayment
         * - Have ScaContext set to USER_NOT_PRESENT
         */
        $serviceMock
            ->expects($this->once())
            ->method('executeMangopayTransfersCreate')
            ->with($builtTransfer)
            ->willReturn($builtTransfer);

        $serviceMock->createTransferPayment(
            $asset,
            $payee,
            $assetUserId,
            $amount,
            $transferType,
        );
    }

    public function testCreateTransferPaymentCustomDebitWallet(): void
    {
        /** Check method calls only */
        $serviceMock = $this
            ->getMockBuilder(MangoPay::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['buildTransferPayment', 'executeMangopayTransfersCreate'])
            ->getMock();
        $debitWalletId = bin2hex(random_bytes(8));
        $serviceMock
            ->expects($this->once())
            ->method('buildTransferPayment')
            ->with(
                $this->isInstanceOf(\App\Entity\Asset::class),
                $this->isInstanceOf(User::class),
                $this->equalTo('hijk2345'),
                $this->equalTo(50.55),
                $this->equalTo(PaymentService::TYPE_INVESTMENT_EXIT),
                $debitWalletId,
            )
            ->willReturn(new Transfer());
        $serviceMock
            ->expects($this->once())
            ->method('executeMangopayTransfersCreate')
            ->willReturn(new Transfer());

        /** @var Mangopay $serviceMock */
        $serviceMock->createTransferPayment(
            new \App\Entity\Asset(),
            new User(),
            'hijk2345',
            50.55,
            PaymentService::TYPE_INVESTMENT_EXIT,
            $debitWalletId,
        );
    }

    public function testCardPayIn(): void
    {
        $user = new User();
        $user->setMangoPayUserId('300');
        $user->setMangoPayWalletId('800');

        $browserInfoDTO = new BrowserInfoDTO(
            'header',
            'agent',
            'English',
            100,
            150,
            1,
            '+1',
            true,
            true,
        );

        $cardPayinDTO = new CardPayinDTO(
            1,
            10000,
            'GBP',
            'test.com',
            '1.1.1.1',
            $browserInfoDTO,
        );

        /** Check method calls only */
        $serviceMock = $this
            ->getMockBuilder(MangoPay::class)
            ->setConstructorArgs([
                static::getContainer()->get(\Psr\Log\LoggerInterface::class),
                static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class),
                static::getContainer()->get(\App\Service\Manager\AssetManager::class),
                static::getContainer()->get(\App\Service\DocumentService::class),
                static::getContainer()->get(\App\Service\MailerService::class),
                null,
                null,
                null,
                '',
                '',
                '',
                'sandbox',
            ])
            ->onlyMethods([
                'isUserValidForMangoPay',
                'hasUserMangoPayId',
                'executeMangopayPayInsCreate',
                'handleMangopayResponse',
            ])
            ->getMock();

        $serviceMock
            ->expects($this->once())
            ->method('isUserValidForMangoPay')
            ->willReturn(true);
        $serviceMock
            ->expects($this->once())
            ->method('hasUserMangoPayId')
            ->willReturn(true);
        $serviceMock
            ->expects($this->once())
            ->method('executeMangopayPayInsCreate')
            ->willReturn(true);
        $serviceMock
            ->expects($this->once())
            ->method('handleMangopayResponse')
            ->willReturn(true);

        /** @var Mangopay $serviceMock */
        $serviceMock->cardPayIn(
            $user,
            'payin_m_01HPSDZKQMVSJN6JF4H04WY9XP',
            $cardPayinDTO,
        );
    }

    public function testBuildCardPayinObj(): void
    {
        /** @var Mangopay $service */
        $service = static::getContainer()->get(MangoPay::class);
        $payIn = $service->buildCardPayinObj(
            self::TEST_MP_ID_USER,
            self::TEST_MP_ID_WALLET,
            self::TEST_MP_ID_PAYIN,
            5000,
            'GBP',
            '1.1.1.1',
            'header',
            'agent',
            'english',
            150,
            100,
            111,
            '+1',
            true,
            true,
            'test.com',
        );

        $this->assertEquals(self::TEST_MP_ID_USER, $payIn->AuthorId);
        $this->assertEquals(self::TEST_MP_ID_USER, $payIn->CreditedUserId);
        $this->assertEquals(self::TEST_MP_ID_WALLET, $payIn->CreditedWalletId);
        $this->assertEquals(5050, $payIn->DebitedFunds->Amount);
        $this->assertEquals(50, $payIn->Fees->Amount);
        $this->assertEquals('GBP', $payIn->DebitedFunds->Currency);
        $this->assertEquals('Type:Card Payment', $payIn->Tag);
        $this->assertEquals(self::TEST_MP_ID_PAYIN, $payIn->PaymentDetails->CardId);
        $this->assertEquals(
            $service::DEFAULT_CARD_TYPE,
            $payIn->PaymentDetails->CardType,
        );
        $this->assertEquals('1.1.1.1', $payIn->PaymentDetails->IpAddress);
        $this->assertEquals(
            'header',
            $payIn->PaymentDetails->BrowserInfo->AcceptHeader,
        );
        $this->assertEquals('agent', $payIn->PaymentDetails->BrowserInfo->UserAgent);
        $this->assertEquals('english', $payIn->PaymentDetails->BrowserInfo->Language);
        $this->assertEquals(150, $payIn->PaymentDetails->BrowserInfo->ScreenWidth);
        $this->assertEquals(100, $payIn->PaymentDetails->BrowserInfo->ScreenHeight);
        $this->assertEquals(111, $payIn->PaymentDetails->BrowserInfo->ColorDepth);
        $this->assertEquals('+1', $payIn->PaymentDetails->BrowserInfo->TimeZoneOffset);
        $this->assertTrue($payIn->PaymentDetails->BrowserInfo->JavaEnabled);
        $this->assertTrue($payIn->PaymentDetails->BrowserInfo->JavascriptEnabled);
        $this->assertEquals('test.com', $payIn->ExecutionDetails->SecureModeReturnURL);
    }

    public function testHandleMangopayResponseWalletBalanceError(): void
    {
        $transferResponse = new Transfer();
        $transferResponse->Status = TransactionStatus::Failed;
        $transferResponse->ResultCode = '001001';
        $transferResponse->ResultMessage = 'Unsufficient wallet balance';

        $service = static::getContainer()->get(MangoPay::class);

        $this->expectExceptionCode(ApiException::ERROR_MANGOPAY_INSUFFICIENT_FUNDS_IN_WALLET);
        $this->expectExceptionMessage('001001');
        $service->handleMangopayResponse($transferResponse);
    }

    public function testHandleMangopayResponseTransactionAmountHighError(): void
    {
        $transferResponse = new Transfer();
        $transferResponse->Status = TransactionStatus::Failed;
        $transferResponse->ResultCode = '001011';
        $transferResponse->ResultMessage = 'Transaction amount is higher than maximum permitted amount';

        $service = static::getContainer()->get(MangoPay::class);

        $this->expectExceptionCode(ApiException::ERROR_TRANSACTION_AMOUNT_HIGHER_THAN_PERMITTED_AMOUNT);
        $this->expectExceptionMessage('001011');
        $service->handleMangopayResponse($transferResponse);
    }
}
