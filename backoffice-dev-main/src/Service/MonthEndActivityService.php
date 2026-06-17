<?php

namespace App\Service;

use App\Entity\AbstractOrder;
use App\Entity\Enum\GenericOrderType;
use App\Entity\Enum\PaymentType;
use App\Entity\Enum\TransferMode;
use App\Entity\Enum\TransferType;
use App\Entity\PaymentOrder;
use App\Entity\TransferOrder;
use App\Repository\PaymentOrderRepository;
use App\Repository\ShareTransferOrderRepository;
use App\Repository\TransferOrderRepository;
use Psr\Log\LoggerInterface;

/**
 * Helper for querying and summarising monthend activity
 * And using that summary for task trackers
 */
class MonthEndActivityService
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
        private PaymentOrderRepository $paymentOrderRepository,
        private TransferOrderRepository $transferOrderRepository,
        private ShareTransferOrderRepository $shareTransferOrderRepository,
    ) {}

    public function getMonthendActivitySummary(
        array $activityTypes,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        array $orderStatuses = [AbstractOrder::STATE_COMPLETED],
        bool $groupByMonth = false,
        array $assetIds = [],
    ): array {
        $summary = [];
        foreach ($activityTypes as $activityType) {
            $taskName = match ($activityType) {
                TransferType::IncomeDisaggregation => 'incomeDisaggregations',
                PaymentType::Dividend => 'dividends',
                TransferType::InvestmentSettlement => 'settlements',
                PaymentType::Repayment => 'repayments',
                TransferType::FeeCollection => 'feeCollections',
                PaymentType::Divestment => 'divestments',
                PaymentType::InvestmentExit => 'exits',
                GenericOrderType::ShareTransfer => 'shareTransfers',
                default => false,
            };
            if ($taskName) {
                $summary[$taskName] = $this->getMonthendActivity(
                    $activityType,
                    $start,
                    $end,
                    $orderStatuses,
                    $assetIds,
                );
                if ($groupByMonth) {
                    $summary[$taskName] = $this->groupOrdersByMonth(
                        $summary[$taskName],
                    );
                }
            }
        }
        return $summary;
    }

    public function getMonthendActivity(
        TransferType|PaymentType|GenericOrderType $activityType,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        array $orderStatuses = [AbstractOrder::STATE_COMPLETED],
        array $assetIds = [],
    ): array {
        return match (true) {
            $activityType instanceof TransferType => $this->getTransferActivity(
                $activityType,
                $start,
                $end,
                $orderStatuses,
                $assetIds,
            ),
            $activityType instanceof PaymentType => $this->getPaymentActivity(
                $activityType,
                $start,
                $end,
                $orderStatuses,
                $assetIds,
            ),
            $activityType == GenericOrderType::ShareTransfer
                => $this->getShareTransferActivity(
                $start,
                $end,
                $orderStatuses,
                $assetIds,
            ),
        };
    }

    /**
     * @param TransferOrder[]|PaymentOrder[] $orders
     */
    public function groupOrdersByMonth(array $orders): array
    {
        $groupedOrders = [];
        foreach ($orders as $order) {
            $month = $order->getScheduledFor()?->format('Y-m');
            if (empty($month)) {
                continue;
            }
            if (!array_key_exists($month, $groupedOrders)) {
                $groupedOrders[$month] = [];
            }
            $groupedOrders[$month][] = $order;
        }
        return $groupedOrders;
    }

    /**
     * @param TransferOrder[] $settlementOrders
     */
    public function separateSettlements(array $settlementOrders): array
    {
        $settlements = [
            'stampDuty' => [],
            'settlement' => [],
        ];
        foreach ($settlementOrders as $settlementOrder) {
            foreach ($settlementOrder->getTransfers() as $transfer) {
                if (
                    TransferMode::Settlement == $transfer->getMode()
                    || str_contains(
                        $transfer->getDescription(),
                        MonthEndService::DESCRIPTION_PRESETS['settlement'],
                    )
                    && !str_contains(
                        $transfer->getDescription(),
                        MonthEndService::DESCRIPTION_PRESETS['stamp duty'],
                    )
                ) {
                    $settlements['settlement'][] = $transfer;
                }
                if (
                    TransferMode::StampDuty == $transfer->getMode()
                    || str_contains(
                        $transfer->getDescription(),
                        MonthEndService::DESCRIPTION_PRESETS['stamp duty'],
                    )
                ) {
                    $settlements['stampDuty'][] = $transfer;
                }
            }
        }
        return $settlements;
    }

    private function getTransferActivity(
        TransferType $activityType,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        array $orderStatuses,
        array $assetIds = [],
    ): array {
        return $this->transferOrderRepository
            ->buildQueryWithAssociations([
                'transferType' => $activityType,
                'scheduledFor_gte' => $start,
                'scheduledFor_lt' => $end,
                'status' => $orderStatuses,
                'assetId' => $assetIds,
            ], ['scheduledFor' => 'DESC'])
            ->getResult();
    }

    private function getPaymentActivity(
        PaymentType $activityType,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        array $orderStatuses,
        array $assetIds = [],
    ): array {
        return $this->paymentOrderRepository
            ->buildQueryWithAssociations([
                'paymentType' => $activityType->value,
                'scheduledFor_gte' => $start,
                'scheduledFor_lt' => $end,
                'status' => $orderStatuses,
                'assetId' => $assetIds,
            ], ['scheduledFor' => 'DESC'])
            ->getResult();
    }

    private function getShareTransferActivity(
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        array $orderStatuses,
        array $assetIds = [],
    ): array {
        return $this->shareTransferOrderRepository
            ->buildQueryWithAssociations([
                'scheduledFor_gte' => $start,
                'scheduledFor_lt' => $end,
                'status' => $orderStatuses,
                'assetId' => $assetIds,
            ], ['scheduledFor' => 'DESC'])
            ->getResult();
    }
}
