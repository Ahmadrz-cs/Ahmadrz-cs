<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Entity\Asset;
use App\Entity\Enum\TradeDirection;
use App\Entity\TradeOrder;
use App\EventListener\AssetAggregateListener;
use App\EventListener\TradeOrderAggregateListener;
use App\Repository\ShareTradeRepository;
use App\Test\Util\EntityIdTestUtil;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AssetAggregateListenerTest extends KernelTestCase
{
    private AssetAggregateListener $service;

    private ShareTradeRepository|MockObject $shareTradeRepositoryMock;

    protected function setUp(): void
    {
        self::bootKernel();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('assetTradeAggregateProvider')]
    public function testPostLoad(int $expectedAvailable, array $aggregates): void
    {
        $asset = EntityIdTestUtil::setEntityId(new Asset(), 851);

        $this->shareTradeRepositoryMock = $this->createMock(ShareTradeRepository::class);
        static::getContainer()->set(
            ShareTradeRepository::class,
            $this->shareTradeRepositoryMock,
        );
        $this->shareTradeRepositoryMock
            ->expects(self::exactly(1))
            ->method('getAssetTradeAggregates')
            ->with($asset->getId())
            ->willReturn($aggregates);
        $this->service = static::getContainer()->get(AssetAggregateListener::class);

        $this->service->postLoad($asset);

        $this->assertEquals($expectedAvailable, $asset->getSharesAvailable());
    }

    public static function assetTradeAggregateProvider(): \Generator
    {
        yield 'Has aggregates and all empty' => [
            1784,
            [
                'sharesListed' => 1784,
                'sharesAvailable' => '1784',
                'shares' => '0',
            ],
        ];
        yield 'Has aggregates and some traded' => [
            1504,
            [
                'sharesListed' => 1784,
                'sharesAvailable' => '1504',
                'shares' => '280',
            ],
        ];
        yield 'Has aggregates and all traded' => [
            0,
            [
                'sharesListed' => 1784,
                'sharesAvailable' => '0',
                'shares' => '1784',
            ],
        ];
        yield 'Has aggregates and over traded' => [
            0,
            [
                'sharesListed' => 1784,
                'sharesAvailable' => '-3216',
                'shares' => '5000',
            ],
        ];
        // Note that the asset aggregate differs from the TradeOrder aggregate
        // In that if no aggregates come back, you interpret this as no trade orders exist for the asset
        // Therefore no shares are available
        yield 'Has no aggregates' => [
            0,
            [
                'sharesListed' => null,
                'sharesAvailable' => null,
                'shares' => null,
            ],
        ];
        yield 'Empty array' => [
            0,
            [],
        ];
    }
}
