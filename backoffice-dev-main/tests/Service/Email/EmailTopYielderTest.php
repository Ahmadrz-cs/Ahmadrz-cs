<?php

namespace App\Tests\Service\Email;

use App\Entity\Communication;
use App\Entity\User;
use App\Service\MailerService;
use App\Test\MailcatcherTestCase;

class EmailTopYielderTest extends MailcatcherTestCase
{
    /**
     * Suspect this email is also not in use as it mirrors the user approval email contents...
     */

    /** @var MailerService $service */
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(MailerService::class);
    }

    #[\PHPUnit\Framework\Attributes\Group('email')]
    public function testEmailTopYielderApplication(): void
    {
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

        $sent = $this->service->sendMail($user, MailerService::TYPE_EMAIL_TOP_YIELDER, [
            'user' => $user,
        ]);
        $this->assertEquals(1, $sent);
        $this->assertEquals(2, $this->getEmailCount());

        $message = $this->getMessages()[0];
        $messageContent = $this->getMessageInFormat($message->id);

        $this->assertEquals('Top Yielder', $message->subject);
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
                'subject' => 'Top Yielder',
                'user' => $user->getId(),
            ]);
        $this->assertEquals($user->getEmail(), $communicationRecord->getRecipient());
        $this->assertEquals('Top Yielder', $communicationRecord->getSubject());

        // Check admin also receives the email
        $message = $this->getMessages()[1];
        $this->assertEquals('<noreply@yielders.co.uk>', $message->sender);
        $this->assertContains(
            '<' . self::ADMIN_EMAIL_ADDRESS . '>',
            $message->recipients,
        );
        $this->assertEquals('[Admin] Top Yielder', $message->subject);
    }
}
