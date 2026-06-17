<?php

namespace App\Tests\Service\Manager;

use App\Entity\Lifecycle\AssetLifecycle;
use App\Service\Manager\AssetManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AssetManagerTest extends KernelTestCase
{
    private AssetManager $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(AssetManager::class);
    }

    private $testQueryParams = [
        'offest' => '0',
        'limit' => '1',
        'sort' => '+id,-updatedAt',
        'id' => '1,3,4',
        'status' => '4,5',
        'type' => 'commercial,residential',
        'term' => '1,3,5',
        'biscuits' => 'digestive,shortbread',
        'user' => 'whodat,whatder',
    ];

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('collection-filter')]
    public function testGetCriteria(): void
    {
        $expected = ['id', 'assetType'];

        // only return allowed criteria
        $actual = $this->service->getCriteria($this->testQueryParams);
        $this->assertEmpty(array_diff($expected, array_keys($actual)));

        // pass empty queryParams
        $actual = $this->service->getCriteria([]);
        $this->assertEmpty($actual);
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('collection-filter')]
    public function testGetAuxiliaryFilters(): void
    {
        $expected = ['status'];

        // only return allowed auxiliary filters
        $actual = $this->service->getAuxiliaryFilters($this->testQueryParams);
        $this->assertEmpty(array_diff($expected, array_keys($actual)));

        // pass default status null as admin - may want to improve it to handle empty arrays
        $actual = $this->service->getAuxiliaryFilters(['status' => null], true);
        $this->assertEmpty($actual);

        // pass default status null as non-admin
        $actual = $this->service->getAuxiliaryFilters(['status' => null]);
        $this->assertEmpty(array_diff($expected, array_keys($actual)));
        $this->assertEquals(1, count($actual['status']));
        $this->assertEquals(AssetLifecycle::STATE_PUBLISHED_INT, $actual['status'][0]);
    }
}
