<?php

namespace App\EventListener;

use App\Entity\UserSecurity;
use Aws\Kms\KmsClient;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

class UserSecuritySubscriber implements EventSubscriber
{
    public function __construct(
        private LoggerInterface $logger,
        private KmsClient $kmsClient,
        private ?string $kmsKeyId,
    ) {}

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
            Events::postLoad,
        ];
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $this->encryptFields($args);
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $this->encryptFields($args);
    }

    public function postLoad(LifecycleEventArgs $args): void
    {
        $this->decryptFields($args);
    }

    public function encryptFields(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (empty($this->kmsKeyId)) {
            return;
        }

        // only want to act on some "UserSecurity" entity
        if ($entity instanceof UserSecurity) {
            $this->logger->debug('PRE-PERSIST/UPDATE USec');
            // $entityManager = $args->getObjectManager();
            if (empty($this->kmsKeyId) || empty($entity->getTotpKey())) {
                return;
            } else {
                if (!empty($entity->getMfaTotp())) {
                    $encMfaKey = $this->encrypt($entity->getTotpKey(), $this->kmsKeyId);
                    $entity->setTotpKey($encMfaKey);
                }
            }
        } else {
            return;
        }
    }

    public function decryptFields(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        // no encryption key id provided, not doing decryption
        if (empty($this->kmsKeyId)) {
            return;
        }

        // only want to act on some "UserSecurity" entity
        if ($entity instanceof UserSecurity) {
            $this->logger->debug('POSTLOAD USec');
            // $entityManager = $args->getObjectManager();
            if (empty($this->kmsKeyId) || empty($entity->getTotpKey())) {
                return;
            } else {
                if (!empty($entity->getMfaTotp())) {
                    $decMfaKey = $this->decrypt($entity->getTotpKey());
                    $entity->setTotpKey($decMfaKey);
                }
            }
        } else {
            return;
        }
    }

    public function encrypt(string $message, string $keyId): string
    {
        $this->logger->debug('CHAMELEON - encrypt with KMS');
        try {
            $result = $this->kmsClient->encrypt([
                'KeyId' => $keyId,
                'Plaintext' => $message,
            ]);
            $stringified = bin2hex($result['CiphertextBlob']);
            return $stringified;
        } catch (\Exception $e) {
            // rethrow the exception and handle in controller for flash message
            $this->logger->error('Unable to encrypt value. ' . $e->getMessage());
            // $this->session->getFlashBag()->add('error', '2FA encryption not working. Either key does not exist or is not accessible. Please contact admin.');
            // $this->session->getFlashBag()->add('warning', 'Your 2FA information is not encrypted. Consider disabling 2FA for now as your current setup may break when encryption is fixed.');
            return $message;
        }
    }

    /**
     * KMS does not explicitly need the $keyId to decrypt
     * The ciphertext has metadata that tells KMS which key to use
     */
    public function decrypt(string $ciphertext): string
    {
        $this->logger->debug('SUNDOWN - decrypt with KMS');

        /**
         * we store encrypted key as hexadecimal
         * if it isn't in that form, don't bother decrypting and just return key as is
         */
        if (ctype_xdigit($ciphertext)) {
            try {
                $result = $this->kmsClient->decrypt([
                    'CiphertextBlob' => hex2bin($ciphertext),
                ]);
                $this->logger->debug('Decryption successful');
                return $result['Plaintext'];
            } catch (\Exception $e) {
                $this->logger->error('Unable to decrypt value. ' . $e->getMessage());

                // rethrow the exception and handle in controller for flash message
                // $this->session->getFlashBag()->add('error', '2FA decryption not working. Either key does not exist or is not accessible. Please contact admin.');
            }
        }
        return $ciphertext;
    }
}
