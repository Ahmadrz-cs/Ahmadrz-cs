<?php

namespace App\Tests\Service;

use App\Entity\Asset;
use App\Entity\Enum\QueryGrouping;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TradeStatus;
use App\Entity\PaymentOrder;
use App\Entity\PaymentRequest;
use App\Entity\Payout;
use App\Entity\ShareTrade;
use App\Entity\ShareTradeStatusLog;
use App\Entity\TradeOrder;
use App\Entity\TradeOrderStatusLog;
use App\Entity\User;
use App\Service\DivestmentService;
use App\Service\PaymentService;
use App\Test\Util\EntityIdTestUtil;
use BcMath\Number;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DivestmentServiceTest extends KernelTestCase
{
    private DivestmentService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(DivestmentService::class);
    }

    public function testCreateBuyBackOrder(): void
    {
        $paymentOrder = new PaymentOrder();
        $paymentOrder->setAsset(EntityIdTestUtil::setEntityId(new Asset(), 253));
        $debitWalletId = bin2hex(random_bytes(8));
        $paymentOrder->getAsset()->setMainWalletId($debitWalletId);
        // $paymentOrder->getAsset()->setDistributionWalletId($debitWalletId);
        $paymentOrder->setPaymentType(PaymentService::TYPE_DIVESTMENT);
        $paymentOrder->setScheduledFor(new \DateTime());
        $paymentOrder->setStatus(PaymentOrder::STATE_APPROVED);

        foreach (range(1, 3) as $i) {
            $payment = new PaymentRequest();
            $payment->setPayee(new User());
            $payment->setAmount((string) round($i * 15, 2));
            $payment->setShareholding($i * 10);
            $payment->setStatus(PaymentRequest::STATE_PENDING);
            $paymentOrder->addPayment($payment);
        }

        $initialOrder = new TradeOrder(
            direction: TradeDirection::Sell,
            asset: $paymentOrder->getAsset(),
            user: EntityIdTestUtil::setEntityId(new User(), 214),
            numberOfShares: 15000,
            pricePerShare: new Number('1.45'),
            type: TradeOrderType::Initial,
        );
        $actual = $this->service->createBuyBackOrder($paymentOrder, $initialOrder);
        $this->assertEquals(TradeOrderStatus::Active, $actual->getStatus());
        $this->assertEquals('1.50', $actual->getPricePerShare());
        $this->assertEquals(60, $actual->getNumberOfShares());
        $this->assertEquals(TradeDirection::Buy, $actual->getDirection());
        $this->assertEquals(TradeOrderType::BuyBack, $actual->getType());
        $this->assertEquals($initialOrder->getAsset(), $actual->getAsset());
        $this->assertEquals($initialOrder->getUser(), $actual->getUser());

        $actual = $this->service->createBuyBackOrder(
            $paymentOrder,
            $initialOrder,
            TradeOrderType::Proxy,
        );
        $this->assertEquals(TradeOrderType::Proxy, $actual->getType());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('finishBuyBackOrderStatesProvider')]
    public function testFinishBuyBackOrder(
        TradeOrderStatus $expected,
        TradeOrderStatus $start,
        int $buyBackShares = 0,
        array $buyBackTrades = [],
        TradeStatus $tradeStatus = TradeStatus::Settled,
    ): void {
        $buyBackOrder = new TradeOrder(
            direction: TradeDirection::Buy,
            asset: new Asset(),
            user: new User(),
            numberOfShares: $buyBackShares,
            pricePerShare: new Number(1),
        );
        $activeLog = new TradeOrderStatusLog($buyBackOrder, $start);
        $buyBackOrder->addStatusLog($activeLog);

        foreach ($buyBackTrades as $i) {
            $trade = new ShareTrade(buyOrder: $buyBackOrder, numberOfShares: $i);
            $settledLog = new ShareTradeStatusLog($trade, $tradeStatus);
            $trade->addStatusLog($settledLog);
            $buyBackOrder->addShareTrade($trade);
        }

        $actual = $this->service->finishBuyBackOrder($buyBackOrder);
        $this->assertEquals($expected, $actual->getStatus());
    }

    public static function finishBuyBackOrderStatesProvider(): \Generator
    {
        yield 'Draft to completed' => [
            TradeOrderStatus::Completed,
            TradeOrderStatus::Draft,
            60,
            [15, 20, 12, 13],
        ];
        yield 'Submitted to completed' => [
            TradeOrderStatus::Completed,
            TradeOrderStatus::Submitted,
            60,
            [15, 20, 12, 13],
        ];
        yield 'Active to completed' => [
            TradeOrderStatus::Completed,
            TradeOrderStatus::Active,
            60,
            [15, 20, 12, 13],
        ];
        yield 'Suspended to completed' => [
            TradeOrderStatus::Completed,
            TradeOrderStatus::Suspended,
        ];
        // Shouldn't usually happen as we only call this method when we're finished
        yield 'Active to cancelled' => [
            TradeOrderStatus::Cancelled,
            TradeOrderStatus::Active,
            60,
            [15, 20, 12],
        ];
        yield 'Active to cancelled, not settled' => [
            TradeOrderStatus::Cancelled,
            TradeOrderStatus::Active,
            60,
            [15, 20, 12, 13],
            TradeStatus::Unsettled,
        ];
        yield 'Completed stays completed' => [
            TradeOrderStatus::Completed,
            TradeOrderStatus::Completed,
        ];
        yield 'Cancelled stays cancelled' => [
            TradeOrderStatus::Cancelled,
            TradeOrderStatus::Cancelled,
        ];
    }

    public function testCreateDivestmentOrder(): void
    {
        $paymentOrder = new PaymentOrder();
        $paymentOrder->setAsset(EntityIdTestUtil::setEntityId(new Asset(), 253));
        $debitWalletId = bin2hex(random_bytes(8));
        $paymentOrder->getAsset()->setMainWalletId($debitWalletId);
        // $paymentOrder->getAsset()->setDistributionWalletId($debitWalletId);
        $paymentOrder->setPaymentType(PaymentService::TYPE_DIVESTMENT);
        $paymentOrder->setScheduledFor(new \DateTime());
        $paymentOrder->setStatus(PaymentOrder::STATE_APPROVED);

        $payment = new PaymentRequest();
        $payment->setPayee(EntityIdTestUtil::setEntityId(new User(), 5515));
        $payment->setAmount('87.59');
        $payment->setShareholding(15);
        $payment->setStatus(PaymentRequest::STATE_PAID);
        $paymentOrder->addPayment($payment);

        $actual = $this->service->createDivestmentOrder($payment);
        $this->assertEquals(TradeOrderStatus::Completed, $actual->getStatus());
        $this->assertEquals('5.839333', $actual->getPricePerShare());
        $this->assertEquals(15, $actual->getNumberOfShares());
        $this->assertEquals(TradeDirection::Sell, $actual->getDirection());
        $this->assertEquals(TradeOrderType::BuyBack, $actual->getType());
        $this->assertEquals($paymentOrder->getAsset(), $actual->getAsset());
        $this->assertEquals($payment->getPayee(), $actual->getUser());
        $this->assertNull($actual->getTransactionReference());

        // If a payout is configured
        $payout = new Payout();
        $actual = $this->service->createDivestmentOrder($payment);
        $this->assertNull($actual->getTransactionReference());

        // Something is finally set if a transaction Id is configured
        $payout->setTransactionId('test_payout_' . bin2hex(random_bytes(6)));
        $payment->setPayout($payout);

        $actual = $this->service->createDivestmentOrder($payment);
        $this->assertEquals(
            $payout->getTransactionId(),
            $actual->getTransactionReference(),
        );
    }

    public function testcreateBuyBackTrade(): void
    {
        $paymentOrder = new PaymentOrder();
        $paymentOrder->setAsset(EntityIdTestUtil::setEntityId(new Asset(), 253));
        $debitWalletId = bin2hex(random_bytes(8));
        $paymentOrder->getAsset()->setMainWalletId($debitWalletId);
        // $paymentOrder->getAsset()->setDistributionWalletId($debitWalletId);
        $paymentOrder->setPaymentType(PaymentService::TYPE_DIVESTMENT);
        $paymentOrder->setScheduledFor(new \DateTime());
        $paymentOrder->setStatus(PaymentOrder::STATE_APPROVED);

        $buyBackOrder = new TradeOrder(
            direction: TradeDirection::Buy,
            asset: $paymentOrder->getAsset(),
            user: new User(),
            numberOfShares: 100,
            pricePerShare: new Number('5.839333'),
        );
        $paymentOrder->setTradeOrder($buyBackOrder);

        $payment = new PaymentRequest();
        $payment->setPayee(EntityIdTestUtil::setEntityId(new User(), 5515));
        $payment->setAmount('87.59');
        $payment->setShareholding(15);
        $payment->setStatus(PaymentRequest::STATE_PAID);
        $paymentOrder->addPayment($payment);

        $sellOrder = new TradeOrder(
            direction: TradeDirection::Sell,
            asset: $payment->getPaymentOrder()->getAsset(),
            user: $payment->getPayee(),
            // Use a different amount here to the payment
            // To simulate how a repayment works, where the sell side dwarfs the buy side
            numberOfShares: 200,
            pricePerShare: new Number('5.839333'),
            type: TradeOrderType::BuyBack,
        );

        $actual = $this->service->createBuyBackTrade($payment, $sellOrder);
        $this->assertEquals(TradeStatus::Settled, $actual->getStatus());
        $this->assertEquals('5.839333', $actual->getPricePerShare());
        $this->assertEquals(15, $actual->getNumberOfShares());
        $this->assertEquals('87.59', $actual->getTradeValue());
        $this->assertFalse($actual->isDerived());
        // Check that the share trade have been added to both buy and sell orders
        $this->assertContains($actual, $buyBackOrder->getShareTrades());
        $this->assertContains($actual, $sellOrder->getShareTrades());
    }

    public function testCheckTradeOrderProgression(): void
    {
        $tradeOrder = new TradeOrder(
            direction: TradeDirection::Sell,
            numberOfShares: 1000,
            type: TradeOrderType::Prefunding, // doesn't actually matter
        );
        $tradeOrder->setStatus(TradeOrderStatus::Active);

        $shareTrade1 = new ShareTrade(sellOrder: $tradeOrder, numberOfShares: 10);
        $shareTrade1->setStatus(TradeStatus::Settled);
        $tradeOrder->addShareTrade($shareTrade1);

        $this->assertFalse($this->service->checkTradeOrderProgression($tradeOrder));
        $this->assertSame(TradeOrderStatus::Active, $tradeOrder->getStatus());
        $this->assertCount(1, $tradeOrder->getStatusLogs());

        $shareTrade1->setNumberOfShares(1000);
        $this->assertTrue($this->service->checkTradeOrderProgression($tradeOrder));
        $this->assertSame(TradeOrderStatus::Completed, $tradeOrder->getStatus());
        $this->assertCount(2, $tradeOrder->getStatusLogs());

        $this->assertTrue($this->service->checkTradeOrderProgression($tradeOrder));
        $this->assertSame(TradeOrderStatus::Completed, $tradeOrder->getStatus());
        // No new status log will be created
        $this->assertCount(2, $tradeOrder->getStatusLogs());

        $tradeOrder->setStatus(TradeOrderStatus::Cancelled);
        $this->assertCount(3, $tradeOrder->getStatusLogs());
        $this->assertTrue($this->service->checkTradeOrderProgression($tradeOrder));
        // Stays cancelled, won't be marked as completed
        $this->assertSame(TradeOrderStatus::Cancelled, $tradeOrder->getStatus());
        // No new status log will be created
        $this->assertCount(3, $tradeOrder->getStatusLogs());

        // Settled share trades will also work
        $tradeOrder->setStatus(TradeOrderStatus::Active);
        $shareTrade1->setStatus(TradeStatus::Unsettled);
        $this->assertTrue($this->service->checkTradeOrderProgression($tradeOrder));
        $this->assertSame(TradeOrderStatus::Completed, $tradeOrder->getStatus());

        // As will suspended
        $tradeOrder->setStatus(TradeOrderStatus::Active);
        $shareTrade1->setStatus(TradeStatus::Suspended);
        $this->assertTrue($this->service->checkTradeOrderProgression($tradeOrder));
        $this->assertSame(TradeOrderStatus::Completed, $tradeOrder->getStatus());
    }

    public function testCompileRepaymentProgressUserGrouping(): void
    {
        $user1 = EntityIdTestUtil::setEntityId(new User(), 618);
        $user2 = EntityIdTestUtil::setEntityId(new User(), 1418);
        $user3 = EntityIdTestUtil::setEntityId(new User(), 5529);

        $tradeOrderU1_1 = new TradeOrder(
            direction: TradeDirection::Sell,
            user: $user1,
            numberOfShares: 2500,
            type: TradeOrderType::Prefunding,
        );
        $tradeOrderU1_1->setStatus(TradeOrderStatus::Active);

        $tradeOrderU1_2 = new TradeOrder(
            // Method DOES filter out Buys
            direction: TradeDirection::Buy,
            user: $user1,
            numberOfShares: 1000,
            type: TradeOrderType::Prefunding,
        );
        $tradeOrderU1_2->setStatus(TradeOrderStatus::Active);

        $tradeOrderU1_3 = new TradeOrder(
            direction: TradeDirection::Sell,
            user: $user1,
            numberOfShares: 7500,
            type: TradeOrderType::Prefunding,
        );
        // Note that the status is ignored in this method
        // It assumes you're passed it valid trade orders
        $tradeOrderU1_3->setStatus(TradeOrderStatus::Suspended);

        $tradeOrderU2_1 = new TradeOrder(
            direction: TradeDirection::Sell,
            user: $user2,
            numberOfShares: 8000,
            type: TradeOrderType::Prefunding,
        );
        $tradeOrderU2_1->setStatus(TradeOrderStatus::Active);

        $tradeOrderU2_2 = new TradeOrder(
            direction: TradeDirection::Sell,
            user: $user2,
            numberOfShares: 800,
            type: TradeOrderType::Prefunding,
        );
        $tradeOrderU2_2->setStatus(TradeOrderStatus::Completed);

        $tradeOrderU3 = new TradeOrder(
            direction: TradeDirection::Sell,
            user: $user3,
            numberOfShares: 12000,
            type: TradeOrderType::Prefunding,
        );
        $tradeOrderU3->setStatus(TradeOrderStatus::Active);

        $expected = [
            618 => [
                'userid' => 618,
                'initialShares' => 10000,
                'repaidShares' => 0,
                'shares' => 10000,
                'sellOrders' => [$tradeOrderU1_1, $tradeOrderU1_3],
                'openSellOrders' => [$tradeOrderU1_1, $tradeOrderU1_3],
            ],
            1418 => [
                'userid' => 1418,
                'initialShares' => 8800,
                'repaidShares' => 0,
                'shares' => 8800,
                'sellOrders' => [$tradeOrderU2_1, $tradeOrderU2_2],
                'openSellOrders' => [$tradeOrderU2_1, $tradeOrderU2_2],
            ],
            5529 => [
                'userid' => 5529,
                'initialShares' => 12000,
                'repaidShares' => 0,
                'shares' => 12000,
                'sellOrders' => [$tradeOrderU3],
                'openSellOrders' => [$tradeOrderU3],
            ],
        ];

        // Should default to user grouping
        $actual = $this->service->compileRepaymentProgress([
            // Mix the ordering up a bit
            $tradeOrderU2_1,
            $tradeOrderU1_1,
            $tradeOrderU3,
            $tradeOrderU1_2,
            $tradeOrderU1_3,
            $tradeOrderU2_2,
        ]);

        $this->assertEquals($expected, $actual);
        // Check ordering, should be largest remaining "shares" first
        $this->assertSame([5529, 618, 1418], array_keys($actual));

        // Start adding share trades
        $expected = [
            618 => [
                'userid' => 618,
                'initialShares' => 10000,
                'repaidShares' => 2500,
                'shares' => 7500,
                'sellOrders' => [$tradeOrderU1_1, $tradeOrderU1_3],
                'openSellOrders' => [$tradeOrderU1_3],
            ],
            1418 => [
                'userid' => 1418,
                'initialShares' => 8800,
                'repaidShares' => 0,
                'shares' => 8800,
                'sellOrders' => [$tradeOrderU2_1, $tradeOrderU2_2],
                'openSellOrders' => [$tradeOrderU2_1, $tradeOrderU2_2],
            ],
            5529 => [
                'userid' => 5529,
                'initialShares' => 12000,
                'repaidShares' => 3500,
                'shares' => 8500,
                'sellOrders' => [$tradeOrderU3],
                'openSellOrders' => [$tradeOrderU3],
            ],
        ];

        foreach ([
            2000 => TradeStatus::Settled, // okay
            200 => TradeStatus::Unsettled, // okay
            300 => TradeStatus::Suspended, // okay
            500 => TradeStatus::Draft, // ignored
            800 => TradeStatus::Cancelled, // ignored
        ] as $key => $value) {
            $shareTrade = new ShareTrade(
                sellOrder: $tradeOrderU1_1,
                numberOfShares: $key,
            );
            $shareTrade->setStatus($value);
            $tradeOrderU1_1->addShareTrade($shareTrade);
        }

        $shareTrade = new ShareTrade(sellOrder: $tradeOrderU3, numberOfShares: 3500);
        $shareTrade->setStatus(TradeStatus::Settled);
        $tradeOrderU3->addShareTrade($shareTrade);

        $actual = $this->service->compileRepaymentProgress(
            [
                // Mix the ordering up a bit
                $tradeOrderU2_1,
                $tradeOrderU1_1,
                $tradeOrderU3,
                $tradeOrderU1_2,
                $tradeOrderU1_3,
                $tradeOrderU2_2,
            ],
            QueryGrouping::User,
        );
        $this->assertEquals($expected, $actual);
        // Check ordering, should be largest remaining "shares" first
        // [1418 => 8800, 5529 => 8500, 618 => 7500] is what the shares are
        $this->assertSame([1418, 5529, 618], array_keys($actual));
    }

    public function testCompileRepaymentProgressAssetGrouping(): void
    {
        $asset1 = EntityIdTestUtil::setEntityId(new Asset(), 618);
        $asset2 = EntityIdTestUtil::setEntityId(new Asset(), 1418);
        $asset3 = EntityIdTestUtil::setEntityId(new Asset(), 5529);

        $tradeOrderA1_1 = new TradeOrder(
            direction: TradeDirection::Sell,
            asset: $asset1,
            numberOfShares: 2500,
            type: TradeOrderType::Prefunding,
        );
        $tradeOrderA1_1->setStatus(TradeOrderStatus::Active);

        $tradeOrderA1_2 = new TradeOrder(
            // Method DOES filter out Buys
            direction: TradeDirection::Buy,
            asset: $asset1,
            numberOfShares: 1000,
            type: TradeOrderType::Prefunding,
        );
        $tradeOrderA1_2->setStatus(TradeOrderStatus::Active);

        $tradeOrderA1_3 = new TradeOrder(
            direction: TradeDirection::Sell,
            asset: $asset1,
            numberOfShares: 7500,
            type: TradeOrderType::Prefunding,
        );
        // Note that the status is ignored in this method
        // It assumes you're passed it valid trade orders
        $tradeOrderA1_3->setStatus(TradeOrderStatus::Suspended);

        $tradeOrderA2_1 = new TradeOrder(
            direction: TradeDirection::Sell,
            asset: $asset2,
            numberOfShares: 8000,
            type: TradeOrderType::Prefunding,
        );
        $tradeOrderA2_1->setStatus(TradeOrderStatus::Active);

        $tradeOrderA2_2 = new TradeOrder(
            direction: TradeDirection::Sell,
            asset: $asset2,
            numberOfShares: 800,
            type: TradeOrderType::Prefunding,
        );
        $tradeOrderA2_2->setStatus(TradeOrderStatus::Completed);

        $tradeOrderA3 = new TradeOrder(
            direction: TradeDirection::Sell,
            asset: $asset3,
            numberOfShares: 12000,
            type: TradeOrderType::Prefunding,
        );
        $tradeOrderA3->setStatus(TradeOrderStatus::Active);

        $expected = [
            618 => [
                'assetid' => 618,
                'initialShares' => 10000,
                'repaidShares' => 0,
                'shares' => 10000,
                'sellOrders' => [$tradeOrderA1_1, $tradeOrderA1_3],
                'openSellOrders' => [$tradeOrderA1_1, $tradeOrderA1_3],
            ],
            1418 => [
                'assetid' => 1418,
                'initialShares' => 8800,
                'repaidShares' => 0,
                'shares' => 8800,
                'sellOrders' => [$tradeOrderA2_1, $tradeOrderA2_2],
                'openSellOrders' => [$tradeOrderA2_1, $tradeOrderA2_2],
            ],
            5529 => [
                'assetid' => 5529,
                'initialShares' => 12000,
                'repaidShares' => 0,
                'shares' => 12000,
                'sellOrders' => [$tradeOrderA3],
                'openSellOrders' => [$tradeOrderA3],
            ],
        ];

        $actual = $this->service->compileRepaymentProgress(
            [
                // Mix the ordering up a bit
                $tradeOrderA2_1,
                $tradeOrderA1_1,
                $tradeOrderA3,
                $tradeOrderA1_2,
                $tradeOrderA1_3,
                $tradeOrderA2_2,
            ],
            QueryGrouping::Asset,
        );

        $this->assertEquals($expected, $actual);
        // Check ordering, should be largest remaining "shares" first
        $this->assertSame([5529, 618, 1418], array_keys($actual));

        // Start adding share trades
        $expected = [
            618 => [
                'assetid' => 618,
                'initialShares' => 10000,
                'repaidShares' => 2500,
                'shares' => 7500,
                'sellOrders' => [$tradeOrderA1_1, $tradeOrderA1_3],
                'openSellOrders' => [$tradeOrderA1_3],
            ],
            1418 => [
                'assetid' => 1418,
                'initialShares' => 8800,
                'repaidShares' => 0,
                'shares' => 8800,
                'sellOrders' => [$tradeOrderA2_1, $tradeOrderA2_2],
                'openSellOrders' => [$tradeOrderA2_1, $tradeOrderA2_2],
            ],
            5529 => [
                'assetid' => 5529,
                'initialShares' => 12000,
                'repaidShares' => 3500,
                'shares' => 8500,
                'sellOrders' => [$tradeOrderA3],
                'openSellOrders' => [$tradeOrderA3],
            ],
        ];

        foreach ([
            2000 => TradeStatus::Settled,
            200 => TradeStatus::Unsettled,
            300 => TradeStatus::Suspended,
        ] as $key => $value) {
            $shareTrade = new ShareTrade(
                sellOrder: $tradeOrderA1_1,
                numberOfShares: $key,
            );
            $shareTrade->setStatus($value);
            $tradeOrderA1_1->addShareTrade($shareTrade);
        }

        $shareTrade = new ShareTrade(sellOrder: $tradeOrderA3, numberOfShares: 3500);
        $shareTrade->setStatus(TradeStatus::Settled);
        $tradeOrderA3->addShareTrade($shareTrade);

        $actual = $this->service->compileRepaymentProgress([
            $tradeOrderA2_1,
            $tradeOrderA1_1,
            $tradeOrderA3,
            $tradeOrderA1_2,
            $tradeOrderA1_3,
            $tradeOrderA2_2,
        ], QueryGrouping::Asset);
        $this->assertEquals($expected, $actual);
        // Check ordering, should be largest remaining "shares" first
        // [1418 => 8800, 5529 => 8500, 618 => 7500] is what the shares are
        $this->assertSame([1418, 5529, 618], array_keys($actual));
    }
}
