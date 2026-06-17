<?php

namespace App\Tests\Service\Util;

use App\Service\Util\Helper;

class RegexSanitisationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @psalm-return \Generator<string, array{0: string, 1: bool}, mixed, void>
     */
    public static function ymdDateProvider(): \Generator
    {
        yield '0 prefix month and day' => ['3030-09-01', true];
        yield '0 prefix year' => ['0930-09-10', true];
        yield '1 prefix month and day' => ['2012-11-15', true];
        yield '2 prefix day' => ['2012-10-25', true];
        yield '3 prefix day' => ['2012-10-31', true];
        yield 'invalid day' => ['2012-12-32', false];
        yield 'invalid month' => ['3030-14-01', false];
        yield 'unprefixed day' => ['3030-01-1', false];
        yield 'unprefixed month' => ['3030-1-01', false];
        yield 'non 4-digit year' => ['303-01-01', false];
        yield '3 digit day' => ['2012-12-109', false];
        yield '3 digit month' => ['2012-127-10', false];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('ymdDateProvider')]
    public function testIsValidDate(string $dateString, bool $expected): void
    {
        $actual = Helper::isValidDate($dateString);
        $this->assertEquals($expected, $actual);
    }
}
