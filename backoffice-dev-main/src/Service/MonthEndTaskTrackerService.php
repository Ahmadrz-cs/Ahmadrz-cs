<?php

namespace App\Service;

use App\Entity\AbstractOrder;
use App\Entity\Enum\OrderStatus;
use App\Entity\Enum\PaymentType;
use App\Entity\Enum\QueryGrouping;
use App\Entity\Enum\TaskStatus;
use App\Entity\Enum\TaskTrackerType;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TransferType;
use App\Entity\Lifecycle\InvestmentLifecycle;
use App\Entity\PaymentOrder;
use App\Entity\ShareTransferOrder;
use App\Entity\TaskTracker;
use App\Entity\TransferOrder;
use App\Repository\AssetRepository;
use App\Repository\InvestmentRepository;
use App\Repository\ShareTradeRepository;
use App\Repository\TradeOrderRepository;
use App\Service\Util\Helper;
use Psr\Log\LoggerInterface;

/**
 * Helper for querying and summarising monthend activity
 * And using that summary for task trackers
 */
class MonthEndTaskTrackerService
{
    public const ASSET_MONTHEND_TASKS = [
        'incomeTransfer',
        'dividends',
        'settlements',
        'repayments',
        'shareTransfers',
    ];

    public const MONTHEND_TASKS = [
        'incomeDeposits',
        'incomeDisaggregations',
        'dividends',
        'settlements',
        'repayments',
        'feeCollections',
    ];

    public function __construct(
        private LoggerInterface $logger,
        private MonthEndService $monthEndService,
        private MonthEndActivityService $monthEndActivityService,
        private ShareTradeRepository $shareTradeRepository,
        private TradeOrderRepository $tradeOrderRepository,
        private InvestmentRepository $investmentRepository,
        private AssetRepository $assetRepository,
        private DivestmentService $divestmentService,
    ) {}

    public function validateTaskTracker(TaskTracker $taskTracker): TaskTracker
    {
        // Regenerate the tracker if it is currently invalid
        if (!$this->isTaskTrackerValid($taskTracker)) {
            return $this->createMonthendTaskTracker(
                $taskTracker->getTaskTrackerType(),
                $taskTracker,
            );
        }
        return $taskTracker;
    }

    public function updateTaskStatusInTracker(
        TaskTracker $taskTracker,
        string $taskToUpdate,
        TaskStatus $updateToStatus,
    ): TaskTracker {
        $tasks = $taskTracker->getTasks();
        // special case for skipping all pending tasks
        if ('remaining' === $taskToUpdate && TaskStatus::Skipped === $updateToStatus) {
            foreach ($tasks as $taskName => $taskStatus) {
                if (in_array($taskStatus, [TaskStatus::Pending, null])) {
                    $tasks[$taskName] = TaskStatus::Skipped;
                }
            }
        }
        // normal case for applying task status update
        if (in_array($taskToUpdate, array_keys($tasks))) {
            $tasks[$taskToUpdate] = $updateToStatus;
        }
        $taskTracker->setTasks($tasks);
        return $taskTracker;
    }

    public function createMonthendTaskTracker(
        TaskTrackerType $taskTrackerType,
        ?TaskTracker $taskTracker = null,
    ): TaskTracker {
        $defaultTasks = array_fill_keys(
            $this->getDefaultTasksForTrackerType($taskTrackerType),
            TaskStatus::Pending,
        );
        $defaultMetadata = [
            'monthend' => date('Y-m'),
            'autoUpdate' => true,
            'syncedAt' => null,
        ];
        // Factory reset on an existing instance if provided
        if (!is_null($taskTracker)) {
            $taskTracker->setTaskTrackerType($taskTrackerType);
            $taskTracker->setTasks($defaultTasks);
            $taskTracker->setMetadata($defaultMetadata);
        } else {
            $taskTracker = new TaskTracker(
                $taskTrackerType,
                $defaultTasks,
                $defaultMetadata,
            );
        }
        return $taskTracker;
    }

