<?php

namespace App\Service;

use App\Entity\AbstractOrder;
use App\Entity\Enum\TradeOrderType;
use App\Entity\PaymentOrder;
use App\Entity\PaymentRequest;
use App\Event\PaymentOrder\PaymentOrderCompletedEvent;
use App\Exception\OrderIssueLimitException;
use App\Repository\TradeOrderRepository;
use App\Service\AppSettingService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Supporting service for PaymentOrderController logic
 */
class PaymentOrderService
{
    public function __construct(
        private LoggerInterface $logger,
        private WorkflowInterface $paymentOrderStateMachine,
        private WorkflowInterface $paymentRequestStateMachine,
        private PaymentService $paymentService,
        private AppSettingService $appSettingService,
        private EventDispatcherInterface $eventDispatcher,
        private DivestmentService $divestmentService,
        private TradeOrderRepository $tradeOrderRepository,
    ) {}

    public function transitionPaymentOrder(
        PaymentOrder $paymentOrder,
        string $transition,
    ): void {
        $this->paymentOrderStateMachine->apply($paymentOrder, $transition);
    }

    public function formatPaymentOrders(iterable $orders): array
    {
        $formattedOrders = [];
        foreach ($orders as $order) {
            $formattedOrders[] = \call_user_func(
                $this->formatPaymentOrdersCallable(),
                $order,
            );
        }
        return $formattedOrders;
    }

    public function formatPayments(iterable $payments): array
    {
        $formattedPayments = [];
        foreach ($payments as $payment) {
            $formattedPayments[] = \call_user_func(
                $this->formatPaymentsCallable(),
                $payment,
            );
        }
        return $formattedPayments;
    }

    public function formatPaymentOrdersCallable(): \Closure
    {
        return function (PaymentOrder $row): array {
            $output = [];
            $output['id'] = $row->getId();
            $output['paymentType'] = $row->getPaymentType();
            $output['assetId'] = $row->getAsset()->getId();
            $output['assetSpv'] = $row->getAsset()->getCompanyNumber();
            $output['assetName'] = $row->getAsset()->getName();
            $output['status'] = $row->getStatus();
            $output['scheduledFor'] = $row->getScheduledFor()->format('Y-m-d');
            $output['description'] = $row->getDescription();
            $output['totalPayments'] = $row->getPayments()->count();
            $output['approvedBy'] = $row->getApprovedBy();
            $output['createdBy'] = $row->getCreatedBy();
            $output['createdAt'] = $row->getCreatedAt()->format('r');
            $output['updatedBy'] = $row->getUpdatedBy();
            $output['updatedAt'] = $row->getUpdatedAt()->format('r');
            return $output;
        };
    }

    public function formatPaymentsCallable(): \Closure
    {
        return function (PaymentRequest $row): array {
            $output = [];
            $output['id'] = $row->getId();
            $output['paymentOrderId'] = $row->getPaymentOrder()->getId();
            $output['paymentType'] = $row->getPaymentOrder()->getPaymentType();
            $output['status'] = $row->getStatus();
            $output['payeeId'] = $row->getPayee()->getId();
            $output['payeeName'] = $row->getPayee()->getFullname();
            $output['payeeUsername'] = $row->getPayee()->getUsername();
            $output['amount'] = $row->getAmount();
            $output['shareholding'] = $row->getShareholding();
            $output['payoutId'] = $row->getPayout() ? $row->getPayout()->getId() : '';
            $output['createdBy'] = $row->getCreatedBy();
            $output['createdAt'] = $row->getCreatedAt()->format('r');
            $output['updatedBy'] = $row->getUpdatedBy();
            $output['updatedAt'] = $row->getUpdatedAt()->format('r');
            return $output;
        };
    }

