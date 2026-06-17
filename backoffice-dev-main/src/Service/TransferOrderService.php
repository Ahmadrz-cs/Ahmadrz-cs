<?php

namespace App\Service;

use App\Entity\AbstractOrder;
use App\Entity\Enum\TradeStatus;
use App\Entity\Enum\TransferMode;
use App\Entity\ShareTradeStatusLog;
use App\Entity\TransferOrder;
use App\Entity\TransferRequest;
use App\Event\TransferOrder\TransferOrderCompletedEvent;
use App\Exception\OrderIssueLimitException;
use App\Service\AppSettingService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Service for managing state and running transfer orders
 * This does NOT do any persistence, use TransferOrderRepository for that
 */
class TransferOrderService
{
    public function __construct(
        private LoggerInterface $logger,
        private WorkflowInterface $transferOrderStateMachine,
        private WorkflowInterface $transferRequestStateMachine,
        private EventDispatcherInterface $eventDispatcher,
        private TransferService $transferService,
        private AppSettingService $appSettingService,
    ) {}

    public function transitionTransferOrder(
        TransferOrder $transferOrder,
        string $transition,
    ): void {
        $this->transferOrderStateMachine->apply($transferOrder, $transition);
    }

    public function formatTransferOrdersCallable(): \Closure
    {
        return function (TransferOrder $row): array {
            $output = [];
            $output['id'] = $row->getId();
            $output['type'] = $row->getTransferType()->value;
            $output['description'] = $row->getDescription();
            $output['assetId'] = $row->getAsset()?->getId();
            $output['assetSpv'] = $row->getAsset()?->getCompanyNumber();
            $output['assetName'] = $row->getAsset()?->getName();
            $output['status'] = $row->getStatus();
            $output['scheduledFor'] = $row->getScheduledFor()->format('Y-m-d');
            $output['totalTransfers'] = $row->getTransfers()->count();
            $output['approvedBy'] = $row->getApprovedBy();
            $output['createdBy'] = $row->getCreatedBy();
            $output['createdAt'] = $row->getCreatedAt()->format('r');
            $output['updatedBy'] = $row->getUpdatedBy();
            $output['updatedAt'] = $row->getUpdatedAt()->format('r');
            return $output;
        };
    }

    public function formatTransfersCallable(): \Closure
    {
        return function (TransferRequest $row): array {
            $output = [];
            $output['id'] = $row->getId();
            $output['investment'] = $row->getInvestment()?->getId();
            $output['shareTrade'] = $row->getShareTrade()?->getId();
            $output['transferOrderId'] = $row->getTransferOrder()->getId();
            $output['mode'] = $row->getMode()->name;
            $output['description'] = $row->getDescription();
            $output['debitWalletId'] = $row->getDebitWalletId();
            // $output['debitWalletOwner'] = $row->getDebitWalletOwner();
            $output['creditWalletId'] = $row->getCreditWalletId();
            // $output['creditWalletOwner'] = $row->getCreditWalletOwner();
            $output['status'] = $row->getStatus();
            $output['amount'] = $row->getAmount();
            $output['transactionId'] = $row->getTransaction()?->getId();
            $output['createdBy'] = $row->getCreatedBy();
            $output['createdAt'] = $row->getCreatedAt()->format('r');
            $output['updatedBy'] = $row->getUpdatedBy();
            $output['updatedAt'] = $row->getUpdatedAt()->format('r');
            return $output;
        };
    }

    /**
     * This method does not save changes to database
     * You must manually trigger flush or save
     */
    public function runRequest(
        TransferRequest $transferRequest,
        ?\MangoPay\Transfer $walletTransfer = null,
    ): void {
        $transferOrder = $transferRequest->getTransferOrder();
        $this->transferOrderStateMachine->apply(
            $transferOrder,
            AbstractOrder::TRANSITION_RUN,
        );
        if ($this->transferRequestStateMachine->can(
            $transferRequest,
            TransferRequest::TRANSITION_TRANSFER,
        )) {
            if ($walletTransfer) {
                $this->linkTransfer($transferRequest, $walletTransfer);
            } else {
                $this->executeTransfer($transferRequest);
            }
        }
        if ($this->isOrderComplete($transferOrder)) {
            $this->transferOrderStateMachine->apply(
                $transferOrder,
                AbstractOrder::TRANSITION_COMPLETE,
            );
            $this->eventDispatcher->dispatch(
                new TransferOrderCompletedEvent($transferOrder),
            );
        }
    }

