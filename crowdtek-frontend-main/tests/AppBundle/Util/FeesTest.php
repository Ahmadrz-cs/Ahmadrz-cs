<?php

// tests/AppBundle/Util/CalculatorTest.php
namespace tests\AppBundle\Util;

use AppBundle\Util\Fees;

class FeesTest extends \PHPUnit\Framework\TestCase
{
    public const DEFAULT_FEES = [
        "0" => 10,
        "300" => 15,
        "800" => 40
    ];

    /**
     * @dataProvider monthlyOfferingsProvider
     */
    public function testGetMonthlyRelistingAmount(
        float $expected,
        array $offerings
    ): void {
        $actual = Fees::getMonthlyRelistingAmount($offerings, 1, 1);
        // want to ignore float arithmetic imprecision
        $this->assertEqualsWithDelta($expected, $actual, 0.001);
    }

    /**
     * @dataProvider feeDueProvider
     */
    public function testGetRelistingFeeDue(
        int $expected,
        float $existing,
        float $new
    ): void {
        $actual = Fees::getRelistingFeeDue(self::DEFAULT_FEES, $existing, $new);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider feeCapsProvider
     */
    public function testGetFeeCap(
        int $expected,
        float $amount
    ): void {
        $actual = Fees::getFeeCap(self::DEFAULT_FEES, $amount);
        $this->assertEquals($expected, $actual);
    }

    public function monthlyOfferingsProvider()
    {
        yield 'simple' => [
            488.94, [
                $this->generateOffering(120.64),
                $this->generateOffering(128.10),
                $this->generateOffering(240.20)
            ]
        ];
        yield 'different months' => [
            240.20, [
                $this->generateOffering(120.64, 1, 1, 1),
                $this->generateOffering(128.10, 1, 1, 1),
                $this->generateOffering(240.20)
            ]
        ];
        yield 'different assets' => [
            128.10, [
                $this->generateOffering(120.64, 2),
                $this->generateOffering(128.10),
                $this->generateOffering(240.20, 5)
            ]
        ];
        yield 'different users' => [
            120.64, [
                $this->generateOffering(120.64),
                $this->generateOffering(128.10, 1, 2),
                $this->generateOffering(240.20, 1, 3)
            ]
        ];
        yield 'mix of different months, assets, users' => [
            600.50, [
                $this->generateOffering(120.64, 1, 1, 1),
                $this->generateOffering(128.10, 2),
                $this->generateOffering(240.20, 1, 3),
                $this->generateOffering(600.50)
            ]
        ];
    }

    public function feeDueProvider()
    {
        yield 'first base cap' => [10, 0, 300];
        yield 'first mid cap' => [15, 0, 300.01];
        yield 'first upper cap' => [40, 0, 800.01];

        yield 'base to mid' => [5, 200, 100.01]; # 300.01, so now mid-band
        yield 'base to upper' => [30, 200, 600.01]; # 800.01, so now upper-band
        yield 'mid to upper' => [25, 400, 400.01]; # 800.01, so now upper-band

        yield 'base to base' => [0, 120, 180]; # 300 total, so still base-band
        yield 'mid to mid' => [0, 360, 340]; # 800 total, so still mid-band
        yield 'upper to upper' => [0, 800.01, 1]; # Already at upper-band

        yield 'boundary mid' => [10, 0, 300]; # 300 exactly, so base-band
        yield 'boundary upper' => [15, 0, 800]; # 800 exactly, so mid-band
    }

    public function feeCapsProvider()
    {
        yield 'base cap' => [10, 100];
        yield 'mid cap' => [15, 400];
        yield 'upper cap' => [40, 1000];
        yield 'boundary base cap' => [10, 300];
        yield 'boundary mid cap' => [15, 800];
        yield 'exact mid cap' => [15, 300.01];
        yield 'exact upper cap' => [40, 800.01];
        yield 'empty case' => [0, 0];
        yield 'negative case' => [0, -1];
    }

    public function generateOffering(
        float $amount = 10,
        int $asset = 1,
        int $user = 1,
        bool $date = false
    ) {
        $date = $date
            ? (new \DateTime("-2 months"))->format(\DateTime::W3C)
            : (new \DateTime())->format(\DateTime::W3C);
        return [
            "funding_goal" => $amount,
            "asset_id" => $asset,
            "user_id" => $user,
            "created_at" => $date,
        ];
    }
}
