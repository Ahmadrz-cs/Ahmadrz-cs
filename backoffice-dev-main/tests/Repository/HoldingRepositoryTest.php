<?php

namespace App\Tests\Repository;

use App\Repository\HoldingRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class HoldingRepositoryTest extends KernelTestCase
{
    private HoldingRepository $service;

    protected function setUp(): void
    {
        /**
         * HoldingRepository is a regular service, no a doctrine repository
         * So just access from container instead of through doctrine
         * The service is private, so must access through special testing container
         */
        self::bootKernel();
        $this->service = static::getContainer()->get(HoldingRepository::class);
    }

    public static function filterShareTradeProvider(): \Generator
    {
        yield 'No filters' => [
            [],
            ['SELECT * FROM getShareTrades ORDER BY settledOn DESC, asset ASC'],
        ];
        yield 'Aggregation only' => [
            ['aggregate' => 1],
            [
                'asset',
                'seller',
                'SUM(numberOfShares) AS numberOfShares',
                'MAX(settledOn) AS lastSettled',
                'COUNT(investment) AS tradeCount',
                'assetId',
                'sellerId',
                'GROUP BY asset, seller',
            ],
        ];
        yield 'Settlement date range filters' => [
            [
                'settledFrom' => '2020-10-01',
                'settledTo' => '2024-10-01',
            ],
            [
                'WHERE',
                'AND',
                'settledOn',
                '>=',
                '<',
            ],
        ];
        yield 'Id filters' => [
            [
                'assetId' => 24,
                'buyerId' => 1251,
                'sellerId' => 1434,
            ],
            [
                'WHERE',
                'AND',
                'assetId',
                'buyerId',
                'sellerId',
                '=',
            ],
        ];
        yield 'Combined filters' => [
            [
                'assetId' => 24,
                'buyerId' => 1251,
                'sellerId' => 1434,
                'settledFrom' => '2020-10-01',
                'settledTo' => '2024-10-01',
            ],
            [
                'WHERE',
                'AND',
                'assetId',
                'buyerId',
                'sellerId',
                'settledOn',
                '>=',
                '<',
                '=',
            ],
        ];
        yield 'Combined filters and aggregation' => [
            [
                'assetId' => 24,
                'buyerId' => 1251,
                'sellerId' => 1434,
                'settledFrom' => '2020-10-01',
                'settledTo' => '2024-10-01',
                'aggregate' => 1,
            ],
            [
                'asset',
                'seller',
                'SUM(numberOfShares) AS numberOfShares',
                'MAX(settledOn) AS lastSettled',
                'COUNT(investment) AS tradeCount',
                'assetId',
                'sellerId',
                'GROUP BY asset, seller',
                'WHERE',
                'AND',
                'assetId',
                'buyerId',
                'sellerId',
                'settledOn',
                '>=',
                '<',
                '=',
            ],
        ];
    }

    public static function invalidFilterShareTradeProvider(): \Generator
    {
        yield 'No aggregation' => [
            [],
            [
                'SUM(numberOfShares) AS numberOfShares',
                'MAX(settledOn) AS lastSettled',
                'COUNT(investment) AS tradeCount',
                'GROUP BY asset, seller',
            ],
        ];
        yield 'Invalid filter types' => [
            [
                'assetId' => 'abc',
                'buyerId' => false,
                'sellerId' => 'fbahfejek',
                'settledFrom' => new \DateTime(),
                'settledTo' => [],
            ],
            [
                'WHERE',
                'AND',
                'assetId',
                'buyerId',
                'sellerId',
                'WHERE settledOn',
                'AND settledOn',
                '>=',
                '<',
                '=',
            ],
        ];
        yield 'Unsupported filters' => [
            [
                'asset' => 'some asset name',
                'buyer' => 'someuser@test.com',
                'seller' => 'anotheruser@test.com',
                'createdFrom' => '2020-10-01',
                'createdTo' => '2024-10-01',
                'aggragate' => 1,
            ],
            [
                'WHERE',
                'AND',
                'assetId',
                'buyerId',
                'sellerId',
                'WHERE asset',
                'AND asset',
                'buyer',
                'seller',
                'createdOn',
                'WHERE settledOn',
                'AND settledOn',
                '>=',
                '<',
                '=',
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('filterShareTradeProvider')]
    public function testBuildShareTradeQuery(array $filters, array $needles): void
    {
        $actual = $this->service->buildShareTradeQuery($filters);
        foreach ($needles as $expected) {
            $this->assertStringContainsString($expected, $actual);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidFilterShareTradeProvider')]
    public function testBuildShareTradeQuerySanitisation(
        array $filters,
        array $needles,
    ): void {
        $actual = $this->service->buildShareTradeQuery($filters);
        foreach ($needles as $expected) {
            $this->assertStringNotContainsString($expected, $actual);
        }
    }
}
