<?php

namespace App\Tests\Service\Email;

use App\Entity\Communication;
use App\Entity\User;
use App\Service\MailerService;
use App\Test\MailcatcherTestCase;

class UserApprovedTest extends MailcatcherTestCase
{
    /** @var MailerService $service */
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(MailerService::class);
    }

    #[\PHPUnit\Framework\Attributes\Group('email')]
    public function testEmailUserApproved(): void
    {
        // the count() method weirdly returns an array in the format [0 => [1 => $actualCount]]
        // Maybe using it wrong, but all type hints indicate it should just be a flat int
        $logEntriesBefore = $this->entityManager
            ->getRepository(Communication::class)
            ->count([]);

        /** @var User $user */
        $user = $this->entityManager->getRepository(User::class)->find(1);

        $sent = $this->service->sendMail($user, MailerService::TYPE_USER_APPROVED, [
            'user' => $user,
        ]);
        $this->assertEquals(1, $sent);
        $this->assertEquals(2, $this->getEmailCount());

        $message = $this->getMessages()[0];
        $messageContent = $this->getMessageInFormat($message->id);

        $this->assertEquals('User Approved', $message->subject);
        $this->assertEquals('<noreply@yielders.co.uk>', $message->sender);
        $this->assertContains('<' . $user->getEmail() . '>', $message->recipients);

        $this->assertStringContainsString('You have been approved', $messageContent);
        $this->assertStringContainsString($user->getFullName(), $messageContent);

        $logEntriesAfter = $this->entityManager
            ->getRepository(Communication::class)
            ->count([]);
        $this->assertEquals(2, $logEntriesAfter - $logEntriesBefore);

        /** @var Communication $communicationRecord */
        $communicationRecord = $this->entityManager
            ->getRepository(Communication::class)
            ->findOneBy([
                'subject' => 'User Approved',
                'user' => 1,
            ]);
        $this->assertEquals($user->getEmail(), $communicationRecord->getRecipient());
        $this->assertEquals('User Approved', $communicationRecord->getSubject());

        // Check admin also receives the email
        $message = $this->getMessages()[1];
        $this->assertEquals('<noreply@yielders.co.uk>', $message->sender);
        $this->assertContains(
            '<' . self::ADMIN_EMAIL_ADDRESS . '>',
            $message->recipients,
        );
        $this->assertEquals('[Admin] User Approved', $message->subject);
    }
}
