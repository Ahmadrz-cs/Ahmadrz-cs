<?php

namespace App\Tests\Service;

use App\Entity\Asset;
use App\Entity\Investment;
use App\Entity\Offering;
use App\Entity\TransferOrder;
use App\Entity\TransferRequest;
use App\Entity\User;
use App\Service\Manager\UserManagerV2;
use App\Service\MangopayWalletService;
use App\Service\TransferService;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class TransferServiceTest extends KernelTestCase
{
    private TransferService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(TransferService::class);
    }

    public function testMakeWalletTransfer(): void
    {
        $transferRequest = new TransferRequest();
        $transferRequest->setDebitWalletId('testDebitWalletId124');
        $transferRequest->setCreditWalletId('testCreditWalletId4816');
        $transferRequest->setAmount('1087.92');
        $transferRequest->setDescription('TransferService test description');

        $debitedFunds = new \MangoPay\Money();
        $debitedFunds->Currency = 'GBP';
        $debitedFunds->Amount = 108792;

        $fees = new \MangoPay\Money();
        $fees->Amount = 0;
        $fees->Currency = 'GBP';

        $walletTransferResponse = new \MangoPay\Transfer();
        $walletTransferResponse->DebitedWalletId = $transferRequest->getDebitWalletId();
        $walletTransferResponse->CreditedWalletId =
            $transferRequest->getCreditWalletId();
        $walletTransferResponse->Tag = $transferRequest->getDescription();
        $walletTransferResponse->DebitedFunds = $debitedFunds;
        $walletTransferResponse->Fees = $fees;
        $walletTransferResponse->AuthorId = 'mockedUserWalletId124';
        // $walletTransferResponse->CreditedUserId = 'mockedUserWalletId124';
        $walletTransferResponse->Status = 'SUCCEEDED';
        $walletTransferResponse->ScaContext = 'USER_NOT_PRESENT';

        /** @var MockObject $walletServiceMock */
        $walletServiceMock = $this->createMock(MangopayWalletService::class);
        $walletServiceMock
            ->expects($this->once())
            ->method('createTransfer')
            ->willReturn($walletTransferResponse);

        $superAdmin = new User();
        $superAdmin->setMangoPayUserId('superAdminExampleId');

        /** @var MockObject $userManagerMock */
        $userManagerMock = $this->createMock(UserManagerV2::class);
        $userManagerMock
            ->expects($this->exactly(1))
            ->method('getSuperAdmin')
            ->willReturn($superAdmin);

        /** @var MangopayWalletService $walletServiceMock */
        /** @var UserManagerV2 $userManagerMock */
        $service = new TransferService(
            static::getContainer()->get(\Psr\Log\LoggerInterface::class),
            $walletServiceMock,
            $userManagerMock,
        );
        $actual = $service->makeWalletTransfer($transferRequest);
        $this->assertNotEmpty($actual);
    }

    public function testMakeWalletTransferException(): void
    {
        $transferRequest = new TransferRequest();
        $transferRequest->setDebitWalletId('testDebitWalletId124');
        $transferRequest->setCreditWalletId('testCreditWalletId4816');
        $transferRequest->setAmount('1087.92');
        $transferRequest->setDescription('TransferService test description');

        $debitedFunds = new \MangoPay\Money();
        $debitedFunds->Currency = 'GBP';
        $debitedFunds->Amount = 108792;

        $fees = new \MangoPay\Money();
        $fees->Amount = 0;
        $fees->Currency = 'GBP';

        $walletTransferResponse = new \MangoPay\Transfer();
        $walletTransferResponse->DebitedWalletId = $transferRequest->getDebitWalletId();
        $walletTransferResponse->CreditedWalletId =
            $transferRequest->getCreditWalletId();
        $walletTransferResponse->Tag = $transferRequest->getDescription();
        $walletTransferResponse->DebitedFunds = $debitedFunds;
        $walletTransferResponse->Fees = $fees;
        $walletTransferResponse->AuthorId = 'mockedUserWalletId124';
        // $walletTransferResponse->CreditedUserId = 'mockedUserWalletId124';
        $walletTransferResponse->Status = 'FAILED';
        $walletTransferResponse->ResultMessage = 'Author is not the wallet owner';
        $walletTransferResponse->ResultCode = '001002';
        $walletTransferResponse->ScaContext = 'USER_NOT_PRESENT';

        /** @var MockObject $walletServiceMock */
        $walletServiceMock = $this->createMock(MangopayWalletService::class);
        $walletServiceMock
            ->expects($this->once())
            ->method('createTransfer')
            ->willReturn($walletTransferResponse);

        $superAdmin = new User();
        $superAdmin->setMangoPayUserId('superAdminExampleId');

        /** @var MockObject $userManagerMock */
        $userManagerMock = $this->createMock(UserManagerV2::class);
        $userManagerMock
            ->expects($this->exactly(1))
            ->method('getSuperAdmin')
            ->willReturn($superAdmin);

        /** @var MangopayWalletService $walletServiceMock */
        /** @var UserManagerV2 $userManagerMock */
        $service = new TransferService(
            static::getContainer()->get(\Psr\Log\LoggerInterface::class),
            $walletServiceMock,
            $userManagerMock,
        );
        $this->expectExceptionMessage('Transfer could not be made');
        $this->expectExceptionMessage('Author is not the wallet owner');
        // Note that exception codes trim preceding 0's
        $this->expectExceptionCode('1002');
        $actual = $service->makeWalletTransfer($transferRequest);
        $this->assertNotEmpty($actual);
    }

    public function testCreateWalletTransfer(): void
    {
        $transferOrder = new TransferOrder();
        $transferRequest = new TransferRequest();
        $transferRequest->setDebitWalletId('testDebitWalletId124');
        $transferRequest->setCreditWalletId('testCreditWalletId4816');
        $transferRequest->setAmount('1087.92');
        $transferRequest->setDescription('TransferService test description');
        $transferOrder->addTransfer($transferRequest);

        $superAdmin = new User();
        $superAdmin->setMangoPayUserId('superAdminExampleId');

        /** @var MockObject $userManagerMock */
        $userManagerMock = $this->createMock(UserManagerV2::class);
        $userManagerMock
            ->expects($this->exactly(3))
            ->method('getSuperAdmin')
            ->willReturn($superAdmin);

        /** @var UserManagerV2 $userManagerMock */
        $service = new TransferService(
            static::getContainer()->get(\Psr\Log\LoggerInterface::class),
            static::getContainer()->get(MangopayWalletService::class),
            $userManagerMock,
        );

        $debitedFunds = new \MangoPay\Money();
        $debitedFunds->Amount = 108792;
        $debitedFunds->Currency = 'GBP';
        $fees = new \MangoPay\Money();
        $fees->Amount = 0;
        $fees->Currency = 'GBP';

        $expected = new \MangoPay\Transfer();
        $expected->DebitedWalletId = $transferRequest->getDebitWalletId();
        $expected->CreditedWalletId = $transferRequest->getCreditWalletId();
        $expected->Tag = $transferRequest->getDescription();
        $expected->DebitedFunds = $debitedFunds;
        $expected->Fees = $fees;
        $expected->AuthorId = 'superAdminExampleId';
        $expected->ScaContext = 'USER_NOT_PRESENT';
        // $expected->CreditedUserId = 'superAdminExampleId';

        $actual = $service->createWalletTransfer($transferRequest);
        $this->assertEquals($expected, $actual);

        // Try with asset but no investment
        $asset = new Asset();
        $offering = new Offering();
        $offering->setAsset($asset);
        $transferOrder->setAsset($asset);
        $actual = $service->createWalletTransfer($transferRequest);
        $this->assertEquals($expected, $actual);

        // Try with no asset but with investment
        $transferOrder->setAsset(null);
        $transferRequest->setInvestment(new Investment());
        $actual = $service->createWalletTransfer($transferRequest);
        $this->assertEquals($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('investmentTransferRequestProvider')]
    public function testCreateWalletTransferWithInvestmentTags(
        Investment $investment,
        string $tagEnd,
    ): void {
        $transferOrder = new TransferOrder();
        $transferOrder->setAsset($investment->getOffering()->getAsset());
        $transferRequest = new TransferRequest();
        $transferRequest->setDebitWalletId('testDebitWalletId124');
        $transferRequest->setCreditWalletId('testCreditWalletId4816');
        $transferRequest->setAmount('1087.92');
        $transferRequest->setDescription('TransferService test description');
        $transferRequest->setInvestment($investment);
        $transferOrder->addTransfer($transferRequest);

        $superAdmin = new User();
        $superAdmin->setMangoPayUserId('superAdminExampleId');

        /** @var MockObject $userManagerMock */
        $userManagerMock = $this->createMock(UserManagerV2::class);
        $userManagerMock
            ->expects($this->exactly(1))
            ->method('getSuperAdmin')
            ->willReturn($superAdmin);

        /** @var UserManagerV2 $userManagerMock */
        $service = new TransferService(
            static::getContainer()->get(\Psr\Log\LoggerInterface::class),
            static::getContainer()->get(MangopayWalletService::class),
            $userManagerMock,
        );

        $debitedFunds = new \MangoPay\Money();
        $debitedFunds->Amount = 108792;
        $debitedFunds->Currency = 'GBP';
        $fees = new \MangoPay\Money();
        $fees->Amount = 0;
        $fees->Currency = 'GBP';

        $expected = new \MangoPay\Transfer();
        $expected->DebitedWalletId = $transferRequest->getDebitWalletId();
        $expected->CreditedWalletId = $transferRequest->getCreditWalletId();
        $expected->Tag = 'Desc:' . $transferRequest->getDescription() . ';' . $tagEnd;
        $expected->DebitedFunds = $debitedFunds;
        $expected->Fees = $fees;
        $expected->AuthorId = 'superAdminExampleId';
        $expected->ScaContext = 'USER_NOT_PRESENT';
        // $expected->CreditedUserId = 'superAdminExampleId';

        $actual = $service->createWalletTransfer($transferRequest);
        $this->assertEquals($expected, $actual);
    }

    public static function investmentTransferRequestProvider(): \Generator
    {
        $asset = new Asset();
        $asset->setName('Tag House');
        $asset->setCompanyNumber('SPVTH0892');
        $offering = new Offering();
        $offering->setAsset($asset);
        $prefundingOffering = new Offering();
        $prefundingOffering->setAsset($asset);
        $prefundingOffering->setOfferingType('prefunding');
        $relistedOffering = new Offering();
        $relistedOffering->setAsset($asset);
        $sellInvestment = new Investment();
        $relistedOffering->setSellInvestment($sellInvestment);

        $investment = new Investment();
        $investment->setOffering($offering);
        $prefundingInvestment = new Investment();
        $prefundingInvestment->setOffering($prefundingOffering);
        $relistingInvestment = new Investment();
        $relistingInvestment->setOffering($relistedOffering);

        yield 'First party investment' => [
            $investment,
            'AstName:Tag House;AstCode:SPVTH0892',
        ];
        yield 'Prefunding investment' => [
            $prefundingInvestment,
            'AstName:Tag House;AstCode:SPVTH0892;Type:Prefunding',
        ];
        yield 'Relisted investment' => [
            $relistingInvestment,
            'AstName:Tag House;AstCode:SPVTH0892;Type:Relisting',
        ];
    }

    public function testCreateTransaction(): void
    {
        $transferRequest = new TransferRequest();
        $transferRequest->setDebitWalletId('testDebitWalletId124');
        $transferRequest->setCreditWalletId('testCreditWalletId4816');
        $transferRequest->setAmount('1087.92');
        $transferRequest->setDescription('TransferService test description');
        $transferRequest->setStatus(TransferRequest::STATE_COMPLETE);

        $debitedFunds = new \MangoPay\Money();
        $debitedFunds->Currency = 'GBP';
        $debitedFunds->Amount = 108792;
        $walletTransfer = new \MangoPay\Transfer();
        $walletTransfer->Id = '';
        $walletTransfer->DebitedFunds = $debitedFunds;
        $walletTransfer->Type = \MangoPay\TransactionType::Transfer;
        $walletTransfer->Status = \MangoPay\TransactionStatus::Succeeded;

        $transaction = $this->service->createTransaction(
            $transferRequest,
            $walletTransfer,
        );

        $this->assertNotEmpty($transaction);
        $this->assertEquals(
            $transferRequest->getDebitWalletId(),
            $transaction->getDebitResourceId(),
        );
        $this->assertEquals(
            $transferRequest->getCreditWalletId(),
            $transaction->getCreditResourceId(),
        );
        $this->assertEquals(
            $transferRequest->getDescription(),
            $transaction->getComments(),
        );

        $this->assertEquals($debitedFunds->Currency, $transaction->getCurrency());
        $this->assertEquals($debitedFunds->Amount, $transaction->getAmount());
        $this->assertEquals($walletTransfer->Status, $transaction->getPaymentStatus());
        $this->assertEquals($walletTransfer->Id, $transaction->getReferenceId());
        $this->assertEquals($walletTransfer->Type, $transaction->getType());
    }
}
