<?php

namespace App\MessageHandler;

use App\Entity\Enum\OrderRequestStatus;
use App\Entity\PaymentOrder;
use App\Entity\TransferOrder;
use App\Exception\OrderIssueLimitException;
use App\Message\OrderBatchRun;
use App\Repository\PaymentOrderRepository;
use App\Repository\TransferOrderRepository;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use App\Service\PaymentOrderService;
use App\Service\TransferOrderService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Message\RedispatchMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

#[AsMessageHandler]
class OrderBatchRunHandler
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
        private PaymentOrderService $paymentOrderService,
        private TransferOrderService $transferOrderService,
        private UserRepository $userRepository,
        private NotificationService $notificationService,
    ) {}

    public function __invoke(OrderBatchRun $message): void
    {
        // Clear any exception set from previous invocations
        $this->orderRunException = null;
        $this->logger->info(
            "Running batch {$message->orderFqcn} ID {$message->orderId} and batch size {$message->batchSize}",
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
        $continue = $this->runOrder($message, $order);
        // Either continue the batch run, or notify user of its completion/termination
        if ($continue) {
            $this->logger->debug('Redispatching message for continued run');
            $this->bus->dispatch(new RedispatchMessage(
                new Envelope($message, [new DelayStamp(self::AUTO_CONTINUE_DELAY)]),
                'async',
            ));
        } else {
            $orderVariant = match (true) {
                $order instanceof PaymentOrder => strtolower($order->getPaymentType()),
                $order instanceof TransferOrder => $order->getTransferType()->value,
            };
            $notificationContent = "Batched run of {$orderVariant} order with ID {$message->orderId} has finished.";
            if ($this->orderRunException) {
                $notificationContent .= " Some issues were encountered during the run, including {$this->orderRunException->getMessage()}.";
            }
            $notificationContent .= ' Review the order for more information.';
            $recipient = $this->userRepository->find($message->submittedByUserId);
            $this->notificationService->notifyUserByEmail(
                $recipient,
                'CMS OrderBatchRun job finished',
                $notificationContent,
                isUserStaff: true,
            );
        }
    }

    private function runOrder(
        OrderBatchRun $message,
        PaymentOrder|TransferOrder $order,
    ): bool {
        $startRequests = match (true) {
            $order instanceof PaymentOrder
                => $this->paymentOrderService->filterPendingRequests($order->getPayments()),
            $order instanceof TransferOrder
                => $this->transferOrderService->filterPendingRequests($order->getTransfers()),
        };
        $totalStartRequests = count(array_merge(...array_values($startRequests)));
        $startPendingRequests = count(
            $startRequests[OrderRequestStatus::Pending->value],
        );
        $startOtherRequests = $totalStartRequests - $startPendingRequests;

        try {
            match (true) {
                $order instanceof PaymentOrder => $this->paymentOrderService->runOrder(
                    $order,
                    30,
                    $message->batchSize,
                ),
                $order instanceof TransferOrder
                    => $this->transferOrderService->runOrder(
                    $order,
                    30,
                    $message->batchSize,
                ),
            };
        } catch (OrderIssueLimitException $e) {
            $this->logger->error(
                'Order run has reached issue limit. Ending run early.',
            );
            $this->orderRunException = $e;
            return false;
        } catch (\Exception $e) {
            $this->logger->error(
                'Order run encountered some non-terminal issues: ' . $e->getMessage(),
            );
            $this->orderRunException = $e;
        } finally {
            $this->em->flush();
        }

        $endRequests = match (true) {
            $order instanceof PaymentOrder
                => $this->paymentOrderService->filterPendingRequests($order->getPayments()),
            $order instanceof TransferOrder
                => $this->transferOrderService->filterPendingRequests($order->getTransfers()),
        };
        $totalEndRequests = count(array_merge(...array_values($endRequests)));
        $endPendingRequests = count($endRequests[OrderRequestStatus::Pending->value]);
        $endOtherRequests = $totalEndRequests - $endPendingRequests;
        $requestsCompleted = $totalEndRequests - $totalStartRequests;

        if ($requestsCompleted === 0) {
            // No requests completed - don't continue run
            return false;
        }

        if ($startPendingRequests > 0) {
            // On initial run to end - are there any pending left?
            if ($message->autoContinue && $endPendingRequests) {
                // Still more to go
                return true;
            }
            // Otherwise end the initial run to end
            return false;
        }

        if ($startOtherRequests > 0) {
            // On subsequent runs - are there any other requests left?
            if ($message->autoContinue && $endOtherRequests) {
                // Continue until future runs fail at "$requestsCompleted === 0" condition further up
                return true;
            }
            // Otherwise end this run
            return false;
        }
        return false;
    }
}
