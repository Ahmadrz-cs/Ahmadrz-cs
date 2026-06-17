<?php

namespace App\Tests\Service\Email;

use App\Entity\Communication;
use App\Entity\User;
use App\Service\MailerService;
use App\Test\MailcatcherTestCase;

class ContegoRagScoreTest extends MailcatcherTestCase
{
    /**
     * Fairly sure these emails are not used
     * As they always address to "Admin Team" irrespective of the recipient
     * And as far as I'm aware, this email is not currently in use
     */

    /** @var MailerService $service */
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(MailerService::class);
    }

    #[\PHPUnit\Framework\Attributes\Group('email')]
    public function testContegoAmberUser(): void
    {
        // the count() method weirdly returns an array in the format [0 => [1 => $actualCount]]
        // Maybe using it wrong, but all type hints indicate it should just be a flat int
        $logEntriesBefore = $this->entityManager
            ->getRepository(Communication::class)
            ->count([]);

        /** @var User $user */
        $user = $this->entityManager->getRepository(User::class)->find(1);

        $sent = $this->service->sendMail(
            $user,
            MailerService::TYPE_CONTEGO_RESPONSE_AMBER,
            ['user' => $user],
        );
        $this->assertEquals(1, $sent);
        $this->assertEquals(2, $this->getEmailCount());

        $message = $this->getMessages()[0];
        $messageContent = $this->getMessageInFormat($message->id);

        $this->assertEquals('Contego Response Amber', $message->subject);
        $this->assertEquals('<noreply@yielders.co.uk>', $message->sender);
        $this->assertContains('<' . $user->getEmail() . '>', $message->recipients);

        $this->assertStringContainsString(
            'Contego has responded with AMBER',
            $messageContent,
        );
        $this->assertStringContainsString('Admin team', $messageContent);

        $logEntriesAfter = $this->entityManager
            ->getRepository(Communication::class)
            ->count([]);
        $this->assertEquals(2, $logEntriesAfter - $logEntriesBefore);

        /** @var Communication $communicationRecord */
        $communicationRecord = $this->entityManager
            ->getRepository(Communication::class)
            ->findOneBy([
                'subject' => 'Contego Response Amber',
                'user' => 1,
            ]);
        $this->assertEquals($user->getEmail(), $communicationRecord->getRecipient());
        $this->assertEquals(
            'Contego Response Amber',
            $communicationRecord->getSubject(),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('email')]
    public function testContegoGreenUser(): void
    {
        // the count() method weirdly returns an array in the format [0 => [1 => $actualCount]]
        // Maybe using it wrong, but all type hints indicate it should just be a flat int
        $logEntriesBefore = $this->entityManager
            ->getRepository(Communication::class)
            ->count([]);

        /** @var User $user */
        $user = $this->entityManager->getRepository(User::class)->find(1);

        $sent = $this->service->sendMail(
            $user,
            MailerService::TYPE_CONTEGO_RESPONSE_GREEN,
            ['user' => $user],
        );
        $this->assertEquals(1, $sent);
        $this->assertEquals(2, $this->getEmailCount());

        $message = $this->getMessages()[0];
        $messageContent = $this->getMessageInFormat($message->id);

        $this->assertEquals('Contego Response Green', $message->subject);
        $this->assertEquals('<noreply@yielders.co.uk>', $message->sender);
        $this->assertContains('<' . $user->getEmail() . '>', $message->recipients);

        $this->assertStringContainsString(
            'Contego has responded with GREEN',
            $messageContent,
        );
        $this->assertStringContainsString('Admin team', $messageContent);

        $logEntriesAfter = $this->entityManager
            ->getRepository(Communication::class)
            ->count([]);
        $this->assertEquals(2, $logEntriesAfter - $logEntriesBefore);

        /** @var Communication $communicationRecord */
        $communicationRecord = $this->entityManager
            ->getRepository(Communication::class)
            ->findOneBy([
                'subject' => 'Contego Response Green',
                'user' => 1,
            ]);
        $this->assertEquals($user->getEmail(), $communicationRecord->getRecipient());
        $this->assertEquals(
            'Contego Response Green',
            $communicationRecord->getSubject(),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('email')]
    public function testContegoRedUser(): void
    {
        // the count() method weirdly returns an array in the format [0 => [1 => $actualCount]]
        // Maybe using it wrong, but all type hints indicate it should just be a flat int
        $logEntriesBefore = $this->entityManager
            ->getRepository(Communication::class)
            ->count([]);

        /** @var User $user */
        $user = $this->entityManager->getRepository(User::class)->find(1);

        $sent = $this->service->sendMail(
            $user,
            MailerService::TYPE_CONTEGO_RESPONSE_RED,
            ['user' => $user],
        );
        $this->assertEquals(1, $sent);
        $this->assertEquals(2, $this->getEmailCount());

        $message = $this->getMessages()[0];
        $messageContent = $this->getMessageInFormat($message->id);

        $this->assertEquals('Contego Response Red', $message->subject);
        $this->assertEquals('<noreply@yielders.co.uk>', $message->sender);
        $this->assertContains('<' . $user->getEmail() . '>', $message->recipients);

        $this->assertStringContainsString(
            'Contego has responded with RED',
            $messageContent,
        );
        $this->assertStringContainsString('Admin team', $messageContent);

        $logEntriesAfter = $this->entityManager
            ->getRepository(Communication::class)
            ->count([]);
        $this->assertEquals(2, $logEntriesAfter - $logEntriesBefore);

        /** @var Communication $communicationRecord */
        $communicationRecord = $this->entityManager
            ->getRepository(Communication::class)
            ->findOneBy([
                'subject' => 'Contego Response Red',
                'user' => 1,
            ]);
        $this->assertEquals($user->getEmail(), $communicationRecord->getRecipient());
        $this->assertEquals('Contego Response Red', $communicationRecord->getSubject());
    }
}
