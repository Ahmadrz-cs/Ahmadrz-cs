<?php

namespace App\Tests\Service;

use App\Dto\Payment\LinkedPaymentRequestDto;
use App\Dto\Sca\ScaActionResponseDto;
use App\Dto\Sca\ScaOutcomeResponseDto;
use App\Entity\Asset;
use App\Entity\Enum\AssetStatus;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TradeStatus;
use App\Entity\ShareTrade;
use App\Entity\TradeOrder;
use App\Entity\TRANS_TYPE_CONSTANT;
use App\Entity\Transaction;
use App\Entity\User;
use App\Service\MangoPay;
use App\Service\TradingService;
use App\Test\Util\EntityIdTestUtil;
use BcMath\Number;
use MangoPay\Money;
use MangoPay\PendingUserAction;
use MangoPay\TransactionStatus;
use MangoPay\Transfer;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

final class TradingServiceTest extends KernelTestCase
{
    private TradingService $service;

    private MangoPay|MockObject $mangopayServiceMock;

    protected function setUp(): void
    {
        self::bootKernel();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('reserveSharesRetailProvider')]
    public function testReserveSharesRetail(
        TradeDirection $initiator,
        string $expectedPrice,
        string $initiatorPrice,
        string $counterpartyPrice,
    ): void {
        $asset = EntityIdTestUtil::setEntityId(new Asset(), 517);
        $asset->setPricePerShare('1.45');
        $asset->setCurrentStatus(AssetStatus::Active);
        $buyer = EntityIdTestUtil::setEntityId(new User(), 223);
        $seller = EntityIdTestUtil::setEntityId(new User(), 4);

        $buyOrder = new TradeOrder(
            direction: TradeDirection::Buy,
            asset: $asset,
            user: $buyer,
            numberOfShares: 500,
            pricePerShare: new Number($asset->getPricePerShare()),
            type: TradeOrderType::Market,
        );

        $sellOrder = new TradeOrder(
            direction: TradeDirection::Sell,
            asset: $asset,
            user: $seller,
            numberOfShares: 500,
            pricePerShare: new Number($asset->getPricePerShare()),
            type: TradeOrderType::Initial,
        );

        if ($initiator == TradeDirection::Buy) {
            $buyOrder->setPricePerShare($initiatorPrice);
            $sellOrder->setPricePerShare($counterpartyPrice);
        } else {
            $sellOrder->setPricePerShare($initiatorPrice);
            $buyOrder->setPricePerShare($counterpartyPrice);
        }

        $this->service = static::getContainer()->get(TradingService::class);
        $actual = $this->service->reserveShares(
            initiator: $initiator,
            buyOrder: $buyOrder,
            sellOrder: $sellOrder,
        );

        $this->assertEquals(new Number($expectedPrice), $actual->getPricePerShare());
        $this->assertEquals(500, $actual->getNumberOfShares());
        $this->assertEquals($buyOrder, $actual->getBuyOrder());
        $this->assertEquals($sellOrder, $actual->getSellOrder());
        $this->assertEquals(TradeStatus::Reserved, $actual->getStatus());
        $this->assertContains($actual, $buyOrder->getShareTrades());
        $this->assertContains($actual, $sellOrder->getShareTrades());
    }

