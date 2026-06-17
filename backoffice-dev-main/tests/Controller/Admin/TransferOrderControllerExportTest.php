<?php

namespace App\Tests\Controller\Admin;

use App\Test\FixtureWebTestCase;
use App\Test\Util\ExportTestUtil;

class TransferOrderControllerExportTest extends FixtureWebTestCase
{
    /**
     * @param string[] $expectedColumns
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('exportRoutesProvider')]
    public function testTransferOrderExportColumns(
        string $route,
        array $expectedColumns,
    ): void {
        $this->loginWebClient(self::USER_SUPER_ADMIN);
        $exportedData = ExportTestUtil::downloadCsvToArray($this->client, $route);
        $this->assertResponseIsSuccessful();
        $actual = array_shift($exportedData);
        $this->assertEmpty(array_diff($expectedColumns, $actual));
    }

    public static function exportRoutesProvider(): \Generator
    {
        yield 'Transfer Orders' => [
            '/admin/transfer-orders/export',
            [
                'id',
                'type',
                'description',
                'assetId',
                'assetSpv',
                'assetName',
                'status',
                'scheduledFor',
                'description',
                'totalTransfers',
                'approvedBy',
            ],
        ];
        yield 'Transfer Requests' => [
            '/admin/transfer-orders/1/export',
            [
                'id',
                'investment',
                'transferOrderId',
                'description',
                'debitWalletId',
                // 'debitWalletOwner',
                'creditWalletId',
                // 'creditWalletOwner',
                'status',
                'amount',
                'transactionId',
            ],
        ];
    }
}
