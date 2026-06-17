<?php

namespace App\Tests\Service;

use App\Entity\Enum\TaskStatus;
use App\Entity\Enum\TaskTrackerType;
use App\Entity\TaskTracker;
use App\Entity\User;
use App\Service\CardCleanupService;
use App\Service\MangopayWalletService;
use App\Test\Util\EntityIdTestUtil;
use Doctrine\ORM\EntityManagerInterface;
use MangoPay\Card;
use MangoPay\Pagination;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CardCleanupServiceTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private CardCleanupService $service;
    private MangopayWalletService|MockObject $mangopayWalletServiceMock;

    protected function setUp(): void
    {
        self::bootKernel();

        // Configure any services that we want to mock (due to interaction with external services)
        $this->mangopayWalletServiceMock = $this->createMock(MangopayWalletService::class);
        static::getContainer()->set(
            MangopayWalletService::class,
            $this->mangopayWalletServiceMock,
        );

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->service = static::getContainer()->get(CardCleanupService::class);

        // Ensure there is no existing taskTracker at the start
        $taskTracker = $this->service->getTaskTracker();
        if ($taskTracker) {
            $this->entityManager->remove($taskTracker);
            $this->entityManager->flush();
        }
    }

    protected function tearDown(): void
    {
        // Ensure there is no existing taskTracker at the end
        $taskTracker = $this->service->getTaskTracker();
        if ($taskTracker) {
            $this->entityManager->remove($taskTracker);
            $this->entityManager->flush();
        }
        // https://symfony.com/doc/current/testing/database.html
        // doing this is recommended to avoid memory leaks
        $this->entityManager->close();
        $this->entityManager = null;
    }

    public function testListCardsForUser(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 5784);
        $user->setMangoPayUserId('user_m_test_' . bin2hex(random_bytes(8)));
        $pagination = new Pagination();
        $cards = [];
        foreach (range(1, 4) as $iteration) {
            $card = new Card();
            $card->Id = 'card_m_test_' . bin2hex(random_bytes(8));
            $card->Active = true;
            $cards[] = $card;
        }
        $this->mangopayWalletServiceMock
            ->method('listUserCards')
            ->with($user->getMangoPayUserId(), true, $pagination)
            ->willReturn($cards);
        $actual = $this->service->listCardsForUser($user);
        $this->assertEquals(['cards' => $cards, 'pagination' => $pagination], $actual);
    }

    public function testCleanupCards(): void
    {
        $metadata = [
            'lastUserId' => '8545',
            'lastRunAt' => '2025-07-25T12:56:05+00:00',
            'jobInProgress' => false,
            'lastRunCleanupCount' => 4,
            'currentRoundCleanupCount' => 67,
            'currentJobCleanupCount' => 0,
            'totalCleanupCount' => 875,
        ];
        $taskTracker = new TaskTracker(
            TaskTrackerType::CardCleanup,
            ['cardCleanup' => TaskStatus::Pending],
            $metadata,
        );
        $cards = [];
        $expected = [];
        $expectedCount = 4;
        // We'll have more cards than the batchSize to check premature exit
        foreach (range(1, $expectedCount + 2) as $iteration) {
            $card = new Card();
            $card->Id = 'card_m_test_' . bin2hex(random_bytes(8));
            $card->Active = true;
            $cards[] = $card;
            $expected[] = $card->Id;
        }
        $this->mangopayWalletServiceMock
            ->expects(self::exactly($expectedCount))
            ->method('deactivateCard')
            ->willReturnOnConsecutiveCalls(...$cards);
        $actual = $this->service->cleanupCards(
            $taskTracker,
            $cards,
            '8566',
            41,
            $expectedCount,
        );

        $this->assertEquals(array_slice($expected, 0, $expectedCount), $actual);
        $this->assertEquals(
            TaskStatus::Started,
            $taskTracker->getTasks()['cardCleanup'],
        );
        $this->assertEquals('8545', $taskTracker->getMetadata()['lastUserId']);
        $this->assertGreaterThan(
            strtotime($metadata['lastRunAt']),
            strtotime($taskTracker->getMetadata()['lastRunAt']),
        );
        $this->assertEquals(4, $taskTracker->getMetadata()['lastRunCleanupCount']);
        $this->assertEquals(
            71,
            $taskTracker->getMetadata()['currentRoundCleanupCount'],
        );
        $this->assertEquals(879, $taskTracker->getMetadata()['totalCleanupCount']);
    }

    public function testCleanupCardsCompletedUser(): void
    {
        $metadata = [
            'lastUserId' => '8545',
            'lastRunAt' => '2025-07-25T12:56:05+00:00',
            'jobInProgress' => false,
            'lastRunCleanupCount' => 4,
            'currentRoundCleanupCount' => 67,
            'currentJobCleanupCount' => 0,
            'totalCleanupCount' => 875,
        ];
        $taskTracker = new TaskTracker(
            TaskTrackerType::CardCleanup,
            ['cardCleanup' => TaskStatus::Pending],
            $metadata,
        );
        $cards = [];
        $expected = [];
        foreach (range(1, 6) as $iteration) {
            $card = new Card();
            $card->Id = 'card_m_test_' . bin2hex(random_bytes(8));
            $card->Active = true;
            $cards[] = $card;
            $expected[] = $card->Id;
        }
        $this->mangopayWalletServiceMock
            ->expects(self::exactly(count($cards)))
            ->method('deactivateCard')
            ->willReturnOnConsecutiveCalls(...$cards);
        $actual = $this->service->cleanupCards(
            $taskTracker,
            $cards,
            '10867',
            6, // be exactly the same as the number of cards
            10, // be bigger than the number of cards
        );

        $this->assertEquals($expected, $actual);
        $this->assertEquals(
            TaskStatus::Started,
            $taskTracker->getTasks()['cardCleanup'],
        );
        // Should move onto the next user
        $this->assertEquals('10867', $taskTracker->getMetadata()['lastUserId']);
        $this->assertGreaterThan(
            strtotime($metadata['lastRunAt']),
            strtotime($taskTracker->getMetadata()['lastRunAt']),
        );
        $this->assertEquals(6, $taskTracker->getMetadata()['lastRunCleanupCount']);
        $this->assertEquals(
            73,
            $taskTracker->getMetadata()['currentRoundCleanupCount'],
        );
        $this->assertEquals(881, $taskTracker->getMetadata()['totalCleanupCount']);
    }

    public function testGetTaskTracker(): void
    {
        $actual = $this->service->getTaskTracker();
        $this->assertEquals(CardCleanupService::DEFAULT_TASKS, $actual->getTasks());
        $this->assertEquals(
            CardCleanupService::DEFAULT_METADATA,
            $actual->getMetadata(),
        );
    }

    public function testGetTaskTrackerExistingValid(): void
    {
        $tasks = [
            'cardCleanup' => TaskStatus::Started,
        ];
        $metadata = [
            'lastUserId' => '8545',
            'lastRunAt' => '2025-07-25T12:56:05+00:00',
            'jobInProgress' => false,
            'lastRunCleanupCount' => 4,
            'currentRoundCleanupCount' => 56,
            'currentJobCleanupCount' => 12,
            'totalCleanupCount' => 875,
        ];
        $taskTracker = new TaskTracker(
            TaskTrackerType::CardCleanup,
            array_replace(CardCleanupService::DEFAULT_TASKS, $tasks),
            array_replace(CardCleanupService::DEFAULT_METADATA, $metadata),
        );
        $this->entityManager->persist($taskTracker);
        $this->entityManager->flush();
        $actual = $this->service->getTaskTracker();
        // $this->assertEquals($tasks, $actual->getTasks());
        $this->assertEquals($metadata, $actual->getMetadata());
    }

    public function testGetTaskTrackerExistingInvalid(): void
    {
        $taskTracker = new TaskTracker(TaskTrackerType::CardCleanup, [], []);
        $this->entityManager->persist($taskTracker);
        $this->entityManager->flush();
        $actual = $this->service->getTaskTracker();
        $this->assertEquals(CardCleanupService::DEFAULT_TASKS, $actual->getTasks());
        $this->assertEquals(
            CardCleanupService::DEFAULT_METADATA,
            $actual->getMetadata(),
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('taskStatusUpdateProvider')]
    public function testUpdateTaskStatusInTracker(
        TaskStatus $expected,
        TaskStatus $start,
    ): void {
        $taskTracker = new TaskTracker(
            TaskTrackerType::CardCleanup,
            ['cardCleanup' => $start],
            CardCleanupService::DEFAULT_METADATA,
        );
        $actual = $this->service->updateTaskStatusInTracker($taskTracker, $expected);
        $this->assertEquals($expected, $actual->getTasks()['cardCleanup']);
    }

    public static function taskStatusUpdateProvider(): \Generator
    {
        yield 'Pending->Start' => [TaskStatus::Started, TaskStatus::Pending];
        yield 'Pending->Completed' => [TaskStatus::Completed, TaskStatus::Pending];
        yield 'Pending->Skipped' => [TaskStatus::Skipped, TaskStatus::Pending];
        yield 'Start->Completed' => [TaskStatus::Completed, TaskStatus::Started];
        yield 'Start->Skipped' => [TaskStatus::Skipped, TaskStatus::Started];
    }

    public function testUpdateTaskTrackerProgress(): void
    {
        $metadata = [
            'lastUserId' => '8545',
            'lastRunAt' => '2025-07-25T12:56:05+00:00',
            'jobInProgress' => false,
            'lastRunCleanupCount' => 4,
            'currentRoundCleanupCount' => 67,
            'currentJobCleanupCount' => 0,
            'totalCleanupCount' => 875,
        ];
        $taskTracker = new TaskTracker(
            TaskTrackerType::CardCleanup,
            ['cardCleanup' => TaskStatus::Pending],
            $metadata,
        );
        $actual = $this->service->updateTaskTrackerProgress(
            taskTracker: $taskTracker,
            lastRunCount: 8,
            lastUserId: '9874',
        );
        // Status should be unchanged
        $this->assertEquals(
            TaskStatus::Pending,
            $taskTracker->getTasks()['cardCleanup'],
        );
        // Metadata should be updated
        $this->assertEquals('9874', $actual->getMetadata()['lastUserId']);
        $this->assertGreaterThan(
            strtotime($metadata['lastRunAt']),
            strtotime($actual->getMetadata()['lastRunAt']),
        );
        $this->assertFalse($actual->getMetadata()['jobInProgress']);
        $this->assertEquals(8, $actual->getMetadata()['lastRunCleanupCount']);
        $this->assertEquals(75, $actual->getMetadata()['currentRoundCleanupCount']);
        $this->assertEquals(883, $actual->getMetadata()['totalCleanupCount']);
    }

    public function testResetTaskTracker(): void
    {
        $tasks = [
            'cardCleanup' => TaskStatus::Started,
        ];
        $expectedTasks = [
            'cardCleanup' => TaskStatus::Completed,
        ];
        $metadata = [
            'lastUserId' => '8545',
            'lastRunAt' => '2025-07-25T12:56:05+00:00',
            'jobInProgress' => true,
            'lastRunCleanupCount' => 4,
            'currentRoundCleanupCount' => 67,
            'currentJobCleanupCount' => 10,
            'totalCleanupCount' => 875,
        ];
        $expectedMetadata = [
            'lastUserId' => null,
            'lastRunAt' => '2025-07-25T12:56:05+00:00',
            'jobInProgress' => false,
            'lastRunCleanupCount' => 0,
            'currentRoundCleanupCount' => 0,
            'currentJobCleanupCount' => 0,
            'totalCleanupCount' => 875,
        ];
        $taskTracker = new TaskTracker(TaskTrackerType::CardCleanup, $tasks, $metadata);
        $actual = $this->service->resetTaskTracker($taskTracker);
        $this->assertEquals($expectedTasks, $actual->getTasks());
        $this->assertEquals($expectedMetadata, $actual->getMetadata());
    }

    public function testResetTaskTrackerFixInvalid(): void
    {
        $tasks = [];
        $expectedTasks = [
            'cardCleanup' => TaskStatus::Completed,
        ];
        $metadata = [
            'lastUserId' => '8545',
            'lastRunAt' => ['date' => '2025-07-25T12:56:05+00:00'],
            'jobInProgress' => true,
            'lastRunCleanupCount' => 4,
            'totalCleanupCount' => '875.1', // numeric string will be converted to string
        ];
        $expectedMetadata = [
            'lastUserId' => null,
            'lastRunAt' => null,
            'jobInProgress' => false,
            'lastRunCleanupCount' => 0,
            'currentRoundCleanupCount' => 0,
            'currentJobCleanupCount' => 0,
            'totalCleanupCount' => 875,
        ];
        $taskTracker = new TaskTracker(TaskTrackerType::CardCleanup, $tasks, $metadata);
        $actual = $this->service->resetTaskTracker($taskTracker);
        $this->assertEquals($expectedTasks, $actual->getTasks());
        $this->assertEquals($expectedMetadata, $actual->getMetadata());
    }

    public function testResetTaskTrackerFixInvalidAll(): void
    {
        $tasks = [];
        $expectedTasks = [
            'cardCleanup' => TaskStatus::Completed,
        ];
        $metadata = [
            'lastUserId' => '8545',
            'lastRunAt' => ['date' => '2025-07-25T12:56:05+00:00'],
            'jobInProgress' => false,
            'lastRunCleanupCount' => 4,
            'currentJobCleanupCount' => 'xyz',
            'totalCleanupCount' => 'abc',
        ];
        $expectedMetadata = [
            'lastUserId' => null,
            'lastRunAt' => null,
            'jobInProgress' => false,
            'lastRunCleanupCount' => 0,
            'currentRoundCleanupCount' => 0,
            'currentJobCleanupCount' => 0,
            'totalCleanupCount' => 0,
        ];
        $taskTracker = new TaskTracker(TaskTrackerType::CardCleanup, $tasks, $metadata);
        $actual = $this->service->resetTaskTracker($taskTracker);
        $this->assertEquals($expectedTasks, $actual->getTasks());
        $this->assertEquals($expectedMetadata, $actual->getMetadata());
    }

    public function testResetTaskTrackerJobCounter(): void
    {
        $tasks = [
            'cardCleanup' => TaskStatus::Started,
        ];
        $expectedTasks = [
            'cardCleanup' => TaskStatus::Started,
        ];
        $metadata = [
            'lastUserId' => '8545',
            'lastRunAt' => '2025-07-25T12:56:05+00:00',
            'jobInProgress' => false,
            'lastRunCleanupCount' => 4,
            'currentRoundCleanupCount' => 67,
            'currentJobCleanupCount' => 10,
            'totalCleanupCount' => 875,
        ];
        $expectedMetadata = [
            'lastUserId' => '8545',
            'lastRunAt' => '2025-07-25T12:56:05+00:00',
            'jobInProgress' => false,
            'lastRunCleanupCount' => 4,
            'currentRoundCleanupCount' => 67,
            'currentJobCleanupCount' => 0,
            'totalCleanupCount' => 875,
        ];
        $taskTracker = new TaskTracker(TaskTrackerType::CardCleanup, $tasks, $metadata);
        $actual = $this->service->resetTaskTrackerJobCounter($taskTracker);
        $this->assertEquals($expectedTasks, $actual->getTasks());
        $this->assertEquals($expectedMetadata, $actual->getMetadata());
    }

    public function testSetJobInProgress(): void
    {
        $taskTracker = $this->service->getTaskTracker();
        $actual = $this->service->setJobInProgress($taskTracker, true);
        $this->assertTrue($actual->getMetadata()['jobInProgress']);
        $actual = $this->service->setJobInProgress($taskTracker, false);
        $this->assertFalse($actual->getMetadata()['jobInProgress']);
        $actual = $this->service->setJobInProgress($taskTracker, true);
        $this->assertTrue($actual->getMetadata()['jobInProgress']);
    }
}
