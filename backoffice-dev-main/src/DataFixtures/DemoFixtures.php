<?php

declare(strict_types=1);

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\ORMFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Fidry\AliceDataFixtures\LoaderInterface;

class DemoFixtures implements ORMFixtureInterface
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
            __DIR__ . '/demo/demoUsers.yaml',
            __DIR__ . '/demo/propertySpa.yaml',
            __DIR__ . '/demo/propertyNotts.yaml',
            __DIR__ . '/demo/propertyFreight.yaml',
            __DIR__ . '/demo/tradesInitial.yaml',
            __DIR__ . '/demo/tradesMarket.yaml',
            __DIR__ . '/demo/dividends.yaml',
            __DIR__ . '/demo/tradesExit.yaml',
        ];

        $this->loader->load($fixtureFiles);
    }
}
