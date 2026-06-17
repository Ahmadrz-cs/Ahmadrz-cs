<?php

namespace App\Tests\Service\Util;

use App\Service\Util\Helper;

class GeneratorTest extends \PHPUnit\Framework\TestCase
{
    public function testgeneratePastMonthsStringsDefault(): void
    {
        $generatedStrings = Helper::generatePastMonthsStrings();

        /**
         * Check
         * - date format is YYYY-MM
         * - Current month included
         * - There are 12 months
         * - Ordering works as expected
         */
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}$/', $generatedStrings[0]);
        $this->assertTrue(in_array(date('Y-m', time()), $generatedStrings));
        $this->assertEquals(12, count($generatedStrings));
        $this->assertLessThan(
            strtotime($generatedStrings[1]),
            strtotime($generatedStrings[0]),
        );
    }

    public function testgeneratePastMonthsStringsOptions(): void
    {
        $options = [
            'numberOfMonths' => 14,
            'offsetFromToday' => 5,
            'dateFormat' => 'y-m-d',
            'order' => 'DESC',
        ];
        $generatedStrings = Helper::generatePastMonthsStrings(
            $options['numberOfMonths'],
            $options['offsetFromToday'],
            $options['dateFormat'],
            $options['order'],
        );

        /**
         * Check
         * - date format is YY-MM-DD as per formatting defined
         * - Current month not included
         * - There are 14 months as defined
         * - Ordering DESC works
         */

        $this->assertMatchesRegularExpression(
            '/^\d{2}-\d{2}-\d{2}$/',
            $generatedStrings[0],
        );
        $this->assertFalse(in_array(date('Y-m', time()), $generatedStrings));
        $this->assertEquals(14, count($generatedStrings));
        $this->assertLessThan(
            strtotime($generatedStrings[0]),
            strtotime($generatedStrings[1]),
        );
    }
}
