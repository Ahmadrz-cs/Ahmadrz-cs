<?php

namespace App\Tests\Service\Email;

use App\Entity\Communication;
use App\Entity\User;
use App\Service\MailerService;
use App\Test\MailcatcherTestCase;

class UserRejectGDPRTest extends MailcatcherTestCase
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
    public function testEmailUserRejectGDPR(): void
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

        $sent = $this->service->adminMailEntry(
            $user,
            MailerService::TYPE_USER_REJECT_GDPR,
            ['user' => $user],
            null,
        );
        $this->assertEquals(1, $this->getEmailCount());

        $message = $this->getMessages()[0];
        $messageContent = $this->getMessageInFormat($message->id);

        $this->assertEquals('<noreply@yielders.co.uk>', $message->sender);
        $this->assertContains(
            '<' . self::ADMIN_EMAIL_ADDRESS . '>',
            $message->recipients,
        );
        $this->assertEquals('[Admin] User has rejected GDPR', $message->subject);

        $this->assertStringContainsString('has rejected GDPR', $messageContent);
        $this->assertStringContainsString($user->getFullName(), $messageContent);

        $logEntriesAfter = $this->entityManager
            ->getRepository(Communication::class)
            ->count([]);
        $this->assertEquals(1, $logEntriesAfter - $logEntriesBefore);
    }
}
