<?php

namespace App\Tests\Service;

use App\Entity\AbstractOrder;
use App\Entity\Asset;
use App\Entity\Enum\OrderStatus;
use App\Entity\Enum\TaskStatus;
use App\Entity\Enum\TaskTrackerType;
use App\Entity\PaymentOrder;
use App\Entity\ShareTransferOrder;
use App\Entity\TaskTracker;
use App\Entity\TransferOrder;
use App\Repository\AssetRepository;
use App\Repository\InvestmentRepository;
use App\Repository\ShareTradeRepository;
use App\Repository\TradeOrderRepository;
use App\Service\DivestmentService;
use App\Service\Manager\HoldingManager;
use App\Service\MonthEndActivityService;
use App\Service\MonthEndService;
use App\Service\MonthEndTaskTrackerService;
use App\Test\Util\EntityIdTestUtil;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MonthEndTaskTrackerServiceTest extends KernelTestCase
{
    private MonthEndTaskTrackerService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(MonthEndTaskTrackerService::class);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('taskTrackerValidationProvider')]
    public function testValidateTaskTracker(
        TaskTracker $input,
        TaskTracker $expected,
    ): void {
        $actual = $this->service->validateTaskTracker($input);
        $this->assertEquals($expected, $actual);
    }

    public static function taskTrackerValidationProvider(): \Generator
    {
        $defaultMetaData = [
            'monthend' => date('Y-m'),
            'autoUpdate' => true,
            'syncedAt' => null,
        ];
        $defaultMonthendTasks = [
            'incomeDeposits' => TaskStatus::Pending,
            'incomeDisaggregations' => TaskStatus::Pending,
            'dividends' => TaskStatus::Pending,
            'settlements' => TaskStatus::Pending,
            'repayments' => TaskStatus::Pending,
            'feeCollections' => TaskStatus::Pending,
        ];
        $defaultAssetMonthendTasks = [
            'incomeTransfer' => TaskStatus::Pending,
            'dividends' => TaskStatus::Pending,
            'settlements' => TaskStatus::Pending,
            'repayments' => TaskStatus::Pending,
            'shareTransfers' => TaskStatus::Pending,
        ];
        $defaultMonthend = new TaskTracker(
            TaskTrackerType::Monthend,
            $defaultMonthendTasks,
            $defaultMetaData,
        );
        $defaultAssetMonthend = new TaskTracker(
            TaskTrackerType::AssetMonthend,
            $defaultAssetMonthendTasks,
            $defaultMetaData,
        );

        yield 'Empty monthend task tracker' => [
            new TaskTracker(TaskTrackerType::Monthend),
            $defaultMonthend,
        ];
        yield 'Empty asset monthend task tracker' => [
            new TaskTracker(TaskTrackerType::AssetMonthend),
            $defaultAssetMonthend,
        ];

        $validMonthendMixed = new TaskTracker(
            TaskTrackerType::Monthend,
            [
                'incomeDeposits' => TaskStatus::Completed,
                'incomeDisaggregations' => TaskStatus::Skipped,
                'dividends' => TaskStatus::Started,
                'settlements' => TaskStatus::Pending,
                'repayments' => TaskStatus::Pending,
                'feeCollections' => TaskStatus::Pending,
            ],
            [
                'monthend' => date('Y-m'),
                'autoUpdate' => false,
                'syncedAt' => null,
            ],
        );

        $validAssetMonthendMixed = new TaskTracker(
            TaskTrackerType::AssetMonthend,
            [
                'incomeTransfer' => TaskStatus::Completed,
                'dividends' => TaskStatus::Completed,
                'settlements' => TaskStatus::Started,
                'repayments' => TaskStatus::Pending,
                'shareTransfers' => TaskStatus::Skipped,
            ],
            [
                'monthend' => date('Y-m'),
                'autoUpdate' => false,
                'syncedAt' => null,
            ],
        );

        yield 'Valid monthend task tracker' => [
            $validMonthendMixed,
            clone $validMonthendMixed,
        ];
        yield 'Valid asset monthend task tracker' => [
            $validAssetMonthendMixed,
            clone $validAssetMonthendMixed,
        ];

        $invalidTaskStatus = new TaskTracker(
            TaskTrackerType::Monthend,
            [
                'incomeDeposits' => TaskStatus::Completed,
                'incomeDisaggregations' => TaskStatus::Skipped,
                'dividends' => TaskStatus::Started,
                'settlements' => TaskStatus::Pending,
                'repayments' => TaskStatus::Pending,
                'feeCollections' => 'not a task status',
            ],
            $defaultMetaData,
        );

        yield 'Invalid status' => [$invalidTaskStatus, $defaultMonthend];

        $missingMetadataAutoUpdate = new TaskTracker(
            TaskTrackerType::Monthend,
            $defaultMonthendTasks,
            ['monthend' => date('Y-m')],
        );

        $missingMetadataMonthend = new TaskTracker(
            TaskTrackerType::Monthend,
            $defaultMonthendTasks,
            [
                'autoUpdate' => false,
            ],
        );

        yield 'Missing metadata - autoupdate' => [
            $missingMetadataAutoUpdate,
            $defaultMonthend,
        ];
        yield 'Missing metadata - monthend' => [
            $missingMetadataMonthend,
            $defaultMonthend,
        ];

        $missingMonthendTasks = new TaskTracker(
            TaskTrackerType::Monthend,
            [
                'incomeDeposits' => TaskStatus::Completed,
                'dividends' => TaskStatus::Started,
                'settlements' => TaskStatus::Pending,
                'repayments' => TaskStatus::Pending,
                'feeCollections' => TaskStatus::Pending,
            ],
            $defaultMetaData,
        );

        $missingAssetMonthendTasks = new TaskTracker(
            TaskTrackerType::AssetMonthend,
            [
                'incomeTransfer' => TaskStatus::Completed,
                'dividends' => TaskStatus::Completed,
                'repayments' => TaskStatus::Pending,
                'shareTransfers' => TaskStatus::Skipped,
            ],
            $defaultMetaData,
        );

        yield 'Missing monthend tasks' => [$missingMonthendTasks, $defaultMonthend];
        yield 'Missing asset monthend tasks' => [
            $missingAssetMonthendTasks,
            $defaultAssetMonthend,
        ];

        $extraMonthendTasks = new TaskTracker(
            TaskTrackerType::Monthend,
            array_merge($defaultMonthendTasks, [
                'unknownTask' => TaskStatus::Completed,
            ]),
            $defaultMetaData,
        );

        $extraAssetMonthendTasks = new TaskTracker(
            TaskTrackerType::AssetMonthend,
            array_merge($defaultAssetMonthendTasks, [
                'unknownTask' => TaskStatus::Completed,
            ]),
            $defaultMetaData,
        );

        yield 'Extra monthend tasks' => [$extraMonthendTasks, $defaultMonthend];
        yield 'Extra asset monthend tasks' => [
            $extraAssetMonthendTasks,
            $defaultAssetMonthend,
        ];

        $oldMonthend = new TaskTracker(
            TaskTrackerType::Monthend,
            $defaultMonthendTasks,
            [
                'monthend' => '2000-01',
                'autoUpdate' => false,
                'syncedAt' => null,
            ],
        );
        yield 'Old monthend' => [$oldMonthend, $defaultMonthend];
    }

    public function testUpdateTaskStatusInTrackerValid(): void
    {
        $taskTracker = $this->service->createMonthendTaskTracker(TaskTrackerType::Monthend);
        foreach (array_keys($taskTracker->getTasks()) as $taskName) {
            $actual = $this->service->updateTaskStatusInTracker(
                $taskTracker,
                $taskName,
                TaskStatus::Started,
            );
            $this->assertEquals(TaskStatus::Started, $actual->getTasks()[$taskName]);
        }

        $taskTracker = $this->service->createMonthendTaskTracker(TaskTrackerType::AssetMonthend);
        foreach (array_keys($taskTracker->getTasks()) as $taskName) {
            $actual = $this->service->updateTaskStatusInTracker(
                $taskTracker,
                $taskName,
                TaskStatus::Skipped,
            );
            $this->assertEquals(TaskStatus::Skipped, $actual->getTasks()[$taskName]);
        }
    }

    public function testUpdateTaskStatusInTrackerRemaining(): void
    {
        $taskTracker = $this->service->createMonthendTaskTracker(TaskTrackerType::Monthend);
        $actual = $this->service->updateTaskStatusInTracker(
            $taskTracker,
            'remaining',
            TaskStatus::Skipped,
        );
        foreach ($actual->getTasks() as $taskStatus) {
            $this->assertEquals(TaskStatus::Skipped, $taskStatus);
        }

        $taskTracker = $this->service->createMonthendTaskTracker(TaskTrackerType::AssetMonthend);
        $actual = $this->service->updateTaskStatusInTracker(
            $taskTracker,
            'remaining',
            TaskStatus::Skipped,
        );
        foreach ($actual->getTasks() as $taskStatus) {
            $this->assertEquals(TaskStatus::Skipped, $taskStatus);
        }
    }

    public function testUpdateTaskStatusInTrackerInvalid(): void
    {
        // Special 'remaining' task only works if the target state is Skipped
        $taskTracker = $this->service->createMonthendTaskTracker(TaskTrackerType::Monthend);
        $expectedTaskTracker = clone $taskTracker;
        foreach (TaskStatus::cases() as $toState) {
            if (TaskStatus::Skipped == $toState) {
                continue;
            }
            $actual = $this->service->updateTaskStatusInTracker(
                $taskTracker,
                'remaining',
                $toState,
            );
            $this->assertEquals($expectedTaskTracker, $actual);
        }

        // Other task names that are not in the list of tasks will result in no changes
        $actual = $this->service->updateTaskStatusInTracker(
            $taskTracker,
            'otherTask',
            TaskStatus::Started,
        );
        $this->assertEquals($expectedTaskTracker, $actual);
    }

    public function testCreateMonthendTaskTracker(): void
    {
        $defaultMetaData = [
            'monthend' => date('Y-m'),
            'autoUpdate' => true,
            'syncedAt' => null,
        ];
        $defaultMonthendTasks = [
            'incomeDeposits' => TaskStatus::Pending,
            'incomeDisaggregations' => TaskStatus::Pending,
            'dividends' => TaskStatus::Pending,
            'settlements' => TaskStatus::Pending,
            'repayments' => TaskStatus::Pending,
            'feeCollections' => TaskStatus::Pending,
        ];
        $defaultAssetMonthendTasks = [
            'incomeTransfer' => TaskStatus::Pending,
            'dividends' => TaskStatus::Pending,
            'settlements' => TaskStatus::Pending,
            'repayments' => TaskStatus::Pending,
            'shareTransfers' => TaskStatus::Pending,
        ];
        $defaultMonthend = new TaskTracker(
            TaskTrackerType::Monthend,
            $defaultMonthendTasks,
            $defaultMetaData,
        );
        $defaultAssetMonthend = new TaskTracker(
            TaskTrackerType::AssetMonthend,
            $defaultAssetMonthendTasks,
            $defaultMetaData,
        );
        $monthendTaskTracker = $this->service->createMonthendTaskTracker(TaskTrackerType::Monthend);
        $this->assertEquals($defaultMonthend, $monthendTaskTracker);
        $this->assertNull($monthendTaskTracker->getId());
        $this->assertNotSame($defaultMonthend, $monthendTaskTracker);

        $assetMonthendTaskTracker = $this->service->createMonthendTaskTracker(TaskTrackerType::AssetMonthend);
        $this->assertEquals($defaultAssetMonthend, $assetMonthendTaskTracker);

        // Factory reset an existing one - set an ID so we know it is the same instance
        /** @var TaskTracker $monthendTaskTracker */
        $monthendTaskTracker = EntityIdTestUtil::setEntityId($monthendTaskTracker, 123);
        $monthendTaskTracker->setTasks(['abc' => '123']);
        $monthendTaskTracker->setMetadata(['meta' => 'data']);

        // Note that we can change the task tracker type as well
        $newMonthendTaskTracker = $this->service->createMonthendTaskTracker(
            TaskTrackerType::AssetMonthend,
            $monthendTaskTracker,
        );
        $this->assertEquals(
            TaskTrackerType::AssetMonthend,
            $monthendTaskTracker->getTaskTrackerType(),
        );
        $this->assertEquals(
            $defaultAssetMonthend->getTasks(),
            $monthendTaskTracker->getTasks(),
        );
        $this->assertEquals(
            $defaultAssetMonthend->getMetadata(),
            $monthendTaskTracker->getMetadata(),
        );
        // Should reference the same object
        $this->assertEquals(
            $newMonthendTaskTracker->getId(),
            $monthendTaskTracker->getId(),
        );
        $this->assertSame($newMonthendTaskTracker, $monthendTaskTracker);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('syncMonthendAssetChecklistProvider')]
    public function testsyncMonthendAssetChecklist(
        bool $autoUpdate,
        array $tasks,
        array $expected,
        ?TransferOrder $incomeTransfer,
        ?PaymentOrder $dividendPayment,
        ?TransferOrder $settlementOrder,
        ?PaymentOrder $prefunderRepayment,
        ?ShareTransferOrder $shareTransferOrder,
    ): void {
        $taskTracker = new TaskTracker(TaskTrackerType::AssetMonthend, $tasks, [
            'monthend' => date('Y-m'),
            'autoUpdate' => $autoUpdate,
        ]);
        $actual = $this->service->syncMonthendAssetChecklist(
            $taskTracker,
            $incomeTransfer,
            $dividendPayment,
            $settlementOrder,
            $prefunderRepayment,
            $shareTransferOrder,
        );
        $this->assertEquals($expected, $actual->getTasks());
    }

    public static function syncMonthendAssetChecklistProvider(): \Generator
    {
        $defaultTaskList = [
            'incomeTransfer' => TaskStatus::Pending,
            'dividends' => TaskStatus::Pending,
            'settlements' => TaskStatus::Pending,
            'repayments' => TaskStatus::Pending,
            'shareTransfers' => TaskStatus::Pending,
        ];
        $manualOnlyTaskList = [
            'incomeTransfer' => TaskStatus::Pending,
            'dividends' => TaskStatus::Pending,
            'settlements' => TaskStatus::Started,
            'repayments' => TaskStatus::Pending,
            'shareTransfers' => TaskStatus::Pending,
        ];
        $mixedTaskList1 = [
            'incomeTransfer' => TaskStatus::Completed,
            'dividends' => TaskStatus::Started,
            'settlements' => TaskStatus::Started,
            'repayments' => TaskStatus::Skipped,
            'shareTransfers' => TaskStatus::Skipped,
        ];
        $newIncomeTransfer = new TransferOrder();
        $newDividendPayment = new PaymentOrder();
        $newSettlementOrder = new TransferOrder();
        $newPrefunderRepayment = new PaymentOrder();
        $newShareTransfer = new ShareTransferOrder();

        yield 'No orders, no changes' => [
            true,
            $defaultTaskList,
            $defaultTaskList,
            null,
            null,
            null,
            null,
            null,
        ];

        yield 'No auto update, no changes' => [
            false,
            $manualOnlyTaskList,
            $manualOnlyTaskList,
            $newIncomeTransfer,
            $newDividendPayment,
            $newSettlementOrder,
            $newPrefunderRepayment,
            $newShareTransfer,
        ];

        yield 'Skipped, unfinished or completed, no changes' => [
            true,
            $mixedTaskList1,
            $mixedTaskList1,
            $newIncomeTransfer,
            $newDividendPayment,
            $newSettlementOrder,
            $newPrefunderRepayment,
            $newShareTransfer,
        ];

        yield 'Start all' => [
            true,
            $defaultTaskList,
            [
                'incomeTransfer' => TaskStatus::Started,
                'dividends' => TaskStatus::Started,
                'settlements' => TaskStatus::Started,
                'repayments' => TaskStatus::Started,
                'shareTransfers' => TaskStatus::Started,
            ],
            $newIncomeTransfer,
            $newDividendPayment,
            $newSettlementOrder,
            $newPrefunderRepayment,
            $newShareTransfer,
        ];

        yield 'Complete all' => [
            true,
            [
                'incomeTransfer' => TaskStatus::Pending,
                'dividends' => TaskStatus::Started,
                'settlements' => TaskStatus::Pending,
                'repayments' => TaskStatus::Started,
                'shareTransfers' => TaskStatus::Pending,
            ],
            [
                'incomeTransfer' => TaskStatus::Completed,
                'dividends' => TaskStatus::Completed,
                'settlements' => TaskStatus::Completed,
                'repayments' => TaskStatus::Completed,
                'shareTransfers' => TaskStatus::Completed,
            ],
            new TransferOrder()->setStatus(AbstractOrder::STATE_COMPLETED),
            new PaymentOrder()->setStatus(AbstractOrder::STATE_COMPLETED),
            new TransferOrder()->setStatus(AbstractOrder::STATE_COMPLETED),
            new PaymentOrder()->setStatus(AbstractOrder::STATE_COMPLETED),
            new ShareTransferOrder()->setStatus(OrderStatus::Completed),
        ];
    }

    public function testSyncMonthendTaskTrackerNoChanges(): void
    {
        $timestamp = time();
        // Skipped and completed tasks should be left as is
        $taskTracker = $this->service->createMonthendTaskTracker(TaskTrackerType::Monthend);
        $tasks = $taskTracker->getTasks();
        foreach ($tasks as $taskName => $status) {
            $tasks[$taskName] = TaskStatus::Skipped;
        }
        $taskTracker->setTasks($tasks);
        $actual = $this->service->syncMonthEndTaskTracker($taskTracker)->getTasks();
        $expected = array_combine(
            array_keys($tasks),
            array_fill(0, count($tasks), TaskStatus::Skipped),
        );
        $this->assertEquals($expected, $actual);

        foreach ($tasks as $taskName => $status) {
            $tasks[$taskName] = TaskStatus::Completed;
        }
        $taskTracker->setTasks($tasks);
        $actual = $this->service->syncMonthEndTaskTracker($taskTracker);
        $expected = array_combine(
            array_keys($tasks),
            array_fill(0, count($tasks), TaskStatus::Completed),
        );
        $this->assertEquals($expected, $actual->getTasks());
        $this->assertGreaterThanOrEqual($timestamp, $actual->getMetadata()['syncedAt']);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('syncOrderOnlyMonthEndTaskProvider')]
    public function testSyncMonthendTaskTrackerOrderOnly(
        array $expected,
        array $orderResults,
    ): void {
        // Test the tasks syncing that only query transfer/payment orders
        $monthendActivityService = $this
            ->getMockBuilder(MonthEndActivityService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $monthendActivityService
            ->method('getMonthendActivity')
            ->willReturnOnConsecutiveCalls(...$orderResults);

        $taskTracker = $this->service->createMonthendTaskTracker(TaskTrackerType::Monthend);
        $tasks = [
            'incomeDeposits' => TaskStatus::Pending, // Note that this has no auto-tracking
            'incomeDisaggregations' => TaskStatus::Pending,
            'dividends' => TaskStatus::Pending,
            'feeCollections' => TaskStatus::Pending,
        ];
        $taskTracker->setTasks($tasks);

        /**
         * @var MonthEndActivityService $monthendActivityService
         */
        $service = new MonthEndTaskTrackerService(
            static::getContainer()->get(\Psr\Log\LoggerInterface::class),
            static::getContainer()->get(MonthEndService::class),
            $monthendActivityService,
            static::getContainer()->get(ShareTradeRepository::class),
            static::getContainer()->get(TradeOrderRepository::class),
            static::getContainer()->get(InvestmentRepository::class),
            static::getContainer()->get(AssetRepository::class),
            static::getContainer()->get(DivestmentService::class),
        );
        $actual = $service->syncMonthEndTaskTracker($taskTracker)->getTasks();
        $this->assertEquals($expected, $actual);
    }

    public static function syncOrderOnlyMonthEndTaskProvider(): \Generator
    {
        /**
         * Simulating queries by returning an array that is either
         * empty or contains something (integer used for succinctness)
         *
         * Example:
         *
         * income disaggregation -> completed
         *   - Return non-empty on query for complete
         *   - No query for incomplete will be sent
         * dividends -> pending
         *   - Empty on query for complete
         *   - Empty on query for incomplete
         * fee collections -> started
         *   - Empty on query for complete
         *   - Non-empty on query for incomplete
         */
        $expected1 = [
            'incomeDeposits' => TaskStatus::Pending,
            'incomeDisaggregations' => TaskStatus::Completed,
            'dividends' => TaskStatus::Pending,
            'feeCollections' => TaskStatus::Started,
        ];
        yield 'Combination Dep-P Dis-C D-P FC-S' => [
            $expected1,
            [
                [1], // completed for disaggregations
                [],
                [], // no completed or incomplete orders for dividends
                [],
                [1], // no completed but some incomplete orders for fee collections
            ],
        ];

        $expected2 = [
            'incomeDeposits' => TaskStatus::Pending,
            'incomeDisaggregations' => TaskStatus::Started,
            'dividends' => TaskStatus::Started,
            'feeCollections' => TaskStatus::Completed,
        ];
        yield 'Combination Dep-P, Dis-S, D-S FC-C' => [
            $expected2,
            [
                [],
                [1],
                [1],
                [1],
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider(
        'syncSettlementOnlyMonthEndTaskProvider',
    )]
    public function testSyncMonthendTaskSettlementOnly(
        TaskStatus $expected,
        array $orderResults,
        array $settlementCounts,
    ): void {
        $monthendActivityService = $this
            ->getMockBuilder(MonthEndActivityService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $monthendActivityService
            ->method('getMonthendActivity')
            ->willReturnOnConsecutiveCalls(...$orderResults);

        $investmentRepository = $this
            ->getMockBuilder(InvestmentRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $investmentRepository
            ->method('countInvestmentsInDateRangeByStatus')
            ->willReturnOnConsecutiveCalls(...$settlementCounts);

        $taskTracker = $this->service->createMonthendTaskTracker(TaskTrackerType::Monthend);
        $tasks = [
            'settlements' => TaskStatus::Pending,
        ];
        $taskTracker->setTasks($tasks);

        /**
         * @var MonthEndActivityService $monthendActivityService
         * @var InvestmentRepository $investmentRepository
         */
        $service = new MonthEndTaskTrackerService(
            static::getContainer()->get(\Psr\Log\LoggerInterface::class),
            static::getContainer()->get(MonthEndService::class),
            $monthendActivityService,
            static::getContainer()->get(ShareTradeRepository::class),
            static::getContainer()->get(TradeOrderRepository::class),
            $investmentRepository,
            static::getContainer()->get(AssetRepository::class),
            static::getContainer()->get(DivestmentService::class),
        );
        $actual = $service->syncMonthEndTaskTracker($taskTracker)->getTasks();
        $this->assertEquals($expected, $actual['settlements']);
    }

    public static function syncSettlementOnlyMonthEndTaskProvider(): \Generator
    {
        // Following logic for settlements outlined in
        // https://gitlab.com/yielders2/backoffice-dev/-/issues/2268#note_1643062000
        yield 'Completed' => [
            TaskStatus::Completed,
            [[1]], //at least 1 order
            [0, 5], // 0 approved, 5 settled investments
        ];
        yield 'Started' => [
            TaskStatus::Started,
            [[1]],
            [1, 4], // 1 approved, 4 settled
        ];
        yield 'Skipped' => [
            TaskStatus::Skipped,
            [[]],
            [0, 0], // both approved and settled return nothing
        ];
        yield 'Pending' => [
            TaskStatus::Pending,
            [[]], // no orders
            [5, 0], // 5 approved, no settled
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider(
        'syncRepaymentOnlyMonthEndTaskProvider',
    )]
    public function testSyncMonthendTaskRepaymentOnly(
        TaskStatus $expected,
        TaskStatus $settlementStatus,
        array $orderResults,
        int $surplus,
    ): void {
        $monthendActivityService = $this
            ->getMockBuilder(MonthEndActivityService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $monthendActivityService
            ->method('getMonthendActivity')
            ->willReturnOnConsecutiveCalls(...$orderResults);

        $asset15 = EntityIdTestUtil::setEntityId(new Asset(), 15);
        $asset15->setAmountOfShares(100);
        $assetRepository = $this
            ->getMockBuilder(AssetRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $assetRepository->method('findBy')->willReturn([$asset15]);

        $shareTradeRepository = $this->createStub(ShareTradeRepository::class);
        $shareTradeRepository
            ->method('aggregateSharesInCirculation')
            ->willReturn([
                ['assetid' => 15, 'shares' => $asset15->getAmountOfShares() + $surplus],
            ]);

        $divestmentService = $this->createStub(DivestmentService::class);
        $divestmentService
            ->method('compileRepaymentProgress')
            ->willReturn([
                15 => ['assetid' => 15, 'shares' => 50],
            ]);

        $taskTracker = $this->service->createMonthendTaskTracker(TaskTrackerType::Monthend);
        $tasks = [
            'repayments' => TaskStatus::Pending,
            'settlements' => $settlementStatus,
        ];
        $taskTracker->setTasks($tasks);

        $service = new MonthEndTaskTrackerService(
            static::getContainer()->get(\Psr\Log\LoggerInterface::class),
            static::getContainer()->get(MonthEndService::class),
            $monthendActivityService,
            $shareTradeRepository,
            static::getContainer()->get(TradeOrderRepository::class),
            static::getContainer()->get(InvestmentRepository::class),
            $assetRepository,
            $divestmentService,
        );
        $actual = $service->syncMonthEndTaskTracker($taskTracker)->getTasks();
        $this->assertEquals($expected, $actual['repayments']);
    }

    public static function syncRepaymentOnlyMonthEndTaskProvider(): \Generator
    {
        // Following logic for repayments outlined in
        // https://gitlab.com/yielders2/backoffice-dev/-/issues/2268#note_1643062000
        yield 'No surplus and skipped' => [
            TaskStatus::Skipped,
            TaskStatus::Skipped,
            [[1]], // at least 1 repayment order - ignored anyway
            0, // no surplus
        ];
        yield 'No surplus and completed' => [
            TaskStatus::Completed,
            TaskStatus::Completed,
            [[]], // no repayment orders - ignored anyway
            0, // no surplus
        ];
        yield 'Surplus with order' => [
            TaskStatus::Started,
            TaskStatus::Completed,
            [[1]], // at least 1 repayment order
            10, // some surplus
        ];
        yield 'Surplus and no orders' => [
            TaskStatus::Pending,
            TaskStatus::Completed,
            [[]], // no repayment orders
            10, // some surplus
        ];
    }
}
