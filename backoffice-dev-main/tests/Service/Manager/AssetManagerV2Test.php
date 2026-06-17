<?php

namespace App\Tests\Service\Manager;

use App\Entity\Asset;
use App\Entity\User;
use App\Service\Manager\AssetManagerV2;
use App\Test\Util\EntityIdTestUtil;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class AssetManagerV2Test extends KernelTestCase
{
    private AssetManagerV2 $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(AssetManagerV2::class);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('assetWalletByTypeProvider')]
    public function testGetWalletIdByType(
        Asset $asset,
        string $walletType,
        string $expected,
    ): void {
        $actual = $this->service->getWalletIdByType($asset, $walletType);
        $this->assertSame($expected, $actual);
    }

    public function testGetWalletIdByTypeNull(): void
    {
        $asset = new Asset();
        // Wallet type is valid, but not set for the asset
        $this->assertNull($this->service->getWalletIdByType($asset, 'hold'));
        // Wallet type is not valid/supported, should return null rather than throw an exception
        $this->assertNull($this->service->getWalletIdByType($asset, 'mystery'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('assetWalletByTypeProvider')]
    public function testGetAssetWalletByType(
        Asset $asset,
        string $walletType,
        string $walletId,
    ): void {
        $balance = new \MangoPay\Money();
        $balance->Amount = 124;
        $providerWallet = new \MangoPay\Wallet($walletId);
        $providerWallet->Balance = $balance;
        $providerWallet->Currency = 'GBP';
        $providerWallet->Description = 'Asset Manager Test Wallet';
        $providerWallet->Owners = ['ASTMNGRTestOwner'];

        $walletService = $this
            ->getMockBuilder(\App\Service\MangopayWalletService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $walletService
            ->method('getWallet')
            ->with($walletId)
            ->willReturn($providerWallet);

        /** @var \App\Service\MangopayWalletService $walletService */
        $this->service = new AssetManagerV2(
            static::getContainer()->get(\Psr\Log\LoggerInterface::class),
            static::getContainer()->get(\App\Repository\AssetRepository::class),
            static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class),
            static::getContainer()->get(\App\Dto\AssetAssembler::class),
            static::getContainer()->get(\Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface::class),
            $walletService,
        );
        $expectedKeys = [
            'type',
            'walletId',
            'balance',
            'currency',
            'description',
            'owner',
        ];
        $actual = $this->service->getAssetWalletByType($asset, $walletType);

        // Check the shape of the array being returned
        $this->assertEqualsCanonicalizing($expectedKeys, array_keys($actual));

        /**
         * Check the fields are correctly mapped
         * - Type is normalised to lowercase
         * - Owner field is flattened into a single string
         * - Balance is converted from pence to pounds and converted to a string
         */
        $this->assertsame($walletId, $actual['walletId']);
        $this->assertsame($providerWallet->Currency, $actual['currency']);
        $this->assertsame($providerWallet->Description, $actual['description']);
        $this->assertsame(strtolower($walletType), $actual['type']);
        $this->assertsame('ASTMNGRTestOwner', $actual['owner']);
        $this->assertsame('1.24', $actual['balance']);
    }

    public static function assetWalletByTypeProvider(): \Generator
    {
        $asset = new Asset();
        $asset->setHoldWalletId(mt_rand(10000000, 99999999));
        $asset->setSettlementWalletId(mt_rand(10000000, 99999999));
        $asset->setDepositWalletId(mt_rand(10000000, 99999999));
        $asset->setExpensesWalletId(mt_rand(10000000, 99999999));
        $asset->setTaxWalletId(mt_rand(10000000, 99999999));
        $asset->setDistributionWalletId(mt_rand(10000000, 99999999));
        $asset->setTreasuryWalletId(mt_rand(10000000, 99999999));

        yield 'Hold' => [$asset, 'hold', $asset->getHoldWalletId()];
        yield 'Settlement' => [$asset, 'settlement', $asset->getSettlementWalletId()];
        yield 'Deposit' => [$asset, 'deposit', $asset->getDepositWalletId()];
        yield 'Expenses' => [$asset, 'expenses', $asset->getExpensesWalletId()];
        yield 'Tax' => [$asset, 'tax', $asset->getTaxWalletId()];
        yield 'Distribution' => [
            $asset,
            'distribution',
            $asset->getDistributionWalletId(),
        ];
        yield 'Treasury' => [$asset, 'treasury', $asset->getTreasuryWalletId()];
        yield 'Case insensitive' => [$asset, 'ExPenSes', $asset->getExpensesWalletId()];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('assetWalletByTypeExceptionsProvider')]
    public function testGetAssetWalletByTypeExceptions(
        Asset $asset,
        string $walletType,
        string $expected,
        ?string $providerExceptionMessage = null,
    ): void {
        if (!is_null($providerExceptionMessage)) {
            $walletService = $this
                ->getMockBuilder(\App\Service\MangopayWalletService::class)
                ->disableOriginalConstructor()
                ->getMock();
            $walletService
                ->method('getWallet')
                ->willThrowException(new \Exception($providerExceptionMessage));

            /** @var \App\Service\MangopayWalletService $walletService */
            $this->service = new AssetManagerV2(
                static::getContainer()->get(\Psr\Log\LoggerInterface::class),
                static::getContainer()->get(\App\Repository\AssetRepository::class),
                static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class),
                static::getContainer()->get(\App\Dto\AssetAssembler::class),
                static::getContainer()->get(\Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface::class),
                $walletService,
            );
        }
        $this->expectExceptionMessage($expected);
        $this->service->getAssetWalletByType($asset, $walletType);
    }

    public static function assetWalletByTypeExceptionsProvider(): \Generator
    {
        $asset = new Asset();
        $asset->setHoldWalletId(mt_rand(10000000, 99999999));

        yield 'Not supported' => [$asset, 'mystery', 'not supported'];
        yield 'Not configured' => [$asset, 'expenses', 'not been configured'];
        // The wallet id is set but not found on Mangopay
        yield 'Not found' => [$asset, 'hold', 'not be found', 'Not found'];
        // Any other issue will rethrow the exception verbatim
        yield 'Other issue with provider' => [
            $asset,
            'hold',
            'Any other issue',
            'Any other issue',
        ];
    }

    public function testCreateAssetWalletObject(): void
    {
        $user = new User();
        $user->setMangoPayUserId('0010710');

        $asset = $this
            ->getMockBuilder(Asset::class)
            ->disableOriginalConstructor()
            ->getMock();
        $asset->method('getId')->willReturn(1);
        $asset->method('getContactPoint')->willReturn($user);
        $asset->method('getName')->willReturn('Test Asset');
        $asset->method('getCompanyNumber')->willReturn('SPV001000A');

        /** @var Asset $asset */
        $wallet = $this->service->createAssetWalletObject($asset, 'Expenses Wallet');

        $this->assertEquals('0010710', $wallet->Owners[0]);
        $this->assertEquals(
            'SPV001000A 1 Test Asset Expenses Wallet',
            $wallet->Description,
        );
        $this->assertEquals('GBP', $wallet->Currency);
    }

    public function testCreateWallet(): void
    {
        $container = static::getContainer();

        $holdWallet = new \MangoPay\Wallet(mt_rand(10000000, 99999999));
        $settlementWallet = new \MangoPay\Wallet(mt_rand(10000000, 99999999));
        $depositWallet = new \MangoPay\Wallet(mt_rand(10000000, 99999999));
        $expensesWallet = new \MangoPay\Wallet(mt_rand(10000000, 99999999));
        $taxWallet = new \MangoPay\Wallet(mt_rand(10000000, 99999999));
        $distributionWallet = new \MangoPay\Wallet(mt_rand(10000000, 99999999));
        $treasuryWallet = new \MangoPay\Wallet(mt_rand(10000000, 99999999));

        $wallets = [
            'hold' => $holdWallet,
            'settlement' => $settlementWallet,
            'deposit' => $depositWallet,
            'expenses' => $expensesWallet,
            'tax' => $taxWallet,
            'distribution' => $distributionWallet,
            'treasury' => $treasuryWallet,
        ];

        $walletService = $this
            ->getMockBuilder(\App\Service\MangopayWalletService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $walletService->method('createWallet')->willReturnOnConsecutiveCalls(
            $holdWallet,
            $settlementWallet,
            $depositWallet,
            $expensesWallet,
            $taxWallet,
            $distributionWallet,
            $treasuryWallet,
        );

        /** @var \App\Service\MangopayWalletService $walletService */
        $this->service = new AssetManagerV2(
            $container->get(\Psr\Log\LoggerInterface::class),
            $container->get(\App\Repository\AssetRepository::class),
            $container->get(\Doctrine\ORM\EntityManagerInterface::class),
            $container->get(\App\Dto\AssetAssembler::class),
            $container->get(\Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface::class),
            $walletService,
        );

        $user = new User();
        $user->setMangoPayUserId(000000);
        $asset = new Asset();
        $asset->setContactPoint($user);

        foreach ($wallets as $type => $wallet) {
            $this->service->createWallet($asset, $type);

            $propertyAccessor =
                PropertyAccess::createPropertyAccessorBuilder()->getPropertyAccessor();

            $actualWalletId = $propertyAccessor->getValue($asset, $type . 'WalletId');

            $this->assertSame((string) $wallet->Id, $actualWalletId);
        }
    }

    public function testCreateWalletAlreadyExists(): void
    {
        $container = static::getContainer();
        $holdWallet = new \MangoPay\Wallet(mt_rand(10000000, 99999999));

        $this->service = new AssetManagerV2(
            $container->get(\Psr\Log\LoggerInterface::class),
            $container->get(\App\Repository\AssetRepository::class),
            $container->get(\Doctrine\ORM\EntityManagerInterface::class),
            $container->get(\App\Dto\AssetAssembler::class),
            $container->get(\Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface::class),
            $container->get(\App\Service\MangopayWalletService::class),
        );

        $user = new User();
        $user->setMangoPayUserId(000000);
        $asset = new Asset();
        $asset->setContactPoint($user);
        $asset->setHoldWalletId($holdWallet->Id);

        $this->service->createWallet($asset, 'hold');
        $this->assertSame((string) $holdWallet->Id, $asset->getHoldWalletId());
    }

    public function testCreateWalletException(): void
    {
        $container = static::getContainer();

        $walletService = $this
            ->getMockBuilder(\App\Service\MangopayWalletService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $walletService->method('createWallet')->willThrowException(new \Exception());

        /** @var \App\Service\MangopayWalletService $walletService */
        $this->service = new AssetManagerV2(
            $container->get(\Psr\Log\LoggerInterface::class),
            $container->get(\App\Repository\AssetRepository::class),
            $container->get(\Doctrine\ORM\EntityManagerInterface::class),
            $container->get(\App\Dto\AssetAssembler::class),
            $container->get(\Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface::class),
            $walletService,
        );

        $user = new User();
        $user->setMangoPayUserId(000000);
        $asset = new Asset();
        $asset->setContactPoint($user);

        $this->expectException(\Exception::class);
        $this->service->createWallet($asset, 'hold');
    }

    public function testCreateAllWallets(): void
    {
        $holdWallet = new \MangoPay\Wallet(mt_rand(10000000, 99999999));
        $settlementWallet = new \MangoPay\Wallet(mt_rand(10000000, 99999999));
        $depositWallet = new \MangoPay\Wallet(mt_rand(10000000, 99999999));
        $expensesWallet = new \MangoPay\Wallet(mt_rand(10000000, 99999999));
        $taxWallet = new \MangoPay\Wallet(mt_rand(10000000, 99999999));
        $distributionWallet = new \MangoPay\Wallet(mt_rand(10000000, 99999999));
        $treasuryWallet = new \MangoPay\Wallet(mt_rand(10000000, 99999999));

        $container = static::getContainer();
        $walletService = $this
            ->getMockBuilder(\App\Service\MangopayWalletService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $walletService->method('createWallet')->willReturnOnConsecutiveCalls(
            $holdWallet,
            $settlementWallet,
            $depositWallet,
            $expensesWallet,
            $taxWallet,
            $distributionWallet,
            $treasuryWallet,
        );

        /** @var \App\Service\MangopayWalletService $walletService */
        $this->service = new AssetManagerV2(
            $container->get(\Psr\Log\LoggerInterface::class),
            $container->get(\App\Repository\AssetRepository::class),
            $container->get(\Doctrine\ORM\EntityManagerInterface::class),
            $container->get(\App\Dto\AssetAssembler::class),
            $container->get(\Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface::class),
            $walletService,
        );

        $user = new User();
        $user->setMangoPayUserId(000000);
        $asset = new Asset();
        $asset->setContactPoint($user);

        $this->service->createAllWallets($asset);

        $this->assertSame((string) $holdWallet->Id, $asset->getHoldWalletId());
        $this->assertSame(
            (string) $settlementWallet->Id,
            $asset->getSettlementWalletId(),
        );
        $this->assertSame((string) $depositWallet->Id, $asset->getDepositWalletId());
        $this->assertSame((string) $expensesWallet->Id, $asset->getExpensesWalletId());
        $this->assertSame((string) $taxWallet->Id, $asset->getTaxWalletId());
        $this->assertSame(
            (string) $distributionWallet->Id,
            $asset->getDistributionWalletId(),
        );
        $this->assertSame((string) $treasuryWallet->Id, $asset->getTreasuryWalletId());
    }

    public function testCreateAllWalletsOnlyMinimum(): void
    {
        $holdWallet = new \MangoPay\Wallet(mt_rand(10000000, 99999999));
        $settlementWallet = new \MangoPay\Wallet(mt_rand(10000000, 99999999));

        $container = static::getContainer();
        $walletService = $this
            ->getMockBuilder(\App\Service\MangopayWalletService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $walletService->method('createWallet')->willReturnOnConsecutiveCalls(
            $holdWallet,
            $settlementWallet,
        );

        /** @var \App\Service\MangopayWalletService $walletService */
        $this->service = new AssetManagerV2(
            $container->get(\Psr\Log\LoggerInterface::class),
            $container->get(\App\Repository\AssetRepository::class),
            $container->get(\Doctrine\ORM\EntityManagerInterface::class),
            $container->get(\App\Dto\AssetAssembler::class),
            $container->get(\Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface::class),
            $walletService,
        );

        $user = new User();
        $user->setMangoPayUserId(000000);
        $asset = new Asset();
        $asset->setContactPoint($user);

        $this->service->createAllWallets($asset, true);

        $this->assertSame((string) $holdWallet->Id, $asset->getHoldWalletId());
        $this->assertSame(
            (string) $settlementWallet->Id,
            $asset->getSettlementWalletId(),
        );
        $this->assertNull($asset->getDepositWalletId());
        $this->assertNull($asset->getExpensesWalletId());
        $this->assertNull($asset->getTaxWalletId());
        $this->assertNull($asset->getDistributionWalletId());
        $this->assertNull($asset->getTreasuryWalletId());
    }

    public function testCreateAllWalletsException(): void
    {
        $container = static::getContainer();

        $walletService = $this
            ->getMockBuilder(\App\Service\MangopayWalletService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $walletService->method('createWallet')->willThrowException(new \Exception());

        /** @var \App\Service\MangopayWalletService $walletService */
        $this->service = new AssetManagerV2(
            $container->get(\Psr\Log\LoggerInterface::class),
            $container->get(\App\Repository\AssetRepository::class),
            $container->get(\Doctrine\ORM\EntityManagerInterface::class),
            $container->get(\App\Dto\AssetAssembler::class),
            $container->get(\Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface::class),
            $walletService,
        );

        $user = new User();
        $user->setMangoPayUserId(000000);
        $asset = new Asset();
        $asset->setContactPoint($user);

        $this->expectException(\Exception::class);
        $this->service->createAllWallets($asset);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getAssetWalletsProvider')]
    public function testGetAssetWallets(
        ?array $walletServiceResponse,
        Asset $asset,
        array $result,
    ): void {
        $container = static::getContainer();

        $walletService = $this
            ->getMockBuilder(\App\Service\MangopayWalletService::class)
            ->disableOriginalConstructor()
            ->getMock();

        if (is_null($walletServiceResponse)) {
            $walletService->method('getWallet')->willThrowException(new \Exception());
        }
        if (!empty($walletServiceResponse)) {
            $walletService
                ->method('getWallet')
                ->willReturnOnConsecutiveCalls(...$walletServiceResponse);
        }

        /** @var \App\Service\MangopayWalletService $walletService */
        $this->service = new AssetManagerV2(
            $container->get(\Psr\Log\LoggerInterface::class),
            $container->get(\App\Repository\AssetRepository::class),
            $container->get(\Doctrine\ORM\EntityManagerInterface::class),
            $container->get(\App\Dto\AssetAssembler::class),
            $container->get(\Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface::class),
            $walletService,
        );

        $wallets = $this->service->getAssetWallets($asset);
        $this->assertSame($result, $wallets);
    }

    public static function getAssetWalletsProvider(): \Generator
    {
        $walletsExpected = [
            'hold',
            'settlement',
            'deposit',
            'expenses',
            'tax',
            'distribution',
            'treasury',
        ];

        $nullWallets = [];
        $assetWithNoWallets = new Asset();
        foreach ($walletsExpected as $walletType) {
            $nullWallets[] = [
                'type' => $walletType,
                'walletId' => null,
                'balance' => null,
                'currency' => null,
                'description' => null,
                'owner' => null,
            ];
        }

        $balance = new \MangoPay\Money();
        $balance->Amount = 10002585;
        $holdWallet = new \MangoPay\Wallet(mt_rand(10000000, 99999999));
        $holdWallet->Balance = $balance;
        $holdWallet->Currency = 'GBP';
        $holdWallet->Description = 'Hold Wallet';
        $holdWallet->Owners = ['26166', '27771'];

        $balance2 = new \MangoPay\Money();
        $balance2->Amount = 859752;
        $taxWallet = new \MangoPay\Wallet(mt_rand(10000000, 99999999));
        $taxWallet->Balance = $balance2;
        $taxWallet->Currency = 'GBP';
        $taxWallet->Description = 'Tax Wallet';
        $taxWallet->Owners = ['27771'];

        $partialWallets = [];
        foreach ($walletsExpected as $walletType) {
            $partialWallets[] = [
                'type' => $walletType,
                'walletId' => null,
                'balance' => null,
                'currency' => null,
                'description' => null,
                'owner' => null,
            ];
        }

        foreach ($partialWallets as &$wallet) {
            if ($wallet['type'] == 'hold') {
                $wallet['walletId'] = (string) $holdWallet->Id;
                $wallet['balance'] = '100025.85';
                $wallet['currency'] = 'GBP';
                $wallet['description'] = 'Hold Wallet';
                $wallet['owner'] = '26166';
            }
            if ($wallet['type'] == 'tax') {
                $wallet['walletId'] = (string) $taxWallet->Id;
                $wallet['balance'] = '8597.52';
                $wallet['currency'] = 'GBP';
                $wallet['description'] = 'Tax Wallet';
                $wallet['owner'] = '27771';
            }
        }

        $assetPartialWallets = new Asset();
        $assetPartialWallets->setHoldWalletId($holdWallet->Id);
        $assetPartialWallets->setTaxWalletId($taxWallet->Id);
        $assetWithMpException = new Asset();
        $mpExceptionN = [];

        foreach ($walletsExpected as $walletType) {
            $mpExceptionN[] = [
                'type' => $walletType,
                'walletId' => null,
                'balance' => null,
                'currency' => null,
                'description' => null,
                'owner' => null,
            ];
        }

        yield 'Null Wallets' => [[], $assetWithNoWallets, $nullWallets];
        yield 'Partial Wallets' => [
            [$holdWallet, $taxWallet],
            $assetPartialWallets,
            $partialWallets,
        ];
        yield 'Mangopay Error' => [null, $assetWithMpException, $mpExceptionN];
    }
}
