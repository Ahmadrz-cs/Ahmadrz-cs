<?php

declare(strict_types=1);

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\ORMFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Fidry\AliceDataFixtures\LoaderInterface;

class TestUserFixtures implements ORMFixtureInterface
{
    public function __construct(
        private LoaderInterface $loader,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $fixtureFiles = [
            __DIR__ . '/coreAdminUsers.yaml',
            __DIR__ . '/standardUsers.yaml',
            __DIR__ . '/standardAdminUsers.yaml',
        ];

        $this->loader->load($fixtureFiles);
    }
}
