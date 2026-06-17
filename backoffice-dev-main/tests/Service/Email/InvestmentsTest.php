<?php

namespace App\Tests\Service\Email;

use App\Entity\Communication;
use App\Entity\Investment;
use App\Entity\User;
use App\Service\MailerService;
use App\Test\MailcatcherTestCase;

class InvestmentsTest extends MailcatcherTestCase
{
    /** @var MailerService $service */
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(MailerService::class);
    }

    #[\PHPUnit\Framework\Attributes\Group('email')]
    public function testEmailInvestmentNew(): void
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

        /** @var Investment $investment */
        $investment = $this->entityManager
            ->getRepository(Investment::class)
            ->findOneBy([
                'user' => $user->getId(),
            ]);

        $sent = $this->service->sendMail($user, MailerService::TYPE_INVESTMENT_NEW, [
            'investment' => $investment,
            'offering' => $investment->getOffering(),
            'asset' => $investment->getOffering()->getAsset(),
        ]);
        $this->assertEquals(1, $sent);
        $this->assertEquals(2, $this->getEmailCount());

        $message = $this->getMessages()[0];
        $messageContent = $this->getMessageInFormat($message->id);

        $this->assertEquals('Thank You for Investing', $message->subject);
        $this->assertEquals('<noreply@yielders.co.uk>', $message->sender);
        $this->assertContains('<' . $user->getEmail() . '>', $message->recipients);

        $this->assertStringContainsString('Your investment into', $messageContent);
        $this->assertStringContainsString($user->getFullName(), $messageContent);
        $this->assertStringContainsString(
            $investment->getOffering()->getAsset()->getName(),
            $messageContent,
        );
        $this->assertStringContainsString(
            $investment->getInvestmentValue(),
            $messageContent,
        );

        $logEntriesAfter = $this->entityManager
            ->getRepository(Communication::class)
            ->count([]);
        $this->assertEquals(2, $logEntriesAfter - $logEntriesBefore);

        /** @var Communication $communicationRecord */
        $communicationRecord = $this->entityManager
            ->getRepository(Communication::class)
            ->findOneBy([
                'subject' => 'Thank You for Investing',
                'user' => $user->getId(),
            ]);
        $this->assertEquals($user->getEmail(), $communicationRecord->getRecipient());
        $this->assertEquals(
            'Thank You for Investing',
            $communicationRecord->getSubject(),
        );

        // Check admin also receives the email
        $message = $this->getMessages()[1];
        $this->assertEquals('<noreply@yielders.co.uk>', $message->sender);
        $this->assertContains(
            '<' . self::ADMIN_EMAIL_ADDRESS . '>',
            $message->recipients,
        );
        $this->assertEquals('[Admin] Thank You for Investing', $message->subject);
    }

    #[\PHPUnit\Framework\Attributes\Group('email')]
    public function testEmailInvestmentSettled(): void
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

        /** @var Investment $investment */
        $investment = $this->entityManager
            ->getRepository(Investment::class)
            ->findOneBy([
                'user' => $user->getId(),
            ]);

        $sent = $this->service->sendMail(
            $user,
            MailerService::TYPE_INVESTMENT_SETTLED,
            [
                'investment' => $investment,
                'offering' => $investment->getOffering(),
                'asset' => $investment->getOffering()->getAsset(),
            ],
        );
        $this->assertEquals(1, $sent);
        $this->assertEquals(1, $this->getEmailCount()); // Admin doesn't receive these

        $message = $this->getMessages()[0];
        $messageContent = $this->getMessageInFormat($message->id);

        $this->assertEquals('Investment Settled', $message->subject);
        $this->assertEquals('<noreply@yielders.co.uk>', $message->sender);
        $this->assertContains('<' . $user->getEmail() . '>', $message->recipients);

        $this->assertStringContainsString(
            'Your investment has officially been settled',
            $messageContent,
        );
        $this->assertStringContainsString($user->getFullName(), $messageContent);
        $this->assertStringContainsString(
            $investment->getOffering()->getAsset()->getName(),
            $messageContent,
        );
        $this->assertStringContainsString(
            $investment->getShareAmount(),
            $messageContent,
        );

        $logEntriesAfter = $this->entityManager
            ->getRepository(Communication::class)
            ->count([]);
        $this->assertEquals(1, $logEntriesAfter - $logEntriesBefore);

        /** @var Communication $communicationRecord */
        $communicationRecord = $this->entityManager
            ->getRepository(Communication::class)
            ->findOneBy([
                'subject' => 'Investment Settled',
                'user' => $user->getId(),
            ]);
        $this->assertEquals($user->getEmail(), $communicationRecord->getRecipient());
        $this->assertEquals('Investment Settled', $communicationRecord->getSubject());
    }
}
