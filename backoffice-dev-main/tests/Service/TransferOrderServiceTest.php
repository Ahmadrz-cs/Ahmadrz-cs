<?php

namespace App\Tests\Service;

use App\Entity\AbstractOrder;
use App\Entity\Asset;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeStatus;
use App\Entity\Enum\TransferMode;
use App\Entity\Enum\TransferType;
use App\Entity\Investment;
use App\Entity\ShareTrade;
use App\Entity\ShareTradeStatusLog;
use App\Entity\TradeOrder;
use App\Entity\Transaction;
use App\Entity\TransferOrder;
use App\Entity\TransferRequest;
use App\Entity\User;
use App\Service\MonthEndService;
use App\Service\TransferOrderService;
use App\Service\TransferService;
use App\Test\Util\EntityIdTestUtil;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\Exception\NotEnabledTransitionException;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
final class TransferOrderServiceTest extends KernelTestCase
{
    private TransferOrderService $service;
    private TransferService|MockObject $transferServiceMock;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->transferServiceMock = $this->createMock(TransferService::class);
        static::getContainer()->set(TransferService::class, $this->transferServiceMock);
        $this->service = static::getContainer()->get(TransferOrderService::class);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('transitionsProvider')]
    public function testTransitionTransferOrder(
        string $transition,
        string $start,
        string $expected,
    ): void {
        $transferOrder = new TransferOrder();
        $transferOrder->setStatus($start);
        $this->service->transitionTransferOrder($transferOrder, $transition);
        $this->assertEquals($expected, $transferOrder->getStatus());
    }

    public static function transitionsProvider(): \Generator
    {
        yield 'approve draft' => [
            AbstractOrder::TRANSITION_APPROVE,
            AbstractOrder::STATE_DRAFT,
            AbstractOrder::STATE_APPROVED,
        ];
        yield 'unapprove' => [
            AbstractOrder::TRANSITION_REQUEST_CHANGE,
            AbstractOrder::STATE_APPROVED,
            AbstractOrder::STATE_DRAFT,
        ];
        yield 'run order' => [
            AbstractOrder::TRANSITION_RUN,
            AbstractOrder::STATE_APPROVED,
            AbstractOrder::STATE_IN_PROGRESS,
        ];
        yield 'run in progress' => [
            AbstractOrder::TRANSITION_RUN,
            AbstractOrder::STATE_IN_PROGRESS,
            AbstractOrder::STATE_IN_PROGRESS,
        ];
        yield 'close draft' => [
            AbstractOrder::TRANSITION_REJECT,
            AbstractOrder::STATE_DRAFT,
            AbstractOrder::STATE_CLOSED,
        ];
        yield 'close approved' => [
            AbstractOrder::TRANSITION_REJECT,
            AbstractOrder::STATE_APPROVED,
            AbstractOrder::STATE_CLOSED,
        ];
        yield AbstractOrder::TRANSITION_ABANDON => [
            AbstractOrder::TRANSITION_ABANDON,
            AbstractOrder::STATE_IN_PROGRESS,
            AbstractOrder::STATE_ABANDONED,
        ];
        yield AbstractOrder::TRANSITION_COMPLETE => [
            AbstractOrder::TRANSITION_COMPLETE,
            AbstractOrder::STATE_IN_PROGRESS,
            AbstractOrder::STATE_COMPLETED,
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidTransitionsProvider')]
    public function testTransitionTransferOrderInvalid(
        string $transition,
        string $start,
    ): void {
        // Just check a handful as a sanity check
        $transferOrder = new TransferOrder();
        $transferOrder->setStatus($start);
        $this->expectException(NotEnabledTransitionException::class);
        $this->service->transitionTransferOrder($transferOrder, $transition);
    }

    public static function invalidTransitionsProvider(): \Generator
    {
        yield 'approve approved' => [
            AbstractOrder::TRANSITION_APPROVE,
            AbstractOrder::STATE_APPROVED,
        ];
        yield 'approve in progress' => [
            AbstractOrder::TRANSITION_APPROVE,
            AbstractOrder::STATE_IN_PROGRESS,
        ];
        yield 'approve complete' => [
            AbstractOrder::TRANSITION_APPROVE,
            AbstractOrder::STATE_COMPLETED,
        ];
        yield 'unapprove in progress' => [
            AbstractOrder::TRANSITION_REQUEST_CHANGE,
            AbstractOrder::STATE_IN_PROGRESS,
        ];
        yield 'run draft' => [
            AbstractOrder::TRANSITION_RUN,
            AbstractOrder::STATE_DRAFT,
        ];
        yield 'run completed' => [
            AbstractOrder::TRANSITION_RUN,
            AbstractOrder::STATE_COMPLETED,
        ];
        yield 'close closed' => [
            AbstractOrder::TRANSITION_REJECT,
            AbstractOrder::STATE_CLOSED,
        ];
        yield 'close completed' => [
            AbstractOrder::TRANSITION_REJECT,
            AbstractOrder::STATE_COMPLETED,
        ];
        yield 'abandon draft' => [
            AbstractOrder::TRANSITION_ABANDON,
            AbstractOrder::STATE_DRAFT,
        ];
        yield 'abandon approved' => [
            AbstractOrder::TRANSITION_ABANDON,
            AbstractOrder::STATE_APPROVED,
        ];
        yield 'complete draft' => [
            AbstractOrder::TRANSITION_COMPLETE,
            AbstractOrder::STATE_DRAFT,
        ];
        yield 'complete approved' => [
            AbstractOrder::TRANSITION_COMPLETE,
            AbstractOrder::STATE_APPROVED,
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('formatTransferOrderProvider')]
    public function testFormatTransferOrdersCallable(
        array $expected,
        TransferOrder $input,
    ): void {
        $actual = \call_user_func(
            $this->service->formatTransferOrdersCallable(),
            $input,
        );
        $this->assertEqualsCanonicalizing($expected, $actual);
        $this->assertEqualsCanonicalizing(array_keys($expected), array_keys($actual));
    }

    public static function formatTransferOrderProvider(): \Generator
    {
        $currentDt = new \DateTime();
        $expectedWithAsset = [
            'id' => 58,
            'type' => TransferType::FeeCollection->value,
            'description' => 'Formatter test transfer order',
            'assetId' => 22,
            'assetSpv' => 'SPVT000328',
            'assetName' => 'Automated test transfer order asset',
            'status' => 'ready',
            'scheduledFor' => $currentDt->format('Y-m-d'),
            'totalTransfers' => 3,
            'approvedBy' => 'finops.auto@test.yielderverse.co.uk',
            'createdBy' => 'ops.auto@test.yielderverse.co.uk',
            'createdAt' => $currentDt->format('r'),
            'updatedBy' => 'finops.auto@test.yielderverse.co.uk',
            'updatedAt' => $currentDt->format('r'),
        ];

        /** @var \App\Entity\Asset $asset */
        $asset = EntityIdTestUtil::setEntityId(
            new \App\Entity\Asset(),
            $expectedWithAsset['assetId'],
        );
        $asset->setCompanyNumber($expectedWithAsset['assetSpv']);
        $asset->setName($expectedWithAsset['assetName']);

        $user = new \App\Entity\User();
        $user->setUsername('finops.auto@test.yielderverse.co.uk');

        /** @var TransferOrder $orderWithAsset */
        $orderWithAsset = EntityIdTestUtil::setEntityId(
            new TransferOrder(),
            $expectedWithAsset['id'],
        );
        $orderWithAsset->setTransferType(TransferType::FeeCollection);
        $orderWithAsset->setAsset($asset);
        $orderWithAsset->setDescription($expectedWithAsset['description']);
        $orderWithAsset->setStatus($expectedWithAsset['status']);
        $orderWithAsset->setScheduledFor($currentDt);
        $orderWithAsset->setApprovedBy($user);
        $orderWithAsset->setCreatedAt($currentDt);
        $orderWithAsset->setCreatedBy($expectedWithAsset['createdBy']);
        $orderWithAsset->setUpdatedAt($currentDt);
        $orderWithAsset->setUpdatedBy($expectedWithAsset['updatedBy']);
        for ($i = 0; $i < $expectedWithAsset['totalTransfers']; $i++) {
            $orderWithAsset->addTransfer(new TransferRequest());
        }

        $expectedNoAsset = [
            'id' => 58,
            'type' => TransferType::Custom->value,
            'description' => 'Formatter test transfer order',
            'assetId' => null,
            'assetSpv' => null,
            'assetName' => null,
            'status' => 'ready',
            'scheduledFor' => $currentDt->format('Y-m-d'),
            'totalTransfers' => 5,
            'approvedBy' => null,
            'createdBy' => 'ops.auto@test.yielderverse.co.uk',
            'createdAt' => $currentDt->format('r'),
            'updatedBy' => 'finops.auto@test.yielderverse.co.uk',
            'updatedAt' => $currentDt->format('r'),
        ];

        /** @var TransferOrder $orderNoAsset */
        $orderNoAsset = EntityIdTestUtil::setEntityId(
            new TransferOrder(),
            $expectedNoAsset['id'],
        );
        $orderNoAsset->setTransferType(TransferType::Custom);
        $orderNoAsset->setDescription($expectedNoAsset['description']);
        $orderNoAsset->setStatus($expectedNoAsset['status']);
        $orderNoAsset->setScheduledFor($currentDt);
        $orderNoAsset->setCreatedAt($currentDt);
        $orderNoAsset->setCreatedBy($expectedNoAsset['createdBy']);
        $orderNoAsset->setUpdatedAt($currentDt);
        $orderNoAsset->setUpdatedBy($expectedNoAsset['updatedBy']);
        for ($i = 0; $i < $expectedNoAsset['totalTransfers']; $i++) {
            $orderNoAsset->addTransfer(new TransferRequest());
        }

        yield 'With asset relation' => [$expectedWithAsset, $orderWithAsset];
        yield 'No asset relation' => [$expectedNoAsset, $orderNoAsset];
    }

    public function testFormatTransfersCallable(): void
    {
        $currentDt = new \DateTime();
        $expected = [
            'id' => 1256,
            'investment' => '6612',
            'shareTrade' => '6612',
            'transferOrderId' => 63,
            'mode' => 'Default',
            'description' => 'Formatter test transfer request',

            'debitWalletId' => '1234abcd9876',
            // 'debitWalletOwner' => 'formatterDebitor',
            'creditWalletId' => 'abcd1234efgh',
            // 'creditWalletOwner' => 'formatterCreditor',
            'status' => TransferRequest::STATE_PENDING,
            'amount' => '14.65',
            'transactionId' => 55223,
            'createdBy' => 'ops.auto@test.yielderverse.co.uk',
            'createdAt' => $currentDt->format('r'),
            'updatedBy' => 'finops.auto@test.yielderverse.co.uk',
            'updatedAt' => $currentDt->format('r'),
        ];

        $relation = EntityIdTestUtil::setEntityId(
            new TransferOrder(),
            $expected['transferOrderId'],
        );
        $investment = EntityIdTestUtil::setEntityId(
            new Investment(),
            $expected['investment'],
        );
        $shareTrade = EntityIdTestUtil::setEntityId(
            new ShareTrade(),
            $expected['shareTrade'],
        );
        $transaction = EntityIdTestUtil::setEntityId(
            new Transaction(),
            $expected['transactionId'],
        );

        $sample = EntityIdTestUtil::setEntityId(new TransferRequest(), $expected['id']);
        $sample->setTransferOrder($relation);
        $sample->setInvestment($investment);
        $sample->setShareTrade($shareTrade);
        $sample->setDescription($expected['description']);
        $sample->setDebitWalletId($expected['debitWalletId']);
        // $sample->setDebitWalletOwner($expected['debitWalletOwner']);
        $sample->setCreditWalletId($expected['creditWalletId']);
        // $sample->setCreditWalletOwner($expected['creditWalletOwner']);
        $sample->setAmount($expected['amount']);
        $sample->setTransaction($transaction);
        $sample->setStatus($expected['status']);
        $sample->setCreatedAt($currentDt);
        $sample->setCreatedBy($expected['createdBy']);
        $sample->setUpdatedAt($currentDt);
        $sample->setUpdatedBy($expected['updatedBy']);

        $actual = \call_user_func($this->service->formatTransfersCallable(), $sample);
        $this->assertEquals($expected, $actual);
        $this->assertEquals(array_keys($expected), array_keys($actual));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('runRequestStatesProvider')]
    public function testRunRequest(
        TransferRequest $input,
        int $expectedTransfers,
        string $expectedOrderEndState,
    ): void {
        $this->transferServiceMock
            ->expects($this->exactly($expectedTransfers))
            ->method('makeWalletTransfer')
            ->willReturn(new Transaction());

        // Check state changes applied
        $this->service->runRequest($input);
        $this->assertEquals(
            $expectedOrderEndState,
            $input->getTransferOrder()->getStatus(),
        );
        $this->assertEquals(TransferRequest::STATE_COMPLETE, $input->getStatus());

        // Check transaction object created and set
        if ($expectedTransfers > 0) {
            $this->assertNotEmpty($input->getTransaction());
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('runRequestStatesProvider')]
    public function testRunRequestLinkMode(
        TransferRequest $input,
        int $expectedTransfers,
        string $expectedOrderEndState,
    ): void {
        // PREPARE
        $this->transferServiceMock
            ->expects($this->never())
            ->method('makeWalletTransfer');

        $mpDebit = new \MangoPay\Money();
        $mpDebit->Currency = 'GBP';
        $mpDebit->Amount = (int) round($input->getAmount() * 100);
        $mpTransfer = new \MangoPay\Transfer();
        $mpTransfer->Id = 'test_mp_trns_' . bin2hex(random_bytes(6));
        $mpTransfer->Status = \MangoPay\TransactionStatus::Succeeded;

        $transaction = new Transaction();
        $transaction->setExternalId($mpTransfer->Id);

        $this->transferServiceMock
            ->expects($this->exactly($expectedTransfers))
            ->method('createTransaction')
            ->willReturn($transaction);

        // RUN
        $this->service->runRequest($input, $mpTransfer);

        // CHECK
        // Check state changes applied
        $this->assertEquals(
            $expectedOrderEndState,
            $input->getTransferOrder()->getStatus(),
        );
        $this->assertEquals(TransferRequest::STATE_COMPLETE, $input->getStatus());

        // Check transaction object created and set
        if ($expectedTransfers > 0) {
            $this->assertNotNull($input->getTransaction());
            $this->assertEquals(
                $mpTransfer->Id,
                $input->getTransaction()->getReferenceId(),
            );
        } else {
            $this->assertNull($input->getTransaction());
        }
    }

    public static function runRequestStatesProvider(): \Generator
    {
        $noneRemaining = new TransferOrder();
        $oneRemaining = new TransferOrder();
        $multipleRemaining = new TransferOrder();
        $emptyRequest = new TransferOrder();

        $noneRemaining->setStatus(AbstractOrder::STATE_IN_PROGRESS);
        $oneRemaining->setStatus(AbstractOrder::STATE_IN_PROGRESS);
        $multipleRemaining->setStatus(AbstractOrder::STATE_APPROVED);
        $emptyRequest->setStatus(AbstractOrder::STATE_APPROVED);

        foreach ([
            $noneRemaining,
            $oneRemaining,
            $multipleRemaining,
            $emptyRequest,
        ] as $order) {
            $order->setScheduledFor(new \DateTime());
        }

        $requests = [];
        for ($i = 0; $i < 8; $i++) {
            $request = new TransferRequest();
            $request->setDebitWalletId('testDebitWalletId124');
            $request->setCreditWalletId('testCreditWalletId4816');
            $request->setDescription('Test transfer request');
            $request->setAmount('0.01');
            $requests[] = $request;
        }
        // 0 transfers remaining
        $requests[0]->setStatus(TransferRequest::STATE_COMPLETE);
        $noneRemaining->addTransfer($requests[0]);

        // 1 transfer remaining
        $requests[1]->setStatus(TransferRequest::STATE_COMPLETE);
        $oneRemaining->addTransfer($requests[1]);
        $oneRemaining->addTransfer($requests[2]);

        // 3 transfers remaining
        $multipleRemaining->addTransfer($requests[3]);
        $multipleRemaining->addTransfer($requests[4]);
        $multipleRemaining->addTransfer($requests[5]);

        // 2 transfers remaining, but we're running the 0 one
        $requests[7]->setAmount('0');
        $emptyRequest->addTransfer($requests[6]);
        $emptyRequest->addTransfer($requests[7]);

        yield 'Already complete' => [$requests[0], 0, AbstractOrder::STATE_COMPLETED];
        yield 'One then complete' => [$requests[2], 1, AbstractOrder::STATE_COMPLETED];
        yield 'Numerous remaining' => [
            $requests[5],
            1,
            AbstractOrder::STATE_IN_PROGRESS,
        ];
        yield 'Empty request' => [$requests[7], 0, AbstractOrder::STATE_IN_PROGRESS];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('runRequestWithShareTradeProvider')]
    public function testRunRequestWithShareTrade(
        string $description,
        TransferMode $transferMode,
        TradeStatus $startStatus,
        TradeStatus $endStatus,
        string $amount = '1.00',
    ): void {
        /**
         * - Check share trades in various states
         *   - Unsettled -> settled
         *   - Settled -> settled (i.e. no change)
         *   - Any other state -> no change to the share trade - not doing a settlement
         * - Stamp duty should not cause the share trade to be settled
         *   - Because this can be a bit problematic if the actual settlement transfer is missing
         *   - Although you can always fix manually by adding a new status log
         */
        $shareTrade = new ShareTrade();
        $statusLog = new ShareTradeStatusLog($shareTrade, $startStatus);
        $shareTrade->addStatusLog($statusLog);

        $transferOrder = new TransferOrder();
        $transferOrder->setStatus(AbstractOrder::STATE_APPROVED);
        $transferRequest = new TransferRequest();
        $transferRequest->setDescription($description);
        // Note that the amount has no influence on the status changes
        // Only on the money transfer
        $transferRequest->setAmount($amount);
        $transferRequest->setShareTrade($shareTrade);
        $transferRequest->setMode($transferMode);
        $transferOrder->addTransfer($transferRequest);

        $this->transferServiceMock
            ->expects($amount > 0 ? $this->once() : $this->never())
            ->method('makeWalletTransfer')
            ->willReturn(new Transaction());

        // Check state changes applied
        $this->service->runRequest($transferRequest);
        $this->assertEquals(
            AbstractOrder::STATE_COMPLETED,
            $transferRequest->getTransferOrder()->getStatus(),
        );
        $this->assertEquals(
            TransferRequest::STATE_COMPLETE,
            $transferRequest->getStatus(),
        );

        // Check investment state changes applied where relevant
        $this->assertEquals($endStatus, $shareTrade->getStatus());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('runRequestWithShareTradeProvider')]
    public function testRunRequestWithShareTradeLinkMode(
        string $description,
        TransferMode $transferMode,
        TradeStatus $startStatus,
        TradeStatus $endStatus,
        string $amount = '1.00',
    ): void {
        /**
         * - Check share trades in various states
         *   - Unsettled -> settled
         *   - Settled -> settled (i.e. no change)
         *   - Any other state -> no change to the share trade - not doing a settlement
         * - Stamp duty should not cause the share trade to be settled
         *   - Because this can be a bit problematic if the actual settlement transfer is missing
         *   - Although you can always fix manually by adding a new status log
         */
        $shareTrade = new ShareTrade();
        $statusLog = new ShareTradeStatusLog($shareTrade, $startStatus);
        $shareTrade->addStatusLog($statusLog);

        $transferOrder = new TransferOrder();
        $transferOrder->setStatus(AbstractOrder::STATE_APPROVED);
        $transferRequest = new TransferRequest();
        $transferRequest->setDescription($description);
        // Note that the amount has no influence on the status changes
        // Only on the transaction linking
        $transferRequest->setAmount($amount);
        $transferRequest->setShareTrade($shareTrade);
        $transferRequest->setMode($transferMode);
        $transferOrder->addTransfer($transferRequest);

        $this->transferServiceMock
            ->expects($this->never())
            ->method('makeWalletTransfer');

        $mpDebit = new \MangoPay\Money();
        $mpDebit->Currency = 'GBP';
        $mpDebit->Amount = (int) round($amount * 100);
        $mpTransfer = new \MangoPay\Transfer();
        $mpTransfer->Id = 'test_mp_trns_' . bin2hex(random_bytes(6));
        $mpTransfer->Status = \MangoPay\TransactionStatus::Succeeded;

        $transaction = new Transaction();
        $transaction->setExternalId($mpTransfer->Id);

        $this->transferServiceMock
            ->expects($amount > 0 ? $this->once() : $this->never())
            ->method('createTransaction')
            ->willReturn($transaction);

        // Check state changes applied
        $this->service->runRequest($transferRequest, $mpTransfer);
        $this->assertEquals(
            AbstractOrder::STATE_COMPLETED,
            $transferRequest->getTransferOrder()->getStatus(),
        );
        $this->assertEquals(
            TransferRequest::STATE_COMPLETE,
            $transferRequest->getStatus(),
        );

        // Check investment state changes applied where relevant
        $this->assertEquals($endStatus, $shareTrade->getStatus());
    }

    public static function runRequestWithShareTradeProvider(): \Generator
    {
        yield 'Unsettled share trade' => [
            'Settle investment',
            TransferMode::Settlement,
            TradeStatus::Unsettled,
            TradeStatus::Settled,
        ];
        yield 'Unsettled share trade, no money transfer' => [
            'Settle investment',
            TransferMode::Settlement,
            TradeStatus::Unsettled,
            TradeStatus::Settled,
            '0.00',
        ];
        yield 'Already settled share trade' => [
            'Another settle investment',
            TransferMode::Settlement,
            TradeStatus::Settled,
            TradeStatus::Settled,
        ];
        yield 'Stamp duty transfer' => [
            MonthEndService::DESCRIPTION_PRESETS['stamp duty'],
            TransferMode::StampDuty,
            TradeStatus::Unsettled,
            TradeStatus::Unsettled,
        ];
        yield 'Cancelled share trade' => [
            'Settle investment',
            TransferMode::Settlement,
            TradeStatus::Cancelled,
            TradeStatus::Cancelled,
        ];
        yield 'Draft share trade' => [
            'Settle investment',
            TransferMode::Settlement,
            TradeStatus::Draft,
            TradeStatus::Draft,
        ];
        yield 'Suspended share trade' => [
            'Settle investment',
            TransferMode::Settlement,
            TradeStatus::Suspended,
            TradeStatus::Suspended,
        ];
    }

    #[\PHPUnit\Framework\Attributes\Group('tradeOrderTransitions')]
    public function testRunOrderWithShareTradeOrderTransition(): void
    {
        $buyOrder = EntityIdTestUtil::setEntityId(
            new TradeOrder(direction: TradeDirection::Buy, numberOfShares: 100),
            444,
        );
        $buyOrder->setStatus(TradeOrderStatus::Active);
        $buyOrder->setSharesTraded(100);
        $sellOrder = EntityIdTestUtil::setEntityId(
            new TradeOrder(direction: TradeDirection::Sell, numberOfShares: 100),
            777,
        );
        $sellOrder->setStatus(TradeOrderStatus::Active);
        $sellOrder->setSharesTraded(100);

        $shareTrade = EntityIdTestUtil::setEntityId(
            new ShareTrade(buyOrder: $buyOrder, sellOrder: $sellOrder),
            5167,
        );
        $shareTrade->setStatus(TradeStatus::Unsettled);
        $buyOrder->addShareTrade($shareTrade);
        $sellOrder->addShareTrade($shareTrade);

        $transferOrder = new TransferOrder();
        $transferOrder->setStatus(AbstractOrder::STATE_APPROVED);
        $transferOrder->setTransferType(TransferType::InvestmentSettlement);
        $transferRequest = new TransferRequest();
        $transferRequest->setDescription('Settle investment');
        // Note that the amount has no influence on the status changes
        $transferRequest->setAmount(0);
        $transferRequest->setShareTrade($shareTrade);
        $transferRequest->setMode(TransferMode::Settlement);
        $transferOrder->addTransfer($transferRequest);

        // Check state changes applied
        $this->service->runOrder($transferOrder);
        $this->assertEquals(
            AbstractOrder::STATE_COMPLETED,
            $transferRequest->getTransferOrder()->getStatus(),
        );
        $this->assertEquals(
            TransferRequest::STATE_COMPLETE,
            $transferRequest->getStatus(),
        );

        $this->assertEquals(TradeStatus::Settled, $shareTrade->getStatus());
        $this->assertEquals(
            TradeOrderStatus::Completed,
            $shareTrade->getBuyOrder()->getStatus(),
        );
        $this->assertEquals(
            TradeOrderStatus::Completed,
            $shareTrade->getSellOrder()->getStatus(),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('tradeOrderTransitions')]
    public function testRunRequestWithShareTradeOrderTransition(): void
    {
        $buyOrder = EntityIdTestUtil::setEntityId(
            new TradeOrder(direction: TradeDirection::Buy, numberOfShares: 100),
            444,
        );
        $buyOrder->setStatus(TradeOrderStatus::Active);
        $buyOrder->setSharesTraded(100);
        $sellOrder = EntityIdTestUtil::setEntityId(
            new TradeOrder(direction: TradeDirection::Sell, numberOfShares: 100),
            777,
        );
        $sellOrder->setStatus(TradeOrderStatus::Active);
        $sellOrder->setSharesTraded(100);

        $shareTrade = EntityIdTestUtil::setEntityId(
            new ShareTrade(buyOrder: $buyOrder, sellOrder: $sellOrder),
            5167,
        );
        $shareTrade->setStatus(TradeStatus::Unsettled);
        $buyOrder->addShareTrade($shareTrade);
        $sellOrder->addShareTrade($shareTrade);

        $transferOrder = new TransferOrder();
        $transferOrder->setStatus(AbstractOrder::STATE_APPROVED);
        $transferOrder->setTransferType(TransferType::InvestmentSettlement);
        $transferRequest = new TransferRequest();
        $transferRequest->setDescription('Settle investment');
        // Note that the amount has no influence on the status changes
        $transferRequest->setAmount(0);
        $transferRequest->setShareTrade($shareTrade);
        $transferRequest->setMode(TransferMode::Settlement);
        $transferOrder->addTransfer($transferRequest);

        // Check state changes applied
        $this->service->runRequest($transferRequest);
        $this->assertEquals(
            AbstractOrder::STATE_COMPLETED,
            $transferRequest->getTransferOrder()->getStatus(),
        );
        $this->assertEquals(
            TransferRequest::STATE_COMPLETE,
            $transferRequest->getStatus(),
        );

        $this->assertEquals(TradeStatus::Settled, $shareTrade->getStatus());
        $this->assertEquals(
            TradeOrderStatus::Completed,
            $shareTrade->getBuyOrder()->getStatus(),
        );
        $this->assertEquals(
            TradeOrderStatus::Completed,
            $shareTrade->getSellOrder()->getStatus(),
        );
    }

    public function testFilterPendingRequests(): void
    {
        $input = [];
        // Out of 13 requests counting from zero (0-12) - based on floor division denoted by //
        // Requests 0, 3 ,6, 9, 12 will be paid
        // Requests 5, 10, will be failed
        // Remainder (1, 2, 4, 7, 8, 11) will be pending
        // (13 - 5 == 8) in total will be considered pending and ready for running (i.e. either pending or failed)
        for ($i = 0; $i < 13; $i++) {
            $transferRequest = new TransferRequest();
            $transferRequest->setStatus(
                ($i % 3) == 0
                    ? TransferRequest::STATE_COMPLETE
                    : (
                        ($i % 5)
                        == 0
                            ? TransferRequest::STATE_FAILED
                            : TransferRequest::STATE_PENDING
                    ),
            );
            $input[] = $transferRequest;
        }
        $actual = $this->service->filterPendingRequests($input);
        $this->assertCount(13, $input);
        $this->assertCount(6, $actual[TransferRequest::STATE_PENDING]);
        $this->assertCount(2, $actual[TransferRequest::STATE_FAILED]);
        $this->assertEqualsCanonicalizing(
            [TransferRequest::STATE_PENDING, TransferRequest::STATE_FAILED],
            array_keys($actual),
        );
        foreach ($actual as $status => $transfers) {
            foreach ($transfers as $transfer) {
                $this->assertEquals($transfer->getStatus(), $status);
            }
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('runOrderStatesProvider')]
    public function testRunOrder(TransferOrder $input, int $expectedTransfers): void
    {
        /**
         * Check state changes applied
         * Check call made to execute the transfer via the TransferService
         */

        $this->transferServiceMock
            ->expects($this->exactly($expectedTransfers))
            ->method('makeWalletTransfer')
            ->willReturn(new Transaction());

        $this->service->runOrder($input);
        $this->assertEquals(AbstractOrder::STATE_COMPLETED, $input->getStatus());
        foreach ($input->getTransfers() as $transfer) {
            $this->assertEquals(
                TransferRequest::STATE_COMPLETE,
                $transfer->getStatus(),
            );
        }
    }

    public static function runOrderStatesProvider(): \Generator
    {
        $approvedToComplete = new TransferOrder();
        $inProgressToComplete = new TransferOrder();
        $emptyComplete = new TransferOrder();

        $approvedToComplete->setStatus(AbstractOrder::STATE_APPROVED);
        $inProgressToComplete->setStatus(AbstractOrder::STATE_IN_PROGRESS);
        $emptyComplete->setStatus(AbstractOrder::STATE_APPROVED);

        foreach ([
            $approvedToComplete,
            $inProgressToComplete,
            $emptyComplete,
        ] as $order) {
            $order->setScheduledFor(new \DateTime());
        }

        $requests = [];
        for ($i = 0; $i < 8; $i++) {
            $request = new TransferRequest();
            $request->setDebitWalletId('testDebitWalletId124');
            $request->setCreditWalletId('testCreditWalletId4816');
            $request->setDescription('Test transfer request');
            $request->setAmount('0.01');
            $requests[] = $request;
        }
        // 2 transfers of 2 to make
        $approvedToComplete->addTransfer($requests[0]);
        $approvedToComplete->addTransfer($requests[1]);

        // 3 transfers of 5 to make
        $requests[2]->setStatus(TransferRequest::STATE_COMPLETE);
        $requests[5]->setStatus(TransferRequest::STATE_COMPLETE);
        $inProgressToComplete->addTransfer($requests[2]);
        $inProgressToComplete->addTransfer($requests[3]);
        $inProgressToComplete->addTransfer($requests[4]);
        $inProgressToComplete->addTransfer($requests[5]);

        // 1 transfer of 2 to make
        $requests[7]->setAmount('0');
        $emptyComplete->addTransfer($requests[6]);
        $emptyComplete->addTransfer($requests[7]);

        yield 'approved to complete' => [$approvedToComplete, 2];
        yield 'in progress to complete' => [$inProgressToComplete, 2];
        yield 'approved to complete no transfer' => [$emptyComplete, 1];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('runOrderForceStatesProvider')]
    public function testRunOrderForce(bool $allowed, string $status): void
    {
        // Check can skip the "run" state transition if necessary
        $transferOrder = new TransferOrder();
        $transferOrder->setScheduledFor(new \DateTime());
        $transferOrder->setStatus($status);
        if (!$allowed) {
            $this->expectException(NotEnabledTransitionException::class);
        }
        $this->service->runOrder($transferOrder, forceComplete: true);
        $this->assertEquals(
            AbstractOrder::STATE_COMPLETED,
            $transferOrder->getStatus(),
        );
    }

    public static function runOrderForceStatesProvider(): \Generator
    {
        yield 'approved to complete' => [true, AbstractOrder::STATE_APPROVED];
        yield 'in progress to complete' => [true, AbstractOrder::STATE_IN_PROGRESS];
        yield 'abandoned to complete' => [true, AbstractOrder::STATE_ABANDONED];

        yield 'draft not allowed' => [false, AbstractOrder::STATE_DRAFT];
        yield 'closed not allowed' => [false, AbstractOrder::STATE_CLOSED];
        yield 'completed not allowed' => [false, AbstractOrder::STATE_COMPLETED];
    }

    public function testCreateOrderFromExisting(): void
    {
        $description = 'Test create new order from existing';
        $transferType = TransferType::PaymentAllocation;

        /** @var Asset $asset */
        $asset = EntityIdTestUtil::setEntityId(new Asset(), 74);

        $existingTransferOrder = new TransferOrder();
        $existingTransferOrder->setTransferType($transferType);
        $existingTransferOrder->setAsset($asset);
        $existingTransferOrder->setScheduledFor(
            new \DateTimeImmutable('first day of -4 month'),
        );
        $existingTransferOrder->setDescription($description);
        $existingTransferOrder->setStatus(AbstractOrder::STATE_ABANDONED);
        $existingTransferOrder->setApprovedBy(new User());

        $requests = [
            [
                'status' => TransferRequest::STATE_COMPLETE,
                'transaction' => new Transaction(),
                'debit' => 'MysteryOriginWallet',
                'credit' => 'EnigmaticDestinationWallet',
                'amount' => '327.82',
                'description' => 'Mystery to enigmatic',
            ],
            [
                'status' => TransferRequest::STATE_PENDING,
                'transaction' => null,
                'debit' => 'NebulousOriginWallet',
                'credit' => 'FoggyDestinationWallet',
                'amount' => '57.82',
                'description' => 'Nebulous to foggy',
            ],
        ];
        foreach ($requests as $template) {
            $request = new TransferRequest();
            $request->setStatus($template['status']);
            $request->setTransaction($template['transaction']);
            $request->setDebitWalletId($template['debit']);
            $request->setCreditWalletId($template['credit']);
            $request->setAmount($template['amount']);
            $request->setDescription($template['description']);
            $existingTransferOrder->addTransfer($request);
        }

        $actual = $this->service->createOrderFromExisting($existingTransferOrder);

        $this->assertEquals($transferType, $actual->getTransferType());
        $this->assertEquals($asset, $actual->getAsset());
        $this->assertEquals($description, $actual->getDescription());
        $this->assertEquals(AbstractOrder::STATE_DRAFT, $actual->getStatus());
        $this->assertEquals(
            new \DateTimeImmutable('first day of this month')->format('Y-m-d'),
            $actual->getScheduledFor()->format('Y-m-d'),
        );
        foreach ($actual->getTransfers() as $index => $transfer) {
            $this->assertEquals(TransferRequest::STATE_PENDING, $transfer->getStatus());
            $this->assertNull($transfer->getTransaction());
            $this->assertEquals(
                $requests[$index]['debit'],
                $transfer->getDebitWalletId(),
            );
            $this->assertEquals(
                $requests[$index]['credit'],
                $transfer->getCreditWalletId(),
            );
            $this->assertEquals($requests[$index]['amount'], $transfer->getAmount());
            $this->assertEquals(
                $requests[$index]['description'],
                $transfer->getDescription(),
            );
            $this->assertEquals($actual, $transfer->getTransferOrder());
        }
    }

    public function testCopyRequestsFromExisting(): void
    {
        $description = 'Just copy the transfers';

        /** @var Asset $asset */
        $asset = EntityIdTestUtil::setEntityId(new Asset(), 74);

        $existingTransferOrder = new TransferOrder();
        $existingTransferOrder->setAsset($asset);
        $existingTransferOrder->setScheduledFor(
            new \DateTimeImmutable('first day of -4 month'),
        );
        $existingTransferOrder->setDescription('Some other transfer order');
        $existingTransferOrder->setStatus(AbstractOrder::STATE_ABANDONED);
        $existingTransferOrder->setApprovedBy(new User());

        $requests = [
            [
                'status' => TransferRequest::STATE_COMPLETE,
                'transaction' => new Transaction(),
                'debit' => 'MysteryOriginWallet',
                'credit' => 'EnigmaticDestinationWallet',
                'amount' => '327.82',
                'description' => 'Mystery to enigmatic',
            ],
            [
                'status' => TransferRequest::STATE_PENDING,
                'transaction' => null,
                'debit' => 'NebulousOriginWallet',
                'credit' => 'FoggyDestinationWallet',
                'amount' => '57.82',
                'description' => 'Nebulous to foggy',
            ],
        ];
        foreach ($requests as $template) {
            $request = new TransferRequest();
            $request->setStatus($template['status']);
            $request->setTransaction($template['transaction']);
            $request->setDebitWalletId($template['debit']);
            $request->setCreditWalletId($template['credit']);
            $request->setAmount($template['amount']);
            $request->setDescription($template['description']);
            $existingTransferOrder->addTransfer($request);
        }

        $currentTransferOrder = new TransferOrder();
        $currentTransferOrder->setDescription($description);
        $currentTransferOrder->setScheduledFor(
            new \DateTimeImmutable('first day of -2 month'),
        );

        $actual = $this->service->copyRequestsFromExisting(
            $currentTransferOrder,
            $existingTransferOrder,
        );

        $this->assertEquals($currentTransferOrder->getAsset(), $actual->getAsset());
        $this->assertEquals(
            $currentTransferOrder->getDescription(),
            $actual->getDescription(),
        );
        $this->assertEquals($currentTransferOrder->getStatus(), $actual->getStatus());
        $this->assertEquals(
            $currentTransferOrder->getScheduledFor()->format('Y-m-d'),
            $actual->getScheduledFor()->format('Y-m-d'),
        );
        foreach ($actual->getTransfers() as $index => $transfer) {
            $this->assertEquals(TransferRequest::STATE_PENDING, $transfer->getStatus());
            $this->assertNull($transfer->getTransaction());
            $this->assertEquals(
                $requests[$index]['debit'],
                $transfer->getDebitWalletId(),
            );
            $this->assertEquals(
                $requests[$index]['credit'],
                $transfer->getCreditWalletId(),
            );
            $this->assertEquals($requests[$index]['amount'], $transfer->getAmount());
            $this->assertEquals(
                $requests[$index]['description'],
                $transfer->getDescription(),
            );
            $this->assertEquals($actual, $transfer->getTransferOrder());
        }

        // Check that resetAmount option only changes the amount to zero, everything else behaves as before
        $currentTransferOrder = new TransferOrder();
        $currentTransferOrder->setDescription($description);
        $currentTransferOrder->setScheduledFor(
            new \DateTimeImmutable('first day of -2 month'),
        );
        $actual = $this->service->copyRequestsFromExisting(
            $currentTransferOrder,
            $existingTransferOrder,
            true,
        );
        $this->assertEquals($currentTransferOrder->getAsset(), $actual->getAsset());
        $this->assertEquals(
            $currentTransferOrder->getDescription(),
            $actual->getDescription(),
        );
        $this->assertEquals($currentTransferOrder->getStatus(), $actual->getStatus());
        $this->assertEquals(
            $currentTransferOrder->getScheduledFor()->format('Y-m-d'),
            $actual->getScheduledFor()->format('Y-m-d'),
        );
        foreach ($actual->getTransfers() as $index => $transfer) {
            $this->assertEquals(TransferRequest::STATE_PENDING, $transfer->getStatus());
            $this->assertNull($transfer->getTransaction());
            $this->assertEquals(
                $requests[$index]['debit'],
                $transfer->getDebitWalletId(),
            );
            $this->assertEquals(
                $requests[$index]['credit'],
                $transfer->getCreditWalletId(),
            );
            $this->assertEquals('0', $transfer->getAmount());
            $this->assertEquals(
                $requests[$index]['description'],
                $transfer->getDescription(),
            );
            $this->assertEquals($actual, $transfer->getTransferOrder());
        }
    }

    public function testCreateRequestFromExisting(): void
    {
        $existingTransferOrder = new TransferOrder();
        $asset = EntityIdTestUtil::setEntityId(new Asset(), 558);
        $requests = [
            [
                'status' => TransferRequest::STATE_COMPLETE,
                'transaction' => new Transaction(),
                'debit' => 'MysteryOriginWallet',
                'credit' => 'EnigmaticDestinationWallet',
                'amount' => '327.82',
                'description' => 'Mystery to enigmatic',
                'asset' => $asset,
            ],
            [
                'status' => TransferRequest::STATE_PENDING,
                'transaction' => null,
                'debit' => 'NebulousOriginWallet',
                'credit' => 'FoggyDestinationWallet',
                'amount' => '57.82',
                'description' => 'Nebulous to foggy',
                'asset' => null,
            ],
        ];
        foreach ($requests as $template) {
            $request = new TransferRequest();
            $request->setStatus($template['status']);
            $request->setTransaction($template['transaction']);
            $request->setDebitWalletId($template['debit']);
            $request->setCreditWalletId($template['credit']);
            $request->setAmount($template['amount']);
            $request->setDescription($template['description']);
            $request->setAsset($template['asset']);
            $existingTransferOrder->addTransfer($request);
        }

        foreach ($existingTransferOrder->getTransfers() as $index => $transfer) {
            $actual = $this->service->createRequestFromExisting($transfer);

            $this->assertEquals(TransferRequest::STATE_PENDING, $actual->getStatus());
            $this->assertNull($actual->getTransaction());
            $this->assertEquals(
                $requests[$index]['debit'],
                $actual->getDebitWalletId(),
            );
            $this->assertEquals(
                $requests[$index]['credit'],
                $actual->getCreditWalletId(),
            );
            $this->assertEquals($requests[$index]['amount'], $actual->getAmount());
            $this->assertEquals(
                $requests[$index]['description'],
                $actual->getDescription(),
            );
            $this->assertEquals($requests[$index]['asset'], $actual->getAsset());

            // Mandatory TransferOrder relation is not yet set
            $this->assertNull($actual->getTransferOrder());
        }

        // with resetAmount mode enabled, everything else is identical except the amount being set to 0
        foreach ($existingTransferOrder->getTransfers() as $index => $transfer) {
            $actual = $this->service->createRequestFromExisting($transfer, true);

            $this->assertEquals(TransferRequest::STATE_PENDING, $actual->getStatus());
            $this->assertNull($actual->getTransaction());
            $this->assertEquals(
                $requests[$index]['debit'],
                $actual->getDebitWalletId(),
            );
            $this->assertEquals(
                $requests[$index]['credit'],
                $actual->getCreditWalletId(),
            );
            $this->assertEquals('0', $actual->getAmount());
            $this->assertEquals(
                $requests[$index]['description'],
                $actual->getDescription(),
            );
            $this->assertEquals($requests[$index]['asset'], $actual->getAsset());

            // Mandatory TransferOrder relation is not yet set
            $this->assertNull($actual->getTransferOrder());
        }
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
        $transferOrder = new TransferOrder();
        $transferRequest = new TransferRequest();
        $transferOrder->addTransfer($transferRequest);
        $transferRequest->setDebitWalletId('testOriginalWallet');
        $transferRequest->setCreditWalletId('testDestinationWallet');
        $transferRequest->setStatus($requestStatus);
        $transferRequest->setAmount('102.58');

        $mpDebit = new \MangoPay\Money();
        $mpDebit->Currency = 'GBP';
        $mpDebit->Amount = $amount;
        $mpTransfer = new \MangoPay\Transfer();
        $mpTransfer->DebitedFunds = $mpDebit;
        $mpTransfer->DebitedWalletId = $debitWallet;
        $mpTransfer->CreditedWalletId = $creditWallet;
        $mpTransfer->Status = $transferStatus;

        $actual = $this->service->isTransferLinkable($transferRequest, $mpTransfer);
        $this->assertSame($expected, $actual);
    }

    public static function isLinkableTransferProvider(): \Generator
    {
        yield 'linkable pending' => [
            true,
            10258,
            'testOriginalWallet',
            'testDestinationWallet',
            \MangoPay\TransactionStatus::Succeeded,
            TransferRequest::STATE_PENDING,
        ];
        yield 'linkable failed before' => [
            true,
            10258,
            'testOriginalWallet',
            'testDestinationWallet',
            \MangoPay\TransactionStatus::Succeeded,
            TransferRequest::STATE_FAILED,
        ];
        yield 'unlinkable completed' => [
            false,
            10258,
            'testOriginalWallet',
            'testDestinationWallet',
            \MangoPay\TransactionStatus::Succeeded,
            TransferRequest::STATE_COMPLETE,
        ];

        yield 'unlinkable wrong amount' => [
            false,
            10259,
            'testOriginalWallet',
            'testDestinationWallet',
            \MangoPay\TransactionStatus::Succeeded,
            TransferRequest::STATE_PENDING,
        ];

        yield 'unlinkable wrong debit wallet' => [
            false,
            10258,
            'diffDestinationWallet',
            'testDestinationWallet',
            \MangoPay\TransactionStatus::Succeeded,
            TransferRequest::STATE_PENDING,
        ];

        yield 'unlinkable wrong credit wallet' => [
            false,
            10258,
            'testOriginalWallet',
            'diffDestinationWallet',
            \MangoPay\TransactionStatus::Succeeded,
            TransferRequest::STATE_PENDING,
        ];
        yield 'unlinkable not succeeded' => [
            false,
            10258,
            'testOriginalWallet',
            'testDestinationWallet',
            \MangoPay\TransactionStatus::Failed,
            TransferRequest::STATE_PENDING,
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('forceCompleteOrderProvider')]
    public function testForceCompleteOrder(
        TransferOrder $input,
        bool $truncate,
        int $expectedTransfers,
    ): void {
        $this->transferServiceMock
            ->expects($this->never())
            ->method('makeWalletTransfer');

        $actual = $this->service->forceCompleteOrder($input, $truncate);
        $this->assertEquals(AbstractOrder::STATE_COMPLETED, $input->getStatus());
        foreach ($input->getTransfers() as $transfer) {
            $this->assertEquals(
                TransferRequest::STATE_COMPLETE,
                $transfer->getStatus(),
            );
        }
        $this->assertCount($expectedTransfers, $actual->getTransfers());
    }

    public static function forceCompleteOrderProvider(): \Generator
    {
        $inProgressZero = new TransferOrder();
        $abandonedZero = new TransferOrder();
        $inProgressZero->setStatus(AbstractOrder::STATE_IN_PROGRESS);
        $abandonedZero->setStatus(AbstractOrder::STATE_ABANDONED);

        $inProgressTruncate = new TransferOrder();
        $abandonedTruncate = new TransferOrder();
        $inProgressTruncate->setStatus(AbstractOrder::STATE_IN_PROGRESS);
        $abandonedTruncate->setStatus(AbstractOrder::STATE_ABANDONED);

        foreach ([
            $inProgressZero,
            $abandonedZero,
            $inProgressTruncate,
            $abandonedTruncate,
        ] as $order) {
            $order->setScheduledFor(new \DateTime());
        }

        $requests = [];
        for ($i = 0; $i < 18; $i++) {
            $request = new TransferRequest();
            $request->setDebitWalletId('testDebitWalletId124');
            $request->setCreditWalletId('testCreditWalletId4816');
            $request->setDescription('Test transfer request');
            $request->setAmount('0.01');
            $requests[] = $request;
        }

        $requests[1]->setStatus(TransferRequest::STATE_COMPLETE);
        $requests[2]->setStatus(TransferRequest::STATE_COMPLETE);
        $inProgressZero->addTransfer($requests[0]);
        $inProgressZero->addTransfer($requests[1]); // Already completed
        $inProgressZero->addTransfer($requests[2]); // Already completed
        $inProgressZero->addTransfer($requests[3]);
        $inProgressZero->addTransfer($requests[4]);

        $requests[5]->setStatus(TransferRequest::STATE_COMPLETE);
        $abandonedZero->addTransfer($requests[5]); // Already completed
        $abandonedZero->addTransfer($requests[6]);
        $abandonedZero->addTransfer($requests[7]);
        $abandonedZero->addTransfer($requests[8]);

        $requests[9]->setStatus(TransferRequest::STATE_COMPLETE);
        $requests[12]->setStatus(TransferRequest::STATE_COMPLETE);
        $requests[13]->setStatus(TransferRequest::STATE_COMPLETE);
        $inProgressTruncate->addTransfer($requests[9]);
        $inProgressTruncate->addTransfer($requests[10]); // Already completed
        $inProgressTruncate->addTransfer($requests[11]); // Already completed
        $inProgressTruncate->addTransfer($requests[12]);
        $inProgressTruncate->addTransfer($requests[13]);

        $requests[17]->setStatus(TransferRequest::STATE_COMPLETE);
        $abandonedTruncate->addTransfer($requests[14]); // Already completed
        $abandonedTruncate->addTransfer($requests[15]);
        $abandonedTruncate->addTransfer($requests[16]);
        $abandonedTruncate->addTransfer($requests[17]);

        yield 'in progress zeroed' => [$inProgressZero, false, 5];
        yield 'abandoned zeroed' => [$abandonedZero, false, 4];
        yield 'in progress truncated' => [$inProgressTruncate, true, 3];
        yield 'abandoned truncated' => [$abandonedTruncate, true, 1];
    }
}