    /**
     * This method does not save changes to database
     * You must manually trigger flush or save
     */
    public function runOrder(
        TransferOrder $transferOrder,
        int $timeLimit = 180,
        ?int $batchSize = null,
        bool $forceComplete = false, // used for force completing abandoned orders
    ): void {
        $timeLimit = max(6, $timeLimit);
        if ($batchSize === null || $batchSize > 10) {
            set_time_limit($timeLimit);
        }
        $timeLimit -= 5;

        // If forceComplete is enabled and the order is in a completable state, skip the run transition
        if (
            $forceComplete
            && $this->transferOrderStateMachine->can(
                $transferOrder,
                AbstractOrder::TRANSITION_COMPLETE,
            )
        ) {
            $this->logger->info('Force completing transfer order', [$transferOrder->getId()]);
        } else {
            $this->transferOrderStateMachine->apply(
                $transferOrder,
                AbstractOrder::TRANSITION_RUN,
            );
        }

        $executableRequests = $this->filterPendingRequests(
            $transferOrder->getTransfers(),
        );
        // $this->logger->debug("Executable requests " . json_encode($executableRequests));
        // Exclusively run pending requests until there are none left, then run everything else
        $requestsToRun = $executableRequests[TransferRequest::STATE_PENDING]
            ? $executableRequests[TransferRequest::STATE_PENDING]
            : array_merge(...array_values($executableRequests));
        // $this->logger->debug("Requests to run " . json_encode($requestsToRun));
        $issueLimit = (int) $this->appSettingService->get(
            'orderIssueLimit',
            (string) AbstractOrder::ISSUE_LIMIT,
        );
        $contiguousIssueCount = 0;
        $iterations = 0;
        $issues = [];
        $startTime = time();
        foreach ($requestsToRun as $transferRequest) {
            if ($batchSize !== null && $iterations >= $batchSize) {
                break;
            }
            if ($this->transferRequestStateMachine->can(
                $transferRequest,
                TransferRequest::TRANSITION_TRANSFER,
            )) {
                // https://gitlab.com/yielders2/backoffice-dev/-/issues/2334
                // Allow up to N fails in a row before exiting early - implied insufficient wallet balance
                // Should handle occasions where user has been blocked by Mangopay
                // And want to continue with rest of the order
                if ($contiguousIssueCount >= $issueLimit) {
                    throw new OrderIssueLimitException(
                        "{$contiguousIssueCount} failed transfers in a row. Ending run early. Failed transfers: "
                            . json_encode($issues),
                    );
                }
                try {
                    $this->executeTransfer($transferRequest);
                    // Reset continguous issue count on success
                    $contiguousIssueCount = 0;

                    // $this->logger->debug("Reset issue count on success");
                } catch (\Exception $th) {
                    $contiguousIssueCount += 1;
                    $issues[$transferRequest->getId()] = [
                        'message' => $th->getMessage(),
                        'code' => $th->getCode(),
                    ];
                    $this->logger->error(
                        "Error when paying request #{$transferRequest->getId()}. "
                            . json_encode($issues[$transferRequest->getId()]),
                    );
                }
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
                count($issues) . ' failed transfers: ' . json_encode($issues),
            );
        }
        if ($this->isOrderComplete($transferOrder)) {
            $this->transferOrderStateMachine->apply(
                $transferOrder,
                AbstractOrder::TRANSITION_COMPLETE,
            );
            $this->eventDispatcher->dispatch(
                new TransferOrderCompletedEvent($transferOrder),
            );
        }
    }

    public function createOrderFromExisting(TransferOrder $existingOrder): TransferOrder
    {
        /**
         * Create a new transfer order
         * Copy over some of the config from an existing transfer order
         * - description
         * - linked asset
         * Generate new transfers based on ones in an existing transfer order
         */
        $newOrder = new TransferOrder();
        $newOrder->setTransferType($existingOrder->getTransferType());
        $newOrder->setScheduledFor(new \DateTimeImmutable('first day of this month'));
        $newOrder->setDescription($existingOrder->getDescription());
        $newOrder->setAsset($existingOrder->getAsset());
        foreach ($existingOrder->getTransfers() as $request) {
            $newRequest = $this->createRequestFromExisting($request);
            $newOrder->addTransfer($newRequest);
        }
        return $newOrder;
    }

    public function copyRequestsFromExisting(
        TransferOrder $transferOrder,
        TransferOrder $existingOrder,
        bool $resetAmount = false,
    ): TransferOrder {
        foreach ($existingOrder->getTransfers() as $request) {
            $newRequest = $this->createRequestFromExisting($request, $resetAmount);
            $transferOrder->addTransfer($newRequest);
        }
        return $transferOrder;
    }

    public function createRequestFromExisting(
        TransferRequest $existingRequest,
        bool $resetAmount = false,
    ): TransferRequest {
        $newRequest = new TransferRequest();
        $newRequest->setDebitWalletId($existingRequest->getDebitWalletId());
        $newRequest->setCreditWalletId($existingRequest->getCreditWalletId());
        $newRequest->setDescription($existingRequest->getDescription());
        $newRequest->setAmount($resetAmount ? '0' : $existingRequest->getAmount());
        $newRequest->setAsset($existingRequest->getAsset());
        return $newRequest;
    }

