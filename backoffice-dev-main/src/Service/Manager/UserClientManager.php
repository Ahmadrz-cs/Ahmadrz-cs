<?php

namespace App\Service\Manager;

use App\Entity\UserClient;
use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class UserClientManager
{
    public function __construct(
        private ClientManagerInterface $clientManager,
        private AccessTokenManagerInterface $accessTokenManager,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private Security $security,
    ) {}

    public function generateClientCredentials(): array
    {
        return [
            bin2hex(random_bytes(8)),
            hash('md5', random_bytes(16)),
            hash('sha512', random_bytes(32)),
        ];
    }

    public function createClient(): UserClient
    {
        $this->logger->info('============= createUserClient ===========');

        $client = new Client(...$this->generateClientCredentials());

        $client->setActive(true);
        $client->setGrants(new Grant('client_credentials'));
        $client->setScopes(new Scope('asset:read'), new Scope('offering:read'));

        $userClient = new UserClient($this->security->getUser(), $client);

        $this->entityManager->persist($userClient);
        $this->entityManager->flush();

        return $userClient;
    }

    public function deleteClient(string $identifier): bool
    {
        $this->logger->info('============= deleteClient ===========');

        $repository = $this->entityManager->getRepository(UserClient::class);
        $userClient = $repository->findOneBy(['client' => $identifier]);
        if ($userClient) {
            $this->entityManager->remove($userClient);
            $this->entityManager->flush();
            return true;
        }
        return false;
    }

    public function listClients(): array
    {
        $this->logger->info('============= listClients ===========');
        $repository = $this->entityManager->getRepository(UserClient::class);
        $criteria = [];
        return $repository->findBy($criteria);
    }

    public function updateClient(UserClient $userClient): bool
    {
        $this->logger->info('============= updateClient ===========');
        try {
            $this->entityManager->persist($userClient);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
        return true;
    }

    public function findClient(array $criteria = []): ?UserClient
    {
        $this->logger->info('============= findClient ===========');
        $repository = $this->entityManager->getRepository(UserClient::class);
        return $repository->findOneBy($criteria);
    }

    public function findClientByTokenId(?string $accessTokenId = null): ?UserClient
    {
        $this->logger->info('============= findClientByTokenId ===========');
        try {
            /**
             * Method only works for API requests which use OAuth2 issued JWTs
             * This will attempt to find the UserClient associated with the request's JWT identifier
             * Return null is no valid UserClient found for the request (including if called for non-API requests)
             */
            if (is_null($accessTokenId)) {
                $accessTokenId = $this->security->getToken()->getCredentials();
            }
            $clientId = $this->accessTokenManager
                ->find($accessTokenId)
                ->getClient()
                ->getIdentifier();
            return $this->findClient(['client' => $clientId]);
        } catch (\Throwable $e) {
            // Need to catch errors as well as exceptions, so catching Throwable instead
            $this->logger->warning($e->getMessage());
            return null;
        }
    }
}
