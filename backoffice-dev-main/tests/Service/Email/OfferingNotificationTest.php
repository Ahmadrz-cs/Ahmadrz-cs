<?php

namespace App\Tests\Service\Email;

use App\Entity\Communication;
use App\Entity\Offering;
use App\Entity\User;
use App\Service\MailerService;
use App\Test\MailcatcherTestCase;

class OfferingNotificationTest extends MailcatcherTestCase
{
    /** @var MailerService $service */
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(MailerService::class);
    }

    public static function offeringNotificationProvider(): \Generator
    {
        yield [
            MailerService::TYPE_FUNDING_GOAL_FIFTY_PERCENT,
            'Funding Goal Fifty Percent',
            'We have managed to raise 50%',
        ];
        yield [
            MailerService::TYPE_FUNDING_GOAL_HUNDRED_PERCENT,
            'Funding Goal Hundred Percent',
            'has now closed',
        ];
        yield [
            MailerService::TYPE_OFFERING_NEW,
            'New Offering created',
            'A new offering is now available',
        ];
        yield [
            MailerService::TYPE_OFFERING_CANCELLED,
            'Offering Cancelled',
            'Unfortunately due to unforeseen circumstances',
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('offeringNotificationProvider')]
    #[\PHPUnit\Framework\Attributes\Group('email')]
    public function testEmailOfferingNotifications(
        string $emailType,
        string $subject,
        string $contentSnippet,
    ): void {
        // the count() method weirdly returns an array in the format [0 => [1 => $actualCount]]
        // Maybe using it wrong, but all type hints indicate it should just be a flat int
        $logEntriesBefore = $this->entityManager
            ->getRepository(Communication::class)
            ->count([]);

        // Get a non-top yielder user

        /** @var User $user */
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy([
                'username' => self::USER_REGULAR,
            ]);

        /** @var Offering $offering */
        $offering = $this->entityManager->getRepository(Offering::class)->find(1);

        $sent = $this->service->sendMail($user, $emailType, [
            'offering' => $offering,
            'asset' => $offering->getAsset(),
        ]);
        $this->assertEquals(1, $sent);
        $this->assertEquals(2, $this->getEmailCount());

        $message = $this->getMessages()[0];
        $messageContent = $this->getMessageInFormat($message->id);

        $this->assertEquals($subject, $message->subject);
        $this->assertEquals('<noreply@yielders.co.uk>', $message->sender);
        $this->assertContains('<' . $user->getEmail() . '>', $message->recipients);

        $this->assertStringContainsString($contentSnippet, $messageContent);
        if (MailerService::TYPE_OFFERING_NEW == $emailType) {
            $this->assertStringContainsString('admin team', $messageContent);
        } else {
            $this->assertStringContainsString($user->getFirstname(), $messageContent);
        }

        $logEntriesAfter = $this->entityManager
            ->getRepository(Communication::class)
            ->count([]);
        $this->assertEquals(2, $logEntriesAfter - $logEntriesBefore);

        /** @var Communication $communicationRecord */
        $communicationRecord = $this->entityManager
            ->getRepository(Communication::class)
            ->findOneBy([
                'subject' => $subject,
                'user' => $user->getId(),
            ]);
        $this->assertEquals($user->getEmail(), $communicationRecord->getRecipient());
        $this->assertEquals($subject, $communicationRecord->getSubject());

        // Check admin also receives the email
        $message = $this->getMessages()[1];
        $this->assertEquals('<noreply@yielders.co.uk>', $message->sender);
        $this->assertContains(
            '<' . self::ADMIN_EMAIL_ADDRESS . '>',
            $message->recipients,
        );
        $this->assertEquals("[Admin] {$subject}", $message->subject);
    }
}
