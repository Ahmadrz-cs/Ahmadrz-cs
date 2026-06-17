<?php

namespace App\Tests\Service;

use App\Service\ProductReviewService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProductReviewServiceTest extends KernelTestCase
{
    private ProductReviewService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(ProductReviewService::class);
    }

    public function testAggregateAssetListingSummary(): void
    {
        // Safely handle empty state
        $this->assertSame([], $this->service->aggregateAssetListingSummary([], [], []));

        $sampleListings = [
            [
                'assetId' => 23,
                'listings' => 6,
                'relistings' => 5,
                'sharesListed' => 105625,
                'valueListed' => 135200,
                'fundingGoal' => 135200,
                'equivalentSharesListed' => 105625,
                'status' => 'published',
            ],
            [
                'assetId' => 23,
                'listings' => 2,
                'relistings' => 2,
                'sharesListed' => 10000,
                'valueListed' => 12800,
                'fundingGoal' => 3302.4,
                'equivalentSharesListed' => 2580,
                'status' => 'draft',
            ],
            [
                'assetId' => 44,
                'listings' => 1,
                'relistings' => 0,
                'sharesListed' => 100000,
                'valueListed' => 178000,
                'fundingGoal' => 178000,
                'equivalentSharesListed' => 100000,
                'status' => 'published',
            ],
        ];

        $sampleSettled = [
            [
                'assetId' => 23,
                'shares' => 100000,
                'value' => 128000,
                'isRelisted' => 0,
                'investmentType' => 'normal',
            ],
            [
                'assetId' => 23,
                'shares' => 4275,
                'value' => 5472,
                'isRelisted' => 1,
                'investmentType' => 'normal',
            ],
            [
                'assetId' => 23,
                'shares' => 88500,
                'value' => 113280,
                'isRelisted' => 0,
                'investmentType' => 'prefunding',
            ],
            [
                'assetId' => 44,
                'shares' => 5800,
                'value' => 10324,
                'isRelisted' => 0,
                'investmentType' => 'off-market',
            ],
            [
                'assetId' => 44,
                'shares' => 64200,
                'value' => 114276,
                'isRelisted' => 0,
                'investmentType' => 'prefunding',
            ],
        ];

        $samplePending = [
            [
                'assetId' => 23,
                'shares' => 385,
                'value' => 492.8,
                'isRelisted' => 1,
                'investmentType' => 'normal',
            ],
            [
                'assetId' => 44,
                'shares' => 2000,
                'value' => 3560,
                'isRelisted' => 0,
                'investmentType' => 'normal',
            ],
            [
                'assetId' => 44,
                'shares' => 10500,
                'value' => 18690,
                'isRelisted' => 0,
                'investmentType' => 'prefunding',
            ],
        ];

        $actual = $this->service->aggregateAssetListingSummary(
            $sampleListings,
            $sampleSettled,
            $samplePending,
        );
        $expected = [
            23 => [
                'listings' => 8,
                'relistings' => 7,
                'shares' => [
                    'listed' => 105625,
                    'altListed' => 105625,
                    'traded' => 104275,
                    'prefunded' => 88500,
                    'pendingListed' => 10000,
                    'altPendingListed' => 2580,
                    'pendingTraded' => 385,
                ],
                'value' => [
                    'listed' => 135200,
                    'altListed' => 135200,
                    'traded' => 133472,
                    'prefunded' => 113280,
                    'pendingListed' => 12800,
                    'altPendingListed' => 3302.4,
                    'pendingTraded' => 492.8,
                ],
            ],
            44 => [
                'listings' => 1,
                'relistings' => 0,
                'shares' => [
                    'listed' => 100000,
                    'altListed' => 100000,
                    'traded' => 5800,
                    'prefunded' => 64200,
                    'pendingListed' => 0,
                    'altPendingListed' => 0,
                    'pendingTraded' => 12500,
                ],
                'value' => [
                    'listed' => 178000,
                    'altListed' => 178000,
                    'traded' => 10324,
                    'prefunded' => 114276,
                    'pendingListed' => 0,
                    'altPendingListed' => 0,
                    'pendingTraded' => 22250,
                ],
            ],
        ];
        $this->assertEquals($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('filterListingSummaryProvider')]
    public function testFilterAssetListingSummary(array $expected, array $filters): void
    {
        $input = [
            'all' => [
                'listings' => 8,
                'relistings' => 7,
                'shares' => [
                    'listed' => 105625,
                    'altListed' => 105625,
                    'traded' => 104275,
                    'prefunded' => 88500,
                    'pendingListed' => 10000,
                    'altPendingListed' => 2580,
                    'pendingTraded' => 385,
                ],
                'value' => [
                    'listed' => 135200,
                    'altListed' => 135200,
                    'traded' => 133472,
                    'prefunded' => 113280,
                    'pendingListed' => 12800,
                    'altPendingListed' => 3302.4,
                    'pendingTraded' => 492.8,
                ],
            ],
            'noStock' => [
                'listings' => 1,
                'relistings' => 0,
                'shares' => [
                    'listed' => 100000,
                    'altListed' => 100000,
                    'traded' => 87500,
                    'prefunded' => 64200,
                    'pendingListed' => 0,
                    'altPendingListed' => 0,
                    'pendingTraded' => 12500,
                ],
                'value' => [
                    'listed' => 178000,
                    'altListed' => 178000,
                    'traded' => 155750,
                    'prefunded' => 114276,
                    'pendingListed' => 0,
                    'altPendingListed' => 0,
                    'pendingTraded' => 22250,
                ],
            ],
            'onlyPrefunding' => [
                'listings' => 1,
                'relistings' => 0,
                'shares' => [
                    'listed' => 100000,
                    'altListed' => 100000,
                    'traded' => 0,
                    'prefunded' => 64200,
                    'pendingListed' => 0,
                    'altPendingListed' => 0,
                    'pendingTraded' => 0,
                ],
                'value' => [
                    'listed' => 178000,
                    'altListed' => 178000,
                    'traded' => 0,
                    'prefunded' => 114276,
                    'pendingListed' => 0,
                    'altPendingListed' => 0,
                    'pendingTraded' => 0,
                ],
            ],
            'noInvestors' => [
                'listings' => 1,
                'relistings' => 0,
                'shares' => [
                    'listed' => 1000,
                    'altListed' => 1000,
                    'traded' => 0,
                    'prefunded' => 0,
                    'pendingListed' => 0,
                    'altPendingListed' => 0,
                    'pendingTraded' => 0,
                ],
                'value' => [
                    'listed' => 27850,
                    'altListed' => 27850,
                    'traded' => 0,
                    'prefunded' => 0,
                    'pendingListed' => 0,
                    'altPendingListed' => 0,
                    'pendingTraded' => 0,
                ],
            ],
            'noActiveListings' => [
                'listings' => 1,
                'relistings' => 0,
                'shares' => [
                    'listed' => 0,
                    'altListed' => 0,
                    'traded' => 5800,
                    'prefunded' => 64200,
                    'pendingListed' => 178000,
                    'altPendingListed' => 100000,
                    'pendingTraded' => 0,
                ],
                'value' => [
                    'listed' => 0,
                    'altListed' => 0,
                    'traded' => 10324,
                    'prefunded' => 114276,
                    'pendingListed' => 178000,
                    'altPendingListed' => 178000,
                    'pendingTraded' => 0,
                ],
            ],
            'earlyListing' => [
                'listings' => 1,
                'relistings' => 0,
                'shares' => [
                    'listed' => 1000,
                    'altListed' => 1000,
                    'traded' => 0,
                    'prefunded' => 0,
                    'pendingListed' => 0,
                    'altPendingListed' => 0,
                    'pendingTraded' => 1,
                ],
                'value' => [
                    'listed' => 1000,
                    'altListed' => 1000,
                    'traded' => 0,
                    'prefunded' => 0,
                    'pendingListed' => 0,
                    'altPendingListed' => 0,
                    'pendingTraded' => 1,
                ],
            ],
        ];
        $actual = $this->service->filterAssetListingSummary($input, $filters);
        $expected = array_filter(
            $input,
            fn($description) => in_array($description, $expected),
            ARRAY_FILTER_USE_KEY,
        );
        $this->assertEquals($expected, $actual);
    }

    public static function filterListingSummaryProvider(): \Generator
    {
        yield 'No filter' => [
            [
                'all',
                'noStock',
                'onlyPrefunding',
                'noInvestors',
                'noActiveListings',
                'earlyListing',
            ],
            [],
        ];
        yield 'Unknown filter' => [
            [
                'all',
                'noStock',
                'onlyPrefunding',
                'noInvestors',
                'noActiveListings',
                'earlyListing',
            ],
            ['thisIsNotAValidFilter' => true],
        ];
        // Note that prefunding only, is filtered out by "no investors"
        yield 'Hide no investors' => [
            ['all', 'noStock', 'noActiveListings', 'earlyListing'],
            ['hideNoInvestors' => true],
        ];
        yield 'Hide no available' => [
            ['all', 'onlyPrefunding', 'noInvestors', 'earlyListing'],
            ['hideNoAvailable' => true],
        ];
        yield 'Hide no active listings' => [
            ['all', 'noStock', 'onlyPrefunding', 'noInvestors', 'earlyListing'],
            ['hideNoListings' => true],
        ];
        yield 'Hide no available and no investors' => [
            ['all', 'earlyListing'],
            [
                'hideNoAvailable' => true,
                'hideNoInvestors' => true,
                'hideNoListings' => false,
            ],
        ];
        yield 'Hide no listings and no investors' => [
            ['all', 'noStock', 'earlyListing'],
            [
                'hideNoAvailable' => false,
                'hideNoInvestors' => true,
                'hideNoListings' => true,
            ],
        ];
        yield 'Hide no listings and no available' => [
            ['all', 'onlyPrefunding', 'noInvestors', 'earlyListing'],
            [
                'hideNoAvailable' => true,
                'hideNoInvestors' => false,
                'hideNoListings' => true,
            ],
        ];
        yield 'Hide no listings, no available, no investors' => [
            ['all', 'earlyListing'],
            [
                'hideNoAvailable' => true,
                'hideNoInvestors' => true,
                'hideNoListings' => true,
            ],
        ];
    }
}
