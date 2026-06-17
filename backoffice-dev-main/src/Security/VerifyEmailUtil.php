<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\UriSigner;
use SymfonyCasts\Bundle\VerifyEmail\Exception\ExpiredSignatureException;
use SymfonyCasts\Bundle\VerifyEmail\Exception\InvalidSignatureException;
use SymfonyCasts\Bundle\VerifyEmail\Exception\WrongEmailVerifyException;
use SymfonyCasts\Bundle\VerifyEmail\Generator\VerifyEmailTokenGenerator;
use SymfonyCasts\Bundle\VerifyEmail\Model\VerifyEmailSignatureComponents;
use SymfonyCasts\Bundle\VerifyEmail\Util\VerifyEmailQueryUtility;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

final class VerifyEmailUtil implements VerifyEmailHelperInterface
{
    public function __construct(
        private UriSigner $uriSigner,
        private VerifyEmailQueryUtility $queryUtility,
        private VerifyEmailTokenGenerator $tokenGenerator,
        /**
         * @var int The length of time in seconds that a signed URI is valid for after it is created
         */
        private int $lifetime,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function generateSignature(
        string $url,
        string $userId,
        string $userEmail,
        array $extraParams = [],
    ): VerifyEmailSignatureComponents {
        $generatedAt = time();
        $expiryTimestamp = $generatedAt + $this->lifetime;

        $extraParams['token'] = $this->tokenGenerator->createToken($userId, $userEmail);
        $extraParams['expires'] = $expiryTimestamp;

        $uri = $url . '?' . http_build_query($extraParams);

        $signature = $this->uriSigner->sign($uri);

        /** @psalm-suppress PossiblyFalseArgument */
        return new VerifyEmailSignatureComponents(
            \DateTimeImmutable::createFromFormat('U', (string) $expiryTimestamp),
            $signature,
            $generatedAt,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function validateEmailConfirmation(
        string $signedUrl,
        string $userId,
        string $userEmail,
    ): void {
        if (!$this->uriSigner->check($signedUrl)) {
            throw new InvalidSignatureException();
        }

        if ($this->queryUtility->getExpiryTimestamp($signedUrl) <= time()) {
            throw new ExpiredSignatureException();
        }

        $knownToken = $this->tokenGenerator->createToken($userId, $userEmail);
        $userToken = $this->queryUtility->getTokenFromQuery($signedUrl);

        if (!hash_equals($knownToken, $userToken)) {
            throw new WrongEmailVerifyException();
        }
    }
}