    public function syncMonthendAssetChecklist(
        TaskTracker $taskTracker,
        ?TransferOrder $incomeTransfer,
        ?PaymentOrder $dividendPayment,
        ?TransferOrder $settlementOrder,
        ?PaymentOrder $prefunderRepayment,
        ?ShareTransferOrder $shareTransferOrder,
    ): TaskTracker {
        // If autoUpdate exists and is set to false, immediately return
        if (!($taskTracker->getMetadata()['autoUpdate'] ?? false)) {
            return $taskTracker;
        }
        // Which payment/transfer order relates to which task
        $taskMap = [
            'incomeTransfer' => $incomeTransfer,
            'dividends' => $dividendPayment,
            'settlements' => $settlementOrder,
            'repayments' => $prefunderRepayment,
            'shareTransfers' => $shareTransferOrder,
        ];
        $tasks = $taskTracker->getTasks();
        foreach ($taskMap as $task => $order) {
            // Don't take action if task is skipped or completed
            if (
                !is_null($order)
                && !in_array($tasks[$task], [
                    TaskStatus::Skipped,
                    TaskStatus::Completed,
                ])
            ) {
                // Check if the task has been started
                if (in_array($order->getStatus(), [
                    AbstractOrder::STATE_DRAFT,
                    AbstractOrder::STATE_APPROVED,
                    AbstractOrder::STATE_IN_PROGRESS,
                    OrderStatus::Draft,
                    OrderStatus::Approved,
                    OrderStatus::InProgress,
                ])) {
                    $tasks[$task] = TaskStatus::Started;
                }

                // Check if the task has been completed
                if (in_array($order->getStatus(), [
                    AbstractOrder::STATE_COMPLETED,
                    OrderStatus::Completed,
                ])) {
                    $tasks[$task] = TaskStatus::Completed;
                }
            }
        }
        $taskTracker->setTasks($tasks);
        return $taskTracker;
    }

    public function syncMonthEndTaskTracker(TaskTracker $taskTracker): TaskTracker
    {
        $tasks = $taskTracker->getTasks();
        foreach ($tasks as $taskName => $status) {
            // Don't update if already skipped or completed
            if (in_array($status, [TaskStatus::Skipped, TaskStatus::Completed])) {
                continue;
            }
            $newStatus = match ($taskName) {
                'incomeDisaggregations'
                    => $this->getStatusOfActivity(TransferType::IncomeDisaggregation),
                'dividends' => $this->getStatusOfActivity(
                    PaymentType::Dividend,
                    TaskStatus::Started,
                ),
                'settlements' => $this->getStatusOfSettlements(),
                'repayments' => $this->getStatusOfRepayments($tasks['settlements']),
                'feeCollections'
                    => $this->getStatusOfActivity(TransferType::FeeCollection),
                default => $status,
            };
            $tasks[$taskName] = $newStatus;
        }
        $taskTracker->setTasks($tasks);

        $metadata = $taskTracker->getMetadata();
        $metadata['syncedAt'] = time();
        $taskTracker->setMetadata($metadata);

        return $taskTracker;
    }

    private function getDefaultTasksForTrackerType(TaskTrackerType $taskTrackerType): array
    {
        return match ($taskTrackerType) {
            TaskTrackerType::Monthend => self::MONTHEND_TASKS,
            TaskTrackerType::AssetMonthend => self::ASSET_MONTHEND_TASKS,
            default => self::MONTHEND_TASKS,
        };
    }

    private function isTaskTrackerValid(TaskTracker $taskTracker): bool
    {
        $expectedTasks = $this->getDefaultTasksForTrackerType(
            $taskTracker->getTaskTrackerType(),
        );
        /**
         * - Have both monthend and autoupdate metadata
         * - Is for the current monthend
         * - Not missing any tasks
         * - No extra tasks
         * - Task statuses are all TaskStatus enums
         */
        if (
            !array_key_exists('monthend', $taskTracker->getMetadata())
            || !array_key_exists('autoUpdate', $taskTracker->getMetadata())
            || date('Y-m') !== ($taskTracker->getMetadata()['monthend'] ?? '')
            || !empty(array_diff($expectedTasks, array_keys($taskTracker->getTasks())))
            || !empty(array_diff(array_keys($taskTracker->getTasks()), $expectedTasks))
            || !empty(array_filter(
                $taskTracker->getTasks(),
                fn(mixed $taskStatus) => !$taskStatus instanceof TaskStatus,
            ))
        ) {
            return false;
        }
        return true;
    }

