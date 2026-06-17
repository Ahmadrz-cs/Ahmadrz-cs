<?php

namespace App\MessageHandler;

use App\Entity\Enum\PaymentType;
use App\Entity\Enum\TransferType;
use App\Entity\PaymentOrder;
use App\Entity\TransferOrder;
use App\Message\OrderBatchNotify;
use App\Repository\PaymentOrderRepository;
use App\Repository\TransferOrderRepository;
use App\Repository\UserRepository;
use App\Service\MonthEndEmailService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Message\RedispatchMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

#[AsMessageHandler]
class OrderBatchNotifyHandler
{
    // Could adjust the delay based on Mangopay API rate limit
    public const int AUTO_CONTINUE_DELAY = 100;

    public ?\Exception $orderRunException = null;

    public function __construct(
        private LoggerInterface $logger,
        private MessageBusInterface $bus,
        private EntityManagerInterface $em,
        private PaymentOrderRepository $paymentOrderRepository,
        private TransferOrderRepository $transferOrderRepository,
        private MonthEndEmailService $monthEndEmailService,
        private UserRepository $userRepository,
        private NotificationService $notificationService,
    ) {}

    public function __invoke(OrderBatchNotify $message): void
    {
        // Clear any exception set from previous invocations
        $this->orderRunException = null;
        $this->logger->info(
            "Sending notification batch for {$message->orderFqcn} ID {$message->orderId} and batch size {$message->batchSize}",
        );
        $order = match ($message->orderFqcn) {
            PaymentOrder::class
                => $this->paymentOrderRepository->find($message->orderId),
            TransferOrder::class
                => $this->transferOrderRepository->find($message->orderId),
            default => throw new UnrecoverableMessageHandlingException(
                "Order of type {$message->orderFqcn} cannot be batch run",
            ),
        };
        if ($order === null) {
            throw new UnrecoverableMessageHandlingException(
                "{$message->orderFqcn} ID {$message->orderId} not found.",
            );
        }

        $orderVariant = match (true) {
            $order instanceof PaymentOrder => strtolower($order->getPaymentType()),
            $order instanceof TransferOrder => $order->getTransferType()->value,
        };

        if (!$this->supportsEmailNotifications($order)) {
            throw new UnrecoverableMessageHandlingException(
                "{$orderVariant} order with ID {$message->orderId} does not support notifications",
            );
        }

        $continue = $this->sendEmailNotifications($message, $order);
        // Either continue the batch run, or notify user of its completion/termination
        if ($continue) {
            $this->logger->debug('Redispatching message for continued run');
            $this->bus->dispatch(new RedispatchMessage(
                new Envelope($message, [new DelayStamp(self::AUTO_CONTINUE_DELAY)]),
                'async',
            ));
        } else {
            $notificationContent = "Sending notifications for {$orderVariant} order with ID {$message->orderId} has finished.";
            if ($this->orderRunException) {
                $notificationContent .= " Some issues were encountered during the run, including {$this->orderRunException->getMessage()}.";
            }
            $recipient = $this->userRepository->find($message->submittedByUserId);
            $this->notificationService->notifyUserByEmail(
                $recipient,
                'CMS OrderBatchNotify job finished',
                $notificationContent,
                isUserStaff: true,
            );
        }
    }

    private function supportsEmailNotifications(PaymentOrder|TransferOrder $order): bool
    {
        if ($order instanceof PaymentOrder) {
            return match ($order->getPaymentType()) {
                PaymentType::Dividend->value,
                PaymentType::Divestment->value,
                PaymentType::InvestmentExit->value,
                    => true,
                default => false,
            };
        }
        if ($order instanceof TransferOrder) {
            return match ($order->getTransferType()) {
                TransferType::InvestmentSettlement => true,
                default => false,
            };
        }
        return false;
    }

    private function sendEmailNotifications(
        OrderBatchNotify $message,
        PaymentOrder|TransferOrder $order,
    ): bool {
        try {
            match (true) {
                $order instanceof PaymentOrder
                    => $this->monthEndEmailService->sendAllPaymentNotifications(
                    $order,
                    30,
                    $message->batchSize,
                ),
                $order instanceof TransferOrder
                    => $this->monthEndEmailService->sendAllSettlementNotifications(
                    $order,
                    30,
                    $message->batchSize,
                ),
            };
        } catch (\Exception $e) {
            $this->logger->error(
                'Issue encountered when sending notifications. ' . $e->getMessage(),
            );
            $this->orderRunException = $e;
            // Don't permit continuation if there were any issues sending
            return false;
        } finally {
            $this->em->flush();
        }
        $remainingRequests = match (true) {
            $order instanceof PaymentOrder
                => $this->monthEndEmailService->filterPaymentsPendingNotification(
                $order,
            ),
            $order instanceof TransferOrder
                => $this->monthEndEmailService->filterSettlementsPendingNotification(
                $order,
            ),
        };
        if ($message->autoContinue && $remainingRequests) {
            // Continue if configured to and there are still some to send
            return true;
        }
        return false;
    }
}
