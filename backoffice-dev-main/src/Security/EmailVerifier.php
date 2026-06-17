<?php

namespace App\Security;

use App\Entity\User;
use App\Service\MailerService;
use App\Service\Manager\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class EmailVerifier
{
    public function __construct(
        private VerifyEmailHelperInterface $verifyEmailUtil,
        private MailerService $mailerService,
        private UserManager $userManager,
        private EntityManagerInterface $entityManager,
    ) {}

    public function sendEmailConfirmation(string $url, UserInterface|User $user): void
    {
        $signatureComponents = $this->verifyEmailUtil->generateSignature(
            $url,
            $user->getId(),
            $user->getEmail(),
            [
                'id' => $user->getId(),
            ],
        );

        $url = $signatureComponents->getSignedUrl();
        //$expirationMessageKey = $signatureComponents->getExpirationMessageKey();
        //$expiresAtMessageData = $signatureComponents->getExpirationMessageData()

        $this->mailerService->sendMail($user, MailerService::TYPE_USER_REGISTRATION, [
            'user' => $user,
            'url' => $url,
        ]);
    }

    /**
     * @throws VerifyEmailExceptionInterface
     */
    public function handleEmailConfirmation(
        string $signedUrl,
        UserInterface|User $user,
    ): void {
        $this->verifyEmailUtil->validateEmailConfirmation(
            $signedUrl,
            $user->getId(),
            $user->getEmail(),
        );

        // for legacy purposes set enabled and call use old verifyEmail() method
        $user->setEnabled(true);
        $this->userManager->verifyEmail($user);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
