<?php

namespace App\Tests\Service;

use App\Entity\AbstractOrder;
use App\Entity\Asset;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\PaymentOrder;
use App\Entity\PaymentRequest;
use App\Entity\ShareTrade;
use App\Entity\TradeOrder;
use App\Entity\TradeOrderStatusLog;
use App\Entity\User;
use App\Repository\TradeOrderRepository;
use App\Service\AppSettingService;
use App\Service\DivestmentService;
use App\Service\PaymentOrderService;
use App\Service\PaymentService;
use App\Test\Util\EntityIdTestUtil;
use BcMath\Number;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Workflow\Exception\NotEnabledTransitionException;

final class PaymentOrderServiceTest extends KernelTestCase
{
    private PaymentOrderService $service;
    private PaymentService|MockObject $paymentServiceMock;

    private TradeOrderRepository|MockObject $tradeOrderRepositoryMock;

    protected function setUp(): void
    {
        self::bootKernel();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('transitionsProvider')]
    public function testTransitionPaymentOrder(
        string $transition,
        string $start,
        string $expected,
    ): void {
        $this->service = static::getContainer()->get(PaymentOrderService::class);

        $paymentOrder = new PaymentOrder();
        $paymentOrder->setStatus($start);
        $this->service->transitionPaymentOrder($paymentOrder, $transition);
        $this->assertEquals($expected, $paymentOrder->getStatus());
    }

