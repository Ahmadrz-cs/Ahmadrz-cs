<?php

namespace App\Tests\Repository;

use App\Entity\Asset;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Repository\TradeOrderRepository;
use App\Test\FixtureTestCase;

final class TradeOrderRepositoryTest extends FixtureTestCase
{
    private TradeOrderRepository $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(TradeOrderRepository::class);
    }

    public function testFindInitialSellOrders(): void
    {
        /**
         * Royal Eversea is completed
         * Sandfox fields is active
         * Saturna Wharf is suspended
         */
        foreach ([
            'Royal Eversea Glades - Cambridge',
            'Sandfox Fields - Kent',
            'Saturna Wharf - Bristol',
        ] as $name) {
            $asset = $this->entityManager
                ->getRepository(Asset::class)
                ->findOneBy([
                    'name' => $name,
                ]);
            $actual = $this->service->findInitialSellOrders($asset);
            $this->assertNotEmpty($actual);
            foreach ($actual as $tradeOrder) {
                $this->assertEquals(TradeOrderType::Initial, $tradeOrder->getType());
                $this->assertEquals(TradeDirection::Sell, $tradeOrder->getDirection());
                $this->assertEquals($asset, $tradeOrder->getAsset());
                $this->assertContains($tradeOrder->getStatus(), [
                    TradeOrderStatus::Active,
                    TradeOrderStatus::Completed,
                    TradeOrderStatus::Suspended,
                ]);
            }
        }

        // Silverhood Down is in draft, so no results should return yet
        $sampleAsset4 = $this->entityManager
            ->getRepository(Asset::class)
            ->findOneBy([
                'name' => 'Silverhood Down - Brighton',
            ]);
        $actual = $this->service->findInitialSellOrders($sampleAsset4);
        $this->assertEmpty($actual);
    }
}
