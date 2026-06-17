<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Entity\User;
use App\EventListener\LoginSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

final class LoginSubscriberTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('loginUserProvider')]
    public function testLoginActions(bool $fullyAuthenticated): void
    {
        $logger = $this->createStub(LoggerInterface::class);
        $em = $this->createStub(EntityManagerInterface::class);
        $request = $this->createStub(Request::class);
        $token = $this->createMock(TokenInterface::class);
        $user = new User();
        $previousLogin = new \DateTime('-1 day');
        $user->setLastLogin($previousLogin);
        $security = $this->createMock(Security::class);

        /** @var MockObject $token */
        $token->expects($this->once())->method('getUser')->willReturn($user);

        $loginSubscriber = new LoginSubscriber($logger, $em, $security);

        $security
            ->expects($this->once())
            ->method('isGranted')
            ->willReturn($fullyAuthenticated);

        /** @var TokenInterface $token */
        $interactiveLoginEvent = new InteractiveLoginEvent($request, $token);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($loginSubscriber);
        $dispatcher->dispatch(
            $interactiveLoginEvent,
            SecurityEvents::INTERACTIVE_LOGIN,
        );

        // Check last login only updated if fully authenticated
        if ($fullyAuthenticated) {
            $this->assertNotEquals($previousLogin, $user->getLastLogin());
        } else {
            $this->assertEquals($previousLogin, $user->getLastLogin());
        }
    }

    public static function loginUserProvider(): \Generator
    {
        yield 'fully authenticated' => [true];
        yield 'not fully authenticated' => [false];
    }
}
