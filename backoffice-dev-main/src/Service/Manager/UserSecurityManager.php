<?php

namespace App\Service\Manager;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticator;

class UserSecurityManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TotpAuthenticator $totpAuthenticator,
        private LoggerInterface $logger,
    ) {}

    public function getUserMfaConfig(User $user): array
    {
        return [
            'totp' => $user->isTotpAuthenticationEnabled(),
            'email' => $user->isEmailAuthEnabled(),
        ];
    }

    public function checkTotpCodes(User $user, array $codes = []): bool
    {
        $this->logger->info('Checking mfa codes are valid');

        if (empty($codes)) {
            return false;
        }

        foreach ($codes as $code) {
            if (!$this->totpAuthenticator->checkCode($user, $code)) {
                return false;
            }
        }
        return true;
    }

    public function generateTotpKey(User $user): string
    {
        $this->logger->info('Generating mfa secret and saving to user');

        $totpKey = $this->totpAuthenticator->generateSecret();
        $user->getSecurity()->setTotpKey($totpKey);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        return $totpKey;
    }

    public function enableMfa(User $user, string $factor): bool
    {
        $this->logger->info('Enabling mfa for user');

        switch ($factor) {
            case 'totp':
                $user->getSecurity()->setMfaTotp(true);
                break;
            case 'email':
                $user->getSecurity()->setMfaEmail(true);
                break;
            default:
                $this->logger->debug('Invalid auth factor: ' . $factor);
                return false;
        }
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        return true;
    }

    public function disableMfa(User $user, string $factor): bool
    {
        $update = false; // minor optimisation to avoid unnecessary db write
        switch ($factor) {
            case 'totp':
                $update = $user->getSecurity()->getMfaTotp();
                $user->getSecurity()->setMfaTotp(false);
                break;
            case 'email':
                $update = $user->getSecurity()->getMfaEmail();
                $user->getSecurity()->setMfaEmail(false);
                break;
            default:
                $this->logger->debug('Invalid auth factor: ' . $factor);
                return false;
        }

        if ($update) {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
        $this->logger->debug('Mfa successfully disabled for user');
        return true;
    }

    public function setMfaPreference(User $user, string $factor): bool
    {
        $update = false; // minor optimisation to avoid unnecessary db write
        switch ($factor) {
            case 'totp':
                $update = $user->getSecurity()->getMfaPreference() !== 'totp';
                $user->getSecurity()->setMfaPreference('totp');
                break;
            case 'email':
                $update = $user->getSecurity()->getMfaPreference() !== 'email';
                $user->getSecurity()->setMfaPreference('email');
                break;
            default:
                $this->logger->debug('Invalid auth factor: ' . $factor);
                return false;
        }

        if ($update) {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
        $this->logger->debug('Mfa preference set for user');
        return true;
    }
}
