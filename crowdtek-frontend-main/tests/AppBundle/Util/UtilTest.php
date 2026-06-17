<?php

// tests/AppBundle/Util/CalculatorTest.php
namespace tests\AppBundle\Util;

use AppBundle\Util\Util;
use PHPUnit\Framework\TestCase;

class UtilTest extends TestCase
{
    public function testRoundToMultiple()
    {
        /**
         * Check
         * 1. Default floor
         * 2. Floor mode actually does floor
         * 3. Ceil mode actually does ceil
         * 4. Round mode does classic rounding up or down by halfway
         */
        $result = Util::roundToMultiple(4785.3123, 1.2);
        $this->assertEqualsWithDelta(4784.4, $result, 0.0001);
        $result = Util::roundToMultiple(4785.3123, 1.2, "floor");
        $this->assertEqualsWithDelta(4784.4, $result, 0.0001);
        $result = Util::roundToMultiple(4785.3123, 1.2, "ceil");
        $this->assertEqualsWithDelta(4785.6, $result, 0.0001);
        $result = Util::roundToMultiple(4784.99, 1.2, "round");
        $this->assertEqualsWithDelta(4784.4, $result, 0.0001);
        $result = Util::roundToMultiple(4785.00, 1.2, "round");
        $this->assertEqualsWithDelta(4785.6, $result, 0.0001);
    }

    public function testGetInfo()
    {
        /**
         * Check
         * 1. Found match
         * 2. No match return default
         * 3. No match return provided default
         */
        $testarray = [
            'info' => [
                [
                    'type' => 'alpha',
                    'value' => true
                ],
                [
                    'type' => 'beta',
                    'value' => false
                ]
            ]
        ];
        $result = Util::getInfo($testarray, "alpha");
        $this->assertEquals(true, $result);

        $result = Util::getInfo($testarray, "omega");
        $this->assertEmpty($result);

        $result = Util::getInfo($testarray, "omega", 8);
        $this->assertEquals(8, $result);
    }

    public function testgetUserInfoArray()
    {
        $testarray = [
            [
                'type' => 'alpha',
                'value' => true
            ],
            [
                'type' => 'beta',
                'value' => 12
            ],
        ];
        $result = Util::getUserInfoArray($testarray);
        $this->assertEquals(true, $result['alpha']);
        $this->assertEquals(12, $result['beta']);
    }

    public function testconvertArrayBoolsToInt()
    {
        /**
         * Check
         * 1. Correctly converts true to 1
         * 2. Correctly converts false to 0
         * 3. Correctly ignores non bool
         */
        $testarray = [
            'alpha' => true,
            'beta' => false,
            'gamma' => "true",
            'delta' => 16
        ];
        $result = Util::convertArrayBoolsToInt($testarray);
        $this->assertEquals(1, $result['alpha']);
        $this->assertEquals(0, $result['beta']);
        $this->assertEquals("true", $result['gamma']);
        $this->assertEquals(16, $result['delta']);
    }

    public function testFilterArrayBy()
    {
        /**
         * Check
         * 1. allowlist filtering (whitelist/positive-filter)
         * 2. denylist filtering (blacklist/negative-filter)
         */

        $testarray = [
            [
                'alpha' => 7,
                'beta' => 1
            ],
            [
                'alpha' => 2,
                'beta' => 6
            ],
            [
                'alpha' => 1,
                'beta' => 4
            ],
            [
                'alpha' => 7,
                'beta' => 2
            ],
            [
                'alpha' => 5,
                'beta' => 2
            ]
        ];

        $resultAllow = Util::filterArrayBy($testarray, "beta", [1, 6]);
        $resultDeny = Util::filterArrayBy($testarray, "alpha", [1, 6], "denylist");

        $this->assertEquals(2, count($resultAllow));
        $this->assertEquals(4, count($resultDeny));
        foreach ($resultAllow as $result) {
            $this->assertTrue(in_array($result["beta"], [1, 6]));
        }
        foreach ($resultDeny as $result) {
            $this->assertTrue(!in_array($result["alpha"], [1, 6]));
        }
    }

    /**
     * @dataProvider minMaxCommitOfferingProvider
     */
    public function testGetAssetTermRemaining(array $input, int $expected)
    {
        $actual = Util::getAssetTermRemaining($input);
        if (!is_null($expected)) {
            // Term remaining will vary by 1 month depending on length of the month (in days)
            $this->assertGreaterThanOrEqual($expected, $actual);
            $this->assertLessThanOrEqual($expected + 1, $actual);
        } else {
            $this->assertNull($actual);
        }
    }

    public function minMaxCommitOfferingProvider(): \Generator
    {
        $createdAt = new \DateTime("-6 months");
        $createdAt = new \DateTime($createdAt->format('Y-m-10 08:05'));
        yield 'No term length' => [
            [
                "created_at" => $createdAt->format(\DateTime::ATOM),
                "custom" => ["investment_term" => ""],
                "info" => [
                    [
                        "type" => "investment_term",
                        "value" => "",
                    ]
                ],
                "term_remaining" => null
            ],
            0
        ];
        yield 'No term remaining but has term length' => [
            [
                "created_at" => $createdAt->format(\DateTime::ATOM),
                "custom" => ["investment_term" => "12"],
                "info" => [
                    [
                        "type" => "investment_term",
                        "value" => "12",
                    ]
                ],
                "term_remaining" => null
            ],
            5
        ];
        yield 'Has term remaining' => [
            [
                "created_at" => $createdAt->format(\DateTime::ATOM),
                "custom" => ["investment_term" => "12"],
                "info" => [
                    [
                        "type" => "investment_term",
                        "value" => "",
                    ]
                ],
                "term_remaining" => 5
            ],
            5
        ];
    }
}