    /**
     * @return array<string, PaymentRequest[]>
     */
    public function filterPendingRequests(iterable $payments): array
    {
        $pendingPayments = [
            PaymentRequest::STATE_PENDING => [],
            PaymentRequest::STATE_FAILED => [],
        ];
        /** @var PaymentRequest[] $payments */
        foreach ($payments as $payment) {
            if ($this->paymentRequestStateMachine->can(
                $payment,
                PaymentRequest::TRANSITION_PAY,
            )) {
                $pendingPayments[$payment->getStatus()][] = $payment;
            }
        }
        return $pendingPayments;
    }

    public function isOrderComplete(PaymentOrder $paymentOrder): bool
    {
        foreach ($paymentOrder->getPayments() as $payment) {
            if (PaymentRequest::STATE_PAID != $payment->getStatus()) {
                return false;
            }
        }
        return true;
    }

    public function payRequest(PaymentRequest $paymentRequest): void
    {
        $paymentType = ucfirst($paymentRequest->getPaymentOrder()->getPaymentType());
        if (!$this->isSupportedPayment($paymentRequest)) {
            throw new \RuntimeException('Unsupported payment type ' . $paymentType);
        }

        $debitWalletId = $this->getDebitWalletIdForOrder(
            $paymentRequest->getPaymentOrder(),
        );

        // Do any pre processing for divestments and repayments
        // Nothing happens for dividends or if the payment shareholding is zero
        // Note that processing happens even if payment amount is zero
        // If you want a "free" transfer of shares
        $this->processTradeRecordsPrepay($paymentRequest);

        // Only make transfers for positive amounts
        if ($paymentRequest->getAmount() > 0) {
            $assetWalletUserId = $this->paymentService->getDefaultAssetWalletUserId();
            if (null === $assetWalletUserId) {
                throw new \RuntimeException(
                    'Superadmin wallet (Mangopay) user id is missing',
                );
            }
            if (PaymentService::TYPE_DIVIDEND == $paymentType) {
                try {
                    $payout = $this->paymentService->payDividend(
                        $paymentRequest->getPaymentOrder()->getAsset(),
                        $paymentRequest->getPayee(),
                        $assetWalletUserId,
                        [
                            'cashValue' => $paymentRequest->getAmount(),
                            'currentHolding' => $paymentRequest->getShareholding(),
                        ],
                        $paymentRequest->getPaymentOrder()->getScheduledFor(),
                        $debitWalletId,
                    );
                    $paymentRequest->setstatusInfo(null);
                } catch (\Exception $e) {
                    $this->paymentRequestStateMachine->apply(
                        $paymentRequest,
                        PaymentRequest::TRANSITION_FAIL,
                    );
                    $paymentRequest->setstatusInfo(substr($e->getMessage(), 0, 240));
                    // Rethrow the exception after transitioning the request status
                    throw $e;
                }
            } else {
                try {
                    $payout = $this->paymentService->payDivestment(
                        $paymentRequest->getPaymentOrder()->getAsset(),
                        $paymentRequest->getPayee(),
                        $assetWalletUserId,
                        [
                            'cashValue' => $paymentRequest->getAmount(),
                            'sharesToLiquidate' => $paymentRequest->getShareholding(),
                        ],
                        $paymentRequest->getPaymentOrder()->getScheduledFor(),
                        $paymentType,
                        $debitWalletId,
                    );
                    $paymentRequest->setstatusInfo(null);
                } catch (\Exception $e) {
                    $this->paymentRequestStateMachine->apply(
                        $paymentRequest,
                        PaymentRequest::TRANSITION_FAIL,
                    );
                    $paymentRequest->setstatusInfo($e->getMessage());
                    // Rethrow the exception after transitioning the request status
                    throw $e;
                }
            }
        }

        // Do any post processing for divestments and repayments
        // Nothing happens for dividends or if the payment shareholding is zero
        // Note that processing happens even if payment amount is zero
        // If you want a "free" transfer of shares
        $this->processTradeRecordsPostPay($paymentRequest);

        if (isset($payout) && $payout->getTransactionId()) {
            $paymentRequest->setPayout($payout);
            $this->paymentRequestStateMachine->apply(
                $paymentRequest,
                PaymentRequest::TRANSITION_PAY,
            );
        } elseif ($paymentRequest->getAmount() <= 0) {
            /**
             * Paying a request of amount 0 will change the request state to paid
             * This prevents an edge case where a payment order cannot be completed
             * due to payments of amount 0
             */
            $this->paymentRequestStateMachine->apply(
                $paymentRequest,
                PaymentRequest::TRANSITION_PAY,
            );
        }
    }