    public static function reserveSharesRetailProvider(): \Generator
    {
        yield 'Buy initiated' => [TradeDirection::Buy, '1.45', '1.45', '1.45'];
        yield 'Sell initiated' => [TradeDirection::Sell, '1.45', '1.45', '1.45'];
        yield 'Initiator price diff used' => [
            TradeDirection::Buy,
            '1.85',
            '1.85',
            '1.45',
        ];
        yield 'Counterparty price diff ignored' => [
            TradeDirection::Buy,
            '1.45',
            '1.45',
            '1.85',
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('reserveSharesExceptionProvider')]
    public function testReserveSharesException(
        string $expectedMessage,
        string $exceptionClass = BadRequestException::class,
        TradeDirection $initiator = TradeDirection::Buy,
        TradeOrderType $buyType = TradeOrderType::Market,
        TradeOrderType $sellType = TradeOrderType::Initial,
        TradeOrderStatus $buyStatus = TradeOrderStatus::Submitted,
        TradeOrderStatus $sellStatus = TradeOrderStatus::Active,
        int $buyShares = 500,
        int $sellShares = 500,
        AssetStatus $assetStatus = AssetStatus::Active,
        bool $diffAsset = false,
    ): void {
        $asset = EntityIdTestUtil::setEntityId(new Asset(), 517);
        $asset->setPricePerShare('1.45');
        $asset->setCurrentStatus($assetStatus);
        $asset2 = EntityIdTestUtil::setEntityId(new Asset(), 532);
        $asset2->setPricePerShare('2.67');
        $asset2->setCurrentStatus($assetStatus);
        $buyer = EntityIdTestUtil::setEntityId(new User(), 223);
        $seller = EntityIdTestUtil::setEntityId(new User(), 4);

        $buyOrder = new TradeOrder(
            direction: TradeDirection::Buy,
            asset: $asset,
            user: $buyer,
            numberOfShares: $buyShares,
            pricePerShare: new Number($asset->getPricePerShare()),
            type: $buyType,
        );
        $buyOrder->setStatus($buyStatus);

        $sellOrder = new TradeOrder(
            direction: TradeDirection::Sell,
            asset: $diffAsset ? $asset2 : $asset,
            user: $seller,
            numberOfShares: $sellShares,
            pricePerShare: new Number($asset->getPricePerShare()),
            type: $sellType,
        );
        $sellOrder->setStatus($sellStatus);

        $this->expectExceptionMessage($expectedMessage);
        $this->expectException($exceptionClass);

        $this->service = static::getContainer()->get(TradingService::class);
        $this->service->reserveShares(
            initiator: $initiator,
            buyOrder: $buyOrder,
            sellOrder: $sellOrder,
        );
    }

    public static function reserveSharesExceptionProvider(): \Generator
    {
        yield 'Sell already completed' => [
            'expectedMessage' => 'order not in suitable state',
            'sellStatus' => TradeOrderStatus::Completed,
        ];
        yield 'Sell suspended' => [
            'expectedMessage' => 'order not in suitable state',
            'sellStatus' => TradeOrderStatus::Suspended,
        ];
        yield 'Buy cancelled' => [
            'expectedMessage' => 'order not in suitable state',
            'buyStatus' => TradeOrderStatus::Cancelled,
        ];
        yield 'Too few sell shares' => [
            'expectedMessage' => 'Not enough shares available',
            'initiator' => TradeDirection::Buy,
            'buyShares' => 500,
            'sellShares' => 499,
        ];
        yield 'Prefunding wrong asset status' => [
            'expectedMessage' => 'must be in fundraising/acquiring status to prefund',
            'assetStatus' => AssetStatus::Active,
            'buyType' => TradeOrderType::Prefunding,
        ];
        yield 'Different assets' => [
            'expectedMessage' => 'cannot be for different assets',
            'diffAsset' => true,
        ];
        // Won't be trying all combos as it is tested in the ShareTradeType test
        yield 'Invalid buy-sell combo - both prefunding' => [
            'expectedMessage' => 'Unsupported buy-sell order pairing types',
            'buyType' => TradeOrderType::Prefunding,
            'sellType' => TradeOrderType::Prefunding,
        ];
        yield 'Invalid buy-sell combo - prefunding on market' => [
            'expectedMessage' => 'Unsupported buy-sell order pairing types',
            'buyType' => TradeOrderType::Prefunding,
            'sellType' => TradeOrderType::Market,
        ];
        yield 'Invalid buy-sell combo - buy back on prefunding' => [
            'expectedMessage' => 'Unsupported buy-sell order pairing types',
            'buyType' => TradeOrderType::BuyBack,
            'sellType' => TradeOrderType::Prefunding,
        ];
        yield 'Invalid buy-sell combo - prefunding on proxy' => [
            'expectedMessage' => 'Unsupported buy-sell order pairing types',
            'buyType' => TradeOrderType::Prefunding,
            'sellType' => TradeOrderType::Proxy,
        ];
        yield 'Invalid buy-sell combo - proxy on market' => [
            'expectedMessage' => 'Unsupported buy-sell order pairing types',
            'buyType' => TradeOrderType::Proxy,
            'sellType' => TradeOrderType::Market,
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validTradeOrderProvider')]
    public function testValidateTradeOrder(
        TradeDirection $direction = TradeDirection::Buy,
        TradeOrderType $orderType = TradeOrderType::Market,
        AssetStatus $assetStatus = AssetStatus::Active,
    ): void {
        $asset = EntityIdTestUtil::setEntityId(new Asset(), 517);
        $asset->setPricePerShare('1.45');
        $asset->setCurrentStatus($assetStatus);
        $user = EntityIdTestUtil::setEntityId(new User(), 223);

        $tradeOrder = new TradeOrder(
            direction: $direction,
            asset: $asset,
            user: $user,
            numberOfShares: 100,
            pricePerShare: new Number(1),
            type: $orderType,
        );

        $this->service = static::getContainer()->get(TradingService::class);
        $actual = $this->service->validateTradeOrder($tradeOrder);
        $this->assertTrue($actual);
    }

    public static function validTradeOrderProvider(): \Generator
    {
        foreach (TradeDirection::cases() as $direction) {
            yield "{$direction->name}" => [
                'direction' => $direction,
            ];
        }
        foreach (AssetStatus::activeCases() as $status) {
            yield "Asset {$status->value}" => [
                'assetStatus' => $status,
            ];
        }
        foreach (TradeOrderType::tradingBuyTypes() as $type) {
            yield "Order type {$type->value}" => [
                'orderType' => $type,
            ];
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validateTradeOrderExceptionProvider')]
    public function testValidateTradeOrderException(
        string $expectedMessage,
        string $exceptionClass = BadRequestException::class,
        TradeDirection $direction = TradeDirection::Buy,
        TradeOrderType $orderType = TradeOrderType::Market,
        AssetStatus $assetStatus = AssetStatus::Active,
        string $sharePrice = '1.86',
        int $shareQuantity = 100,
        bool $buyRestricted = false,
        bool $sellRestricted = false,
    ): void {
        $asset = EntityIdTestUtil::setEntityId(new Asset(), 517);
        $asset->setPricePerShare('1.45');
        $asset->setCurrentStatus($assetStatus);
        $asset->setBuyRestricted($buyRestricted);
        $asset->setSellRestricted($sellRestricted);
        $user = EntityIdTestUtil::setEntityId(new User(), 223);

        $tradeOrder = new TradeOrder(
            direction: $direction,
            asset: $asset,
            user: $user,
            numberOfShares: $shareQuantity,
            pricePerShare: new Number($sharePrice),
            type: $orderType,
        );

        $this->expectExceptionMessage($expectedMessage);
        $this->expectException($exceptionClass);

        $this->service = static::getContainer()->get(TradingService::class);
        $this->service->validateTradeOrder($tradeOrder);
    }

    public static function validateTradeOrderExceptionProvider(): \Generator
    {
        yield 'Buy restricted' => [
            'expectedMessage' => 'Buying shares in this asset is currently restricted',
            'direction' => TradeDirection::Buy,
            'buyRestricted' => true,
        ];
        yield 'Sell restricted' => [
            'expectedMessage' => 'Selling shares in this asset is currently restricted',
            'direction' => TradeDirection::Sell,
            'sellRestricted' => true,
        ];
        yield 'No shares' => [
            'expectedMessage' => 'Number of shares must be greater than zero',
            'shareQuantity' => 0,
        ];
        yield 'No price' => [
            'expectedMessage' => 'Price per share must be greater than zero',
            'sharePrice' => '0',
        ];
        foreach (array_udiff(
            AssetStatus::cases(),
            AssetStatus::activeCases(),
            fn($r1, $r2) => $r1->value <=> $r2->value,
        ) as $status) {
            yield "Asset {$status->value}" => [
                'expectedMessage' => 'Asset must be in a tradeable status to invest',
                'assetStatus' => $status,
            ];
        }
        foreach (array_udiff(
            TradeOrderType::cases(),
            TradeOrderType::tradingBuyTypes(),
            fn($r1, $r2) => $r1->value <=> $r2->value,
        ) as $type) {
            yield "Order type {$type->value}" => [
                'expectedMessage' => 'Trade order type must be a valid trading type',
                'orderType' => $type,
            ];
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validShareTradeProvider')]
    public function testValidateShareTrade(
        TradeOrderType $buyType = TradeOrderType::Market,
        TradeOrderType $sellType = TradeOrderType::Initial,
        AssetStatus $assetStatus = AssetStatus::Active,
    ): void {
        $asset = EntityIdTestUtil::setEntityId(new Asset(), 517);
        $asset->setPricePerShare('1.45');
        $asset->setCurrentStatus($assetStatus);
        $buyer = EntityIdTestUtil::setEntityId(new User(), 223);
        $seller = EntityIdTestUtil::setEntityId(new User(), 4);
        $buyOrder = new TradeOrder(
            direction: TradeDirection::Buy,
            asset: $asset,
            user: $buyer,
            numberOfShares: 500,
            pricePerShare: new Number($asset->getPricePerShare()),
            type: $buyType,
        );

        $sellOrder = new TradeOrder(
            direction: TradeDirection::Sell,
            asset: $asset,
            user: $seller,
            numberOfShares: 500,
            pricePerShare: new Number($asset->getPricePerShare()),
            type: $sellType,
        );
        $shareTrade = new ShareTrade(
            buyOrder: $buyOrder,
            sellOrder: $sellOrder,
            numberOfShares: 500,
            pricePerShare: $buyOrder->getPricePerShare(),
        );

        $this->service = static::getContainer()->get(TradingService::class);
        $actual = $this->service->validateShareTrade($shareTrade);
        $this->assertTrue($actual);
    }

    public static function validShareTradeProvider(): \Generator
    {
        yield 'Prefunding' => [
            'buyType' => TradeOrderType::Prefunding,
            'assetStatus' => AssetStatus::Acquiring,
        ];
        yield 'Buy-sell combo primary' => [
            'buyType' => TradeOrderType::Market,
            'sellType' => TradeOrderType::Initial,
        ];
        yield 'Buy-sell combo secondary' => [
            'buyType' => TradeOrderType::Market,
            'sellType' => TradeOrderType::Market,
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validateShareTradeExceptionProvider')]
    public function testValidateShareTradeException(
        string $expectedMessage,
        string $exceptionClass = BadRequestException::class,
        TradeOrderType $buyType = TradeOrderType::Market,
        TradeOrderType $sellType = TradeOrderType::Initial,
        AssetStatus $assetStatus = AssetStatus::Active,
        bool $diffAsset = false,
    ): void {
        $asset = EntityIdTestUtil::setEntityId(new Asset(), 517);
        $asset->setPricePerShare('1.45');
        $asset->setCurrentStatus($assetStatus);
        $asset2 = EntityIdTestUtil::setEntityId(new Asset(), 532);
        $asset2->setPricePerShare('2.67');
        $asset2->setCurrentStatus($assetStatus);
        $buyer = EntityIdTestUtil::setEntityId(new User(), 223);
        $seller = EntityIdTestUtil::setEntityId(new User(), 4);
        $buyOrder = new TradeOrder(
            direction: TradeDirection::Buy,
            asset: $asset,
            user: $buyer,
            numberOfShares: 500,
            pricePerShare: new Number($asset->getPricePerShare()),
            type: $buyType,
        );

        $sellOrder = new TradeOrder(
            direction: TradeDirection::Sell,
            asset: $diffAsset ? $asset2 : $asset,
            user: $seller,
            numberOfShares: 500,
            pricePerShare: new Number($asset->getPricePerShare()),
            type: $sellType,
        );
        $shareTrade = new ShareTrade(
            buyOrder: $buyOrder,
            sellOrder: $sellOrder,
            numberOfShares: 500,
            pricePerShare: $buyOrder->getPricePerShare(),
        );

        $this->expectExceptionMessage($expectedMessage);
        $this->expectException($exceptionClass);

        $this->service = static::getContainer()->get(TradingService::class);
        $this->service->validateShareTrade($shareTrade);
    }

    public static function validateShareTradeExceptionProvider(): \Generator
    {
        yield 'Prefunding wrong asset status' => [
            'expectedMessage' => 'must be in fundraising/acquiring status to prefund',
            'assetStatus' => AssetStatus::Active,
            'buyType' => TradeOrderType::Prefunding,
        ];
        yield 'Different assets' => [
            'expectedMessage' => 'cannot be for different assets',
            'diffAsset' => true,
        ];
        // Won't be trying all combos as it is tested in the ShareTradeType test
        yield 'Invalid buy-sell combo - both prefunding' => [
            'expectedMessage' => 'Unsupported buy-sell order pairing types',
            'buyType' => TradeOrderType::Prefunding,
            'sellType' => TradeOrderType::Prefunding,
        ];
        yield 'Invalid buy-sell combo - prefunding on market' => [
            'expectedMessage' => 'Unsupported buy-sell order pairing types',
            'buyType' => TradeOrderType::Prefunding,
            'sellType' => TradeOrderType::Market,
        ];
        yield 'Invalid buy-sell combo - buy back on prefunding' => [
            'expectedMessage' => 'Unsupported buy-sell order pairing types',
            'buyType' => TradeOrderType::BuyBack,
            'sellType' => TradeOrderType::Prefunding,
        ];
        yield 'Invalid buy-sell combo - prefunding on proxy' => [
            'expectedMessage' => 'Unsupported buy-sell order pairing types',
            'buyType' => TradeOrderType::Prefunding,
            'sellType' => TradeOrderType::Proxy,
        ];
        yield 'Invalid buy-sell combo - proxy on market' => [
            'expectedMessage' => 'Unsupported buy-sell order pairing types',
            'buyType' => TradeOrderType::Proxy,
            'sellType' => TradeOrderType::Market,
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('complementaryOrderExceptionProvider')]
    public function testValidateComplementaryOrderException(
        string $expectedMessage,
        string $exceptionClass = BadRequestException::class,
        TradeOrderType $buyType = TradeOrderType::Prefunding,
        TradeOrderType $sellType = TradeOrderType::Prefunding,
        AssetStatus $assetStatus = AssetStatus::Acquiring,
        int $buyShares = 500,
        int $sellShares = 500,
        bool $diffAsset = false,
        bool $diffUser = false,
        bool $sameDirection = false,
    ): void {
        $asset = EntityIdTestUtil::setEntityId(new Asset(), 517);
        $asset->setPricePerShare('1.45');
        $asset->setCurrentStatus($assetStatus);
        $asset2 = EntityIdTestUtil::setEntityId(new Asset(), 532);
        $asset2->setPricePerShare('2.67');
        $asset2->setCurrentStatus($assetStatus);
        $user = EntityIdTestUtil::setEntityId(new User(), 223);
        $user2 = EntityIdTestUtil::setEntityId(new User(), 4515);
        $tradeOrder = new TradeOrder(
            direction: TradeDirection::Buy,
            asset: $asset,
            user: $user,
            numberOfShares: $buyShares,
            pricePerShare: new Number($asset->getPricePerShare()),
            type: $buyType,
        );

        $complement = new TradeOrder(
            direction: $sameDirection ? TradeDirection::Buy : TradeDirection::Sell,
            asset: $diffAsset ? $asset2 : $asset,
            user: $diffUser ? $user2 : $user,
            numberOfShares: $sellShares,
            pricePerShare: new Number($asset->getPricePerShare()),
            type: $sellType,
        );

        $this->expectExceptionMessage($expectedMessage);
        $this->expectException($exceptionClass);

        $this->service = static::getContainer()->get(TradingService::class);
        $this->service->validateComplementaryOrder($tradeOrder, $complement);
    }

    public static function complementaryOrderExceptionProvider(): \Generator
    {
        yield 'Prefunding wrong asset status' => [
            'expectedMessage' => 'must be in fundraising/acquiring status to prefund',
            'assetStatus' => AssetStatus::Active,
        ];
        yield 'Different assets' => [
            'expectedMessage' => 'cannot be for different assets',
            'diffAsset' => true,
        ];
        yield 'Different users' => [
            'expectedMessage' => 'cannot be for different users',
            'diffUser' => true,
        ];
        yield 'Same direction' => [
            'expectedMessage' => 'must be opposite directions',
            'sameDirection' => true,
        ];
        yield 'Same type but not prefunding' => [
            'expectedMessage' => 'must both prefunding type',
            'buyType' => TradeOrderType::Market,
            'sellType' => TradeOrderType::Market,
        ];
        yield 'Different types' => [
            'expectedMessage' => 'must both prefunding type',
            'buyType' => TradeOrderType::Prefunding,
            'sellType' => TradeOrderType::Market,
        ];
        yield 'Sell more than bought' => [
            'expectedMessage' => 'sell shares cannot be greater than the buy shares',
            'buyShares' => 100,
            'sellShares' => 500,
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('takeTradeOrderPaymentProvider')]
    public function testTakeTradeOrderPayment(bool $hasRedirectUrl): void
    {
        $asset = EntityIdTestUtil::setEntityId(new Asset(), 517);
        $asset->setPricePerShare('1.45');
        $asset->setCurrentStatus(AssetStatus::Active);
        $buyer = EntityIdTestUtil::setEntityId(new User(), 223);
        $tradeOrder = EntityIdTestUtil::setEntityId(
            new TradeOrder(
                direction: TradeDirection::Buy,
                asset: $asset,
                user: $buyer,
                numberOfShares: 500,
                pricePerShare: new Number($asset->getPricePerShare()),
                type: TradeOrderType::Market,
            ),
            1745,
        );
        $tradeOrder->setStatus(TradeOrderStatus::Submitted);
        $dto = new LinkedPaymentRequestDto('1.09', true);
        $pendingUserAction = new PendingUserAction();
        if ($hasRedirectUrl) {
            $pendingUserAction->RedirectUrl =
                'https://example.com/' . bin2hex(random_bytes(8));
        }
        $mangopayTransfer = new Transfer('xfer_t_' . bin2hex(random_bytes(6)));
        $mangopayTransfer->PendingUserAction = $pendingUserAction;

        $mangopayTransfer->DebitedFunds = new Money();
        $mangopayTransfer->Fees = new Money();
        $mangopayTransfer->DebitedWalletId = 'test_debit_wallet';
        $mangopayTransfer->CreditedWalletId = 'test_credit_wallet';
        $mangopayTransfer->Status = TransactionStatus::Succeeded;

        $this->mangopayServiceMock = $this->createMock(Mangopay::class);
        static::getContainer()->set(Mangopay::class, $this->mangopayServiceMock);
        $this->mangopayServiceMock
            ->expects(self::once())
            ->method('createTradeOrderTransfer')
            ->with($tradeOrder, '1.09', true)
            ->willReturn($mangopayTransfer);

        $expected = new ScaActionResponseDto(
            id: '1745',
            object: 'tradeOrder',
            status: TradeOrderStatus::Submitted->value,
            providerId: $mangopayTransfer->Id,
            providerStatus: $mangopayTransfer->Status,
            pendingUserAction: $hasRedirectUrl
                ? ['redirectUrl' => $pendingUserAction->RedirectUrl]
                : [],
        );
        $this->service = static::getContainer()->get(TradingService::class);
        $actual = $this->service->takeTradeOrderPayment($tradeOrder, $dto);
        $this->assertEquals($expected, $actual);
    }

    public static function takeTradeOrderPaymentProvider(): \Generator
    {
        yield 'Has redirectUrl' => [true];
        yield 'No redirectUrl' => [false];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('paymentOutcomeProvider')]
    public function testProcessPaymentOutcome(
        TradeOrderStatus $expectedOrderStatus,
        TradeOrderStatus $expectedComplementStatus,
        TradeStatus $expectedTradeStatus,
        string $expectedTransactionStatus,
        bool $success,
        TradeOrderType $complementType = TradeOrderType::Prefunding,
        TradeDirection $direction = TradeDirection::Buy,
        TradeOrderStatus $orderStartStatus = TradeOrderStatus::Submitted,
        TradeOrderStatus $complementStartStatus = TradeOrderStatus::Draft,
        TradeStatus $tradeStartStatus = TradeStatus::Reserved,

        bool $full = false,
    ): void {
        $asset = EntityIdTestUtil::setEntityId(new Asset(), 517);
        $asset->setPricePerShare('1.45');
        $asset->setCurrentStatus(AssetStatus::Active);
        $buyer = EntityIdTestUtil::setEntityId(new User(), 223);
        $tradeOrder = EntityIdTestUtil::setEntityId(
            new TradeOrder(
                direction: $direction,
                asset: $asset,
                user: $buyer,
                numberOfShares: 500,
                pricePerShare: new Number($asset->getPricePerShare()),
                type: TradeOrderType::Market,
            ),
            1745,
        );
        $complement = EntityIdTestUtil::setEntityId(
            new TradeOrder(
                direction: $direction->opposite(),
                asset: $asset,
                user: $buyer,
                numberOfShares: 500,
                pricePerShare: new Number($asset->getPricePerShare()),
                type: $complementType,
            ),
            1745,
        );
        $shareTrade = new ShareTrade(
            numberOfShares: $full ? $tradeOrder->getNumberOfShares() : 1,
        );
        $transaction = new Transaction();
        $transaction->setPaymentStatus(TransactionStatus::Created);

        $tradeOrder->setStatus($orderStartStatus);
        $complement->setStatus($complementStartStatus);
        $shareTrade->setStatus($tradeStartStatus);

        $tradeOrder->setComplementaryOrder($complement);
        $tradeOrder->addShareTrade($shareTrade);
        $tradeOrder->setTransaction($transaction);

        $refString = bin2hex(random_bytes(8));
        $tradeOrder->setTransactionReference($refString);

        $expected = new ScaOutcomeResponseDto(
            id: '1745',
            object: 'tradeOrder',
            status: $expectedOrderStatus->value,
            providerId: $refString,
            success: $success,
        );

        $this->service = static::getContainer()->get(TradingService::class);
        $actual = $this->service->processPaymentOutcome($tradeOrder, $success);
        $this->assertEquals($expected, $actual);

        $this->assertEquals($expectedOrderStatus, $tradeOrder->getStatus());
        $this->assertEquals($expectedTradeStatus, $shareTrade->getStatus());
        $this->assertEquals($expectedComplementStatus, $complement->getStatus());
        $this->assertEquals(
            $expectedTransactionStatus,
            $transaction->getPaymentStatus(),
        );
    }

    public static function paymentOutcomeProvider(): \Generator
    {
        yield 'Success - Full' => [
            'expectedOrderStatus' => TradeOrderStatus::Completed,
            'expectedComplementStatus' => TradeOrderStatus::Active,
            'expectedTradeStatus' => TradeStatus::Unsettled,
            'expectedTransactionStatus' => TransactionStatus::Succeeded,
            'success' => true,
            'full' => true,
        ];
        yield 'Success - partial' => [
            'expectedOrderStatus' => TradeOrderStatus::Active,
            'expectedComplementStatus' => TradeOrderStatus::Active,
            'expectedTradeStatus' => TradeStatus::Unsettled,
            'expectedTransactionStatus' => TransactionStatus::Succeeded,
            'success' => true,
            'full' => false,
        ];
        yield 'Success - not prefunding complement ignored' => [
            'expectedOrderStatus' => TradeOrderStatus::Active,
            'expectedComplementStatus' => TradeOrderStatus::Draft,
            'expectedTradeStatus' => TradeStatus::Unsettled,
            'expectedTransactionStatus' => TransactionStatus::Succeeded,
            'success' => true,
            'complementType' => TradeOrderType::Market,
            'complementStartStatus' => TradeOrderStatus::Draft,
        ];
        yield 'Success - trade not reserved state' => [
            'expectedOrderStatus' => TradeOrderStatus::Active,
            'expectedComplementStatus' => TradeOrderStatus::Active,
            'expectedTradeStatus' => TradeStatus::Draft,
            'expectedTransactionStatus' => TransactionStatus::Succeeded,
            'success' => true,
            'tradeStartStatus' => TradeStatus::Draft,
        ];
        yield 'Success - order was cancelled but will be reactivated' => [
            'expectedOrderStatus' => TradeOrderStatus::Active,
            'expectedComplementStatus' => TradeOrderStatus::Active,
            'expectedTradeStatus' => TradeStatus::Unsettled,
            'expectedTransactionStatus' => TransactionStatus::Succeeded,
            'success' => true,
            'orderStartStatus' => TradeOrderStatus::Cancelled,
        ];
        yield 'Failed' => [
            'expectedOrderStatus' => TradeOrderStatus::Cancelled,
            'expectedComplementStatus' => TradeOrderStatus::Cancelled,
            'expectedTradeStatus' => TradeStatus::Cancelled,
            'expectedTransactionStatus' => TransactionStatus::Failed,
            'success' => false,
        ];
    }

    public function testCreateTradeOrderTransaction(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 5146);
        $tradeOrder = EntityIdTestUtil::setEntityId(new TradeOrder(user: $user), 13681);

        $transfer = new \MangoPay\Transfer();
        $transfer->Id = 'xfer_c_01JXAWYTQVQD78EC5JMD822AX0';
        $debit = new \MangoPay\Money();
        $debit->Amount = 40684;
        $debit->Currency = 'GBP';
        $fee = new \MangoPay\Money();
        $fee->Amount = 50;
        $fee->Currency = 'GBP';
        $transfer->DebitedFunds = $debit;
        $transfer->Fees = $fee;
        $transfer->Status = \MangoPay\TransactionStatus::Created;
        $transfer->DebitedWalletId = 'wlt_m_01HW3EFRH8GM978NHCM8ZGGXGV';
        $transfer->CreditedWalletId = 'wlt_m_01HW3DD8S6MFPYGVC0FPBHXAF2';

        $expected = new Transaction();
        $expected->setReferenceId($transfer->Id);
        $expected->setValueAmount((string) $debit->Amount);
        $expected->setFee((string) $fee->Amount);
        $expected->setCurrency($debit->Currency);
        $expected->setDebitorId($user->getId());
        $expected->setDebitedWalletId($transfer->DebitedWalletId);
        $expected->setCreditedWalletId($transfer->CreditedWalletId);
        $expected->setTradeOrder($tradeOrder);
        $expected->setPaymentStatus($transfer->Status);
        $expected->setTransType(TRANS_TYPE_CONSTANT::TRANS_NP);

        $this->service = static::getContainer()->get(TradingService::class);
        $actual = $this->service->createTradeOrderTransaction($tradeOrder, $transfer);
        $this->assertEquals($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('sellOrderMinSharesProvider')]
    public function testPrepareSellOrder(
        ?int $expectedMinShares,
        ?string $assetMinInvest = null,
        ?int $orderMinShares = null,
        string $pricePerShare = '1.27',
        TradeDirection $direction = TradeDirection::Sell,
        bool $hasAsset = true,
    ): void {
        $asset = $asset = EntityIdTestUtil::setEntityId(new Asset(), 517);
        $asset->setMinimumInvestment($assetMinInvest);
        $tradeOrder = new TradeOrder(
            direction: $direction,
            asset: $hasAsset ? $asset : null,
            pricePerShare: new Number($pricePerShare),
            numberOfShares: 810,
        );
        $tradeOrder->setMinimumShares($orderMinShares);

        $this->service = static::getContainer()->get(TradingService::class);
        $actual = $this->service->prepareSellOrder($tradeOrder);
        $this->assertEquals($expectedMinShares, $actual->getMinimumShares());
    }

    public static function sellOrderMinSharesProvider(): \Generator
    {
        yield 'Default to 100' => [
            'expectedMinShares' => 79,
        ];
        yield 'Use asset minimum below default 100' => [
            'expectedMinShares' => 39,
            'assetMinInvest' => '48.50',
        ];
        yield 'Use asset minimum below above 100' => [
            'expectedMinShares' => 398,
            'assetMinInvest' => '505.25',
        ];
        yield 'Use order existing minimum' => [
            'expectedMinShares' => 651,
            'orderMinShares' => 651,
        ];
        yield 'Use order existing minimum more than offered' => [
            'expectedMinShares' => 810,
            'orderMinShares' => 2410,
        ];
        yield 'No asset all null' => [
            'expectedMinShares' => null,
            'hasAsset' => false,
        ];
        yield 'No asset has existing more than offered fixed' => [
            'expectedMinShares' => 810,
            'orderMinShares' => 2410,
            'hasAsset' => false,
        ];
        yield 'Buy all null' => [
            'expectedMinShares' => null,
            'direction' => TradeDirection::Buy,
        ];
        yield 'Buy all has existing more than offered not fixed' => [
            'expectedMinShares' => 2410,
            'orderMinShares' => 2410,
            'direction' => TradeDirection::Buy,
        ];
        yield 'Zero pricePerShare' => [
            'expectedMinShares' => null,
            'pricePerShare' => '0',
            'hasAsset' => false,
        ];
        yield 'Zero pricePerShare has existing more than offered fixed' => [
            'expectedMinShares' => 810,
            'orderMinShares' => 2410,
            'pricePerShare' => '0.00',
            'hasAsset' => false,
        ];
    }
}
