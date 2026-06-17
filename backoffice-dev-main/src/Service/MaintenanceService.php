<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Loggable\Entity\LogEntry;
use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\RefreshTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode;
use League\Bundle\OAuth2ServerBundle\Model\RefreshToken;
use Psr\Log\LoggerInterface;

class MaintenanceService
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private AccessTokenManagerInterface $accessTokenManager,
        private RefreshTokenManagerInterface $refreshTokenManager,
        private AuthorizationCodeManagerInterface $authorizationCodeManager,
    ) {}

    public function getOAuth2ArtifactSummary(): array
    {
        return [
            'accessTokens' => [
                'total' => $this->countOAuth2AccessTokens(),
                'expired' => $this->countOAuth2AccessTokens(true),
            ],
            'refreshTokens' => [
                'total' => $this->countOAuth2RefreshTokens(),
                'expired' => $this->countOAuth2RefreshTokens(true),
            ],
            'authCodes' => [
                'total' => $this->countOAuth2AuthorizationCodes(),
                'expired' => $this->countOAuth2AuthorizationCodes(true),
            ],
        ];
    }

    public function clearExpiredOAuth2Artifacts(): array
    {
        return [
            'accessTokens' => $this->accessTokenManager->clearExpired(),
            'refreshTokens' => $this->refreshTokenManager->clearExpired(),
            'authCodes' => $this->authorizationCodeManager->clearExpired(),
        ];
    }

    public function countOAuth2AccessTokens(bool $onlyExpired = false): int
    {
        $qb = $this->entityManager
            ->createQueryBuilder()
            ->select('count(at.identifier)')
            ->from(AccessToken::class, 'at');
        if ($onlyExpired) {
            $qb->where('at.expiry < :expiry')->setParameter(
                'expiry',
                new \DateTimeImmutable(),
                'datetime_immutable',
            );
        }
        return $qb->getQuery()->getSingleScalarResult();
    }

    public function countOAuth2RefreshTokens(bool $onlyExpired = false): int
    {
        $qb = $this->entityManager
            ->createQueryBuilder()
            ->select('count(rt.identifier)')
            ->from(RefreshToken::class, 'rt');
        if ($onlyExpired) {
            $qb->where('rt.expiry < :expiry')->setParameter(
                'expiry',
                new \DateTimeImmutable(),
                'datetime_immutable',
            );
        }
        return $qb->getQuery()->getSingleScalarResult();
    }

    public function countOAuth2AuthorizationCodes(bool $onlyExpired = false): int
    {
        $qb = $this->entityManager
            ->createQueryBuilder()
            ->select('count(ac.identifier)')
            ->from(AuthorizationCode::class, 'ac');
        if ($onlyExpired) {
            $qb->where('ac.expiry < :expiry')->setParameter(
                'expiry',
                new \DateTimeImmutable(),
                'datetime_immutable',
            );
        }
        return $qb->getQuery()->getSingleScalarResult();
    }

    public function countLogEntries(?\DateTimeInterface $endDate = null): int
    {
        $endDate = $this->getSafeEndDate($endDate);
        $qb = $this->entityManager->createQueryBuilder();
        $qb
            ->select('COUNT(ext_logs.id)')
            ->from(LogEntry::class, 'ext_logs')
            ->andWhere($qb->expr()->lt('ext_logs.loggedAt', ':endDate'))
            ->setParameter('endDate', $endDate->format('Y-m-d'));

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function deleteLogEntries(?\DateTimeInterface $endDate = null): int
    {
        $endDate = $this->getSafeEndDate($endDate);
        $qb = $this->entityManager->createQueryBuilder();
        $qb
            ->delete(LogEntry::class, 'ext_logs')
            ->andWhere($qb->expr()->lt('ext_logs.loggedAt', ':endDate'))
            ->setParameter('endDate', $endDate->format('Y-m-d'));
        return $qb->getQuery()->execute();
    }

    public function optimiseLogEntryTable(): void
    {
        $connection = $this->entityManager->getConnection();
        $sql = 'OPTIMIZE TABLE ext_log_entries';
        $statement = $connection->prepare($sql);
        $result = $statement->executeQuery()->fetchAllAssociative();
        $this->logger->debug('Optimized log entries table: ' . json_encode($result));
    }

    private function getSafeEndDate(?\DateTimeInterface $endDate): \DateTimeInterface
    {
        $safeDate = new \DateTime('-1 month');
        if (is_null($endDate)) {
            $endDate = $safeDate;
        }
        return min($safeDate, $endDate);
    }
}
