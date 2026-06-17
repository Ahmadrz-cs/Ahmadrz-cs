<?php

namespace App\Tests\MessageHandler;

use App\Entity\AbstractOrder;
use App\Entity\Asset;
use App\Entity\PaymentOrder;
use App\Entity\PaymentRequest;
use App\Entity\Payout;
use App\Entity\Transaction;
use App\Entity\TransferOrder;
use App\Entity\TransferRequest;
use App\Entity\User;
use App\Message\OrderBatchRun;
use App\MessageHandler\OrderBatchRunHandler;
use App\Repository\PaymentOrderRepository;
use App\Repository\TransferOrderRepository;
use App\Repository\UserRepository;
use App\Service\AppSettingService;
use App\Service\PaymentService;
use App\Service\TransferService;
use App\Test\Util\EntityIdTestUtil;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

final class OrderBatchRunHandlerTest extends KernelTestCase
{
    private const bool PRINT_PROGRESS = false; // Use for debugging

    private OrderBatchRunHandler $service;
    private PaymentOrderRepository|MockObject $paymentOrderRepositoryMock;
    private TransferOrderRepository|MockObject $transferOrderRepositoryMock;
    private UserRepository|MockObject $userRepositoryMock;
    private PaymentService|MockObject $paymentServiceMock;
    private TransferService|MockObject $transferServiceMock;

    protected function setUp(): void
    {
        self::bootKernel();

        // Setup mock service dependencies that we'll configure in the individual tests
        // Repositories - mocking database
        $this->paymentOrderRepositoryMock = $this->createMock(PaymentOrderRepository::class);
        static::getContainer()->set(
            PaymentOrderRepository::class,
            $this->paymentOrderRepositoryMock,
        );
        $this->transferOrderRepositoryMock = $this->createMock(TransferOrderRepository::class);
        static::getContainer()->set(
            TransferOrderRepository::class,
            $this->transferOrderRepositoryMock,
        );
        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        static::getContainer()->set(UserRepository::class, $this->userRepositoryMock);

        // payment/transfer services - mocking Mangopay API
        $this->paymentServiceMock = $this->createMock(PaymentService::class);
        static::getContainer()->set(PaymentService::class, $this->paymentServiceMock);
        $this->transferServiceMock = $this->createMock(TransferService::class);
        static::getContainer()->set(TransferService::class, $this->transferServiceMock);
        // Should always return a valid test superadmin mangopay user id
        $this->paymentServiceMock
            ->expects($this->any())
            ->method('getDefaultAssetWalletUserId')
            ->willReturn('test_superadmin_manogpay_user_id');

        // We'll also set the issue limit to a custom one for our tests
        /** @var AppSettingService $appSettingService */
        $appSettingService = static::getContainer()->get(AppSettingService::class);
        $appSettingService->setup();
        $appSettingService->setMultiple(['orderIssueLimit' => '3']);

        $this->service = static::getContainer()->get(OrderBatchRunHandler::class);

        /**
         * Note that we will directly invoke the handler rather than sending a message to a bus
         * As there is not message consumer running and no first party way to consume messages individually
         * See https://github.com/zenstruck/messenger-test if this ability is required
         *
         * Testing the following behaviours for both payment and transfer orders
         * - Successive/contiguous issue limit (set to 3 for testing) - end run immediately - don't continue
         * - More to go (pending or failed) - continue
         * - Run by status - end run after all of same status are finished
         *   - E.g. all pending are done, finish, even if there are failed ones waiting for retry
         * - Entire batch failed - end run immediately - don't continue
         *   - Failed will keep retrying until there are none in a given batch that succeed
         *
         * See https://github.com/sebastianbergmann/phpunit/issues/5469
         * On willReturnOnConsecutiveCalls and throwing exceptions
         */
    }

