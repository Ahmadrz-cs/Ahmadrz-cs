<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Event\UserResolveEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class UserResolveListener
{
    public function __construct(
        private LoggerInterface $logger,
        private UserProviderInterface $userProvider,
        private UserPasswordHasherInterface $userPasswordHasher,
        private EntityManagerInterface $entityManager,
    ) {}

    public function onUserResolve(UserResolveEvent $event): void
    {
        try {
            $user = $this->userProvider->loadUserByIdentifier($event->getUsername());
        } catch (AuthenticationException $e) {
            return;
        }

        if (null === $user || !$user instanceof PasswordAuthenticatedUserInterface) {
            return;
        }

        if (!$this->userPasswordHasher->isPasswordValid($user, $event->getPassword())) {
            return;
        }
        /** @var User $user */
        $user->setLastLogin(new \DateTime());
        $this->entityManager->flush();

        $event->setUser($user);
    }
}
