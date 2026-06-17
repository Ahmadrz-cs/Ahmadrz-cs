<?php

namespace App\Tests\Service\Util;

use App\Service\Util\Helper;

class PhoneNumberHelperTest extends \PHPUnit\Framework\TestCase
{
    public static function phoneNumberStringProvider(): \Generator
    {
        yield 'UK E.164' => ['+447111222333', '+447111222333'];
        yield 'UK E.164 with spaces' => ['+44 7111 222 333', '+447111222333'];
        yield 'USA E.164' => ['+14151231234', '+14151231234'];
        yield 'UK local' => ['07111222333', '+447111222333'];
        yield 'UK local with spaces' => ['07 111 222 333', '+447111222333'];
        yield 'UK missing country code and 0' => ['+7111222333', '+7111222333']; // not fixable
        yield 'Account deleted' => ['Account deleted', null];
        yield 'Undefined JS issue' => ['+undefined7111222333', null];
        yield 'Clearly invalid number' => ['111111222333', '111111222333'];
        yield 'Too long' => ['+1234567891011121314', '+123456789101112'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('phoneNumberStringProvider')]
    public function testValidateExportFormat(string $input, ?string $expected): void
    {
        $actual = Helper::preparePhoneNumber($input);
        $this->assertEquals($expected, $actual);
    }
}
