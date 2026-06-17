<?php

declare(strict_types=1);

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\ORMFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Fidry\AliceDataFixtures\LoaderInterface;

class DevTradeFixtures implements ORMFixtureInterface
{
    public function __construct(
        private LoaderInterface $loader,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $fixtureFiles = [
            __DIR__ . '/coreAdminUsers.yaml',
            __DIR__ . '/coreOauthClients.yaml',
            __DIR__ . '/standardAppSettings.yaml',
            __DIR__ . '/standardUsers.yaml',
            __DIR__ . '/standardAdminUsers.yaml',
            __DIR__ . '/standardVendorUsers.yaml',
            __DIR__ . '/standardMails.yaml',
            __DIR__ . '/standardQuestionsSet1.yaml',
            __DIR__ . '/standardQuestionsSet2.yaml',
            __DIR__ . '/standardQuestionsSet3.yaml',
            __DIR__ . '/auxiliaryUsers.yaml',
            __DIR__ . '/auxiliaryProperties.yaml',
            // __DIR__ . '/auxiliaryInvestments.yaml',
            __DIR__ . '/auxiliaryTrades.yaml',
            // __DIR__ . '/auxiliaryPayouts.yaml',
            __DIR__ . '/auxiliaryCommunications.yaml',
            __DIR__ . '/auxiliaryOrders.yaml',
            __DIR__ . '/auxiliaryIntegrations.yaml',
        ];

        $this->loader->load($fixtureFiles);
    }
}
