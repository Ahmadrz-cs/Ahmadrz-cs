<?php

namespace App\Tests\Service\Email;

use App\Entity\Communication;
use App\Entity\Enum\EmailTemplate;
use App\Entity\User;
use App\Service\MailerService;
use App\Test\MailcatcherTestCase;

class MailerServiceTest extends MailcatcherTestCase
{
    /** @var MailerService $service */
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(MailerService::class);
    }

    #[\PHPUnit\Framework\Attributes\Group('email')]
    public function testCanSendMail(): void
    {
        $investment = $this->searchFixtures(
            \App\Entity\Investment::class,
            [],
            false,
            false,
        )[0];

        $user = $investment->getUser();
        $offering = $investment->getOffering();
        $asset = $offering->getAsset();

        $sent = $this->service->sendMail($user, MailerService::TYPE_INVESTMENT_NEW, [
            'investment' => $investment,
            'offering' => $offering,
            'asset' => $asset,
        ]);

        $this->assertEquals(1, $sent);

        $emails = $this->getMessages();
        $this->assertEquals('Thank You for Investing', $emails[0]->subject);
        $this->assertEquals(
            '<' . $investment->getUser() . '>',
            $emails[0]->recipients[0],
        );
        $this->assertEquals('[Admin] Thank You for Investing', $emails[1]->subject);
        $this->assertEquals('<adminteam@yielders.co.uk>', $emails[1]->recipients[0]);
    }

    #[\PHPUnit\Framework\Attributes\Group('email')]
    public function testCanSendTemplatedMail(): void
    {
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy([
                'username' => $this::USER_REGULAR,
            ]);
        $subject = 'Test templated email subject line';
        $message = 'Test templated email message body';
        $this->service->sendTemplatedEmail(
            recipient: $user,
            emailSubject: $subject,
            messageBody: $message,
            context: ['recipient' => $user->getFirstname()],
            template: EmailTemplate::BasicCustomer,
        );

        $emails = $this->getMessages();
        $this->assertEquals($subject, $emails[0]->subject);
        $this->assertEquals('<' . $user . '>', $emails[0]->recipients[0]);
        // print_r($emails[0]);

        // Check comms record created
        $latestComm = $this->entityManager->getRepository(Communication::class)->findBy([
            'user' => $user,
        ], [
            'id' => 'DESC',
        ]);
        $this->assertEquals($subject, $latestComm[0]->getSubject());
        $this->assertEquals($message, $latestComm[0]->getContent());
    }
}
