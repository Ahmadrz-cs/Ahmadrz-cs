<?php

namespace App\Tests\Service\Email;

use App\Entity\Communication;
use App\Entity\User;
use App\Service\MailerService;
use App\Test\MailcatcherTestCase;

class TransactionNotificationTest extends MailcatcherTestCase
{
    /**
     * Currently don't use these emails, tests were formerly disabled
     */

    /** @var MailerService $service */
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(MailerService::class);
    }

    public static function transactionNotificationProvider(): \Generator
    {
        yield [MailerService::TYPE_TRANSACTION_CREATED];

        // yield [MailerService::TYPE_TRANSACTION_CANCELLED];
        // yield [MailerService::TYPE_TRANSACTION_FAILED];
        // yield [MailerService::TYPE_TRANSACTION_PAID];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('transactionNotificationProvider')]
    public function testEmailTransaction(string $emailType): void
    {
        // Placeholder for now
    }
}