    public function linkTransfer(
        PaymentRequest $paymentRequest,
        \MangoPay\Transfer $walletTransfer,
    ): void {
        $paymentType = ucfirst($paymentRequest->getPaymentOrder()->getPaymentType());
        if (!$this->isSupportedPayment($paymentRequest)) {
            throw new \RuntimeException('Unsupported payment type ' . $paymentType);
        }

        // Do any pre processing for divestments and repayments
        // Nothing happens for dividends or if the payment shareholding is zero
        // Note that processing happens even if payment amount is zero
        // If you want a "free" transfer of shares
        $this->processTradeRecordsPrepay($paymentRequest);

        // Only make transfers for positive amounts
        if ($paymentRequest->getAmount() > 0) {
            $payout = $this->paymentService->buildPayout(
                $paymentRequest->getPaymentOrder()->getAsset(),
                $paymentRequest->getPayee(),
                $paymentRequest->getAmount(),
                $paymentRequest->getShareholding(),
                $walletTransfer->Id,
                $paymentRequest->getPaymentOrder()->getScheduledFor(),
                $paymentType,
            );
            $paymentRequest->setPayout($payout);
        }
        // Do any post processing for divestments and repayments
        // Nothing happens for dividends or if the payment shareholding is zero
        // Note that processing happens even if payment amount is zero
        // If you want a "free" transfer of shares
        $this->processTradeRecordsPostPay($paymentRequest);

        $paymentRequest->setstatusInfo(null);
        $this->paymentRequestStateMachine->apply(
            $paymentRequest,
            PaymentRequest::TRANSITION_PAY,
        );
    }

    /**
     * This method does not save changes to database
     * You must manually trigger flush or save
     */
    public function runRequest(
        PaymentRequest $paymentRequest,
        ?\MangoPay\Transfer $walletTransfer = null,
    ): void {
        $paymentOrder = $paymentRequest->getPaymentOrder();
        $this->paymentOrderStateMachine->apply(
            $paymentOrder,
            AbstractOrder::TRANSITION_RUN,
        );
        if ($this->paymentRequestStateMachine->can(
            $paymentRequest,
            PaymentRequest::TRANSITION_PAY,
        )) {
            if ($walletTransfer) {
                $this->linkTransfer($paymentRequest, $walletTransfer);
            } else {
                $this->payRequest($paymentRequest);
            }
        }
        if ($this->isOrderComplete($paymentOrder)) {
            $this->paymentOrderStateMachine->apply(
                $paymentOrder,
                AbstractOrder::TRANSITION_COMPLETE,
            );
            if ($paymentOrder->getTradeOrder()) {
                $this->divestmentService->finishBuyBackOrder(
                    $paymentOrder->getTradeOrder(),
                );
            }
        }
    }

