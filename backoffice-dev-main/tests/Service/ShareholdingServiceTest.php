<?php

namespace App\Tests\Service;

use App\Service\ShareholdingService;
use BcMath\Number;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ShareholdingServiceTest extends KernelTestCase
{
    private ShareholdingService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(ShareholdingService::class);
    }

    public function testAnnotateAggregateShareholdings(): void
    {
        // Use various mix of ints and strings for integers to see that it makes no difference
        // decimals should always be strings
        $input = [
            [
                'buyTrades' => 5,
                'buyShares' => 10000,
                'buyValue' => '12500',
                'sellTrades' => 2,
                'sellShares' => 7580,
                'sellValue' => '10081.40',
                'trades' => '7',
                'shares' => '2420',
                'value' => '3025',
                'aggregatorId' => 'not used in this method, but would be userid or assetid',
            ],
            [
                'buyTrades' => 5,
                'buyShares' => 10000,
                'buyValue' => '13400',
                'sellTrades' => 4,
                'sellShares' => 10000,
                'sellValue' => '13340.56',
                'trades' => '9',
                'shares' => '0',
                'value' => '-59.44',
                'aggregatorId' => 'not used in this method, but would be userid or assetid',
            ],
        ];
        $expected = [
            [
                'buyTrades' => 5,
                'buyShares' => 10000,
                'buyValue' => '12500',
                'sellTrades' => 2,
                'sellShares' => 7580,
                'sellValue' => '10081.40',
                'trades' => '7',
                'shares' => '2420',
                'value' => '3025',
                'aggregatorId' => 'not used in this method, but would be userid or assetid',
                // data that should be added
                'buyMean' => '1.25',
                'sellMean' => new Number('10081.40')->div(7580),
                'spread' => new Number('10081.40')->div(7580)->sub('1.25'),
                'profit' => '606.4',
                'currentValue' => new Number('3025'),
            ],
            [
                'buyTrades' => 5,
                'buyShares' => 10000,
                'buyValue' => '13400',
                'sellTrades' => 4,
                'sellShares' => 10000,
                'sellValue' => '13340.56',
                'trades' => '9',
                'shares' => '0',
                'value' => '-59.44',
                'aggregatorId' => 'not used in this method, but would be userid or assetid',
                // data that should be added
                'buyMean' => new Number('1.34'),
                'sellMean' => new Number('13340.56')->div(10000),
                'spread' => new Number('13340.56')->div(10000)->sub('1.34'),
                'profit' => '-59.44',
                'currentValue' => new Number(0),
            ],
        ];
        $actual = $this->service->annotateAggregateShareholdings($input);
        $this->assertEquals($expected, $actual);
    }
}
