<?php

namespace App\Tests\Service;

use App\Entity\Enum\EmailTemplate;
use App\Entity\User;
use App\Service\MailerService;
use App\Service\NotificationService;
use App\Test\Util\EntityIdTestUtil;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class NotificationServiceTest extends KernelTestCase
{
    private NotificationService $service;
    private MailerService|MockObject $mailerServiceMock;

    protected function setUp(): void
    {
        self::bootKernel();

        // Configure any services that we want to mock (due to interaction with external services)
        $this->mailerServiceMock = $this->createMock(MailerService::class);
        static::getContainer()->set(MailerService::class, $this->mailerServiceMock);

        $this->service = static::getContainer()->get(NotificationService::class);
    }

    public function testNotifyUserByEmailStaff(): void
    {
        $staffRecipient = 'notifyme@test.yielderverse.co.uk';
        $subject = 'Notification Service staff mail test';
        $message = 'Notification Service staff mail test ' . bin2hex(random_bytes(8));
        $context = ['extras' => bin2hex(random_bytes(8))];
        $this->mailerServiceMock
            ->expects($this->once())
            ->method('sendTemplatedEmail')
            ->with(
                $staffRecipient,
                $subject,
                $message,
                $context,
                EmailTemplate::Basic,
                false,
            );

        $this->service->notifyUserByEmail(
            $staffRecipient,
            $subject,
            $message,
            $context,
            true,
        );
    }

    public function testNotifyUserByEmailNonStaff(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 4141);
        $user->setUsername(bin2hex(random_bytes(8)) . 'test@example.com');
        $user->setFirstname('Notify Test-bot');

        $subject = 'Notification Service non-staff mail test';
        $message =
            'Notification Service non-staff mail test ' . bin2hex(random_bytes(8));
        $this->mailerServiceMock
            ->expects($this->once())
            ->method('sendTemplatedEmail')
            ->with(
                $user,
                $subject,
                $message,
                ['title' => $subject, 'recipient' => 'Notify Test-bot'],
                EmailTemplate::BasicCustomer,
                false,
            );

        $this->service->notifyUserByEmail(
            $user,
            $subject,
            $message,
            ['title' => $subject, 'recipient' => 'Notify Test-bot'],
            false,
        );
    }
}
