<?php

namespace App\Tests\Service;

use App\Entity\AbstractOrder;
use App\Entity\Enum\GenericOrderType;
use App\Entity\Enum\OrderStatus;
use App\Entity\Enum\PaymentType;
use App\Entity\Enum\TransferType;
use App\Entity\PaymentOrder;
use App\Entity\ShareTransferOrder;
use App\Entity\TransferOrder;
use App\Entity\TransferRequest;
use App\Service\MonthEndActivityService;
use App\Service\MonthEndService;
use App\Test\FixtureTestCase;

final class MonthEndActivityServiceTest extends FixtureTestCase
{
    private MonthEndActivityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(MonthEndActivityService::class);
    }

    public function testGetMonthendActivitySummary(): void
    {
        $dateStart = new \DateTime('first day of -2 month')->setTime(0, 0);
        $dateEnd = new \DateTime()->setTime(0, 0);
        $types = [
            TransferType::IncomeDisaggregation,
            PaymentType::Dividend,
            TransferType::InvestmentSettlement,
            PaymentType::Repayment,
            TransferType::FeeCollection,
            PaymentType::Divestment,
            PaymentType::InvestmentExit,
            GenericOrderType::ShareTransfer,
            'somethingelse',
        ];
        $expected = [
            'incomeDisaggregations',
            'dividends',
            'settlements',
            'repayments',
            'feeCollections',
            'divestments',
            'exits',
            'shareTransfers',
        ];
        $actual = $this->service->getMonthendActivitySummary(
            $types,
            $dateStart,
            $dateEnd,
        );
        $this->assertNotEmpty($actual);
        $this->assertEquals($expected, array_keys($actual));

        // if grouping enabled, then there should be date keys under each activity type
        // Use dividend as test fixtures ensure there are completed orders every month in previous 12 months
        $actual = $this->service->getMonthendActivitySummary(
            [PaymentType::Dividend],
            $dateStart,
            $dateEnd,
            [AbstractOrder::STATE_COMPLETED],
            true,
        );
        $maximumExpected = [
            $dateEnd->format('Y-m'),
            new \DateTime('-1 month')->format('Y-m'),
            $dateStart->format('Y-m'),
        ];
        // Actual summary may or may not contain the current month
        // Depends on whether there has been any activity so far in the month (usually not at start of month)
        $this->assertEmpty(array_diff(array_keys(
            $actual['dividends'],
            $maximumExpected,
        )));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('activityTypeProvider')]
    public function testGetMonthendActivity(TransferType|PaymentType|GenericOrderType $activityType): void
    {
        $dateStart = new \DateTime('-66 days')->setTime(0, 0);
        $dateEnd = new \DateTime('+15 days')->setTime(0, 0);
        $actual = $this->service->getMonthendActivity(
            $activityType,
            $dateStart,
            $dateEnd,
        );
        $this->assertNotEmpty($actual);

        /** @var TransferOrder[]|PaymentOrder[]|ShareTransferOrder[] $actual */
        foreach ($actual as $order) {
            if ($activityType instanceof TransferType) {
                $this->assertSame($activityType, $order->getTransferType());
            }
            if ($activityType instanceof PaymentType) {
                // Note that the payment order type is a string, not an enum
                $this->assertSame($activityType->value, $order->getPaymentType());
            }
            if ($activityType == GenericOrderType::ShareTransfer) {
                $this->assertInstanceOf(ShareTransferOrder::class, $order);
                $this->assertSame(OrderStatus::Completed, $order->getStatus());
            } else {
                $this->assertSame(AbstractOrder::STATE_COMPLETED, $order->getStatus());
            }
            $this->assertGreaterThanOrEqual($dateStart, $order->getScheduledFor());
            $this->assertLessThan($dateEnd, $order->getScheduledFor());
        }
    }

    public static function activityTypeProvider(): \Generator
    {
        yield 'Income Disaggregations' => [TransferType::IncomeDisaggregation];
        yield 'Settlements' => [TransferType::InvestmentSettlement];
        yield 'Fee Collection' => [TransferType::FeeCollection];
        yield 'Dividends' => [PaymentType::Dividend];
        yield 'Repayment' => [PaymentType::Repayment];
        yield 'ShareTransfer' => [GenericOrderType::ShareTransfer];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('activityStatusesProvider')]
    public function testGetMonthendActivityStatusFilter(array $statuses): void
    {
        $dateStart = new \DateTime('-15 months')->setTime(0, 0);
        $dateEnd = new \DateTime()->setTime(0, 0);
        $actual = $this->service->getMonthendActivity(
            PaymentType::Dividend,
            $dateStart,
            $dateEnd,
            $statuses,
        );
        $this->assertNotEmpty($actual);

        /** @var TransferOrder[]|PaymentOrder[] $actual */
        foreach ($actual as $order) {
            $this->assertContains($order->getStatus(), $statuses);
        }
    }

    public static function activityStatusesProvider(): \Generator
    {
        yield 'Single non-default status' => [[AbstractOrder::STATE_COMPLETED]];
        yield 'Multiple statuses' => [
            [
                AbstractOrder::STATE_DRAFT,
                AbstractOrder::STATE_APPROVED,
                AbstractOrder::STATE_IN_PROGRESS,
                AbstractOrder::STATE_CLOSED,
            ],
        ];
    }

    public function testGroupOrdersByMonth(): void
    {
        $dates = [
            new \DateTime('2020-02-27'),
            new \DateTime('2020-10-12'),
            new \DateTime('2020-10-24'),
            new \DateTime('2020-09-01'),
            new \DateTime('2020-06-08'),
        ];
        $orders = [];
        foreach ($dates as $scheduledForDate) {
            $transferOrder = new TransferOrder();
            $transferOrder->setScheduledFor($scheduledForDate);
            $transferOrder->setDescription('Test grouping of orders by month');
            $transferOrder->setTransferType(TransferType::Custom);
            $orders[] = $transferOrder;
        }
        $expectedKeys = [
            '2020-02',
            '2020-10',
            '2020-09',
            '2020-06',
        ];
        $actual = $this->service->groupOrdersByMonth($orders);
        $this->assertCount(4, $actual);
        // Should process in original order, without any reordering
        $this->assertSame($expectedKeys, array_keys($actual));
        $this->assertSame($orders[0], $actual['2020-02'][0]);
        $this->assertSame($orders[1], $actual['2020-10'][0]);
        $this->assertSame($orders[2], $actual['2020-10'][1]);
        $this->assertSame($orders[3], $actual['2020-09'][0]);
        $this->assertSame($orders[4], $actual['2020-06'][0]);
    }

    public function testSeparateSettlements(): void
    {
        $transferOrder = new TransferOrder();
        $transferOrder->setScheduledFor(new \DateTime());
        $transferOrder->setDescription('Test separating settlements and stamp duty');
        $transferOrder->setTransferType(TransferType::InvestmentSettlement);

        $settlements = [];
        $stampDuties = [];
        // Popular the setlement order with settlement transfers - distinguished by the description
        foreach (range(0, 4) as $i) {
            $settlement = new TransferRequest();
            $settlement->setDescription(
                MonthEndService::DESCRIPTION_PRESETS['settlement'],
            );
            if (($i % 2) === 0) {
                $settlement->setDescription(
                    MonthEndService::DESCRIPTION_PRESETS['settlement'] . ' abc',
                );
            }
            $transferOrder->addTransfer($settlement);
            $settlements[] = $settlement;
        }
        // Popular the setlement order with stamp duty transfers - distinguished by the description
        foreach (range(0, 2) as $i) {
            $duty = new TransferRequest();
            $duty->setDescription(MonthEndService::DESCRIPTION_PRESETS['stamp duty']);
            if (($i % 2) === 0) {
                $duty->setDescription(
                    MonthEndService::DESCRIPTION_PRESETS['stamp duty'] . ' 123',
                );
            }
            $transferOrder->addTransfer($duty);
            $stampDuties[] = $duty;
        }
        // Add an additional transfer, that should be ignored and left out of the result
        $extra = new TransferRequest();
        $extra->setDescription(MonthEndService::DESCRIPTION_PRESETS['maintenance']);
        $transferOrder->addTransfer($extra);

        $actual = $this->service->separateSettlements([$transferOrder]);
        $this->assertEquals(['stampDuty', 'settlement'], array_keys($actual));
        $this->assertSame($settlements, $actual['settlement']);
        $this->assertSame($stampDuties, $actual['stampDuty']);
    }
}