    public function testRunPaymentsMultiBatch(): void
    {
        $asset = EntityIdTestUtil::setEntityId(new Asset(), 15);

        $asset->setMainWalletId('for_testing_only');
        $payee = EntityIdTestUtil::setEntityId(new User(), 5152);

        $admin = EntityIdTestUtil::setEntityId(new User(), 6);
        $admin->setFirstname('TestAdmin');
        $admin->setUsername('test@example.com');
        $admin->setEmail('test@example.com');

        $order = EntityIdTestUtil::setEntityId(new PaymentOrder(), 223);
        $order->setPaymentType(PaymentService::TYPE_DIVIDEND);
        $order->setAsset($asset);
        $order->setStatus(AbstractOrder::STATE_APPROVED);
        $order->setScheduledFor(new \DateTime('first day of this month'));

        for ($i = 0; $i < 10; $i++) {
            $request = new PaymentRequest();
            $request->setPayee($payee);
            $request->setAmount('0.01');
            $order->addPayment($request);
        }
        $this->assertCount(10, $order->getPayments());

        $this->paymentOrderRepositoryMock
            ->expects(self::atLeastOnce())
            ->method('find')
            ->with($order->getId(), null, null)
            ->willReturn($order);

        $this->userRepositoryMock
            // ->expects(self::exactly(3))
            ->method('find')
            ->with($admin->getId(), null, null)
            ->willReturn($admin);

        // There are 10 payments in total
        // Run 1 will end after the 2 failures due to issueLimit
        $this->paymentServiceMock
            ->expects(self::atLeastOnce())
            ->method('payDividend')
            ->willReturnOnConsecutiveCalls(
                // Run 1 - ends prematurely before batch finished
                $this->createValidPayout(), // rq0
                $this->throwException(new \Exception('payee test issue')), // rq1
                $this->throwException(new \Exception('payee test issue too')), // rq2
                $this->throwException(new \Exception('payee test issue again')), // rq3
                // Run 2.1
                $this->createValidPayout(), // rq4
                $this->createValidPayout(), // rq5
                $this->createValidPayout(), // rq6
                $this->createValidPayout(), // rq7
                $this->createValidPayout(), // rq8
                // Run 2.2 - after redispatch
                $this->createValidPayout(), // rq9
                // Run 3.1 - run failed requests
                $this->createValidPayout(), // rq1
                $this->throwException(new \Exception('credit wlt too')), // rq2
                $this->throwException(new \Exception('diff issue now')), // rq3
                // Run 3.2 - redispatch as there are 2 failed still to go - but no successes this time so won't retry again
                $this->throwException(new \Exception('credit wlt too')), // rq2
                $this->throwException(new \Exception('diff issue now')), // rq3
                // Run 4 - finally finish
                $this->createValidPayout(), // rq2
                $this->createValidPayout(), // rq3
            );

        $message = new OrderBatchRun(
            orderFqcn: PaymentOrder::class,
            orderId: $order->getId(),
            submittedByUserId: $admin->getId(),
            autoContinue: true,
            batchSize: 5,
        );

        /** @var InMemoryTransport $transport */
        $transport = $this->getContainer()->get('messenger.transport.async');

        $this->assertEmailCount(0);

        // Run 1
        $this->service->__invoke($message);
        $this->assertEmailCount(1);
        $groupedRequests = $this->groupRequestsByState($order->getPayments());
        if (self::PRINT_PROGRESS) {
            echo PHP_EOL . json_encode($groupedRequests) . PHP_EOL;
        }
        $this->assertCount(6, $groupedRequests[PaymentRequest::STATE_PENDING]);
        $this->assertCount(3, $groupedRequests[PaymentRequest::STATE_FAILED]);
        $this->assertCount(1, $groupedRequests[PaymentRequest::STATE_PAID]);
        $this->assertEquals(AbstractOrder::STATE_IN_PROGRESS, $order->getStatus());

        // Run 2.1 - check for redispatch
        $this->service->__invoke($message);
        $this->assertEmailCount(1);
        $groupedRequests = $this->groupRequestsByState($order->getPayments());
        if (self::PRINT_PROGRESS) {
            echo json_encode($groupedRequests) . PHP_EOL;
        }
        // First batch of this run will process 5 requests
        $this->assertCount(1, $groupedRequests[PaymentRequest::STATE_PENDING]);
        $this->assertCount(3, $groupedRequests[PaymentRequest::STATE_FAILED]);
        $this->assertCount(6, $groupedRequests[PaymentRequest::STATE_PAID]);
        $this->assertEquals(AbstractOrder::STATE_IN_PROGRESS, $order->getStatus());
        $this->assertCount(1, $transport->getSent());

        // Run 2.2 - check for redispatch
        // Note that the handler will need to be reinvoked to consume the redispatched message
        // In practice, the consumer will automatically invoke on receiving a new message
        // But we're simulating invocations manually here
        $this->service->__invoke($message);
        $this->assertEmailCount(2);
        $groupedRequests = $this->groupRequestsByState($order->getPayments());
        if (self::PRINT_PROGRESS) {
            echo json_encode($groupedRequests) . PHP_EOL;
        }
        // Should have finished all the pending requests
        // Despite a batch size of 4, as autContinue was enabled
        $this->assertArrayNotHasKey(PaymentRequest::STATE_PENDING, $groupedRequests);
        $this->assertCount(3, $groupedRequests[PaymentRequest::STATE_FAILED]);
        $this->assertCount(7, $groupedRequests[PaymentRequest::STATE_PAID]);
        $this->assertEquals(AbstractOrder::STATE_IN_PROGRESS, $order->getStatus());

        // Run 3.1 - run failed requests
        // One will succeed, but others will fail
        // Will redispatch for a retry
        $this->service->__invoke($message);
        $this->assertEmailCount(2);
        $groupedRequests = $this->groupRequestsByState($order->getPayments());
        if (self::PRINT_PROGRESS) {
            echo json_encode($groupedRequests) . PHP_EOL;
        }
        $this->assertArrayNotHasKey(PaymentRequest::STATE_PENDING, $groupedRequests);
        $this->assertCount(2, $groupedRequests[PaymentRequest::STATE_FAILED]);
        $this->assertCount(8, $groupedRequests[PaymentRequest::STATE_PAID]);
        $this->assertEquals(AbstractOrder::STATE_IN_PROGRESS, $order->getStatus());

        // Run 3.2 - retry of failed batch
        // No further requests succeeded, so will terminate the run and notify user
        $this->service->__invoke($message);
        $this->assertEmailCount(3);
        $groupedRequests = $this->groupRequestsByState($order->getPayments());
        if (self::PRINT_PROGRESS) {
            echo json_encode($groupedRequests) . PHP_EOL;
        }
        $this->assertArrayNotHasKey(PaymentRequest::STATE_PENDING, $groupedRequests);
        $this->assertCount(2, $groupedRequests[PaymentRequest::STATE_FAILED]);
        $this->assertCount(8, $groupedRequests[PaymentRequest::STATE_PAID]);
        $this->assertEquals(AbstractOrder::STATE_IN_PROGRESS, $order->getStatus());

        // Run 4 - Finish
        $this->service->__invoke($message);
        $this->assertEmailCount(4);
        $groupedRequests = $this->groupRequestsByState($order->getPayments());
        if (self::PRINT_PROGRESS) {
            echo json_encode($groupedRequests) . PHP_EOL;
        }
        // Everything should be paid now
        $this->assertArrayNotHasKey(PaymentRequest::STATE_PENDING, $groupedRequests);
        $this->assertArrayNotHasKey(PaymentRequest::STATE_FAILED, $groupedRequests);
        $this->assertCount(10, $groupedRequests[PaymentRequest::STATE_PAID]);
        $this->assertEquals(AbstractOrder::STATE_COMPLETED, $order->getStatus());
    }

