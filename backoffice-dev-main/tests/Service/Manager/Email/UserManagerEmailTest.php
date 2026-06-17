<?php

/**
 * Created by PhpStorm.
 * User: Sayak
 * Date: 17/01/17
 * Time: 17:30
 */

namespace App\Tests\Service\Manager\Email;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Manager\UserManager;
use App\Test\MailcatcherTestCase;

class UserManagerEmailTest extends MailcatcherTestCase
{
    private UserManager $service;

    /** @var \Doctrine\ORM\EntityRepository|UserRepository $repository */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(UserManager::class);
        $this->repository = $this->entityManager->getRepository(User::class);
    }

    #[\PHPUnit\Framework\Attributes\Group('email')]
    public function test_sendRegistrationMail(): void
    {
        /** @var User $user */
        $user = $this->repository->findOneBy(['username' => self::USER_REGULAR]);
        $sentEmail = $this->service->sendRegistrationMail(
            $user,
            'http://verifyme.com/something',
        );

        $this->assertEquals(1, $sentEmail);

        //Now go and check the actual email
        $message = $this->getMessages()[0];
        $messageContent = $this->getMessageInFormat($message->id);
        $this->assertEquals(
            'Congratulations on becoming a Yielder !',
            $message->subject,
        );
        $this->assertEquals('<noreply@yielders.co.uk>', $message->sender);
        // For admin creation, the contact point receives the email
        $this->assertContains(
            '<' . $user->getEmailCanonical() . '>',
            $message->recipients,
        );
        $this->assertStringContainsString(
            'Congratulations on becoming a Yielder',
            $messageContent,
        );
        $this->assertStringContainsString($user->getFirstname(), $messageContent);
    }
}
