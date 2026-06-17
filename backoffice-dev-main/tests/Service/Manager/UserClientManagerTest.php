<?php

namespace App\Tests\Service\Manager;

use App\Entity\UserClient;
use App\Service\Manager\UserClientManager;
use App\Test\FixtureWebTestCase;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;

class UserClientManagerTest extends FixtureWebTestCase
{
    private UserClientManager $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(UserClientManager::class);
    }

    public function testServiceExists(): void
    {
        $service = $this->service;
        $this->assertInstanceOf(UserClientManager::class, $service);
    }

    public function testGenerateClientCredentials(): void
    {
        $actual = $this->service->generateClientCredentials();
        $this->assertEquals(3, count($actual));
        $this->assertEquals(16, strlen($actual[0]));
        $this->assertEquals(32, strlen($actual[1])); // md5 hash produces 32 char string
        $this->assertEquals(128, strlen($actual[2])); // sha512 hash produces 128 char string
    }

    public function testCreateClient(): void
    {
        $this->loginWebClient(self::USER_ADMIN);
        $actual = $this->service->createClient();
        $this->assertInstanceOf(UserClient::class, $actual);

        $this->assertNotEmpty($actual->getUser());
        $this->assertEmpty($actual->getAlias());
        $this->assertEmpty($actual->getDescription());

        $this->assertNotEmpty($actual->getClient()->getIdentifier());
        $this->assertNotEmpty($actual->getClient()->getSecret());
        $this->assertEmpty($actual->getClient()->getRedirectUris());
        $this->assertNotEmpty($actual->getClient()->getGrants());
        $this->assertNotEmpty($actual->getClient()->getScopes());
        $this->assertTrue($actual->getClient()->isActive());
    }

    public function testDeleteClient(): void
    {
        $this->assertTrue($this->service->deleteClient(
            self::OAUTH2_CLIENT_VENDOR['clientId'],
        ));
        $this->assertNull($this->service->findClient([
            'client' => self::OAUTH2_CLIENT_VENDOR['clientId'],
        ]));

        // if client does not exist (we've just deleted it), returns false
        $this->assertFalse($this->service->deleteClient(
            self::OAUTH2_CLIENT_VENDOR['clientId'],
        ));

        // check that the actual oauth2 client inside has also been removed by cascade
        $clientManager = static::getContainer()->get(ClientManagerInterface::class);
        $this->assertNull($clientManager->find(self::OAUTH2_CLIENT_VENDOR['clientId']));
    }

    public function testListClients(): void
    {
        $actual = $this->service->listClients();
        $this->assertIsArray($actual);
        $this->assertNotEmpty($actual);
    }

    public function testUpdateClient(): void
    {
        // simple sanity test rather than in-depth per field checks
        $beforeClient = $this->getSampleUserClient();
        $beforeClient->setAlias(null);
        $beforeClient->setDescription(null);
        $beforeClient->setAlias('yielderverse');
        $beforeClient->setDescription('yielderneoverse');
        $client = $beforeClient->getClient();
        $client->setActive(false);
        $beforeClient->setClient($client);
        $this->service->updateClient($beforeClient);

        $afterClient = $this->getSampleUserClient();
        $this->assertEquals(
            $afterClient->getDescription(),
            $beforeClient->getDescription(),
        );
        $this->assertEquals(
            $afterClient->getClient()->isActive(),
            $beforeClient->getClient()->isActive(),
        );
    }

    public function testFindClient(): void
    {
        $clientId = '904c1b4d9a15529ed70ff5e686345a9f';
        $criteria = [
            'client' => $clientId,
        ];
        $actual = $this->service->findClient($criteria);
        $this->assertInstanceOf(UserClient::class, $actual);
        $this->assertEquals($clientId, $actual->getClient()->getIdentifier());
    }

    public function testFindClientByTokenIdNull(): void
    {
        /**
         * Full integration test is found at
         * tests/Service/Manager/UserManagerV2Test.php:testfindManagerClientForRequest
         *
         * The 'Vendor' provider checks findClientByTokenId works
         *
         * Only need to unit test the "empty" state here
         */
        $this->assertNull($this->service->findClientByTokenId());
    }

    private function getSampleUserClient(): UserClient
    {
        return $this->searchFixtures(UserClient::class, [
            'alias' => 'yielderverse',
        ])[0];
    }
}
