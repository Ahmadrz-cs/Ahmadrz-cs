<?php

declare(strict_types=1);

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\ORMFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Finder\Finder;

class DevViewFixtures implements ORMFixtureInterface
{
    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        // Bundle to manage file and directories
        $finder = new Finder();
        $finder->in(__DIR__ . '/../SQLReports');
        $finder->name('HoldingsView.sql');
        $finder->files();
        $finder->sortByName();

        foreach ($finder as $file) {
            // print PHP_EOL . "Importing: {$file->getBasename()} " . PHP_EOL;

            $sql = $file->getContents();

            /** @var \Doctrine\ORM\EntityManagerInterface $manager */
            $manager->getConnection()->executeStatement($sql); // Execute native SQL

            $manager->flush();
        }
    }
}
