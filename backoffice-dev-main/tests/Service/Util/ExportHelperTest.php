<?php

namespace App\Tests\Service\Util;

use App\Service\Util\ExportHelper;

class ExportHelperTest extends \PHPUnit\Framework\TestCase
{
    public static function exportFormatProvider(): \Generator
    {
        yield 'csv' => ['csv', 'csv'];
        yield 'json' => ['json', 'json'];
        yield 'xls' => ['xls', 'xls'];
        yield 'txt' => ['txt', 'csv'];
        yield 'sql' => ['sql', 'csv'];
        yield 'empty' => ['', 'csv'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('exportFormatProvider')]
    public function testValidateExportFormat(string $format, string $expected): void
    {
        $actual = ExportHelper::validateExportFormat($format);
        $this->assertEquals($expected, $actual);
    }

    public function testGenerateFileName(): void
    {
        $actual = ExportHelper::generateFileName('shareholdings', 'json');
        $this->assertMatchesRegularExpression('/^shareholdings.{25}\.json$/', $actual);
    }
}
