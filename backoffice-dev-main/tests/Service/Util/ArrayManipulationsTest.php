<?php

namespace App\Tests\Service\Util;

use App\Service\Util\Helper;

class ArrayManipulationsTest extends \PHPUnit\Framework\TestCase
{
    public function testConvertArrayToCumulative(): void
    {
        $numArr = [5, 2, 4, 1, 7, 9, 3, 2, 3, 1, 6];
        $cumulativeArr = [5, 7, 11, 12, 19, 28, 31, 33, 36, 37, 43];

        $this->assertEmpty(array_diff(
            $cumulativeArr,
            Helper::convertArrayToCumulative($numArr),
        ));
    }

    public function testConvertArrayToCumulativeWithBase(): void
    {
        $numArr = [5, 2, 4, 1, 7, 9, 3, 2, 3, 1, 6];
        $cumulativeArr = [21, 23, 27, 28, 35, 44, 47, 49, 52, 53, 59];
        $starterNum = 16;

        $this->assertEmpty(array_diff($cumulativeArr, Helper::convertArrayToCumulative(
            $numArr,
            $starterNum,
        )));
    }

    public function testConvertArrayKeysAsIds(): void
    {
        $expected = 8;

        $entity = new \App\Entity\Asset();
        $reflection = new \ReflectionClass($entity);
        $reflectionProperty = $reflection->getProperty('id');
        $reflectionProperty->setValue($entity, $expected);

        $actual = Helper::convertArrayKeysAsIds([$entity]);

        $this->assertArrayHasKey($expected, $actual);
        $this->assertSame($expected, $actual[$expected]->getId());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('kvPairProvider')]
    public function testStringifyKeyValuePairs(
        string $expected,
        array $input,
        ?string $separator = null,
    ): void {
        if ($separator) {
            $actual = Helper::stringifyKeyValuePairs($input, $separator);
        } else {
            $actual = Helper::stringifyKeyValuePairs($input);
        }
        $this->assertSame($expected, $actual);
    }

    public static function kvPairProvider(): \Generator
    {
        yield 'Empty' => [
            '',
            [],
        ];
        yield 'Single' => [
            'AstName:Clarence Hold A - Camden;',
            [
                'AstName' => 'Clarence Hold A - Camden',
            ],
        ];
        yield 'Multi' => [
            'AstName:Clarence Hold A - Camden;AstCode:SPVT00098;Type:Divestment;',
            [
                'AstName' => 'Clarence Hold A - Camden',
                'AstCode' => 'SPVT00098',
                'Type' => 'Divestment',
            ],
        ];
        yield 'Multi diff separator' => [
            'AstName:Clarence Hold A - Camden~==||==~AstCode:SPVT00098~==||==~Type:Divestment~==||==~',
            [
                'AstName' => 'Clarence Hold A - Camden',
                'AstCode' => 'SPVT00098',
                'Type' => 'Divestment',
            ],
            '~==||==~',
        ];
    }
}
