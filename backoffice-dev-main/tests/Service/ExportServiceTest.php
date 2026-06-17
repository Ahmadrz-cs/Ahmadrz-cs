<?php

namespace App\Tests\Service;

use App\Service\ExportService;
use App\Test\FixtureTestCase;

final class ExportServiceTest extends FixtureTestCase
{
    private ExportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(ExportService::class);
    }

    public function testGetAvailableCustomReports(): void
    {
        $expected = [
            ExportService::REPORT_ASSET => 1,
            ExportService::REPORT_CONTEGO => 1,
            ExportService::REPORT_INVESTMENT => 1,
            ExportService::REPORT_OFFERING => 1,
            ExportService::REPORT_PAYOUT => 1,
            ExportService::REPORT_INVESTMENT_PAYOUT => 1,
            ExportService::REPORT_SHARE_REGISTER => 1,
            ExportService::REPORT_SHARE_REGISTER_OLD => 1,
            ExportService::REPORT_LEGACY_SHARE_TRADES => 0,
            ExportService::REPORT_LEGACY_SHAREHOLDINGS => 0,
            ExportService::REPORT_LEGACY_SHAREHOLDINGS_EXT => 0,
            ExportService::REPORT_TRANSACTION => 1,
            ExportService::REPORT_USER => 1,
        ];

        $actual = $this->service->getAvailableCustomReports();
        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('reportValidProvider')]
    public function testIsSupportedReport(string $reportName, bool $expected): void
    {
        $actual = $this->service->isSupportedReport($reportName);
        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    public static function reportValidProvider(): \Generator
    {
        yield 'Asset' => [ExportService::REPORT_ASSET, true];
        yield 'Contego' => [ExportService::REPORT_CONTEGO, true];
        yield 'Investment' => [ExportService::REPORT_INVESTMENT, true];
        yield 'Offering' => [ExportService::REPORT_OFFERING, true];
        yield 'Payout' => [ExportService::REPORT_PAYOUT, true];
        yield 'Investment Payout' => [ExportService::REPORT_INVESTMENT_PAYOUT, true];
        yield 'Share register' => [ExportService::REPORT_SHARE_REGISTER, true];
        yield 'Slow Share register' => [ExportService::REPORT_SHARE_REGISTER_OLD, true];
        yield 'Share trades' => [ExportService::REPORT_LEGACY_SHARE_TRADES, true];
        yield 'Shareholdings' => [ExportService::REPORT_LEGACY_SHAREHOLDINGS, true];
        yield 'Shareholdings Ext' => [
            ExportService::REPORT_LEGACY_SHAREHOLDINGS_EXT,
            true,
        ];
        yield 'Transaction' => [ExportService::REPORT_TRANSACTION, true];
        yield 'User' => [ExportService::REPORT_USER, true];
        yield 'Invalid string' => ['Not a real view', false];
        yield 'Empty string' => ['', false];
    }

    public function testGetFieldNames(): void
    {
        /**
         * Only checking one view to check core behaviour
         * There are SQLite issues with the more complex views anyway (e.g. nested queries)
         * Only using a subset of fields which are more important
         * This also reduces maintenance pressure when changing the view
         */
        $expected = [
            'id',
            'name',
            'additionalType',
            'alternateName',
            'companyNumber',
            'amountOfShares',
            'pricePerShare',
            'stampDutyUser',
            'assetType',
            'investmentTerm',
            'mangoPayUserId',
            'mangoPayWalletId',
            'additional_wallet',
            'createdAt',
            'lifecycleStatus',
        ];
        $actual = $this->service->getFieldNames(ExportService::REPORT_ASSET);
        $this->assertEmpty(array_diff($expected, $actual));
    }

    public function testPrepareColumnNames(): void
    {
        $input = [
            'alllower',
            'camelCase',
            'under_Score_separator',
            'dot.separator',
            'space separator',
        ];
        $expected = [
            '`alllower`',
            '`camelCase`',
            '`under_Score_separator`',
            '`dot.separator`',
            '`space separator`',
        ];
        $actual = $this->service->prepareColumnNames($input);
        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    public function testGetReportDataNotSupported(): void
    {
        $this->assertEmpty($this->service->getReportData('dunnoThisOne'));
    }

    public function testGetReportData(): void
    {
        // Just checking a subset of fields as a sanity check
        $expected = [
            'id',
            'name',
            'additionalType',
            'alternateName',
            'companyNumber',
            'amountOfShares',
            'pricePerShare',
            'stampDutyUser',
            'assetType',
            'investmentTerm',
            'mangoPayUserId',
            'mangoPayWalletId',
            'additional_wallet',
            'createdAt',
            'lifecycleStatus',
        ];
        $actual = $this->service->getReportData(ExportService::REPORT_ASSET);
        $this->assertNotEmpty($actual);
        $this->assertEmpty(array_diff($expected, array_keys($actual[0])));
    }
}