    private function getStatusOfActivity(
        TransferType|PaymentType $activityType,
        TaskStatus $statusLimit = TaskStatus::Completed,
    ): TaskStatus {
        $currentMonthend = $this->monthEndService->getMonthEndDateRangeFromDateTime(
            new \DateTime(),
        );
        $completedOrders = $this->monthEndActivityService->getMonthendActivity(
            $activityType,
            $currentMonthend['start'],
            $currentMonthend['end'],
        );
        if (!empty($completedOrders)) {
            return $statusLimit;
        }
        $incompleteOrders = $this->monthEndActivityService->getMonthendActivity(
            $activityType,
            $currentMonthend['start'],
            $currentMonthend['end'],
            AbstractOrder::STATES_INCOMPLETE,
        );
        return match (true) {
            !empty($incompleteOrders) => TaskStatus::Started,
            default => TaskStatus::Pending,
        };
    }

    private function getStatusOfSettlements(): TaskStatus
    {
        $currentMonthend = $this->monthEndService->getMonthEndDateRangeFromDateTime(
            new \DateTime(),
        );
        $settlementRange = $this->monthEndService->getMonthEndDateRangeFromDateTime(
            new \DateTime(),
            -1,
        );
        $approved = $this->investmentRepository->countInvestmentsInDateRangeByStatus(
            InvestmentLifecycle::STATE_APPROVED,
            $settlementRange['start'],
            $settlementRange['end'],
        );
        $settled = $this->investmentRepository->countInvestmentsInDateRangeByStatus(
            InvestmentLifecycle::STATE_SETTLED,
            $settlementRange['start'],
            $settlementRange['end'],
        );
        $total = $approved + $settled;

        if ($total == 0) {
            return TaskStatus::Skipped;
        }
        if ($approved > 0) {
            $allOrders = $this->monthEndActivityService->getMonthendActivity(
                TransferType::InvestmentSettlement,
                $currentMonthend['start'],
                $currentMonthend['end'],
                [AbstractOrder::STATE_COMPLETED, ...AbstractOrder::STATES_INCOMPLETE],
            );
            return match (true) {
                !empty($allOrders) => TaskStatus::Started,
                default => TaskStatus::Pending,
            };
        } else {
            // Must all be settled if total > 0, but no approved
            return TaskStatus::Completed;
        }
    }

    private function getStatusOfRepayments(TaskStatus $settlementStatus): TaskStatus
    {
        $currentMonthend = $this->monthEndService->getMonthEndDateRangeFromDateTime(
            new \DateTime(),
        );

        // Get asset shares in circulation
        $shareholdings =
            $this->shareTradeRepository->aggregateSharesInCirculation(nonZero: true);
        $shareholdings = array_combine(
            array_column($shareholdings, 'assetid'),
            $shareholdings,
        );

        // Get assets where there are prefunders to repay
        $prefunderSellOrders = $this->tradeOrderRepository
            ->buildQueryWithAssociations([
                'status' => [TradeOrderStatus::Active, TradeOrderStatus::Completed],
                'type' => TradeOrderType::Prefunding,
                'direction' => TradeDirection::Sell,
            ], ['numberOfShares' => 'ASC'])
            ->getResult();
        $repaymentSummary = $this->divestmentService->compileRepaymentProgress(
            $prefunderSellOrders,
            QueryGrouping::Asset,
        );
        $assets = Helper::convertArrayKeysAsIds($this->assetRepository->findBy([
            'id' => array_keys($repaymentSummary),
        ]));

        // Get surplus shares in circulation
        $surplus = 0;
        foreach ($assets as $assetId => $asset) {
            if (array_key_exists($assetId, $shareholdings)) {
                $surplus += max(
                    0,
                    $shareholdings[$assetId]['shares'] - $asset->getAmountOfShares(),
                );
            }
        }

        if (
            $surplus == 0
            && in_array($settlementStatus, [TaskStatus::Skipped, TaskStatus::Completed])
        ) {
            return $settlementStatus;
        }

        $allOrders = $this->monthEndActivityService->getMonthendActivity(
            PaymentType::Repayment,
            $currentMonthend['start'],
            $currentMonthend['end'],
            [AbstractOrder::STATE_COMPLETED, ...AbstractOrder::STATES_INCOMPLETE],
        );
        return match (true) {
            !empty($allOrders) => TaskStatus::Started,
            default => TaskStatus::Pending,
        };
    }
}
