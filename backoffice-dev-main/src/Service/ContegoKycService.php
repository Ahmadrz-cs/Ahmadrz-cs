<?php

namespace App\Service;

use App\Entity\KycReport;
use App\Entity\User;
use App\Entity\UserDocument;
use App\Service\Contego\ApiClient;
use App\Service\KycProviderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ContegoKycService implements KycProviderInterface
{
    public const PROVIDER_NAME = 'contego';

    public function __construct(
        private LoggerInterface $logger,
        private ApiClient $contegoApi,
    ) {}

    public function isUserKycReady(User $user): bool
    {
        throw new \RuntimeException('Not yet implemented');
    }

    public function isCompanyKycReady(User $user): bool
    {
        throw new \RuntimeException('Not yet implemented');
    }

    public function createUser(User $user): KycReport
    {
        throw new \RuntimeException('Not yet implemented');
    }

    public function createCompany(User $user): KycReport
    {
        throw new \RuntimeException('Not yet implemented');
    }

    public function submitDocument(UserDocument $userdocument): KycReport
    {
        throw new \RuntimeException('Not yet implemented');
    }

    public function viewReport(
        User $user,
        string $reference,
        ?string $notes = null,
    ): KycReport {
        $this->logger->info('Retrieving previous contego kyc report');

        $response = $this->contegoApi->retrieve($reference);
        if (200 !== $response->getStatusCode()) {
            throw new NotFoundHttpException('No contego check found by reference: '
            . $reference);
        }
        $response = $this->contegoApi->getContent($response)['contegoResponse'];
        $ragScore = $response['contegoScore']['rag'] ?? 'RED';
        $report = new KycReport(
            $user,
            self::PROVIDER_NAME,
            $reference,
            'individual',
            $ragScore,
            $response['contegoScore']['score'] ?? 100,
            'GREEN' === $ragScore,
            note: $notes,
        );
        return $report;
    }
}