    public static function transitionsProvider(): \Generator
    {
        yield 'approve draft' => [
            'approve',
            PaymentOrder::STATE_DRAFT,
            PaymentOrder::STATE_APPROVED,
        ];
        yield 'unapprove' => [
            'request_change',
            PaymentOrder::STATE_APPROVED,
            PaymentOrder::STATE_DRAFT,
        ];
        yield 'run order' => [
            'run',
            PaymentOrder::STATE_APPROVED,
            PaymentOrder::STATE_IN_PROGRESS,
        ];
        yield 'run in progress' => [
            'run',
            PaymentOrder::STATE_IN_PROGRESS,
            PaymentOrder::STATE_IN_PROGRESS,
        ];
        yield 'close draft' => [
            'reject',
            PaymentOrder::STATE_DRAFT,
            PaymentOrder::STATE_CLOSED,
        ];
        yield 'close approved' => [
            'reject',
            PaymentOrder::STATE_APPROVED,
            PaymentOrder::STATE_CLOSED,
        ];
        yield 'abandon' => [
            'abandon',
            PaymentOrder::STATE_IN_PROGRESS,
            PaymentOrder::STATE_ABANDONED,
        ];
        yield 'complete' => [
            'complete',
            PaymentOrder::STATE_IN_PROGRESS,
            PaymentOrder::STATE_COMPLETED,
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidTransitionsProvider')]
    public function testTransitionPaymentOrderInvalid(
        string $transition,
        string $start,
    ): void {
        $this->service = static::getContainer()->get(PaymentOrderService::class);
        // Just check a handful as a sanity check
        $paymentOrder = new PaymentOrder();
        $paymentOrder->setStatus($start);
        $this->expectException(NotEnabledTransitionException::class);
        $this->service->transitionPaymentOrder($paymentOrder, $transition);
    }

    public static function invalidTransitionsProvider(): \Generator
    {
        yield 'approve approved' => ['approve', PaymentOrder::STATE_APPROVED];
        yield 'approve in progress' => ['approve', PaymentOrder::STATE_IN_PROGRESS];
        yield 'approve complete' => ['approve', PaymentOrder::STATE_COMPLETED];
        yield 'unapprove in progress' => [
            'request_change',
            PaymentOrder::STATE_IN_PROGRESS,
        ];
        yield 'run draft' => ['run', PaymentOrder::STATE_DRAFT];
        yield 'run completed' => ['run', PaymentOrder::STATE_COMPLETED];
        yield 'close closed' => ['reject', PaymentOrder::STATE_CLOSED];
        yield 'close completed' => ['reject', PaymentOrder::STATE_COMPLETED];
        yield 'abandon draft' => ['abandon', PaymentOrder::STATE_DRAFT];
        yield 'abandon approved' => ['abandon', PaymentOrder::STATE_APPROVED];
        yield 'complete draft' => ['complete', PaymentOrder::STATE_DRAFT];
        yield 'complete approved' => ['complete', PaymentOrder::STATE_APPROVED];
    }

    public function testformatPaymentOrders(): void
    {
        $this->service = static::getContainer()->get(PaymentOrderService::class);

        $currentDt = new \DateTime();
        $expected = [
            'id' => 58,
            'paymentType' => 'Investment Exit',
            'assetId' => 22,
            'assetSpv' => 'SPVT000328',
            'assetName' => 'Automated test payment order asset',
            'status' => 'ready',
            'scheduledFor' => $currentDt->format('Y-m-d'),
            'description' => 'automated test order description',
            'totalPayments' => 3,
            'approvedBy' => 'finops.auto@test.yielderverse.co.uk',
            'createdBy' => 'ops.auto@test.yielderverse.co.uk',
            'createdAt' => $currentDt->format('r'),
            'updatedBy' => 'finops.auto@test.yielderverse.co.uk',
            'updatedAt' => $currentDt->format('r'),
        ];

        /** @var \App\Entity\Asset $asset */
        $asset = EntityIdTestUtil::setEntityId(
            new \App\Entity\Asset(),
            $expected['assetId'],
        );
        $asset->setCompanyNumber($expected['assetSpv']);
        $asset->setName($expected['assetName']);

        $user = new \App\Entity\User();
        $user->setUsername('finops.auto@test.yielderverse.co.uk');

        /** @var PaymentOrder $sample */
        $sample = EntityIdTestUtil::setEntityId(new PaymentOrder(), $expected['id']);
        $sample->setAsset($asset);
        $sample->setPaymentType($expected['paymentType']);
        $sample->setStatus($expected['status']);
        $sample->setDescription($expected['description']);
        $sample->setScheduledFor($currentDt);
        $sample->setApprovedBy($user);
        $sample->setCreatedAt($currentDt);
        $sample->setCreatedBy($expected['createdBy']);
        $sample->setUpdatedAt($currentDt);
        $sample->setUpdatedBy($expected['updatedBy']);
        for ($i = 0; $i < $expected['totalPayments']; $i++) {
            $sample->addPayment(new PaymentRequest());
        }

        $actual = $this->service->formatPaymentOrders([$sample])[0];
        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    public function testformatPayments(): void
    {
        $this->service = static::getContainer()->get(PaymentOrderService::class);

        $currentDt = new \DateTime();
        $expected = [
            'id' => 1256,
            'paymentOrderId' => 63,
            'paymentType' => 'Divestment',
            'status' => 'pending',
            'payeeId' => 7761,
            'payeeName' => 'Liv  Orlais',
            'payeeUsername' => 'lorlais@yielderverse.co.uk',
            'amount' => '14.65',
            'shareholding' => 1566,
            'payoutId' => '',
            'createdBy' => 'ops.auto@test.yielderverse.co.uk',
            'createdAt' => $currentDt->format('r'),
            'updatedBy' => 'finops.auto@test.yielderverse.co.uk',
            'updatedAt' => $currentDt->format('r'),
        ];

        /** @var \App\Entity\User $user */
        $user = EntityIdTestUtil::setEntityId(
            new \App\Entity\User(),
            $expected['payeeId'],
        );
        $user->setUsername('lorlais@yielderverse.co.uk');
        $user->setFirstname('Liv');
        $user->setLastname('Orlais');

        /** @var PaymentOrder $relation */
        $relation = EntityIdTestUtil::setEntityId(
            new PaymentOrder(),
            $expected['paymentOrderId'],
        );
        $relation->setPaymentType($expected['paymentType']);

        /** @var PaymentRequest $sample */
        $sample = EntityIdTestUtil::setEntityId(new PaymentRequest(), $expected['id']);
        $sample->setPaymentOrder($relation);
        $sample->setPayee($user);
        $sample->setAmount($expected['amount']);
        $sample->setShareholding($expected['shareholding']);
        $sample->setStatus($expected['status']);
        $sample->setCreatedAt($currentDt);
        $sample->setCreatedBy($expected['createdBy']);
        $sample->setUpdatedAt($currentDt);
        $sample->setUpdatedBy($expected['updatedBy']);

        $actual = $this->service->formatPayments([$sample])[0];
        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    public function testFilterPendingRequests(): void
    {
        $this->service = static::getContainer()->get(PaymentOrderService::class);

        $input = [];
        // Out of 13 requests counting from zero (0-12) - based on floor division denoted by //
        // Requests 0, 3 ,6, 9, 12 will be paid
        // Requests 5, 10, will be failed
        // Remainder (1, 2, 4, 7, 8, 11) will be pending
        // (13 - 5 == 8) in total will be considered pending and ready for running (i.e. either pending or failed)
        for ($i = 0; $i < 13; $i++) {
            $paymentRequest = new PaymentRequest();
            $paymentRequest->setStatus(
                ($i % 3) == 0
                    ? PaymentRequest::STATE_PAID
                    : (
                        ($i % 5)
                        == 0
                            ? PaymentRequest::STATE_FAILED
                            : PaymentRequest::STATE_PENDING
                    ),
            );
            $input[] = $paymentRequest;
        }
        $actual = $this->service->filterPendingRequests($input);
        $this->assertCount(13, $input);
        $this->assertCount(6, $actual[PaymentRequest::STATE_PENDING]);
        $this->assertCount(2, $actual[PaymentRequest::STATE_FAILED]);
        $this->assertEqualsCanonicalizing(
            [PaymentRequest::STATE_PENDING, PaymentRequest::STATE_FAILED],
            array_keys($actual),
        );
        foreach ($actual as $status => $payments) {
            foreach ($payments as $payment) {
                $this->assertEquals($payment->getStatus(), $status);
            }
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('orderCompletionProvider')]
    public function testIsOrderComplete(PaymentOrder $input, bool $expected): void
    {
        $this->service = static::getContainer()->get(PaymentOrderService::class);

        $actual = $this->service->isOrderComplete($input);
        $this->assertSame($expected, $actual);
    }

    public static function orderCompletionProvider(): \Generator
    {
        $paymentOrderComplete = new PaymentOrder();
        $paymentOrderPartial = new PaymentOrder();
        $paymentOrderNew = new PaymentOrder();
        for ($i = 0; $i < 11; $i++) {
            $paymentRequest = new PaymentRequest();
            $paymentRequest->setStatus(
                ($i % 3) == 0
                    ? PaymentRequest::STATE_PAID
                    : PaymentRequest::STATE_PENDING,
            );
            if (in_array($i, [0, 3, 6])) {
                $paymentOrderComplete->addPayment($paymentRequest);
            } elseif (in_array($i, [1, 2, 4, 5, 7, 9])) {
                $paymentOrderPartial->addPayment($paymentRequest);
            } else {
                $paymentOrderNew->addPayment($paymentRequest);
            }
        }
        yield 'complete' => [$paymentOrderComplete, true];
        yield 'partial' => [$paymentOrderPartial, false];
        yield 'new' => [$paymentOrderNew, false];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('payRequestProvider')]
    public function testPayRequestDividend(
        \App\Entity\Payout $payoutCreated,
        string $expectedEndState,
    ): void {
        $this->paymentServiceMock = $this->createMock(PaymentService::class);
        static::getContainer()->set(PaymentService::class, $this->paymentServiceMock);
        $this->service = static::getContainer()->get(PaymentOrderService::class);

        /**
         * Mock calls to other services
         * - payDividend
         * - getDefaultAssetWalletUserId
         */
        $paymentRequest = new PaymentRequest();
        $paymentRequest->setPayee(new \App\Entity\User());
        $paymentRequest->setStatus(PaymentRequest::STATE_PENDING);
        $paymentRequest->setShareholding(120);
        $paymentRequest->setAmount('2.58');
        $paymentOrder = new PaymentOrder();
        $paymentOrder->setAsset(new \App\Entity\Asset());
        $debitWalletId = bin2hex(random_bytes(8));
        $paymentOrder->getAsset()->setMainWalletId($debitWalletId);
        // $paymentOrder->getAsset()->setDistributionWalletId($debitWalletId);
        $paymentOrder->setPaymentType(PaymentService::TYPE_DIVIDEND);
        $paymentOrder->setScheduledFor(new \DateTime());
        $paymentOrder->setStatus(PaymentOrder::STATE_APPROVED);
        $paymentOrder->addPayment($paymentRequest);

        $this->paymentServiceMock
            ->expects($this->once())
            ->method('payDividend')
            ->with(
                $paymentOrder->getAsset(),
                $paymentRequest->getPayee(),
                'abc',
                ['cashValue' => '2.58', 'currentHolding' => 120],
                $paymentOrder->getScheduledFor(),
                $debitWalletId,
            )
            ->willReturn($payoutCreated);
        $this->paymentServiceMock
            ->expects($this->once())
            ->method('getDefaultAssetWalletUserId')
            ->willReturn('abc');

        $this->service->payRequest($paymentRequest);
        $this->assertEquals($expectedEndState, $paymentRequest->getStatus());

        /**
         * Check payout is only set if payout had a successful transfer
         * Successful meaning it has a corresponding transactionId
         */
        if (PaymentRequest::STATE_PAID == $expectedEndState) {
            $this->assertEquals($payoutCreated, $paymentRequest->getPayout());
            $this->assertNotEmpty($paymentRequest->getPayout()->getTransactionId());
        } else {
            $this->assertNull($paymentRequest->getPayout());
        }
    }

    public static function payRequestProvider(): \Generator
    {
        $payoutWithTransactionId = new \App\Entity\Payout();
        $payoutWithTransactionId->setTransactionId('123');
        yield 'failed transfer' => [
            new \App\Entity\Payout(),
            PaymentRequest::STATE_PENDING,
        ];
        yield 'successful transfer' => [
            $payoutWithTransactionId,
            PaymentRequest::STATE_PAID,
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('paymentShareAndValueProvider')]
    public function testPayRequestDivestment(string $amount, int $shares): void
    {
        $sharePriceEqv = $shares > 0
            ? (string) new Number($amount)->div($shares)->round(6)
            : '0.00';

        // PREPARE
        $payoutWithTransactionId = new \App\Entity\Payout();
        $payoutWithTransactionId->setTransactionId('123');

        $paymentRequest = new PaymentRequest();
        $paymentRequest->setPayee(EntityIdTestUtil::setEntityId(new User(), 7569));
        $paymentRequest->setStatus(PaymentRequest::STATE_PENDING);
        $paymentRequest->setShareholding($shares);
        $paymentRequest->setAmount($amount);
        $paymentOrder = new PaymentOrder();
        $paymentOrder->setAsset(EntityIdTestUtil::setEntityId(new Asset(), 253));
        $debitWalletId = bin2hex(random_bytes(8));
        $paymentOrder->getAsset()->setMainWalletId($debitWalletId);
        // $paymentOrder->getAsset()->setDistributionWalletId($debitWalletId);
        $paymentOrder->setPaymentType(PaymentService::TYPE_DIVESTMENT);
        $paymentOrder->setScheduledFor(new \DateTime());
        $paymentOrder->setStatus(PaymentOrder::STATE_APPROVED);
        $paymentOrder->addPayment($paymentRequest);

        // Prepare service mocks
        $this->paymentServiceMock = $this->createMock(PaymentService::class);
        static::getContainer()->set(PaymentService::class, $this->paymentServiceMock);

        $this->tradeOrderRepositoryMock = $this->createMock(TradeOrderRepository::class);
        static::getContainer()->set(
            TradeOrderRepository::class,
            $this->tradeOrderRepositoryMock,
        );
        $initialOrder = new TradeOrder(
            direction: TradeDirection::Sell,
            asset: $paymentOrder->getAsset(),
            user: EntityIdTestUtil::setEntityId(new User(), 214),
            numberOfShares: 1,
            pricePerShare: new Number(1),
            type: TradeOrderType::Initial,
        );
        $this->tradeOrderRepositoryMock
            ->expects($this->atMost(1))
            ->method('findInitialSellOrders')
            ->willReturn([$initialOrder]);
        $this->service = static::getContainer()->get(PaymentOrderService::class);

        // Divestment/liquidation payments will make a update investments method call in addition to the pay method call
        // payDivestments passes a payRequest (array/dict) with the field sharesToLiquidate instead of currentHolding
        // trade system retains this as a carry over from existing
        $this->paymentServiceMock
            ->expects($amount > 0 ? $this->once() : $this->never())
            ->method('payDivestment')
            ->with(
                $paymentOrder->getAsset(),
                $paymentRequest->getPayee(),
                'abc',
                ['cashValue' => $amount, 'sharesToLiquidate' => $shares],
                $paymentOrder->getScheduledFor(),
                PaymentService::TYPE_DIVESTMENT,
                $debitWalletId,
            )
            ->willReturn($payoutWithTransactionId);

        $this->paymentServiceMock
            ->expects($amount > 0 ? $this->once() : $this->never())
            ->method('getDefaultAssetWalletUserId')
            ->willReturn('abc');

        // RUN

        $this->service->payRequest($paymentRequest);

        // CHECK
        $this->assertEquals(PaymentRequest::STATE_PAID, $paymentRequest->getStatus());
        if ($amount > 0) {
            $this->assertEquals($payoutWithTransactionId, $paymentRequest->getPayout());
            $this->assertNotEmpty($paymentRequest->getPayout()->getTransactionId());
        } else {
            $this->assertNull($paymentRequest->getPayout());
        }

        if ($shares > 0) {
            // Check the BuyBack buy order has been created and set
            // There's only 1 paymentRequest, so should amount to everything
            $this->assertNotEmpty($paymentOrder->getTradeOrder());
            $buyBackOrder = $paymentOrder->getTradeOrder();
            // Share price is 2.58/120 which rounds to 0.0215
            $this->assertEquals($sharePriceEqv, $buyBackOrder->getPricePerShare());
            $this->assertEquals($shares, $buyBackOrder->getNumberOfShares());
            $this->assertEquals($paymentOrder->getAsset(), $buyBackOrder->getAsset());
            $this->assertEquals($initialOrder->getUser(), $buyBackOrder->getUser());
            $this->assertEquals(TradeDirection::Buy, $buyBackOrder->getDirection());
            $this->assertEquals(TradeOrderType::BuyBack, $buyBackOrder->getType());
            // Note that only runRequest and runOrder will transition to Completed
            $this->assertEquals(TradeOrderStatus::Active, $buyBackOrder->getStatus());

            // Check the BuyBack sell order and the share trade has been created and set
            $this->assertNotEmpty($paymentRequest->getShareTrade());
            $this->assertNotEmpty($paymentRequest->getShareTrade()->getSellOrder());
            $shareTrade = $paymentRequest->getShareTrade();
            $this->assertEquals($sharePriceEqv, $shareTrade->getPricePerShare());
            $this->assertEquals($shares, $shareTrade->getNumberOfShares());
            $this->assertEquals($amount, $shareTrade->getTradeValue());
            $this->assertFalse($shareTrade->isDerived());

            $sellBackorder = $shareTrade->getSellOrder();
            $this->assertEquals($sharePriceEqv, $sellBackorder->getPricePerShare());
            $this->assertEquals($shares, $sellBackorder->getNumberOfShares());
            $this->assertEquals($buyBackOrder->getAsset(), $sellBackorder->getAsset());
            $this->assertEquals($paymentRequest->getPayee(), $sellBackorder->getUser());
            $this->assertEquals(TradeDirection::Sell, $sellBackorder->getDirection());
            $this->assertEquals(TradeOrderType::BuyBack, $sellBackorder->getType());
            $this->assertEquals(
                TradeOrderStatus::Completed,
                $sellBackorder->getStatus(),
            );
        } else {
            // No share trade processing is performed if payment shareholding is zero
            $this->assertNull($paymentRequest->getShareTrade());
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('payRepaymentTestProvider')]
    public function testPayRequestRepayment(
        TradeOrderStatus $expected,
        bool $biggerThanPayment,
        string $amount,
        int $shares,
    ): void {
        $sharePriceEqv = $shares > 0
            ? (string) new Number($amount)->div($shares)->round(6)
            : '0.00';

        // PREPARE
        $payoutWithTransactionId = new \App\Entity\Payout();
        $payoutWithTransactionId->setTransactionId('123');

        $paymentRequest = new PaymentRequest();
        $paymentRequest->setPayee(EntityIdTestUtil::setEntityId(new User(), 7569));
        $paymentRequest->setStatus(PaymentRequest::STATE_PENDING);
        $paymentRequest->setShareholding($shares);
        $paymentRequest->setAmount($amount);

        $paymentOrder = new PaymentOrder();
        $paymentOrder->setAsset(EntityIdTestUtil::setEntityId(new Asset(), 253));
        $debitWalletId = bin2hex(random_bytes(8));
        $paymentOrder->getAsset()->setMainWalletId($debitWalletId);
        $paymentOrder->setPaymentType(PaymentService::TYPE_REPAYMENT);
        $paymentOrder->setScheduledFor(new \DateTime());
        $paymentOrder->setStatus(PaymentOrder::STATE_APPROVED);
        $paymentOrder->addPayment($paymentRequest);

        $prefunderSellOrder = EntityIdTestUtil::setEntityId(
            new TradeOrder(
                direction: TradeDirection::Sell,
                type: TradeOrderType::Prefunding,
                numberOfShares: $paymentRequest->getShareholding()
                + (int) $biggerThanPayment,
                user: $paymentRequest->getPayee(),
                asset: $paymentOrder->getAsset(),
            ),
            7851,
        );
        $prefunderSellOrder->setStatus(TradeOrderStatus::Active);
        $paymentRequest->setTradeOrder($prefunderSellOrder);

        // Prepare service mocks
        $this->paymentServiceMock = $this->createMock(PaymentService::class);
        static::getContainer()->set(PaymentService::class, $this->paymentServiceMock);

        $this->tradeOrderRepositoryMock = $this->createMock(TradeOrderRepository::class);
        static::getContainer()->set(
            TradeOrderRepository::class,
            $this->tradeOrderRepositoryMock,
        );
        $initialOrder = new TradeOrder(
            direction: TradeDirection::Sell,
            asset: $paymentOrder->getAsset(),
            user: EntityIdTestUtil::setEntityId(new User(), 214),
            numberOfShares: 1,
            pricePerShare: new Number(1),
            type: TradeOrderType::Initial,
        );
        $this->tradeOrderRepositoryMock
            ->expects($this->atMost(1))
            ->method('findInitialSellOrders')
            ->willReturn([$initialOrder]);
        $this->service = static::getContainer()->get(PaymentOrderService::class);

        // Divestment/liquidation payments will make a update investments method call in addition to the pay method call
        // payDivestments passes a payRequest (array/dict) with the field sharesToLiquidate instead of currentHolding
        // trade system retains this as a carry over from existing
        $this->paymentServiceMock
            ->expects($amount > 0 ? $this->once() : $this->never())
            ->method('payDivestment')
            ->with(
                $paymentOrder->getAsset(),
                $paymentRequest->getPayee(),
                'abc',
                ['cashValue' => '2.58', 'sharesToLiquidate' => 120],
                $paymentOrder->getScheduledFor(),
                PaymentService::TYPE_REPAYMENT,
                $debitWalletId,
            )
            ->willReturn($payoutWithTransactionId);

        $this->paymentServiceMock
            ->expects($amount > 0 ? $this->once() : $this->never())
            ->method('getDefaultAssetWalletUserId')
            ->willReturn('abc');

        // RUN
        $this->service->payRequest($paymentRequest);

        // CHECK
        $this->assertEquals(PaymentRequest::STATE_PAID, $paymentRequest->getStatus());
        if ($amount > 0) {
            $this->assertEquals($payoutWithTransactionId, $paymentRequest->getPayout());
            $this->assertNotEmpty($paymentRequest->getPayout()->getTransactionId());
        } else {
            $this->assertNull($paymentRequest->getPayout());
        }

        if ($shares > 0) {
            // Check the BuyBack buy order has been created and set
            // There's only 1 paymentRequest, so should amount to everything
            $this->assertNotEmpty($paymentOrder->getTradeOrder());
            $buyBackOrder = $paymentOrder->getTradeOrder();
            // Share price is 2.58/120 which rounds to 0.0215
            $this->assertEquals($sharePriceEqv, $buyBackOrder->getPricePerShare());
            $this->assertEquals($shares, $buyBackOrder->getNumberOfShares());
            $this->assertEquals($paymentOrder->getAsset(), $buyBackOrder->getAsset());
            $this->assertEquals($initialOrder->getUser(), $buyBackOrder->getUser());
            $this->assertEquals(TradeDirection::Buy, $buyBackOrder->getDirection());
            $this->assertEquals(TradeOrderType::Proxy, $buyBackOrder->getType());
            // Note that only runRequest and runOrder will transition to Completed
            $this->assertEquals(TradeOrderStatus::Active, $buyBackOrder->getStatus());

            // Check the BuyBack sell order and the share trade has been created and set
            $this->assertNotEmpty($paymentRequest->getShareTrade());
            $this->assertNotEmpty($paymentRequest->getShareTrade()->getSellOrder());
            $shareTrade = $paymentRequest->getShareTrade();
            $this->assertEquals($sharePriceEqv, $shareTrade->getPricePerShare());
            $this->assertEquals($shares, $shareTrade->getNumberOfShares());
            $this->assertEquals($amount, $shareTrade->getTradeValue());
            $this->assertFalse($shareTrade->isDerived());

            $sellBackorder = $shareTrade->getSellOrder();
            // Should be the same order, not a new one that is created
            $this->assertSame($prefunderSellOrder->getId(), $sellBackorder->getId());
            $this->assertEquals(
                $prefunderSellOrder->getNumberOfShares(),
                $sellBackorder->getNumberOfShares(),
            );
            $this->assertEquals($buyBackOrder->getAsset(), $sellBackorder->getAsset());
            $this->assertEquals($paymentRequest->getPayee(), $sellBackorder->getUser());
            $this->assertEquals(TradeDirection::Sell, $sellBackorder->getDirection());
            $this->assertEquals(TradeOrderType::Prefunding, $sellBackorder->getType());
            $this->assertEquals($expected, $sellBackorder->getStatus());
        } else {
            // No share trade processing is performed if payment shareholding is zero
            $this->assertNull($paymentRequest->getShareTrade());
        }
    }

    public static function payRepaymentTestProvider(): \Generator
    {
        yield 'Full repay, non empty share and value' => [
            TradeOrderStatus::Completed,
            false,
            '2.58',
            120,
        ];
        yield 'Partial repay, non empty share and value' => [
            TradeOrderStatus::Active, // what we'll originally set it to
            true,
            '2.58',
            120,
        ];
        yield 'Full repay, non empty share and empty value' => [
            TradeOrderStatus::Completed,
            false,
            '0.00',
            120,
        ];
        yield 'No repay, empty share and empty value' => [
            TradeOrderStatus::Active,
            true,
            '0.00',
            0,
        ];
    }

    public function testPayRequestFromDistributionWallet(): void
    {
        $payoutWithTransactionId = new \App\Entity\Payout();
        $payoutWithTransactionId->setTransactionId('123');

        $paymentRequest = new PaymentRequest();
        $paymentRequest->setPayee(EntityIdTestUtil::setEntityId(
            new \App\Entity\User(),
            7569,
        ));
        $paymentRequest->setStatus(PaymentRequest::STATE_PENDING);
        $paymentRequest->setShareholding(120);
        $paymentRequest->setAmount('2.58');
        $paymentOrder = new PaymentOrder();
        $paymentOrder->setAsset(EntityIdTestUtil::setEntityId(
            new \App\Entity\Asset(),
            253,
        ));
        $paymentOrder->setDebitWallet('distribution');
        $debitWalletId = bin2hex(random_bytes(8));
        // $paymentOrder->getAsset()->setMainWalletId($debitWalletId);
        $paymentOrder->getAsset()->setDistributionWalletId($debitWalletId);
        $paymentOrder->setPaymentType(PaymentService::TYPE_DIVESTMENT);
        $paymentOrder->setScheduledFor(new \DateTime());
        $paymentOrder->setStatus(PaymentOrder::STATE_APPROVED);
        $paymentOrder->addPayment($paymentRequest);

        // Prepare service mocks
        $this->paymentServiceMock = $this->createMock(PaymentService::class);
        static::getContainer()->set(PaymentService::class, $this->paymentServiceMock);

        $this->tradeOrderRepositoryMock = $this->createMock(TradeOrderRepository::class);
        static::getContainer()->set(
            TradeOrderRepository::class,
            $this->tradeOrderRepositoryMock,
        );
        $initialOrder = new TradeOrder(
            direction: TradeDirection::Sell,
            asset: $paymentOrder->getAsset(),
            user: new User(),
            numberOfShares: 1,
            pricePerShare: new Number(1),
            type: TradeOrderType::Initial,
        );
        $this->tradeOrderRepositoryMock
            ->expects($this->atMost(1))
            ->method('findInitialSellOrders')
            ->willReturn([$initialOrder]);
        $this->service = static::getContainer()->get(PaymentOrderService::class);

        // Divestment/liquidation payments will make a update investments method call in addition to the pay method call
        // payDivestments passes a payRequest (array/dict) with the field sharesToLiquidate instead of currentHolding
        $this->paymentServiceMock
            ->expects($this->once())
            ->method('payDivestment')
            ->with(
                $paymentOrder->getAsset(),
                $paymentRequest->getPayee(),
                'abc',
                ['cashValue' => '2.58', 'sharesToLiquidate' => 120],
                $paymentOrder->getScheduledFor(),
                PaymentService::TYPE_DIVESTMENT,
                $debitWalletId,
            )
            ->willReturn($payoutWithTransactionId);

        $this->paymentServiceMock
            ->expects($this->once())
            ->method('getDefaultAssetWalletUserId')
            ->willReturn('abc');

        $this->service->payRequest($paymentRequest);
        $this->assertEquals(PaymentRequest::STATE_PAID, $paymentRequest->getStatus());
        $this->assertEquals($payoutWithTransactionId, $paymentRequest->getPayout());
        $this->assertNotEmpty($paymentRequest->getPayout()->getTransactionId());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('supportedPaymentTypesProvider')]
    public function testPayRequestSupportedTypes(
        string $paymentType,
        bool $supported,
    ): void {
        $paymentRequest = new PaymentRequest();
        $paymentRequest->setPayee(EntityIdTestUtil::setEntityId(
            new \App\Entity\User(),
            7569,
        ));
        $paymentRequest->setStatus(PaymentRequest::STATE_PENDING);
        $paymentRequest->setShareholding(120);
        $paymentRequest->setAmount('2.58');
        $paymentOrder = new PaymentOrder();
        $paymentOrder->setAsset(EntityIdTestUtil::setEntityId(
            new \App\Entity\Asset(),
            253,
        ));
        $paymentOrder->setPaymentType($paymentType);
        $paymentOrder->setScheduledFor(new \DateTime());
        $paymentOrder->setStatus(PaymentOrder::STATE_APPROVED);
        $paymentOrder->addPayment($paymentRequest);

        $payoutWithTransactionId = new \App\Entity\Payout();
        $payoutWithTransactionId->setTransactionId('123');

        if ($paymentType == PaymentService::TYPE_REPAYMENT) {
            $paymentRequest->setTradeOrder(new TradeOrder(TradeDirection::Sell));
        }

        // Prepare service mocks
        $this->paymentServiceMock = $this->createMock(PaymentService::class);
        static::getContainer()->set(PaymentService::class, $this->paymentServiceMock);

        $this->tradeOrderRepositoryMock = $this->createMock(TradeOrderRepository::class);
        static::getContainer()->set(
            TradeOrderRepository::class,
            $this->tradeOrderRepositoryMock,
        );
        $initialOrder = new TradeOrder(
            direction: TradeDirection::Sell,
            asset: $paymentOrder->getAsset(),
            user: new User(),
            numberOfShares: 1,
            pricePerShare: new Number(1),
            type: TradeOrderType::Initial,
        );
        $this->tradeOrderRepositoryMock
            ->expects($this->atMost(1))
            ->method('findInitialSellOrders')
            ->willReturn([$initialOrder]);
        $this->service = static::getContainer()->get(PaymentOrderService::class);

        $this->paymentServiceMock
            ->expects($this->any())
            ->method('payDividend')
            ->willReturn($payoutWithTransactionId);
        $this->paymentServiceMock
            ->expects($this->any())
            ->method('payDivestment')
            ->willReturn($payoutWithTransactionId);
        $this->paymentServiceMock
            ->expects($this->any())
            ->method('getDefaultAssetWalletUserId')
            ->willReturn('abc');

        // If the payment type isn't support, an exception should be thrown
        if (!$supported) {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Unsupported payment type');
        }
        $this->service->payRequest($paymentRequest);
        // If the payment type was supported, the payment request should have a payout attached to it
        if ($supported) {
            $this->assertNotEmpty($paymentRequest->getPayout()->getTransactionId());
        }
    }

    public static function supportedPaymentTypesProvider(): \Generator
    {
        yield 'dividend' => [PaymentService::TYPE_DIVIDEND, true];
        yield 'generic liquidation' => [PaymentService::TYPE_LIQUIDATION, true];
        yield 'repayment liquidation' => [PaymentService::TYPE_REPAYMENT, true];
        yield 'divestment liquidation' => [PaymentService::TYPE_DIVESTMENT, true];
        yield 'asset exit liquidation' => [PaymentService::TYPE_INVESTMENT_EXIT, true];
        yield 'unknown type' => ['something-different', false];
    }

    public function testRunRequest(): void
    {
        /**
         * runRequest is a helper method that coordinates calls to other methods
         * Test that it does the following:
         * - Call payRequest on payment request
         * - Check if the order is complete at the end
         */
        $this->tradeOrderRepositoryMock = $this->createMock(TradeOrderRepository::class);
        static::getContainer()->set(
            TradeOrderRepository::class,
            $this->tradeOrderRepositoryMock,
        );
        $service = $this
            ->getMockBuilder(PaymentOrderService::class)
            ->setConstructorArgs([
                $this->createMock(\Psr\Log\LoggerInterface::class),
                static::getContainer()->get('state_machine.payment_order'),
                static::getContainer()->get('state_machine.payment_request'),
                $this->createMock(PaymentService::class),
                static::getContainer()->get(AppSettingService::class),
                static::getContainer()->get(EventDispatcherInterface::class),
                static::getContainer()->get(DivestmentService::class),
                $this->tradeOrderRepositoryMock,
            ])
            ->onlyMethods(['payRequest', 'isOrderComplete'])
            ->getMock();

        $paymentOrder = new PaymentOrder();
        $paymentOrder->setStatus(PaymentOrder::STATE_APPROVED);
        $paymentRequest = new PaymentRequest();
        $paymentRequest->setAmount('1.11');
        $paymentRequest->setStatus(PaymentRequest::STATE_PENDING);
        $paymentOrder->addPayment($paymentRequest);

        $service->expects($this->once())->method('payRequest')->with($paymentRequest);
        $service
            ->expects($this->once())
            ->method('isOrderComplete')
            ->with($paymentOrder);

        /** @var PaymentOrderService $service */
        $service->runRequest($paymentRequest);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('runRequestStatesProvider')]
    public function testRunRequestOrderStateChanges(
        PaymentRequest $paymentRequest,
        string $expectedEndState,
    ): void {
        $this->paymentServiceMock = $this->createMock(PaymentService::class);
        static::getContainer()->set(PaymentService::class, $this->paymentServiceMock);
        $this->service = static::getContainer()->get(PaymentOrderService::class);

        $payoutCreated = new \App\Entity\Payout();
        $payoutCreated->setTransactionId('123');

        $this->paymentServiceMock
            ->expects($this->atMost(1))
            ->method('payDividend')
            ->willReturn($payoutCreated);
        $this->paymentServiceMock
            ->expects($this->atMost(1))
            ->method('getDefaultAssetWalletUserId')
            ->willReturn('abc');

        /** @var PaymentOrderService $service */
        $this->service->runRequest($paymentRequest);
        $this->assertEquals(
            $expectedEndState,
            $paymentRequest->getPaymentOrder()->getStatus(),
        );
    }

    public static function runRequestStatesProvider(): \Generator
    {
        $approvedToComplete = new PaymentOrder(); // w/ 1 pending
        $approvedToInProgress = new PaymentOrder(); // w/ 2 pending
        $inProgressToInProgress = new PaymentOrder(); // w/ 2 pending and 1 complete
        $inProgressToComplete = new PaymentOrder(); // w/ 1 pending and 1 complete
        $emptyComplete = new PaymentOrder(); // w/ 1 pending amount 0

        $approvedToComplete->setStatus(PaymentOrder::STATE_APPROVED);
        $approvedToInProgress->setStatus(PaymentOrder::STATE_APPROVED);
        $inProgressToInProgress->setStatus(PaymentOrder::STATE_IN_PROGRESS);
        $inProgressToComplete->setStatus(PaymentOrder::STATE_IN_PROGRESS);
        $emptyComplete->setStatus(PaymentOrder::STATE_APPROVED);

        $asset = new \App\Entity\Asset();
        foreach ([
            $approvedToComplete,
            $approvedToInProgress,
            $inProgressToInProgress,
            $inProgressToComplete,
            $emptyComplete,
        ] as $order) {
            $order->setPaymentType(PaymentService::TYPE_DIVIDEND);
            $order->setAsset($asset);
            $order->setScheduledFor(new \DateTime());
        }
        $user = new \App\Entity\User();
        $requests = [];
        for ($i = 0; $i < 9; $i++) {
            $request = new PaymentRequest();
            $request->setStatus(
                ($i % 6) == 0
                    ? PaymentRequest::STATE_PAID
                    : PaymentRequest::STATE_PENDING,
            );
            $request->setPayee($user);
            $request->setAmount('0.01');
            $requests[] = $request;
        }
        $approvedToComplete->addPayment($requests[1]);

        $approvedToInProgress->addPayment($requests[2]);
        $approvedToInProgress->addPayment($requests[3]);

        $inProgressToInProgress->addPayment($requests[0]);
        $inProgressToInProgress->addPayment($requests[4]);
        $inProgressToInProgress->addPayment($requests[5]);

        $inProgressToComplete->addPayment($requests[6]);
        $inProgressToComplete->addPayment($requests[7]);

        $requests[8]->setAmount('0');
        $emptyComplete->addPayment($requests[8]);

        yield 'approved to complete' => [$requests[1], PaymentOrder::STATE_COMPLETED];
        yield 'approved to in progress' => [
            $requests[2],
            PaymentOrder::STATE_IN_PROGRESS,
        ];
        yield 'in progress to in progress' => [
            $requests[4],
            PaymentOrder::STATE_IN_PROGRESS,
        ];
        yield 'in progress to complete' => [
            $requests[7],
            PaymentOrder::STATE_COMPLETED,
        ];
        yield 'in progress to in progress no transfer' => [
            $requests[0],
            PaymentOrder::STATE_IN_PROGRESS,
        ];
        yield 'approved to complete no transfer' => [
            $requests[8],
            PaymentOrder::STATE_COMPLETED,
        ];
    }

    public function testRunOrder(): void
    {
        /**
         * runOrder is a helper method that coordinates calls to other methods
         * Test that it does the following:
         * - Get only the payment requests in the order that are pending
         * - Call payRequest on each pending payment request
         * - Check if the order is complete at the end
         */
        $this->tradeOrderRepositoryMock = $this->createMock(TradeOrderRepository::class);
        static::getContainer()->set(
            TradeOrderRepository::class,
            $this->tradeOrderRepositoryMock,
        );
        $service = $this
            ->getMockBuilder(PaymentOrderService::class)
            ->setConstructorArgs([
                $this->createMock(\Psr\Log\LoggerInterface::class),
                static::getContainer()->get('state_machine.payment_order'),
                static::getContainer()->get('state_machine.payment_request'),
                $this->createMock(PaymentService::class),
                static::getContainer()->get(AppSettingService::class),
                static::getContainer()->get(EventDispatcherInterface::class),
                static::getContainer()->get(DivestmentService::class),
                $this->tradeOrderRepositoryMock,
            ])
            ->onlyMethods(['filterPendingRequests', 'payRequest', 'isOrderComplete'])
            ->getMock();

        $paymentOrder = new PaymentOrder();
        $paymentOrder->setStatus(PaymentOrder::STATE_APPROVED);
        $paymentRequest = new PaymentRequest();
        $paymentRequest->setAmount('1.11');
        $paymentRequest->setStatus(PaymentRequest::STATE_PAID);
        $paymentOrder->addPayment($paymentRequest);

        $pendingPayments = [
            PaymentRequest::STATE_PENDING => [
                $paymentRequest,
                $paymentRequest,
                $paymentRequest,
            ],
            PaymentRequest::STATE_FAILED => [],
        ];

        $service
            ->expects($this->once())
            ->method('filterPendingRequests')
            ->with($paymentOrder->getPayments())
            ->willReturn($pendingPayments);
        $service
            ->expects($this->exactly(3))
            ->method('payRequest')
            ->with($paymentRequest);
        $service
            ->expects($this->once())
            ->method('isOrderComplete')
            ->with($paymentOrder)
            ->willReturn(true); // We'll simulate the order being completed to check the status transition

        /** @var PaymentOrderService $service */
        $service->runOrder($paymentOrder);
        $this->assertEquals(AbstractOrder::STATE_COMPLETED, $paymentOrder->getStatus());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('finishBuyBackOrderStatesProvider')]
    public function testRunOrderTradeOrderFinish(
        TradeOrderStatus $expected,
        TradeOrderStatus $start,
    ): void {
        $this->service = static::getContainer()->get(PaymentOrderService::class);

        $paymentOrder = new PaymentOrder();
        $paymentOrder->setAsset(EntityIdTestUtil::setEntityId(new Asset(), 253));
        // Technically the payment type does not matter,
        // $paymentOrder->setPaymentType(PaymentService::TYPE_DIVESTMENT);
        $paymentOrder->setScheduledFor(new \DateTime());
        $paymentOrder->setStatus(PaymentOrder::STATE_APPROVED);

        // The trade order type also does not matter, so we'll omit that
        // The point of the closer method is to ensure any attached orders are cancelled or completed
        $buyBackOrder = new TradeOrder(
            direction: TradeDirection::Buy,
            asset: $paymentOrder->getAsset(),
            user: EntityIdTestUtil::setEntityId(new User(), 41),
            numberOfShares: 0,
            pricePerShare: new Number(0),
        );
        $activeLog = new TradeOrderStatusLog($buyBackOrder, $start);
        $buyBackOrder->addStatusLog($activeLog);
        $paymentOrder->setTradeOrder($buyBackOrder);

        $this->service->runOrder($paymentOrder);
        $this->assertEquals($expected, $buyBackOrder->getStatus());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('finishBuyBackOrderStatesProvider')]
    public function testRunRequestTradeOrderFinish(
        TradeOrderStatus $expected,
        TradeOrderStatus $start,
    ): void {
        $this->service = static::getContainer()->get(PaymentOrderService::class);

        $paymentRequest = new PaymentRequest();
        $paymentRequest->setPayee(EntityIdTestUtil::setEntityId(new User(), 7569));
        $paymentRequest->setStatus(PaymentRequest::STATE_PENDING);
        $paymentRequest->setShareholding(0);
        $paymentRequest->setAmount('0');

        $paymentOrder = new PaymentOrder();
        $paymentOrder->setAsset(EntityIdTestUtil::setEntityId(new Asset(), 253));
        // Technically the payment type does not matter,
        // $paymentOrder->setPaymentType(PaymentService::TYPE_DIVESTMENT);
        $paymentOrder->setScheduledFor(new \DateTime());
        $paymentOrder->setStatus(PaymentOrder::STATE_APPROVED);
        $paymentOrder->addPayment($paymentRequest);

        // The trade order type also does not matter, so we'll omit that
        // The point of the closer method is to ensure any attached orders are cancelled or completed
        $buyBackOrder = new TradeOrder(
            direction: TradeDirection::Buy,
            asset: $paymentOrder->getAsset(),
            user: EntityIdTestUtil::setEntityId(new User(), 41),
            numberOfShares: 0,
            pricePerShare: new Number(0),
        );
        $activeLog = new TradeOrderStatusLog($buyBackOrder, $start);
        $buyBackOrder->addStatusLog($activeLog);
        $paymentOrder->setTradeOrder($buyBackOrder);

        $this->service->runRequest($paymentRequest);
        $this->assertEquals($expected, $buyBackOrder->getStatus());
    }

    public static function finishBuyBackOrderStatesProvider(): \Generator
    {
        yield 'Draft to completed' => [
            TradeOrderStatus::Completed,
            TradeOrderStatus::Draft,
        ];
        yield 'Submitted to completed' => [
            TradeOrderStatus::Completed,
            TradeOrderStatus::Submitted,
        ];
        yield 'Active to completed' => [
            TradeOrderStatus::Completed,
            TradeOrderStatus::Active,
        ];
        yield 'Suspended to completed' => [
            TradeOrderStatus::Completed,
            TradeOrderStatus::Suspended,
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

    #[\PHPUnit\Framework\Attributes\DataProvider('runOrderStatesProvider')]
    public function testRunOrderStateChanges(
        PaymentOrder $input,
        string $expectedEndState,
    ): void {
        $this->paymentServiceMock = $this->createMock(PaymentService::class);
        static::getContainer()->set(PaymentService::class, $this->paymentServiceMock);
        $this->service = static::getContainer()->get(PaymentOrderService::class);

        $this->paymentServiceMock
            ->expects($this->any())
            ->method('getDefaultAssetWalletUserId')
            ->willReturn('abc');

        $this->service->runOrder($input);
        $this->assertEquals($expectedEndState, $input->getStatus());
    }

    public static function runOrderStatesProvider(): \Generator
    {
        $approvedToComplete = new PaymentOrder();
        $approvedToInProgress = new PaymentOrder();
        $inProgressToInProgress = new PaymentOrder();
        $inProgressToComplete = new PaymentOrder();
        $emptyComplete = new PaymentOrder();

        $approvedToComplete->setStatus(PaymentOrder::STATE_APPROVED);
        $approvedToInProgress->setStatus(PaymentOrder::STATE_APPROVED);
        $inProgressToInProgress->setStatus(PaymentOrder::STATE_IN_PROGRESS);
        $inProgressToComplete->setStatus(PaymentOrder::STATE_IN_PROGRESS);
        $emptyComplete->setStatus(PaymentOrder::STATE_APPROVED);

        $asset = new \App\Entity\Asset();
        foreach ([
            $approvedToInProgress,
            $inProgressToInProgress,
            $emptyComplete,
        ] as $order) {
            $order->setPaymentType(PaymentService::TYPE_DIVIDEND);
            $order->setAsset($asset);
            $order->setScheduledFor(new \DateTime());
        }

        $user = new \App\Entity\User();
        $requests = [];
        for ($i = 0; $i < 3; $i++) {
            $request = new PaymentRequest();
            $request->setStatus(PaymentRequest::STATE_PENDING);
            $request->setPayee($user);
            $request->setAmount('0.01');
            $requests[] = $request;
        }
        $approvedToInProgress->addPayment($requests[0]);
        $inProgressToInProgress->addPayment($requests[1]);
        $requests[2]->setAmount('0');
        $emptyComplete->addPayment($requests[2]);

        yield 'approved to complete' => [
            $approvedToComplete,
            PaymentOrder::STATE_COMPLETED,
        ];
        yield 'approved to in progress' => [
            $approvedToInProgress,
            PaymentOrder::STATE_IN_PROGRESS,
        ];
        yield 'in progress to in progress' => [
            $inProgressToInProgress,
            PaymentOrder::STATE_IN_PROGRESS,
        ];
        yield 'in progress to complete' => [
            $inProgressToComplete,
            PaymentOrder::STATE_COMPLETED,
        ];
        yield 'approved to complete no transfer' => [
            $emptyComplete,
            PaymentOrder::STATE_COMPLETED,
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('notRunnableStateProvider')]
    public function testRunOrderNotRunnable(string $startStatus): void
    {
        $this->service = static::getContainer()->get(PaymentOrderService::class);

        $paymentOrder = new PaymentOrder();
        $paymentOrder->setStatus($startStatus);
        $this->expectException(NotEnabledTransitionException::class);
        $this->service->runOrder($paymentOrder);
    }

    public static function notRunnableStateProvider(): \Generator
    {
        yield 'draft' => [PaymentOrder::STATE_DRAFT];
        yield 'closed' => [PaymentOrder::STATE_CLOSED];
        yield 'abandoned' => [PaymentOrder::STATE_ABANDONED];
        yield 'complete' => [PaymentOrder::STATE_COMPLETED];
    }

    public function testGetDebitWalletIdForOrder(): void
    {
        $this->service = static::getContainer()->get(PaymentOrderService::class);

        $asset = new Asset();
        $asset->setMainWalletId('testMainWallet');
        $asset->setDistributionWalletId('testDistributionWallet');
        $paymentOrder = new PaymentOrder();
        $paymentOrder->setAsset($asset);

        $paymentOrder->setDebitWallet('main');
        $actual = $this->service->getDebitWalletIdForOrder($paymentOrder);
        $this->assertEquals('testMainWallet', $actual);

        $paymentOrder->setDebitWallet('distribution');
        $actual = $this->service->getDebitWalletIdForOrder($paymentOrder);
        $this->assertEquals('testDistributionWallet', $actual);

        // Anything else, including another possible wallet, should return the main wallet
        $paymentOrder->setDebitWallet('treasury');
        $actual = $this->service->getDebitWalletIdForOrder($paymentOrder);
        $this->assertEquals('testMainWallet', $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('isLinkableTransferProvider')]
    public function testIsTransferLinkable(
        bool $expected,
        int $amount,
        string $debitWallet,
        string $creditWallet,
        string $transferStatus,
        string $requestStatus,
    ): void {
        $this->service = static::getContainer()->get(PaymentOrderService::class);

        $asset = new Asset();
        $asset->setMainWalletId('testMainWallet');
        $user = new User();
        $user->setMangoPayWalletId('testUserWallet');
        $paymentOrder = new PaymentOrder();
        $paymentOrder->setAsset($asset);
        $paymentRequest = new PaymentRequest();
        $paymentOrder->addPayment($paymentRequest);
        $paymentRequest->setPayee($user);
        $paymentRequest->setStatus($requestStatus);
        $paymentRequest->setAmount('102.58');

        $mpDebit = new \MangoPay\Money();
        $mpDebit->Currency = 'GBP';
        $mpDebit->Amount = $amount;
        $mpTransfer = new \MangoPay\Transfer();
        $mpTransfer->DebitedFunds = $mpDebit;
        $mpTransfer->DebitedWalletId = $debitWallet;
        $mpTransfer->CreditedWalletId = $creditWallet;
        $mpTransfer->Status = $transferStatus;

        $actual = $this->service->isTransferLinkable($paymentRequest, $mpTransfer);
        $this->assertSame($expected, $actual);
    }

    public static function isLinkableTransferProvider(): \Generator
    {
        yield 'linkable pending' => [
            true,
            10258,
            'testMainWallet',
            'testUserWallet',
            \MangoPay\TransactionStatus::Succeeded,
            PaymentRequest::STATE_PENDING,
        ];
        yield 'linkable failed before' => [
            true,
            10258,
            'testMainWallet',
            'testUserWallet',
            \MangoPay\TransactionStatus::Succeeded,
            PaymentRequest::STATE_FAILED,
        ];
        yield 'unlinkable completed' => [
            false,
            10258,
            'testMainWallet',
            'testUserWallet',
            \MangoPay\TransactionStatus::Succeeded,
            PaymentRequest::STATE_PAID,
        ];

        yield 'unlinkable wrong amount' => [
            false,
            10259,
            'testMainWallet',
            'testUserWallet',
            \MangoPay\TransactionStatus::Succeeded,
            PaymentRequest::STATE_PENDING,
        ];

        yield 'unlinkable wrong debit wallet' => [
            false,
            10258,
            'diffMainWallet',
            'testUserWallet',
            \MangoPay\TransactionStatus::Succeeded,
            PaymentRequest::STATE_PENDING,
        ];

        yield 'unlinkable wrong credit wallet' => [
            false,
            10258,
            'testMainWallet',
            'diffUserWallet',
            \MangoPay\TransactionStatus::Succeeded,
            PaymentRequest::STATE_PENDING,
        ];
        yield 'unlinkable not succeeded' => [
            false,
            10258,
            'testMainWallet',
            'testUserWallet',
            \MangoPay\TransactionStatus::Failed,
            PaymentRequest::STATE_PENDING,
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('paymentShareAndValueProvider')]
    public function testLinkTransferDividend(string $amount, int $shares): void
    {
        $this->service = static::getContainer()->get(PaymentOrderService::class);

        // PREPARE
        $paymentRequest = new PaymentRequest();
        $paymentRequest->setPayee(new User());
        $paymentRequest->setStatus(PaymentRequest::STATE_PENDING);
        $paymentRequest->setShareholding($shares);
        $paymentRequest->setAmount($amount);
        $paymentOrder = new PaymentOrder();
        $paymentOrder->setAsset(new Asset());
        $debitWalletId = bin2hex(random_bytes(8));
        $paymentOrder->getAsset()->setMainWalletId($debitWalletId);
        // $paymentOrder->getAsset()->setDistributionWalletId($debitWalletId);
        $paymentOrder->setPaymentType(PaymentService::TYPE_DIVIDEND);
        $paymentOrder->setScheduledFor(new \DateTime());
        $paymentOrder->setStatus(PaymentOrder::STATE_APPROVED);
        $paymentOrder->addPayment($paymentRequest);

        $mpDebit = new \MangoPay\Money();
        $mpDebit->Currency = 'GBP';
        $mpDebit->Amount = (int) round($amount * 100);
        $mpTransfer = new \MangoPay\Transfer();
        $mpTransfer->Id = 'test_mp_trns_' . bin2hex(random_bytes(6));
        $mpTransfer->DebitedFunds = $mpDebit;
        $mpTransfer->DebitedWalletId = 'testMainWallet';
        $mpTransfer->CreditedWalletId = 'testUserWallet';
        $mpTransfer->Status = \MangoPay\TransactionStatus::Succeeded;

        // RUN
        $this->service->linkTransfer($paymentRequest, $mpTransfer);

        // CHECK
        $this->assertEquals(PaymentRequest::STATE_PAID, $paymentRequest->getStatus());
        if ($amount > 0) {
            $this->assertEquals(
                $mpTransfer->Id,
                $paymentRequest->getPayout()->getTransactionId(),
            );
        } else {
            $this->assertNull($paymentRequest->getPayout());
        }
        // Should not be doing anything related to the trade system
        // As no shares are changing hands with dividends
        $this->assertNull($paymentRequest->getShareTrade());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('paymentShareAndValueProvider')]
    public function testLinkTransferDivestment(string $amount, int $shares): void
    {
        $sharePriceEqv = $shares > 0
            ? (string) new Number($amount)->div($shares)->round(6)
            : '0.00';

        // PREPARE
        $paymentRequest = new PaymentRequest();
        $paymentRequest->setPayee(EntityIdTestUtil::setEntityId(new User(), 7569));
        $paymentRequest->setStatus(PaymentRequest::STATE_PENDING);
        $paymentRequest->setShareholding($shares);
        $paymentRequest->setAmount($amount);

        $paymentOrder = new PaymentOrder();
        $paymentOrder->setAsset(EntityIdTestUtil::setEntityId(new Asset(), 253));
        $debitWalletId = bin2hex(random_bytes(8));
        $paymentOrder->getAsset()->setMainWalletId($debitWalletId);
        $paymentOrder->setPaymentType(PaymentService::TYPE_DIVESTMENT);
        $paymentOrder->setScheduledFor(new \DateTime());
        $paymentOrder->setStatus(PaymentOrder::STATE_APPROVED);
        $paymentOrder->addPayment($paymentRequest);

        $mpDebit = new \MangoPay\Money();
        $mpDebit->Currency = 'GBP';
        $mpDebit->Amount = (int) round($amount * 100);
        $mpTransfer = new \MangoPay\Transfer();
        $mpTransfer->Id = 'test_mp_trns_' . bin2hex(random_bytes(6));
        $mpTransfer->DebitedFunds = $mpDebit;
        $mpTransfer->DebitedWalletId = 'testMainWallet';
        $mpTransfer->CreditedWalletId = 'testUserWallet';
        $mpTransfer->Status = \MangoPay\TransactionStatus::Succeeded;

        // Prepare service mocks
        $this->tradeOrderRepositoryMock = $this->createMock(TradeOrderRepository::class);
        static::getContainer()->set(
            TradeOrderRepository::class,
            $this->tradeOrderRepositoryMock,
        );
        $initialOrder = new TradeOrder(
            direction: TradeDirection::Sell,
            asset: $paymentOrder->getAsset(),
            user: EntityIdTestUtil::setEntityId(new User(), 214),
            numberOfShares: 1,
            pricePerShare: new Number(1),
            type: TradeOrderType::Initial,
        );
        $this->tradeOrderRepositoryMock
            ->expects($this->atMost(1))
            ->method('findInitialSellOrders')
            ->willReturn([$initialOrder]);
        $this->service = static::getContainer()->get(PaymentOrderService::class);

        // RUN
        $this->service->linkTransfer($paymentRequest, $mpTransfer);

        // CHECK
        $this->assertEquals(PaymentRequest::STATE_PAID, $paymentRequest->getStatus());
        if ($amount > 0) {
            $this->assertEquals(
                $mpTransfer->Id,
                $paymentRequest->getPayout()->getTransactionId(),
            );
        } else {
            $this->assertNull($paymentRequest->getPayout());
        }

        if ($shares > 0) {
            // Check the BuyBack buy order has been created and set
            // There's only 1 paymentRequest, so should amount to everything
            $this->assertNotEmpty($paymentOrder->getTradeOrder());
            $buyBackOrder = $paymentOrder->getTradeOrder();
            // Share price is 2.58/120 which rounds to 0.0215
            $this->assertEquals($sharePriceEqv, $buyBackOrder->getPricePerShare());
            $this->assertEquals($shares, $buyBackOrder->getNumberOfShares());
            $this->assertEquals($paymentOrder->getAsset(), $buyBackOrder->getAsset());
            $this->assertEquals($initialOrder->getUser(), $buyBackOrder->getUser());
            $this->assertEquals(TradeDirection::Buy, $buyBackOrder->getDirection());
            $this->assertEquals(TradeOrderType::BuyBack, $buyBackOrder->getType());
            // Note that only runRequest and runOrder will transition to Completed
            $this->assertEquals(TradeOrderStatus::Active, $buyBackOrder->getStatus());

            // Check the BuyBack sell order and the share trade has been created and set
            $this->assertNotEmpty($paymentRequest->getShareTrade());
            $this->assertNotEmpty($paymentRequest->getShareTrade()->getSellOrder());
            $shareTrade = $paymentRequest->getShareTrade();
            $this->assertEquals($sharePriceEqv, $shareTrade->getPricePerShare());
            $this->assertEquals($shares, $shareTrade->getNumberOfShares());
            $this->assertEquals($amount, $shareTrade->getTradeValue());
            $this->assertFalse($shareTrade->isDerived());

            $sellBackorder = $shareTrade->getSellOrder();
            $this->assertEquals($sharePriceEqv, $sellBackorder->getPricePerShare());
            $this->assertEquals($shares, $sellBackorder->getNumberOfShares());
            $this->assertEquals($buyBackOrder->getAsset(), $sellBackorder->getAsset());
            $this->assertEquals($paymentRequest->getPayee(), $sellBackorder->getUser());
            $this->assertEquals(TradeDirection::Sell, $sellBackorder->getDirection());
            $this->assertEquals(TradeOrderType::BuyBack, $sellBackorder->getType());
            $this->assertEquals(
                TradeOrderStatus::Completed,
                $sellBackorder->getStatus(),
            );
        } else {
            // No share trade processing is performed if payment shareholding is zero
            $this->assertNull($paymentRequest->getShareTrade());
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('paymentShareAndValueProvider')]
    public function testLinkTransferRepayment(string $amount, int $shares): void
    {
        $sharePriceEqv = $shares > 0
            ? (string) new Number($amount)->div($shares)->round(6)
            : '0.00';
        // PREPARE
        $paymentRequest = new PaymentRequest();
        $paymentRequest->setPayee(EntityIdTestUtil::setEntityId(new User(), 7569));
        $paymentRequest->setStatus(PaymentRequest::STATE_PENDING);
        $paymentRequest->setShareholding($shares);
        $paymentRequest->setAmount($amount);

        $paymentOrder = new PaymentOrder();
        $paymentOrder->setAsset(EntityIdTestUtil::setEntityId(new Asset(), 253));
        $debitWalletId = bin2hex(random_bytes(8));
        $paymentOrder->getAsset()->setMainWalletId($debitWalletId);
        $paymentOrder->setPaymentType(PaymentService::TYPE_REPAYMENT);
        $paymentOrder->setScheduledFor(new \DateTime());
        $paymentOrder->setStatus(PaymentOrder::STATE_APPROVED);
        $paymentOrder->addPayment($paymentRequest);

        $prefunderSellOrder = EntityIdTestUtil::setEntityId(
            new TradeOrder(
                direction: TradeDirection::Sell,
                type: TradeOrderType::Prefunding,
                numberOfShares: $paymentRequest->getShareholding(),
                user: $paymentRequest->getPayee(),
                asset: $paymentOrder->getAsset(),
            ),
            7851,
        );
        $prefunderSellOrder->setStatus(TradeOrderStatus::Active);
        $paymentRequest->setTradeOrder($prefunderSellOrder);

        $mpDebit = new \MangoPay\Money();
        $mpDebit->Currency = 'GBP';
        $mpDebit->Amount = (int) round($amount * 100);
        $mpTransfer = new \MangoPay\Transfer();
        $mpTransfer->Id = 'test_mp_trns_' . bin2hex(random_bytes(6));
        $mpTransfer->DebitedFunds = $mpDebit;
        $mpTransfer->DebitedWalletId = 'testMainWallet';
        $mpTransfer->CreditedWalletId = 'testUserWallet';
        $mpTransfer->Status = \MangoPay\TransactionStatus::Succeeded;

        // Prepare service mocks
        $this->tradeOrderRepositoryMock = $this->createMock(TradeOrderRepository::class);
        static::getContainer()->set(
            TradeOrderRepository::class,
            $this->tradeOrderRepositoryMock,
        );
        $initialOrder = new TradeOrder(
            direction: TradeDirection::Sell,
            asset: $paymentOrder->getAsset(),
            user: EntityIdTestUtil::setEntityId(new User(), 214),
            numberOfShares: 1,
            pricePerShare: new Number(1),
            type: TradeOrderType::Initial,
        );
        $this->tradeOrderRepositoryMock
            ->expects($this->atMost(1))
            ->method('findInitialSellOrders')
            ->willReturn([$initialOrder]);
        $this->service = static::getContainer()->get(PaymentOrderService::class);

        // RUN
        $this->service->linkTransfer($paymentRequest, $mpTransfer);

        // CHECK
        $this->assertEquals(PaymentRequest::STATE_PAID, $paymentRequest->getStatus());
        if ($amount > 0) {
            $this->assertEquals(
                $mpTransfer->Id,
                $paymentRequest->getPayout()->getTransactionId(),
            );
        } else {
            $this->assertNull($paymentRequest->getPayout());
        }

        if ($shares > 0) {
            // Check the BuyBack buy order has been created and set
            // There's only 1 paymentRequest, so should amount to everything
            $this->assertNotEmpty($paymentOrder->getTradeOrder());
            $buyBackOrder = $paymentOrder->getTradeOrder();
            // Share price is 2.58/120 which rounds to 0.0215
            $this->assertEquals($sharePriceEqv, $buyBackOrder->getPricePerShare());
            $this->assertEquals($shares, $buyBackOrder->getNumberOfShares());
            $this->assertEquals($paymentOrder->getAsset(), $buyBackOrder->getAsset());
            $this->assertEquals($initialOrder->getUser(), $buyBackOrder->getUser());
            $this->assertEquals(TradeDirection::Buy, $buyBackOrder->getDirection());
            $this->assertEquals(TradeOrderType::Proxy, $buyBackOrder->getType());
            // Note that only runRequest and runOrder will transition to Completed
            $this->assertEquals(TradeOrderStatus::Active, $buyBackOrder->getStatus());

            // Check the BuyBack sell order and the share trade has been created and set
            $this->assertNotEmpty($paymentRequest->getShareTrade());
            $this->assertNotEmpty($paymentRequest->getShareTrade()->getSellOrder());
            $shareTrade = $paymentRequest->getShareTrade();
            $this->assertEquals($sharePriceEqv, $shareTrade->getPricePerShare());
            $this->assertEquals($shares, $shareTrade->getNumberOfShares());
            $this->assertEquals($amount, $shareTrade->getTradeValue());
            $this->assertFalse($shareTrade->isDerived());

            $sellBackorder = $shareTrade->getSellOrder();
            // Should be the same order, not a new one that is created
            $this->assertSame($prefunderSellOrder->getId(), $sellBackorder->getId());
            $this->assertEquals(
                $prefunderSellOrder->getNumberOfShares(),
                $sellBackorder->getNumberOfShares(),
            );
            $this->assertEquals($buyBackOrder->getAsset(), $sellBackorder->getAsset());
            $this->assertEquals($paymentRequest->getPayee(), $sellBackorder->getUser());
            $this->assertEquals(TradeDirection::Sell, $sellBackorder->getDirection());
            $this->assertEquals(TradeOrderType::Prefunding, $sellBackorder->getType());
            // Should be completed as the test uses the same number of shares as the payment
            $this->assertEquals(
                TradeOrderStatus::Completed,
                $sellBackorder->getStatus(),
            );
        } else {
            // No share trade processing is performed if payment shareholding is zero
            $this->assertNull($paymentRequest->getShareTrade());
        }
    }

    public static function paymentShareAndValueProvider(): \Generator
    {
        yield 'Non empty share and value' => ['2.58', 120];
        yield 'Non empty share and empty value' => ['0.00', 120];
        yield 'Empty share and empty value' => ['0.00', 0];
    }
}