    /**
     * This method does not save changes to database
     * You must manually trigger flush or save
     */
    public function runOrder(
        PaymentOrder $paymentOrder,
        int $timeLimit = 180,
        ?int $batchSize = null,
    ): void {
        $timeLimit = max(6, $timeLimit);
        if ($batchSize === null || $batchSize > 10) {
            set_time_limit($timeLimit);
        }
        $timeLimit -= 5;
        $this->paymentOrderStateMachine->apply($paymentOrder, 'run');
        $executableRequests = $this->filterPendingRequests(
            $paymentOrder->getPayments(),
        );
        // $this->logger->debug("Executable requests " . json_encode($executableRequests));
        // Exclusively run pending requests until there are none left, then run everything else
        $requestsToRun = $executableRequests[PaymentRequest::STATE_PENDING]
            ? $executableRequests[PaymentRequest::STATE_PENDING]
            : array_merge(...array_values($executableRequests));
        $issueLimit = (int) $this->appSettingService->get(
            'orderIssueLimit',
            (string) AbstractOrder::ISSUE_LIMIT,
        );
        $contiguousIssueCount = 0;
        $iterations = 0;
        $issues = [];
        $startTime = time();
        foreach ($requestsToRun as $paymentRequest) {
            if ($batchSize !== null && $iterations >= $batchSize) {
                break;
            }
            // https://gitlab.com/yielders2/backoffice-dev/-/issues/2334
            // Allow up to N fails in a row before exiting early - implied insufficient wallet balance
            // Should handle occasions where user has been blocked by Mangopay
            // And want to continue with rest of the order
            if ($contiguousIssueCount >= $issueLimit) {
                throw new OrderIssueLimitException(
                    "{$contiguousIssueCount} failed transfers in a row. Ending run early. Issues found: "
                        . json_encode($issues),
                );
            }
            try {
                $this->payRequest($paymentRequest);
                // Reset continguous issue count on success
                $contiguousIssueCount = 0;
            } catch (\Exception $th) {
                $contiguousIssueCount += 1;
                $issues[$paymentRequest->getId()] = [
                    'message' => $th->getMessage(),
                    'code' => $th->getCode(),
                ];
                $this->logger->error(
                    "Error when paying request #{$paymentRequest->getId()}. "
                        . json_encode($issues[$paymentRequest->getId()]),
                );
            }
            $iterations += 1;
            $executionTime = time() - $startTime;
            if ($executionTime > $timeLimit) {
                throw new \Exception(
                    "Order took too long to run. Ending run early after {$timeLimit} seconds.",
                );
            }
        }
        if (count($issues) > 0) {
            throw new \Exception(
                count($issues) . ' failed payments: ' . json_encode($issues),
            );
        }
        if ($this->isOrderComplete($paymentOrder)) {
            $this->paymentOrderStateMachine->apply(
                $paymentOrder,
                AbstractOrder::TRANSITION_COMPLETE,
            );
            if ($paymentOrder->getTradeOrder()) {
                $this->divestmentService->finishBuyBackOrder(
                    $paymentOrder->getTradeOrder(),
                );
            }
            $this->eventDispatcher->dispatch(
                new PaymentOrderCompletedEvent($paymentOrder),
            );
        }
    }

    public function getDebitWalletIdForOrder(PaymentOrder $paymentOrder): ?string
    {
        // Determine which of the asset's wallet to pay from
        // Default to the main/actual wallet like it is wth the old tools
        $asset = $paymentOrder->getAsset();
        return match ($paymentOrder->getDebitWallet()) {
            'main' => $asset->getMainWalletId(),
            'distribution' => $asset->getDistributionWalletId(),
            default => $asset->getMainWalletId(),
        };
    }

    public function isTransferLinkable(
        PaymentRequest $paymentRequest,
        \MangoPay\Transfer $walletTransfer,
    ): bool {
        $debitWalletId = $this->getDebitWalletIdForOrder(
            $paymentRequest->getPaymentOrder(),
        );
        if (
            (int) round($paymentRequest->getAmount() * 100)
                == $walletTransfer->DebitedFunds->Amount
            && $debitWalletId == $walletTransfer->DebitedWalletId
            && $paymentRequest->getPayee()->getMangoPayWalletId()
                == $walletTransfer->CreditedWalletId
            && $paymentRequest->getStatus() != PaymentRequest::STATE_PAID
            && $walletTransfer->Status == \MangoPay\TransactionStatus::Succeeded
        ) {
            return true;
        }
        return false;
    }

