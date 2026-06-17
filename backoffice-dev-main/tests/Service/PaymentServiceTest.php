<?php

namespace App\Tests\Service;

use App\Entity\Asset;
use App\Entity\Investment;
use App\Entity\User;
use App\Service\MangoPay;
use App\Service\PaymentService;
use App\Test\Util\EntityIdTestUtil;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PaymentServiceTest extends KernelTestCase
{
    private PaymentService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(PaymentService::class);
    }

    public function testPayDividend(): void
    {
        $asset = new Asset();
        $user = new User();
        $payoutRequest = [
            'currentHolding' => 256,
            'proportion' => 0.25,
            'sharesToLiquidate' => 192,
            'cashValue' => 238.08,
        ];
        $transfer = new \MangoPay\Transfer();
        $transfer->Id = 'hijklmnop67890123';
        $transfer->Status = 'SUCCEEDED';

        $walletServiceMock = $this
            ->getMockBuilder(MangoPay::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createTransferPayment'])
            ->getMock();
        $walletServiceMock
            ->expects($this->once())
            ->method('createTransferPayment')
            ->with(
                $this->isInstanceOf(Asset::class),
                $this->isInstanceOf(User::class),
                $this->stringContains('opqr9012'),
                $this->equalTo(238.08),
                $this->equalTo(PaymentService::TYPE_DIVIDEND),
                null,
            )
            ->willReturn($transfer);

        /** @var MangoPay $walletServiceMock */
        $service = new PaymentService(
            static::getContainer()->get(\Psr\Log\LoggerInterface::class),
            static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class),
            static::getContainer()->get(\App\Service\Manager\PayoutManagerV2::class),
            static::getContainer()->get(\App\Repository\UserRepository::class),
            static::getContainer()->get(\App\Repository\InvestmentRepository::class),
            $walletServiceMock,
        );
        $datetime = new \DateTime('first day of this month');
        $actual = $service->payDividend(
            $asset,
            $user,
            'opqr9012',
            $payoutRequest,
            $datetime,
        );
        $this->assertEquals(238.08, $actual->getPayoutAmount());
        $this->assertEquals(256, $actual->getShareholding()); // Note that this should be the current holding for dividends!
        $this->assertEquals('GBP', $actual->getCurrency());
        $this->assertEquals(0, $actual->getPayoutType());
        $this->assertEquals('hijklmnop67890123', $actual->getTransactionId());
        $this->assertEquals($datetime, $actual->getDueDate());
    }

    public function testPayDividendCustomDebitWallet(): void
    {
        $asset = new Asset();
        $user = new User();
        $payoutRequest = [
            'currentHolding' => 256,
            'proportion' => 0.25,
            'sharesToLiquidate' => 192,
            'cashValue' => 238.08,
        ];
        $transfer = new \MangoPay\Transfer();
        $transfer->Id = 'hijklmnop67890123';
        $transfer->Status = 'SUCCEEDED';
        $debitWalletId = bin2hex(random_bytes(8));

        $walletServiceMock = $this
            ->getMockBuilder(MangoPay::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createTransferPayment'])
            ->getMock();
        $walletServiceMock
            ->expects($this->once())
            ->method('createTransferPayment')
            ->with(
                $this->isInstanceOf(Asset::class),
                $this->isInstanceOf(User::class),
                $this->stringContains('opqr9012'),
                $this->equalTo(238.08),
                $this->equalTo(PaymentService::TYPE_DIVIDEND),
                $debitWalletId,
            )
            ->willReturn($transfer);

        /** @var MangoPay $walletServiceMock */
        $service = new PaymentService(
            static::getContainer()->get(\Psr\Log\LoggerInterface::class),
            static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class),
            static::getContainer()->get(\App\Service\Manager\PayoutManagerV2::class),
            static::getContainer()->get(\App\Repository\UserRepository::class),
            static::getContainer()->get(\App\Repository\InvestmentRepository::class),
            $walletServiceMock,
        );
        $datetime = new \DateTime('first day of this month');
        $actual = $service->payDividend(
            $asset,
            $user,
            'opqr9012',
            $payoutRequest,
            $datetime,
            $debitWalletId,
        );
        $this->assertEquals(238.08, $actual->getPayoutAmount());
        $this->assertEquals(256, $actual->getShareholding()); // Note that this should be the current holding for dividends!
        $this->assertEquals('GBP', $actual->getCurrency());
        $this->assertEquals(0, $actual->getPayoutType());
        $this->assertEquals('hijklmnop67890123', $actual->getTransactionId());
        $this->assertEquals($datetime, $actual->getDueDate());
    }

    public function testPayDivestment(): void
    {
        $asset = new Asset();
        $user = new User();
        $payoutRequest = [
            'currentHolding' => 256,
            'proportion' => 0.25,
            'sharesToLiquidate' => 192,
            'cashValue' => 238.08,
        ];
        $transfer = new \MangoPay\Transfer();
        $transfer->Id = 'hijklmnop67890123';
        $transfer->Status = 'SUCCEEDED';

        $walletServiceMock = $this
            ->getMockBuilder(MangoPay::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createTransferPayment'])
            ->getMock();
        $walletServiceMock
            ->expects($this->once())
            ->method('createTransferPayment')
            ->with(
                $this->isInstanceOf(Asset::class),
                $this->isInstanceOf(User::class),
                $this->stringContains('opqr9012'),
                $this->equalTo(238.08),
                $this->equalTo(PaymentService::TYPE_DIVESTMENT),
                null,
            )
            ->willReturn($transfer);

        /** @var MangoPay $walletServiceMock */
        $service = new PaymentService(
            static::getContainer()->get(\Psr\Log\LoggerInterface::class),
            static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class),
            static::getContainer()->get(\App\Service\Manager\PayoutManagerV2::class),
            static::getContainer()->get(\App\Repository\UserRepository::class),
            static::getContainer()->get(\App\Repository\InvestmentRepository::class),
            $walletServiceMock,
        );
        $datetime = new \DateTime('first day of this month');
        $actual = $service->payDivestment(
            $asset,
            $user,
            'opqr9012',
            $payoutRequest,
            $datetime,
            PaymentService::TYPE_DIVESTMENT,
        );
        $this->assertEquals(238.08, $actual->getPayoutAmount());
        $this->assertEquals(192, $actual->getShareholding());
        $this->assertEquals('GBP', $actual->getCurrency());
        $this->assertEquals(1, $actual->getPayoutType());
        $this->assertEquals('hijklmnop67890123', $actual->getTransactionId());
        $this->assertEquals($datetime, $actual->getDueDate());
    }

    public function testPayDivestmentCustomDebitWallet(): void
    {
        $asset = new Asset();
        $user = new User();
        $payoutRequest = [
            'currentHolding' => 256,
            'proportion' => 0.25,
            'sharesToLiquidate' => 192,
            'cashValue' => 238.08,
        ];
        $transfer = new \MangoPay\Transfer();
        $transfer->Id = 'hijklmnop67890123';
        $transfer->Status = 'SUCCEEDED';
        $debitWalletId = bin2hex(random_bytes(8));

        $walletServiceMock = $this
            ->getMockBuilder(MangoPay::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createTransferPayment'])
            ->getMock();
        $walletServiceMock
            ->expects($this->once())
            ->method('createTransferPayment')
            ->with(
                $this->isInstanceOf(Asset::class),
                $this->isInstanceOf(User::class),
                $this->stringContains('opqr9012'),
                $this->equalTo(238.08),
                $this->equalTo(PaymentService::TYPE_DIVESTMENT),
                $debitWalletId,
            )
            ->willReturn($transfer);

        /** @var MangoPay $walletServiceMock */
        $service = new PaymentService(
            static::getContainer()->get(\Psr\Log\LoggerInterface::class),
            static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class),
            static::getContainer()->get(\App\Service\Manager\PayoutManagerV2::class),
            static::getContainer()->get(\App\Repository\UserRepository::class),
            static::getContainer()->get(\App\Repository\InvestmentRepository::class),
            $walletServiceMock,
        );
        $datetime = new \DateTime('first day of this month');
        $actual = $service->payDivestment(
            $asset,
            $user,
            'opqr9012',
            $payoutRequest,
            $datetime,
            PaymentService::TYPE_DIVESTMENT,
            $debitWalletId,
        );
        $this->assertEquals(238.08, $actual->getPayoutAmount());
        $this->assertEquals(192, $actual->getShareholding());
        $this->assertEquals('GBP', $actual->getCurrency());
        $this->assertEquals(1, $actual->getPayoutType());
        $this->assertEquals('hijklmnop67890123', $actual->getTransactionId());
        $this->assertEquals($datetime, $actual->getDueDate());
    }

    public function testPayDividendException(): void
    {
        $asset = new Asset();
        $user = new User();
        $payoutRequest = [
            'currentHolding' => 256,
            'proportion' => 0.25,
            'sharesToLiquidate' => 192,
            'cashValue' => 238.08,
        ];
        $transfer = new \MangoPay\Transfer();
        $transfer->Id = 'hijklmnop67890123';
        $transfer->Status = 'FAILED';

        $walletServiceMock = $this
            ->getMockBuilder(MangoPay::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createTransferPayment'])
            ->getMock();
        $walletServiceMock
            ->expects($this->once())
            ->method('createTransferPayment')
            ->willReturn($transfer);

        /** @var MangoPay $walletServiceMock */
        $service = new PaymentService(
            static::getContainer()->get(\Psr\Log\LoggerInterface::class),
            static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class),
            static::getContainer()->get(\App\Service\Manager\PayoutManagerV2::class),
            static::getContainer()->get(\App\Repository\UserRepository::class),
            static::getContainer()->get(\App\Repository\InvestmentRepository::class),
            $walletServiceMock,
        );
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Transfer could not be made');
        $service->payDividend(
            $asset,
            $user,
            'opqr9012',
            $payoutRequest,
            new \DateTime(),
        );
    }

    public function testPayDivestmentException(): void
    {
        $asset = new Asset();
        $user = new User();
        $payoutRequest = [
            'currentHolding' => 256,
            'proportion' => 0.25,
            'sharesToLiquidate' => 192,
            'cashValue' => 238.08,
        ];
        $transfer = new \MangoPay\Transfer();
        $transfer->Id = 'hijklmnop67890123';
        $transfer->Status = 'FAILED';

        $walletServiceMock = $this
            ->getMockBuilder(MangoPay::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createTransferPayment'])
            ->getMock();
        $walletServiceMock
            ->expects($this->once())
            ->method('createTransferPayment')
            ->willReturn($transfer);

        /** @var MangoPay $walletServiceMock */
        $service = new PaymentService(
            static::getContainer()->get(\Psr\Log\LoggerInterface::class),
            static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class),
            static::getContainer()->get(\App\Service\Manager\PayoutManagerV2::class),
            static::getContainer()->get(\App\Repository\UserRepository::class),
            static::getContainer()->get(\App\Repository\InvestmentRepository::class),
            $walletServiceMock,
        );
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Transfer could not be made');
        $service->payDivestment(
            $asset,
            $user,
            'opqr9012',
            $payoutRequest,
            new \DateTime(),
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('buildPayoutProvider')]
    public function testBuildPayout(string $paymentType, int $paymentTypeInt): void
    {
        $asset = new Asset();
        $asset->setName('payoutTestAsset');
        $user = new User();
        $user->setUsername('payoutTestUser');
        $datetime = new \DateTime('first day of this month');
        $actual = $this->service->buildPayout(
            $asset,
            $user,
            350.72,
            256,
            'testTransaction0082',
            $datetime,
            $paymentType,
        );
        $this->assertEquals('payoutTestAsset', $actual->getAsset()->getName());
        $this->assertEquals(
            'payoutTestUser',
            $actual->getCreditedUser()->getUsername(),
        );
        $this->assertEquals(350.72, $actual->getPayoutAmount());
        $this->assertEquals(256, $actual->getShareholding());
        $this->assertEquals('GBP', $actual->getCurrency());
        $this->assertEquals($paymentTypeInt, $actual->getPayoutType());
        $this->assertEquals('testTransaction0082', $actual->getTransactionId());
        $this->assertEquals($datetime, $actual->getDueDate());
    }

    public static function buildPayoutProvider(): \Generator
    {
        yield 'Repayments' => [PaymentService::TYPE_REPAYMENT, 1];
        yield 'Divestment' => [PaymentService::TYPE_DIVESTMENT, 1];
        yield 'Dividend' => [PaymentService::TYPE_DIVIDEND, 0];
        yield 'Investment exit' => [PaymentService::TYPE_INVESTMENT_EXIT, 1];
        yield 'Liquidation' => [PaymentService::TYPE_LIQUIDATION, 1];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('prefundingOnlyByTypeProvider')]
    public function testOnlyUpdatePrefundingInvestmentsForType(
        bool $expected,
        string $transferType,
    ): void {
        $actual = $this->service->onlyUpdatePrefundingInvestmentsForType($transferType);
        $this->assertSame($expected, $actual);
    }

    /**
     * @psalm-return \Generator<string, array{0: bool, 1: string}, mixed, void>
     */
    public static function prefundingOnlyByTypeProvider(): \Generator
    {
        yield 'Repayments' => [true, PaymentService::TYPE_REPAYMENT];
        yield 'Divestment' => [false, PaymentService::TYPE_DIVESTMENT];
        yield 'Dividend' => [false, PaymentService::TYPE_DIVIDEND];
        yield 'Investment exit' => [false, PaymentService::TYPE_INVESTMENT_EXIT];
        yield 'Liquidation' => [false, PaymentService::TYPE_LIQUIDATION];
    }

    public function testGetDefaultAssetWalletUserId(): void
    {
        /**
         * getDefaultAssetWalletUserId() is just a wrapper method
         * Check that it correctly calls the corresponding method in PayoutManagerV2
         */
        $payoutManagerMock = $this
            ->getMockBuilder(\App\Service\Manager\PayoutManagerV2::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSuperAdminAuthId'])
            ->getMock();
        $payoutManagerMock
            ->expects($this->once())
            ->method('getSuperAdminAuthId')
            ->willReturn('abc');

        /** @var \App\Service\Manager\PayoutManagerV2 $payoutManagerMock */
        $service = new PaymentService(
            static::getContainer()->get(\Psr\Log\LoggerInterface::class),
            static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class),
            $payoutManagerMock,
            static::getContainer()->get(\App\Repository\UserRepository::class),
            static::getContainer()->get(\App\Repository\InvestmentRepository::class),
            static::getContainer()->get(\App\Service\MangoPay::class),
        );
        $service->getDefaultAssetWalletUserId();
    }
}
