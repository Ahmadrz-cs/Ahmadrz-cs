<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 13/06/18
 * Time: 13:14
 */

namespace App\Tests\Service\Util;

use App\Service\Util\Helper;

class CountryCodeConversionTest extends \PHPUnit\Framework\TestCase
{
    public function testCodeConversion(): void
    {
        $countryCode = Helper::getCountryCode('United Kingdom');
        $this->assertEquals('GB', $countryCode);

        $countryCode = Helper::getCountryCode('United States');
        $this->assertEquals('US', $countryCode);
    }

    public function testNameConversion(): void
    {
        $countryName = Helper::getCountryCode('GB');
        $this->assertEquals('United Kingdom', $countryName);

        $countryName = Helper::getCountryCode('US');
        $this->assertEquals('United States', $countryName);
    }

    public function testPalestinian_Issue1198(): void
    {
        $countryName = Helper::getCountryCode('Palestinian Territories');

        $this->assertEquals('PS', $countryName);
    }

    public function testPS_Issue1198(): void
    {
        $countryName = Helper::getCountryCode('PS');

        $this->assertEquals('Palestinian Territories', $countryName);
    }

    public function testSyria_Issue1204(): void
    {
        $countryName = Helper::getCountryCode('Syria');

        $this->assertEquals('SY', $countryName);
    }

    public function testSY_Issue1204(): void
    {
        $countryName = Helper::getCountryCode('SY');

        $this->assertEquals('Syrian Arab Republic', $countryName);
    }
}
