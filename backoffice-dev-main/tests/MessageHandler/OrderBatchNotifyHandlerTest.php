<?php

namespace App\Tests\MessageHandler;

use App\Entity\Enum\PaymentType;
use App\Entity\Enum\TransferType;
use App\Entity\PaymentOrder;
use App\Entity\TransferOrder;
use App\Entity\User;
use App\Message\AbstractOrderBatchJob;
use App\Message\OrderBatchNotify;
use App\MessageHandler\OrderBatchNotifyHandler;
use App\Repository\PaymentOrderRepository;
use App\Repository\TransferOrderRepository;
use App\Repository\UserRepository;
use App\Service\AppSettingService;
use App\Service\MonthEndEmailService;
use App\Test\Util\EntityIdTestUtil;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

final class OrderBatchNotifyHandlerTest extends KernelTestCase
{
    private OrderBatchNotifyHandler $service;
    private PaymentOrderRepository|MockObject $paymentOrderRepositoryMock;
    private TransferOrderRepository|MockObject $transferOrderRepositoryMock;
    private UserRepository|MockObject $userRepositoryMock;
    private MonthEndEmailService|MockObject $monthEndEmailServiceMock;

    protected function setUp(): void
    {
        self::bootKernel();

        // Setup mock service dependencies that we'll configure in the individual tests
        // Repositories - mocking database
        $this->paymentOrderRepositoryMock = $this->createMock(PaymentOrderRepository::class);
        $this->transferOrderRepositoryMock = $this->createMock(TransferOrderRepository::class);
        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        $this->monthEndEmailServiceMock = $this->createMock(MonthEndEmailService::class);
        static::getContainer()->set(
            PaymentOrderRepository::class,
            $this->paymentOrderRepositoryMock,
        );
        static::getContainer()->set(
            TransferOrderRepository::class,
            $this->transferOrderRepositoryMock,
        );
        static::getContainer()->set(UserRepository::class, $this->userRepositoryMock);
        static::getContainer()->set(
            MonthEndEmailService::class,
            $this->monthEndEmailServiceMock,
        );

        // We'll also set the issue limit to a custom one for our tests
        /** @var AppSettingService $appSettingService */
        $appSettingService = static::getContainer()->get(AppSettingService::class);
        $appSettingService->setup();
        $appSettingService->setMultiple(['orderIssueLimit' => '3']);

        $this->service = static::getContainer()->get(OrderBatchNotifyHandler::class);

        /**
         * Note that we will directly invoke the handler rather than sending a message to a bus
         * As there is not message consumer running and no first party way to consume messages individually
         * See https://github.com/zenstruck/messenger-test if this ability is required
         *
         * Testing the following behaviours for both payment and transfer orders
         * - Only specific order types supported
         * - Calls to the relevant MonthEndEmailService method is being called
         * - Optionally check emails being sent out, but that's more the remit of MonthEndEmailServiceTest
         */
    }

    public function testOrderNotFound(): void
    {
        $this->expectException(UnrecoverableMessageHandlingException::class);
        $this->expectExceptionMessage('not found');

        $admin = EntityIdTestUtil::setEntityId(new User(), 6);
        $admin->setFirstname('TestAdmin');
        $admin->setUsername('test@example.com');
        $admin->setEmail('test@example.com');

        $this->userRepositoryMock
            ->method('find')
            ->with($admin->getId(), null, null)
            ->willReturn($admin);

        $message = new OrderBatchNotify(
            orderFqcn: PaymentOrder::class,
            orderId: 14,
            submittedByUserId: $admin->getId(),
            autoContinue: true,
            batchSize: 5,
        );
        $this->service->__invoke($message);
    }

    public static function supportedOrderTypesProvider(): \Generator
    {
        $supportedPayments = [
            PaymentType::Dividend,
            PaymentType::Divestment,
            PaymentType::InvestmentExit,
        ];
        $supportedTransfers = [
            TransferType::InvestmentSettlement,
        ];
        foreach (PaymentType::cases() as $pt) {
            $order = EntityIdTestUtil::setEntityId(new PaymentOrder(), 223);
            $order->setPaymentType($pt->value);
            yield $pt->value => [$order, in_array($pt, $supportedPayments)];
        }
        foreach (TransferType::cases() as $tt) {
            $order = EntityIdTestUtil::setEntityId(new TransferOrder(), 223);
            $order->setTransferType($tt);
            yield $tt->value => [$order, in_array($tt, $supportedTransfers)];
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('supportedOrderTypesProvider')]
    public function testSupportedOrderTypes(
        PaymentOrder|TransferOrder $order,
        bool $allowed,
    ): void {
        $admin = EntityIdTestUtil::setEntityId(new User(), 6);
        $admin->setFirstname('TestAdmin');
        $admin->setUsername('test@example.com');
        $admin->setEmail('test@example.com');

        $this->userRepositoryMock
            ->method('find')
            ->with($admin->getId(), null, null)
            ->willReturn($admin);

        if ($order instanceof PaymentOrder) {
            $this->paymentOrderRepositoryMock
                ->expects(self::atLeastOnce())
                ->method('find')
                ->with($order->getId(), null, null)
                ->willReturn($order);
            $methodName = 'sendAllPaymentNotifications';
        }
        if ($order instanceof TransferOrder) {
            $this->transferOrderRepositoryMock
                ->expects(self::atLeastOnce())
                ->method('find')
                ->with($order->getId(), null, null)
                ->willReturn($order);
            $methodName = 'sendAllSettlementNotifications';
        }

        if ($allowed) {
            $this->monthEndEmailServiceMock
                ->expects(self::once())
                ->method($methodName)
                ->with($order, 30, AbstractOrderBatchJob::BATCH_LIMIT);
        } else {
            $this->expectException(UnrecoverableMessageHandlingException::class);
            $this->expectExceptionMessage('does not support notifications');
        }

        $message = new OrderBatchNotify(
            orderFqcn: get_class($order),
            orderId: $order->getId(),
            submittedByUserId: $admin->getId(),
        );

        $this->assertEmailCount(0);

        $this->service->__invoke($message);

        // Only allowed order types from here on/success
        // Not allowed will have ended with an exception
        $this->assertEmailCount(1);
        $this->assertEmailSubjectContains($this->getMailerMessage(), 'job finished');
    }
}
