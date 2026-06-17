<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\Entity\Enum\TradeDirection;
use App\Entity\TradeOrder;
use App\EventListener\TradeOrderAggregateListener;
use App\Repository\ShareTradeRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class TradeOrderAggregateListenerTest extends KernelTestCase
{
    private TradeOrderAggregateListener $service;

    private ShareTradeRepository|MockObject $shareTradeRepositoryMock;

    protected function setUp(): void
    {
        self::bootKernel();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('tradeOrderAggregateProvider')]
    public function testPostLoad(
        int $expectedAvailable,
        int $expectedTraded,
        array $aggregates,
    ): void {
        $tradeOrder = new TradeOrder(
            direction: TradeDirection::Sell,
            numberOfShares: 1784,
        );

        $this->shareTradeRepositoryMock = $this->createMock(ShareTradeRepository::class);
        static::getContainer()->set(
            ShareTradeRepository::class,
            $this->shareTradeRepositoryMock,
        );
        $this->shareTradeRepositoryMock
            ->expects(self::exactly(1))
            ->method('getTradeOrderAggregates')
            ->with($tradeOrder)
            ->willReturn($aggregates);
        $this->service = static::getContainer()->get(TradeOrderAggregateListener::class);

        $this->service->postLoad($tradeOrder);

        $this->assertEquals($expectedAvailable, $tradeOrder->getSharesAvailable());
        $this->assertEquals($expectedTraded, $tradeOrder->getSharesTraded());
    }

    public static function tradeOrderAggregateProvider(): \Generator
    {
        yield 'Has aggregates and all empty' => [
            1784,
            0,
            [
                'sharesListed' => 1784,
                // 'sharesAvailable' => '1784',
                'shares' => '0',
            ],
        ];
        yield 'Has aggregates and some traded' => [
            1504,
            280,
            [
                'sharesListed' => 1784,
                // 'sharesAvailable' => '1504',
                'shares' => '280',
            ],
        ];
        yield 'Has aggregates and all traded' => [
            0,
            1784,
            [
                'sharesListed' => 1784,
                // 'sharesAvailable' => '0',
                'shares' => '1784',
            ],
        ];
        yield 'Has aggregates and over traded' => [
            0,
            5000,
            [
                'sharesListed' => 1784,
                // 'sharesAvailable' => '-3216',
                'shares' => '5000',
            ],
        ];
        // Note that the asset aggregate differs from the TradeOrder aggregate
        // In that if no aggregates come back, you interpret this as no share trades exist for the trade order
        // Therefore no shares have been traded yet, so all the shares are still available
        yield 'Has no aggregates' => [
            1784,
            0,
            [
                'sharesListed' => null,
                // 'sharesAvailable' => null,
                'shares' => null,
            ],
        ];
        yield 'Empty array' => [
            1784,
            0,
            [],
        ];
    }
}