    public function testRunTransfersMultiBatch(): void
    {
        $admin = EntityIdTestUtil::setEntityId(new User(), 6);
        $admin->setFirstname('TestAdmin');
        $admin->setUsername('test@example.com');
        $admin->setEmail('test@example.com');

        $order = EntityIdTestUtil::setEntityId(new TransferOrder(), 223);
        $order->setStatus(AbstractOrder::STATE_APPROVED);
        $order->setScheduledFor(new \DateTime('first day of this month'));

        for ($i = 0; $i < 10; $i++) {
            $request = new TransferRequest();
            $request->setDebitWalletId('test_debit_wlt');
            $request->setCreditWalletId('test_credit_wlt');
            $request->setAmount('0.01');
            $order->addTransfer($request);
        }
        $this->assertCount(10, $order->getTransfers());

        $this->transferOrderRepositoryMock
            ->expects(self::atLeastOnce())
            ->method('find')
            ->with($order->getId(), null, null)
            ->willReturn($order);

        $this->userRepositoryMock
            // ->expects(self::exactly(3))
            ->method('find')
            ->with($admin->getId(), null, null)
            ->willReturn($admin);

        // There are 10 payments in total
        // Run 1 will end after the 2 failures due to issueLimit
        $this->transferServiceMock
            ->expects(self::atLeastOnce())
            ->method('makeWalletTransfer')
            ->willReturnOnConsecutiveCalls(
                // Run 1 - ends prematurely before batch finished
                new Transaction(), // rq0
                $this->throwException(new \Exception('debit wlt issue')), // rq1
                $this->throwException(new \Exception('credit wlt too')), // rq2
                $this->throwException(new \Exception('diff issue now')), // rq3
                // Run 2.1
                new Transaction(), // rq4
                new Transaction(), // rq5
                new Transaction(), // rq6
                new Transaction(), // rq7
                new Transaction(), // rq8
                // Run 2.2 - after redispatch - will finish remainder
                new Transaction(), // rq9
                // Run 3.1 - run failed requests
                new Transaction(), // rq1
                $this->throwException(new \Exception('credit wlt too')), // rq2
                $this->throwException(new \Exception('diff issue now')), // rq3
                // Run 3.2 - redispatch as there are 2 failed still to go - but no successes this time so won't retry again
                $this->throwException(new \Exception('credit wlt too')), // rq2
                $this->throwException(new \Exception('diff issue now')), // rq3
                // Run 4 - finally finish
                new Transaction(), // rq2
                new Transaction(), // rq3
            );

        $message = new OrderBatchRun(
            orderFqcn: TransferOrder::class,
            orderId: $order->getId(),
            submittedByUserId: $admin->getId(),
            autoContinue: true,
            batchSize: 5,
        );

        /** @var InMemoryTransport $transport */
        $transport = $this->getContainer()->get('messenger.transport.async');

        $this->assertEmailCount(0);

        // Run 1
        $this->service->__invoke($message);
        $this->assertEmailCount(1);
        $groupedRequests = $this->groupRequestsByState($order->getTransfers());
        if (self::PRINT_PROGRESS) {
            echo PHP_EOL . json_encode($groupedRequests) . PHP_EOL;
        }
        $this->assertCount(6, $groupedRequests[TransferRequest::STATE_PENDING]);
        $this->assertCount(3, $groupedRequests[TransferRequest::STATE_FAILED]);
        $this->assertCount(1, $groupedRequests[TransferRequest::STATE_COMPLETE]);
        $this->assertEquals(AbstractOrder::STATE_IN_PROGRESS, $order->getStatus());

        // Run 2.1 - check for redispatch
        $this->service->__invoke($message);
        $this->assertEmailCount(1);
        $groupedRequests = $this->groupRequestsByState($order->getTransfers());
        if (self::PRINT_PROGRESS) {
            echo json_encode($groupedRequests) . PHP_EOL;
        }
        // First batch of this run will process 5 requests
        $this->assertCount(1, $groupedRequests[TransferRequest::STATE_PENDING]);
        $this->assertCount(3, $groupedRequests[TransferRequest::STATE_FAILED]);
        $this->assertCount(6, $groupedRequests[TransferRequest::STATE_COMPLETE]);
        $this->assertEquals(AbstractOrder::STATE_IN_PROGRESS, $order->getStatus());
        $this->assertCount(1, $transport->getSent());

        // Run 2.2 - check for redispatch
        // Note that the handler will need to be reinvoked to consume the redispatched message
        // In practice, the consumer will automatically invoke on receiving a new message
        // But we're simulating invocations manually here
        $this->service->__invoke($message);
        $this->assertEmailCount(2);
        $groupedRequests = $this->groupRequestsByState($order->getTransfers());
        if (self::PRINT_PROGRESS) {
            echo json_encode($groupedRequests) . PHP_EOL;
        }
        // Should have finished all the pending requests
        // Despite a batch size of 4, as autContinue was enabled
        $this->assertArrayNotHasKey(TransferRequest::STATE_PENDING, $groupedRequests);
        $this->assertCount(3, $groupedRequests[TransferRequest::STATE_FAILED]);
        $this->assertCount(7, $groupedRequests[TransferRequest::STATE_COMPLETE]);
        $this->assertEquals(AbstractOrder::STATE_IN_PROGRESS, $order->getStatus());

        // Run 3.1 - for failed
        // One will succeed, but others will fail
        // Will redispatch for a retry
        $this->service->__invoke($message);
        $this->assertEmailCount(2);
        $groupedRequests = $this->groupRequestsByState($order->getTransfers());
        if (self::PRINT_PROGRESS) {
            echo json_encode($groupedRequests) . PHP_EOL;
        }
        // Will retry after batch of 2 has single fail
        // Will give up on second retry after no successes
        $this->assertArrayNotHasKey(TransferRequest::STATE_PENDING, $groupedRequests);
        $this->assertCount(2, $groupedRequests[TransferRequest::STATE_FAILED]);
        $this->assertCount(8, $groupedRequests[TransferRequest::STATE_COMPLETE]);
        $this->assertEquals(AbstractOrder::STATE_IN_PROGRESS, $order->getStatus());

        // Run 3.2 - retry of failed batch
        // No further requests succeeded, so will terminate the run and notify user
        $this->service->__invoke($message);
        $this->assertEmailCount(3);
        $groupedRequests = $this->groupRequestsByState($order->getTransfers());
        if (self::PRINT_PROGRESS) {
            echo json_encode($groupedRequests) . PHP_EOL;
        }
        // Will retry after batch of 2 has single fail
        // Will give up on second retry after no successes
        $this->assertArrayNotHasKey(TransferRequest::STATE_PENDING, $groupedRequests);
        $this->assertCount(2, $groupedRequests[TransferRequest::STATE_FAILED]);
        $this->assertCount(8, $groupedRequests[TransferRequest::STATE_COMPLETE]);
        $this->assertEquals(AbstractOrder::STATE_IN_PROGRESS, $order->getStatus());

        // // Run 4 - for failed
        $this->service->__invoke($message);
        $this->assertEmailCount(4);
        $groupedRequests = $this->groupRequestsByState($order->getTransfers());
        if (self::PRINT_PROGRESS) {
            echo json_encode($groupedRequests) . PHP_EOL;
        }
        // Everything should be paid now
        $this->assertArrayNotHasKey(TransferRequest::STATE_PENDING, $groupedRequests);
        $this->assertArrayNotHasKey(TransferRequest::STATE_FAILED, $groupedRequests);
        $this->assertCount(10, $groupedRequests[TransferRequest::STATE_COMPLETE]);
        $this->assertEquals(AbstractOrder::STATE_COMPLETED, $order->getStatus());
    }

    /**
     * @param iterable<PaymentRequest>|iterable<TransferRequest> $requests
     * @return array<string, PaymentRequest[]|TransferRequest[]>
     */
    private function groupRequestsByState(iterable $requests): array
    {
        $groupedRequests = [];
        foreach ($requests as $rq) {
            $groupedRequests[$rq->getStatus()][] = $rq;
        }
        return $groupedRequests;
    }

    private function createValidPayout(): Payout
    {
        return new Payout()->setTransactionId(bin2hex(random_bytes(4)));
    }
}
