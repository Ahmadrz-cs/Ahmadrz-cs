<?php

namespace App\Tests\MessageHandler;

use App\Entity\User;
use App\Message\CardCleanupBatchRun;
use App\MessageHandler\CardCleanupBatchRunHandler;
use App\Repository\TaskTrackerRepository;
use App\Repository\UserRepository;
use App\Service\CardCleanupService;
use App\Service\MangopayWalletService;
use App\Test\Util\EntityIdTestUtil;
use Doctrine\ORM\EntityManagerInterface;
use MangoPay\Card;
use MangoPay\Pagination;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CardCleanupBatchRunHandlerTest extends KernelTestCase
{
    private const bool PRINT_PROGRESS = false; // Use for debugging

    private CardCleanupBatchRunHandler $service;
    private UserRepository|MockObject $userRepositoryMock;
    private MangopayWalletService|MockObject $walletServiceMock;
    private CardCleanupService|MockObject $cardCleanupServiceMock;
    private ?EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();

        // Setup mock service dependencies that we'll configure in the individual tests
        // Repositories - mocking database
        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        static::getContainer()->set(UserRepository::class, $this->userRepositoryMock);

        // mocking Mangopay API
        $this->walletServiceMock = $this->createMock(MangopayWalletService::class);
        static::getContainer()->set(
            MangopayWalletService::class,
            $this->walletServiceMock,
        );

        // Only mock the listCardsForUser method due to the pagination fickleness
        $this->cardCleanupServiceMock = $this
            ->getMockBuilder(CardCleanupService::class)
            ->setConstructorArgs([
                static::getContainer()->get(LoggerInterface::class),
                static::getContainer()->get(TaskTrackerRepository::class),
                static::getContainer()->get(MangopayWalletService::class),
            ])
            ->onlyMethods(['listCardsForUser'])
            ->getMock();
        static::getContainer()->set(
            CardCleanupService::class,
            $this->cardCleanupServiceMock,
        );

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->service = static::getContainer()->get(CardCleanupBatchRunHandler::class);

        // Ensure there is no existing taskTracker at the start
        $taskTracker = $this->cardCleanupServiceMock->getTaskTracker();
        if ($taskTracker) {
            $this->entityManager->remove($taskTracker);
            $this->entityManager->flush();
        }

        /**
         * Note that we will directly invoke the handler rather than sending a message to a bus
         * As there is not message consumer running and no first party way to consume messages individually
         * See https://github.com/zenstruck/messenger-test if this ability is required
         *
         * Testing the following behaviours for both payment and transfer orders
         * - Successive/contiguous issue limit (set to 3 for testing) - end run immediately - don't continue
         * - More to go (pending or failed) - continue
         * - Run by status - end run after all of same status are finished
         *   - E.g. all pending are done, finish, even if there are failed ones waiting for retry
         * - Entire batch failed - end run immediately - don't continue
         *   - Failed will keep retrying until there are none in a given batch that succeed
         *
         * See https://github.com/sebastianbergmann/phpunit/issues/5469
         * On willReturnOnConsecutiveCalls and throwing exceptions
         */
    }

    protected function tearDown(): void
    {
        // Ensure there is no existing taskTracker at the end
        $taskTracker = $this->cardCleanupServiceMock->getTaskTracker();
        if ($taskTracker) {
            $this->entityManager->remove($taskTracker);
            $this->entityManager->flush();
        }
        // https://symfony.com/doc/current/testing/database.html
        // doing this is recommended to avoid memory leaks
        $this->entityManager->close();
        $this->entityManager = null;
    }

    public function testCardCleanupBatchRunBatchSize(): void
    {
        $message = new CardCleanupBatchRun(
            submittedByUserId: 1,
            autoContinue: true,
            batchSize: 5,
            jobSize: 21,
        );
        $this->assertEquals(5, $message->batchSize);
        $this->assertEquals(21, $message->jobSize);

        // If you attempt to set the batchSize greater than the jobSize
        // The batchsize will be set to the jobSize
        $message = new CardCleanupBatchRun(
            submittedByUserId: 1,
            autoContinue: true,
            batchSize: 8,
            jobSize: 4,
        );
        $this->assertEquals(4, $message->batchSize);
        $this->assertEquals(4, $message->jobSize);
    }

    public function testRunCleanupJobSizeZero(): void
    {
        $message = new CardCleanupBatchRun(
            submittedByUserId: 1,
            autoContinue: true,
            batchSize: 0,
            jobSize: 0,
        );

        $this->userRepositoryMock
            ->expects(self::never())
            ->method('findNextMangopayReadyUser');
        $this->cardCleanupServiceMock
            ->expects(self::never())
            ->method('listCardsForUser');
        $this->walletServiceMock->expects(self::never())->method('deactivateCard');

        $this->assertEmailCount(0);

        // Batch 1 - finished with nothing to do
        $this->service->__invoke($message);
        $this->assertEmailCount(1);

        $taskTracker = $this->cardCleanupServiceMock->getTaskTracker();
        if (self::PRINT_PROGRESS) {
            print_r($taskTracker->getMetadata());
        }
        // Metadata should be unchanged from the default
        $this->assertEquals(
            CardCleanupService::DEFAULT_METADATA,
            $taskTracker->getMetadata(),
        );
    }

    public function testRunCleanupJobInProgressNotEnabled(): void
    {
        $message = new CardCleanupBatchRun(
            submittedByUserId: 1,
            autoContinue: true,
            batchSize: 1,
            jobSize: 1,
        );

        $this->userRepositoryMock
            ->expects(self::never())
            ->method('findNextMangopayReadyUser');
        $this->cardCleanupServiceMock
            ->expects(self::never())
            ->method('listCardsForUser');
        $this->walletServiceMock->expects(self::never())->method('deactivateCard');

        $this->assertEmailCount(0);

        $taskTracker = $this->cardCleanupServiceMock->getTaskTracker();
        $this->entityManager->persist($taskTracker);
        $this->entityManager->flush();
        $this->assertFalse($taskTracker->getMetadata()['jobInProgress']);

        // Batch 1 - finished with no action taken as `jobInProgress` not true
        $this->service->__invoke($message);
        $this->assertEmailCount(1);

        if (self::PRINT_PROGRESS) {
            print_r($taskTracker->getMetadata());
        }
        // Metadata should be unchanged from the default as nothing actually happened
        $this->assertEquals(
            CardCleanupService::DEFAULT_METADATA,
            $taskTracker->getMetadata(),
        );
    }

    public function testRunCleanupUserNothingToProcess(): void
    {
        $admin = EntityIdTestUtil::setEntityId(new User(), 6);
        $admin->setFirstname('TestAdmin');
        $admin->setUsername('test@example.com');
        $admin->setEmail('test@example.com');

        $message = new CardCleanupBatchRun(
            submittedByUserId: $admin->getId(),
            autoContinue: true,
            batchSize: 5,
            jobSize: 10,
        );

        $sampleUser1 = EntityIdTestUtil::setEntityId(new User(), 551);
        // We're expect 4 batch runs in total
        // 3 with actual cleaning up to do, 1 as part of the completion detection
        $this->userRepositoryMock
            ->expects(self::exactly(2))
            ->method('findNextMangopayReadyUser')
            ->willReturnOnConsecutiveCalls($sampleUser1, null);

        $pagination1 = new Pagination();
        $pagination1->TotalItems = 0;

        $batch1 = ['cards' => [], 'pagination' => $pagination1];

        $this->cardCleanupServiceMock
            ->expects(self::exactly(1))
            ->method('listCardsForUser')
            ->willReturnOnConsecutiveCalls($batch1);
        $this->walletServiceMock->expects(self::never())->method('deactivateCard');

        $this->assertEmailCount(0);

        $taskTracker = $this->cardCleanupServiceMock->getTaskTracker();
        $taskTracker = $this->cardCleanupServiceMock->setJobInProgress(
            $taskTracker,
            true,
        );
        $this->entityManager->persist($taskTracker);
        $this->entityManager->flush();
        $this->assertTrue($taskTracker->getMetadata()['jobInProgress']);

        // Batch 1 - finished with nothing to do
        $this->service->__invoke($message);
        // No email sent as job is not finished, even though nothing was cleaned up
        // There could be more users (see MultiBatch test)
        $this->assertEmailCount(0);

        if (self::PRINT_PROGRESS) {
            print_r($taskTracker->getMetadata());
        }
        // User 1 should be completed
        $this->assertEquals(
            $sampleUser1->getId(),
            $taskTracker->getMetadata()['lastUserId'],
        );
        $this->assertNotNull($taskTracker->getMetadata()['lastRunAt']);
        $this->assertTrue($taskTracker->getMetadata()['jobInProgress']);
        $this->assertEquals(0, $taskTracker->getMetadata()['lastRunCleanupCount']);
        $this->assertEquals(0, $taskTracker->getMetadata()['currentRoundCleanupCount']);
        $this->assertEquals(0, $taskTracker->getMetadata()['currentJobCleanupCount']);
        $this->assertEquals(0, $taskTracker->getMetadata()['totalCleanupCount']);

        // On second invocation, job should be marked as finished
        // Notification sent and jobInProgress toggled off
        $this->service->__invoke($message);
        $this->assertEmailCount(1);
        $this->assertFalse($taskTracker->getMetadata()['jobInProgress']);
    }

    public function testRunCleanupExact(): void
    {
        $message = new CardCleanupBatchRun(
            submittedByUserId: 1,
            autoContinue: true,
            batchSize: 5,
            jobSize: 5,
        );

        $sampleUser1 = EntityIdTestUtil::setEntityId(new User(), 551);
        // We're expect 4 batch runs in total
        // 3 with actual cleaning up to do, 1 as part of the completion detection
        $this->userRepositoryMock
            ->expects(self::exactly(1))
            ->method('findNextMangopayReadyUser')
            ->willReturnOnConsecutiveCalls($sampleUser1);

        $cards = [];
        foreach (range(1, $message->jobSize) as $value) {
            $card = new Card();
            $card->Active = true;
            $card->Id = "card_test_#{$value}_" . bin2hex(random_bytes(8));
            $cards[] = $card;
        }
        $pagination1 = new Pagination();
        $pagination1->TotalItems = 5;
        $batch1 = ['cards' => $cards, 'pagination' => $pagination1];

        $this->cardCleanupServiceMock
            ->expects(self::exactly(1))
            ->method('listCardsForUser')
            ->willReturnOnConsecutiveCalls($batch1);
        $this->walletServiceMock->expects(self::exactly(5))->method('deactivateCard');

        $this->assertEmailCount(0);

        $taskTracker = $this->cardCleanupServiceMock->getTaskTracker();
        $taskTracker = $this->cardCleanupServiceMock->setJobInProgress(
            $taskTracker,
            true,
        );
        $this->entityManager->persist($taskTracker);
        $this->entityManager->flush();
        $this->assertTrue($taskTracker->getMetadata()['jobInProgress']);

        // Batch 1 - instant completion
        $this->service->__invoke($message);
        $this->assertEmailCount(1);

        if (self::PRINT_PROGRESS) {
            print_r($taskTracker->getMetadata());
        }
        // User 1 should be completed
        $this->assertEquals(
            $sampleUser1->getId(),
            $taskTracker->getMetadata()['lastUserId'],
        );
        $this->assertNotNull($taskTracker->getMetadata()['lastRunAt']);
        $this->assertFalse($taskTracker->getMetadata()['jobInProgress']);
        $this->assertEquals(
            $message->batchSize,
            $taskTracker->getMetadata()['lastRunCleanupCount'],
        );
        $this->assertEquals(
            $message->batchSize,
            $taskTracker->getMetadata()['currentRoundCleanupCount'],
        );
        $this->assertEquals(
            $message->batchSize,
            $taskTracker->getMetadata()['currentJobCleanupCount'],
        );
        $this->assertEquals(
            $message->batchSize,
            $taskTracker->getMetadata()['totalCleanupCount'],
        );
    }

    public function testRunCleanupMultiBatch(): void
    {
        $admin = EntityIdTestUtil::setEntityId(new User(), 6);
        $admin->setFirstname('TestAdmin');
        $admin->setUsername('test@example.com');
        $admin->setEmail('test@example.com');

        $jobSize = 13;
        $message = new CardCleanupBatchRun(
            submittedByUserId: $admin->getId(),
            autoContinue: true,
            batchSize: 5,
            // Job size limit should be greater than the actual job size
            // To test what happens if you run out of users
            jobSize: $jobSize + 5,
        );

        $sampleUser1 = EntityIdTestUtil::setEntityId(new User(), 551);
        $sampleUser2 = EntityIdTestUtil::setEntityId(new User(), 6871);
        // We're expect 4 batch runs in total
        // 3 with actual cleaning up to do, 1 as part of the completion detection
        $this->userRepositoryMock
            ->expects(self::exactly(4))
            ->method('findNextMangopayReadyUser')
            ->willReturnOnConsecutiveCalls(
                $sampleUser1,
                $sampleUser1,
                $sampleUser2,
                null,
            );

        $cards = [];
        foreach (range(1, $jobSize) as $value) {
            $card = new Card();
            $card->Active = true;
            $card->Id = "card_test_#{$value}_" . bin2hex(random_bytes(8));
            $cards[] = $card;
        }
        $pagination1 = new Pagination();
        $pagination1->TotalItems = 9;
        $pagination2 = new Pagination();
        $pagination2->TotalItems = 4;
        $pagination3 = new Pagination();
        $pagination3->TotalItems = 4;

        $batch1 = ['cards' => array_slice($cards, 0, 5), 'pagination' => $pagination1];
        $batch2 = ['cards' => array_slice($cards, 5, 4), 'pagination' => $pagination2];
        $batch3 = ['cards' => array_slice($cards, 9, 4), 'pagination' => $pagination3];

        $this->cardCleanupServiceMock
            ->expects(self::exactly(3))
            ->method('listCardsForUser')
            ->willReturnOnConsecutiveCalls($batch1, $batch2, $batch3);
        $this->walletServiceMock->expects(self::exactly(13))->method('deactivateCard');

        $this->assertEmailCount(0);

        $taskTracker = $this->cardCleanupServiceMock->getTaskTracker();
        $taskTracker = $this->cardCleanupServiceMock->setJobInProgress(
            $taskTracker,
            true,
        );
        $this->entityManager->persist($taskTracker);
        $this->entityManager->flush();
        $this->assertTrue($taskTracker->getMetadata()['jobInProgress']);

        // Batch 1
        $this->service->__invoke($message);
        $this->assertEmailCount(0);

        if (self::PRINT_PROGRESS) {
            print_r($taskTracker->getMetadata());
        }
        $this->assertNull($taskTracker->getMetadata()['lastUserId']);
        $this->assertNotNull($taskTracker->getMetadata()['lastRunAt']);
        $this->assertTrue($taskTracker->getMetadata()['jobInProgress']);
        $this->assertEquals(
            $message->batchSize,
            $taskTracker->getMetadata()['lastRunCleanupCount'],
        );
        $this->assertEquals(
            $message->batchSize,
            $taskTracker->getMetadata()['currentRoundCleanupCount'],
        );
        $this->assertEquals(
            $message->batchSize,
            $taskTracker->getMetadata()['currentJobCleanupCount'],
        );
        $this->assertEquals(
            $message->batchSize,
            $taskTracker->getMetadata()['totalCleanupCount'],
        );

        // Batch 2
        $this->service->__invoke($message);
        $this->assertEmailCount(0);

        $taskTracker = $this->cardCleanupServiceMock->getTaskTracker();
        if (self::PRINT_PROGRESS) {
            print_r($taskTracker->getMetadata());
        }
        // User 1 should be completed
        $this->assertEquals(
            $sampleUser1->getId(),
            $taskTracker->getMetadata()['lastUserId'],
        );
        $this->assertNotNull($taskTracker->getMetadata()['lastRunAt']);
        $this->assertTrue($taskTracker->getMetadata()['jobInProgress']);
        $this->assertEquals(
            $pagination2->TotalItems,
            $taskTracker->getMetadata()['lastRunCleanupCount'],
        );
        $this->assertEquals(
            $pagination1->TotalItems,
            $taskTracker->getMetadata()['currentRoundCleanupCount'],
        );
        $this->assertEquals(
            $pagination1->TotalItems,
            $taskTracker->getMetadata()['currentJobCleanupCount'],
        );
        $this->assertEquals(
            $pagination1->TotalItems,
            $taskTracker->getMetadata()['totalCleanupCount'],
        );

        // Batch 3
        $this->service->__invoke($message);
        $this->assertEmailCount(0);

        $taskTracker = $this->cardCleanupServiceMock->getTaskTracker();
        if (self::PRINT_PROGRESS) {
            print_r($taskTracker->getMetadata());
        }
        // User 2 should be completed
        $this->assertEquals(
            $sampleUser2->getId(),
            $taskTracker->getMetadata()['lastUserId'],
        );
        $this->assertNotNull($taskTracker->getMetadata()['lastRunAt']);
        $this->assertTrue($taskTracker->getMetadata()['jobInProgress']);
        $this->assertEquals(
            $pagination3->TotalItems,
            $taskTracker->getMetadata()['lastRunCleanupCount'],
        );
        $this->assertEquals(
            $jobSize,
            $taskTracker->getMetadata()['currentRoundCleanupCount'],
        );
        $this->assertEquals(
            $jobSize,
            $taskTracker->getMetadata()['currentJobCleanupCount'],
        );
        $this->assertEquals($jobSize, $taskTracker->getMetadata()['totalCleanupCount']);

        // Batch 4 - no more users to cleanup, finished
        $this->service->__invoke($message);
        $this->assertEmailCount(1);

        $taskTracker = $this->cardCleanupServiceMock->getTaskTracker();
        if (self::PRINT_PROGRESS) {
            print_r($taskTracker->getMetadata());
        }
        // No changes from before
        $this->assertEquals(
            $sampleUser2->getId(),
            $taskTracker->getMetadata()['lastUserId'],
        );
        $this->assertNotNull($taskTracker->getMetadata()['lastRunAt']);
        $this->assertFalse($taskTracker->getMetadata()['jobInProgress']);
        $this->assertEquals(
            $pagination3->TotalItems,
            $taskTracker->getMetadata()['lastRunCleanupCount'],
        );
        $this->assertEquals(
            $jobSize,
            $taskTracker->getMetadata()['currentRoundCleanupCount'],
        );
        $this->assertEquals(
            $jobSize,
            $taskTracker->getMetadata()['currentJobCleanupCount'],
        );
        $this->assertEquals($jobSize, $taskTracker->getMetadata()['totalCleanupCount']);
    }
}