    private function isSupportedPayment(PaymentRequest $paymentRequest): bool
    {
        $paymentType = ucfirst($paymentRequest->getPaymentOrder()->getPaymentType());
        return in_array($paymentType, [
            PaymentService::TYPE_DIVIDEND,
            PaymentService::TYPE_DIVESTMENT,
            PaymentService::TYPE_INVESTMENT_EXIT,
            PaymentService::TYPE_LIQUIDATION,
            PaymentService::TYPE_REPAYMENT,
        ]);
    }

    /**
     * Create the buyback or proxy buyOrder for divestment and repayment orders respectively
     * Only creates if the buy back order has not already been created
     * @param PaymentRequest $paymentRequest
     * @throws \RuntimeException
     * @return PaymentRequest
     */
    private function processTradeRecordsPrepay(PaymentRequest $paymentRequest): PaymentRequest
    {
        // No shares changing hands in this request, so don't need to do anything
        if ($paymentRequest->getShareholding() <= 0) {
            return $paymentRequest;
        }
        if (
            in_array($paymentRequest->getPaymentOrder()->getPaymentType(), [
                PaymentService::TYPE_DIVESTMENT,
                PaymentService::TYPE_INVESTMENT_EXIT,
                PaymentService::TYPE_LIQUIDATION,
                PaymentService::TYPE_REPAYMENT,
            ])
            && $paymentRequest->getPaymentOrder()->getTradeOrder() === null
        ) {
            $initialOrders = $this->tradeOrderRepository->findInitialSellOrders(
                $paymentRequest->getPaymentOrder()->getAsset(),
            );
            if (count($initialOrders) <= 0) {
                throw new \RuntimeException(
                    'Unable to find initial sell order to determine original asset issuer',
                );
            }
            $buyBackType =
                match ($paymentRequest->getPaymentOrder()->getPaymentType()) {
                    PaymentService::TYPE_REPAYMENT => TradeOrderType::Proxy,
                    default => TradeOrderType::BuyBack,
                };
            $buybackOrder = $this->divestmentService->createBuyBackOrder(
                $paymentRequest->getPaymentOrder(),
                $initialOrders[0],
                $buyBackType,
            );

            if (
                $buybackOrder === null
                || $paymentRequest->getPaymentOrder()->getTradeOrder() === null
            ) {
                throw new \RuntimeException('Unable to create buyback order');
            }
        }
        return $paymentRequest;
    }

    /**
     * Create relevant share trade system records after payment has been made for divestments and repayments
     * - Divestments get a buyBack sellOrder AND a ShareTrade
     * - Repayments just get a ShareTrade, as the sellOrder should already exist
     * @param PaymentRequest $paymentRequest
     * @return PaymentRequest
     */
    private function processTradeRecordsPostPay(PaymentRequest $paymentRequest): PaymentRequest
    {
        // No shares changing hands in this request, so don't need to do anything
        if ($paymentRequest->getShareholding() <= 0) {
            return $paymentRequest;
        }
        if (in_array($paymentRequest->getPaymentOrder()->getPaymentType(), [
            PaymentService::TYPE_DIVESTMENT,
            PaymentService::TYPE_INVESTMENT_EXIT,
            PaymentService::TYPE_LIQUIDATION,
        ])) {
            $divestmentSellOrder =
                $this->divestmentService->createDivestmentOrder($paymentRequest);
            $this->divestmentService->createBuyBackTrade(
                $paymentRequest,
                $divestmentSellOrder,
            );
        }
        if (in_array($paymentRequest->getPaymentOrder()->getPaymentType(), [
            PaymentService::TYPE_REPAYMENT,
        ])) {
            $this->divestmentService->createBuyBackTrade(
                $paymentRequest,
                $paymentRequest->getTradeOrder(),
            );
            $this->divestmentService->checkTradeOrderProgression(
                $paymentRequest->getTradeOrder(),
            );
        }
        return $paymentRequest;
    }
}