    public function isOrderComplete(TransferOrder $transferOrder): bool
    {
        foreach ($transferOrder->getTransfers() as $transfer) {
            if (TransferRequest::STATE_COMPLETE !== $transfer->getStatus()) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return array<string, TransferRequest[]>
     */
    public function filterPendingRequests(iterable $transfers): array
    {
        $pendingTransfers = [
            TransferRequest::STATE_PENDING => [],
            TransferRequest::STATE_FAILED => [],
        ];
        /** @var TransferRequest[] $transfers */
        foreach ($transfers as $transfer) {
            if ($this->transferRequestStateMachine->can(
                $transfer,
                TransferRequest::TRANSITION_TRANSFER,
            )) {
                $pendingTransfers[$transfer->getStatus()][] = $transfer;
            }
        }
        return $pendingTransfers;
    }

    public function isTransferLinkable(
        TransferRequest $transferRequest,
        \MangoPay\Transfer $walletTransfer,
    ): bool {
        if (
            (int) round($transferRequest->getAmount() * 100)
                == $walletTransfer->DebitedFunds->Amount
            && $transferRequest->getDebitWalletId() == $walletTransfer->DebitedWalletId
            && $transferRequest->getCreditWalletId()
                == $walletTransfer->CreditedWalletId
            && $transferRequest->getStatus() != TransferRequest::STATE_COMPLETE
            && $walletTransfer->Status == \MangoPay\TransactionStatus::Succeeded
        ) {
            return true;
        }
        return false;
    }

    public function forceCompleteOrder(
        TransferOrder $transferOrder,
        bool $truncate = false,
    ): TransferOrder {
        if ($this->transferOrderStateMachine->can(
            $transferOrder,
            AbstractOrder::TRANSITION_COMPLETE,
        )) {
            $pendingRequests = array_merge(...array_values(
                $this->filterPendingRequests($transferOrder->getTransfers()),
            ));
            foreach ($pendingRequests as $request) {
                if ($truncate) {
                    $transferOrder->removeTransfer($request);
                } else {
                    $request->setAmount(0);
                }
            }
            $this->runOrder(transferOrder: $transferOrder, forceComplete: true);
        }
        return $transferOrder;
    }

    /**
     * Use the transfer service to make the transfer with the wallet provider
     */
    private function executeTransfer(TransferRequest $transferRequest): void
    {
        // Only attempt to make a transfer that is non-zero
        // Empty transfers are still marked as complete once executed
        if ((float) $transferRequest->getAmount() > 0) {
            try {
                $transaction =
                    $this->transferService->makeWalletTransfer($transferRequest);
                $transferRequest->setTransaction($transaction);
                $transferRequest->setstatusInfo(null);
            } catch (\Exception $e) {
                $this->transferRequestStateMachine->apply(
                    $transferRequest,
                    TransferRequest::TRANSITION_FAIL,
                );
                $transferRequest->setstatusInfo(substr($e->getMessage(), 0, 240));
                // Rethrow the exception after transitioning the request status
                throw $e;
            }
        }
        // For settlements transfers, also transition the share trade to settled
        $this->processTradeSettlement($transferRequest);
        $this->transferRequestStateMachine->apply(
            $transferRequest,
            TransferRequest::TRANSITION_TRANSFER,
        );
    }

    private function linkTransfer(
        TransferRequest $transferRequest,
        \MangoPay\Transfer $walletTransfer,
    ): void {
        // Only attempt to make a transfer that is non-zero
        // Empty transfers are still marked as complete once executed
        $this->logger->debug("Amount : {$transferRequest->getAmount()}");
        if ((float) $transferRequest->getAmount() > 0) {
            $this->logger->debug("Creating transaction $walletTransfer->Id");
            ;
            $transaction = $this->transferService->createTransaction(
                $transferRequest,
                $walletTransfer,
            );
            $transferRequest->setTransaction($transaction);
        }
        $transferRequest->setstatusInfo(null);
        $this->processTradeSettlement($transferRequest);
        $this->transferRequestStateMachine->apply(
            $transferRequest,
            TransferRequest::TRANSITION_TRANSFER,
        );
    }

    private function processTradeSettlement(TransferRequest $transferRequest): TransferRequest
    {
        if (
            $transferRequest->getShareTrade()
            && $transferRequest->getShareTrade()->getStatus() == TradeStatus::Unsettled
            && (
                TransferMode::Settlement == $transferRequest->getMode()
                || !str_contains(
                    $transferRequest->getDescription(),
                    MonthEndService::DESCRIPTION_PRESETS['stamp duty'],
                )
            )
        ) {
            $settledLog = new ShareTradeStatusLog(
                $transferRequest->getShareTrade(),
                TradeStatus::Settled,
            );
            $transferRequest->getShareTrade()->addStatusLog($settledLog);
        }
        return $transferRequest;
    }
}
