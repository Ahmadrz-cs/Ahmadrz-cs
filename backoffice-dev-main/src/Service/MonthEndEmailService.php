<?php

namespace App\Service;

use App\Entity\Enum\PaymentType;
use App\Entity\Enum\TransferMode;
use App\Entity\Enum\TransferType;
use App\Entity\PaymentOrder;
use App\Entity\PaymentRequest;
use App\Entity\TransferOrder;
use App\Entity\TransferRequest;
use App\Service\MailerService;
use Psr\Log\LoggerInterface;

class MonthEndEmailService
{
    public function __construct(
        private LoggerInterface $logger,
        private MailerService $mailerService,
        private NotificationService $notificationService,
    ) {}

    public function sendAllPaymentNotifications(
        PaymentOrder $paymentOrder,
        int $timeLimit = 180,
        ?int $batchSize = null,
    ): PaymentOrder {
        $timeLimit = max(6, $timeLimit);
        if ($batchSize === null || $batchSize > 10) {
            set_time_limit($timeLimit);
        }
        $timeLimit -= 5;
        $iterations = 0;
        $startTime = time();
        $paymentsToNotify = $this->filterPaymentsPendingNotification($paymentOrder);
        foreach ($paymentsToNotify as $paymentRequest) {
            if ($batchSize !== null && $iterations >= $batchSize) {
                break;
            }
            $this->sendOnePaymentNotification($paymentRequest);
            $executionTime = time() - $startTime;
            if ($executionTime > $timeLimit) {
                throw new \Exception(
                    "Notifications took too long to send. Ending early after {$timeLimit} seconds.",
                );
            }
            $iterations += 1;
        }
        return $paymentOrder;
    }

    public function sendOnePaymentNotification(PaymentRequest $paymentRequest): PaymentRequest
    {
        // Deliberately do not support dividends or capital repayments yet
        // Add support for them in a new issue if appropriate
        // Note that this will intentionally crash, if you try any other payment type
        // Ideally, these notifications will become less mandatory once we have monthly statement emails
        $emailBlueprint = match ($paymentRequest->getPaymentOrder()->getPaymentType()) {
            // PaymentType::Divestment->value, PaymentType::InvestmentExit->value => EmailPreset::MonthendDivestment,
            PaymentType::Dividend->value => MailerService::TYPE_DIVIDEND_PAYMENT,
            PaymentType::Divestment->value,
            PaymentType::InvestmentExit->value,
                => MailerService::TYPE_DIVESTMENT_PAYMENT,
        };
        if (PaymentRequest::STATE_PAID == $paymentRequest->getStatus()) {
            $this->mailerService->sendMail(
                $paymentRequest->getPayee(),
                $emailBlueprint,
                [
                    'asset' => $paymentRequest
                        ->getPaymentOrder()
                        ->getAsset()
                        ->getName(),
                    'paymentDate' => $paymentRequest
                        ->getPaymentOrder()
                        ->getScheduledFor()
                        ->format('F Y'),
                    'paymentAmount' => $paymentRequest->getAmount(),
                    'shareholding' => $paymentRequest->getShareholding(),
                    // Back compat for older dividend email parameters
                    'user' => $paymentRequest->getPayee(),
                    'assetName' => $paymentRequest
                        ->getPaymentOrder()
                        ->getAsset()
                        ->getName(),
                    'month' => $paymentRequest
                        ->getPaymentOrder()
                        ->getScheduledFor()
                        ->format('F Y'),
                    'amount' => number_format(
                        (float) $paymentRequest->getAmount(),
                        2,
                        '.',
                        '',
                    ),
                    'numOfShares' => $paymentRequest->getShareholding(),
                ],
            );
            $paymentRequest->setPayeeNotifiedAt(new \DateTime());
        }
        return $paymentRequest;
    }

    /**
     * @return PaymentRequest[]
     */
    public function filterPaymentsPendingNotification(PaymentOrder $paymentOrder): array
    {
        $paymentsPendingNotification = [];
        foreach ($paymentOrder->getPayments() as $paymentRequest) {
            if (
                is_null($paymentRequest->getPayeeNotifiedAt())
                && PaymentRequest::STATE_PAID === $paymentRequest->getStatus()
                && $paymentRequest->getAmount() > 0
            ) {
                $paymentsPendingNotification[] = $paymentRequest;
            }
        }
        return $paymentsPendingNotification;
    }

    public function sendAllSettlementNotifications(
        TransferOrder $transferOrder,
        int $timeLimit = 180,
        ?int $batchSize = null,
    ): TransferOrder {
        $timeLimit = max(6, $timeLimit);
        if ($batchSize === null || $batchSize > 10) {
            set_time_limit($timeLimit);
        }
        $timeLimit -= 5;
        $iterations = 0;
        $startTime = time();
        $investmentsToNotify =
            $this->filterSettlementsPendingNotification($transferOrder);
        foreach ($investmentsToNotify as $transferRequest) {
            if ($batchSize !== null && $iterations >= $batchSize) {
                break;
            }
            $this->sendOneSettlementNotification($transferRequest);
            $executionTime = time() - $startTime;
            if ($executionTime > $timeLimit) {
                throw new \Exception(
                    "Notifications took too long to send. Ending early after {$timeLimit} seconds.",
                );
            }
            $iterations += 1;
        }
        return $transferOrder;
    }

    public function sendOneSettlementNotification(TransferRequest $transferRequest): TransferRequest
    {
        if (
            $transferRequest->getTransferOrder()->getTransferType()
            !== TransferType::InvestmentSettlement
        ) {
            throw new \InvalidArgumentException('Transfer must be of type: '
            . TransferType::InvestmentSettlement->value);
        }
        if (TransferRequest::STATE_COMPLETE == $transferRequest->getStatus()) {
            $shareTrade = $transferRequest->getShareTrade();
            $asset = $shareTrade->getBuyOrder()->getAsset()->getName();
            $shares = number_format($shareTrade->getNumberOfShares());
            $user = $shareTrade->getBuyOrder()->getUser();
            $content = "Your investment of {$shares} shares in {$asset} has been settled. These shares are now eligible for future dividends.";
            $this->notificationService->notifyUserByEmail(
                recipient: $user,
                subject: 'Your investment has been settled',
                content: $content,
                context: [
                    'title' => 'Investment Settled',
                ],
            );
            $transferRequest->setUserNotifiedAt(new \DateTime());
        }
        return $transferRequest;
    }

    /**
     * @return TransferRequest[]
     */
    public function filterSettlementsPendingNotification(TransferOrder $transferOrder): array
    {
        $settlementsPendingNotification = [];
        foreach ($transferOrder->getTransfers() as $transferRequest) {
            if (
                is_null($transferRequest->getUserNotifiedAt())
                && TransferRequest::STATE_COMPLETE === $transferRequest->getStatus()
                && (
                    TransferMode::Settlement == $transferRequest->getMode()
                    || str_contains(
                        $transferRequest->getDescription(),
                        MonthEndService::DESCRIPTION_PRESETS['settlement'],
                    )
                )
            ) {
                $settlementsPendingNotification[] = $transferRequest;
            }
        }
        return $settlementsPendingNotification;
    }
}
