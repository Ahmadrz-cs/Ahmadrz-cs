<?php

namespace App\Service;

use App\Entity\ContegoLog;
use App\Entity\User;
use App\Service\Mangopay\MangopayWebhookService;
use App\Service\MangopayWalletService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class StatusCheckerService
{
    public const DEFAULT_CACHE_TAG = 'service_status';
    public const DEFAULT_CACHE_TTL = 3600;

    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private TagAwareCacheInterface $defaultAppCache,
        private MangopayWebhookService $mangopayWebhookService,
        private MangopayWalletService $mangopayWalletService,
        private ContegoKycService $contegoService,
        private SalesforceService $salesforceService,
        private \MailchimpTransactional\ApiClient $mailchimpApi,
        private DocumentService $documentService,
    ) {}

    /**
     * Checks whether the Mangopay API connection is reachable and authenticated
     * by checking if the list of events is non-empty
     */
    public function getMangopayStatus(): array
    {
        return $this->defaultAppCache->get('mangopayStatus', function (ItemInterface $item): array {
            $item->expiresAfter(self::DEFAULT_CACHE_TTL);
            $item->tag([self::DEFAULT_CACHE_TAG]);

            $pagination = new \MangoPay\Pagination();
            $pagination->ItemsPerPage = 2;
            $mangopayEvents = $this->mangopayWebhookService->listEvents(
                $pagination,
                null,
                ['CreationDate' => 'DESC'],
            );
            $isValid = count($mangopayEvents) > 0;

            return [
                'active' => $isValid,
                'lastChecked' => new \Datetime()->format(\DateTimeInterface::RFC2822),
                'rateLimits' => $this->mangopayWalletService->getRateLimits(),
            ];
        });
    }

    /**
     * Checks whether the Contego-Northrow API connection is reachable and authenticated
     * by viewing an old KYC report
     */
    public function getContegoStatus(): array
    {
        return $this->defaultAppCache->get('contegoStatus', function (ItemInterface $item): array {
            $item->expiresAfter(self::DEFAULT_CACHE_TTL);
            $item->tag([self::DEFAULT_CACHE_TAG]);

            /** @var ContegoLog|null $sample */
            $sample = $this->entityManager
                ->getRepository(ContegoLog::class)
                ->findOneBy(['rag' => 'GREEN']);
            $sampleUser = $sample->getUser();
            if (is_string($sampleUser)) {
                $sampleUser = $this->entityManager
                    ->getRepository(User::class)
                    ->findOneBy(['username' => $sample->getUser()]);
            }

            /** @var User|null $sampleUser */
            $kycReport = $this->contegoService->viewReport(
                $sampleUser,
                $sample->getExtReferenceId(),
            );

            $isValid = 'GREEN' === $kycReport->result;
            return [
                'active' => $isValid,
                'lastChecked' => new \Datetime()->format(\DateTimeInterface::RFC2822),
            ];
        });
        ;
    }

    /**
     * Alias of getContegoStatus as Contego and Northrow are the same company by a different name
     */
    public function getNorthrowStatus(): array
    {
        return $this->getContegoStatus();
    }

    /**
     * Checks whether the Salesforce API connection is reachable and authenticated
     */
    public function getSalesforceStatus(): array
    {
        return $this->defaultAppCache->get('salesforceStatus', function (ItemInterface $item): array {
            $item->expiresAfter(self::DEFAULT_CACHE_TTL);
            $item->tag([self::DEFAULT_CACHE_TAG]);

            $response = $this->salesforceService->retrieve('Contact', '');
            $isValid = Response::HTTP_OK == $response->getStatusCode();
            return [
                'active' => $isValid,
                'lastChecked' => new \Datetime()->format(\DateTimeInterface::RFC2822),
            ];
        });
    }

    /**
     * Checks whether the Mailchimp transactional API connection is reachable and authenticated
     */
    public function getMailchimpStatus(): array
    {
        return $this->defaultAppCache->get('mailchimpStatus', function (ItemInterface $item): array {
            $item->expiresAfter(self::DEFAULT_CACHE_TTL);
            $item->tag([self::DEFAULT_CACHE_TAG]);

            // Note that the Mailchimp-transaction client library is using dynamic properties
            // See https://github.com/mailchimp/mailchimp-client-lib-codegen/pull/328 for eventual fix
            $response = $this->mailchimpApi->users->info();
            $isValid = $response instanceof \stdClass;

            if ($response instanceof \GuzzleHttp\Exception\RequestException) {
                $this->logger->error(
                    'Mailchimp status error '
                    . (string) $response->getResponse()->getBody(),
                );
            }

            return [
                'active' => $isValid,
                'lastChecked' => new \Datetime()->format(\DateTimeInterface::RFC2822),
                'userInfo' => json_decode(json_encode($response), true),
            ];
        });
    }

    /**
     * Checks whether the public docstore is both writable and readable by using a json file
     */
    public function getPublicDocumentStorageStatus(): array
    {
        return $this->defaultAppCache->get('docstorePublicStatus', function (ItemInterface $item): array {
            $item->expiresAfter(self::DEFAULT_CACHE_TTL);
            $item->tag([self::DEFAULT_CACHE_TAG]);

            $statusCheckString = json_encode([
                'checkedAt' => new \DateTime()->format(\DateTimeInterface::ATOM),
            ]);
            $checkFilePath = 'status/check.json';
            $this->documentService->put($checkFilePath, $statusCheckString, 'public');
            $response = $this->documentService->read($checkFilePath, 'public');
            $isValid = is_string($response);

            return [
                'active' => $isValid,
                'lastChecked' => new \Datetime()->format(\DateTimeInterface::RFC2822),
            ];
        });
    }

    /**
     * Checks whether the private docstore is both writable and readable by using a json file
     */
    public function getPrivateDocumentStorageStatus(): array
    {
        return $this->defaultAppCache->get('docstorePrivateStatus', function (ItemInterface $item): array {
            $item->expiresAfter(self::DEFAULT_CACHE_TTL);
            $item->tag([self::DEFAULT_CACHE_TAG]);

            $statusCheckString = json_encode([
                'checkedAt' => new \DateTime()->format(\DateTimeInterface::ATOM),
            ]);
            $checkFilePath = 'status/check.json';
            $this->documentService->put($checkFilePath, $statusCheckString, 'private');
            $response = $this->documentService->read($checkFilePath, 'private');
            $isValid = is_string($response);

            return [
                'active' => $isValid,
                'lastChecked' => new \Datetime()->format(\DateTimeInterface::RFC2822),
            ];
        });
    }
}
