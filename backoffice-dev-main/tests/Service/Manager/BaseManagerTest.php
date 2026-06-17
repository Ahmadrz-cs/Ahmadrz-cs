<?php

namespace App\Tests\Service\Manager;

use App\Service\Manager\AssetManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BaseManagerTest extends KernelTestCase
{
    /**
     * Note that base manager is not exposed as a service
     * So we'll use Asset Manager which extends it to test its methods
     */
    private AssetManager $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(AssetManager::class);
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('collection-filter')]
    public function testGetSortPreferences(): void
    {
        $sortString = '-id,+updatedAt,fundingGoal';
        $expected = [
            'id' => 'DESC',
            'updatedAt' => 'ASC',
            'fundingGoal' => 'DESC',
        ];

        $actual = $this->service->getSortPreferences($sortString);
        // note we use this assert method rather than assertEquals since ordering does not matter
        $this->assertEmpty(array_diff_assoc($expected, $actual));

        // check that empty string returns the default sorting mechanism
        $actual = $this->service->getSortPreferences('');
        $this->assertEquals(['updatedAt' => 'DESC'], $actual);
    }
}
