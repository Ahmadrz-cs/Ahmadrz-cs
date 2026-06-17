<?php

namespace App\Tests\Controller\Admin;

use App\Test\FixtureWebTestCase;
use App\Test\Util\ExportTestUtil;

class PaymentOrderControllerExportTest extends FixtureWebTestCase
{
    /**
     * @param string[] $expectedColumns
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('exportRoutesProvider')]
    public function testPaymentOrderExportColumns(
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
        yield 'Payment Orders' => [
            '/admin/payment-order/export',
            [
                'id',
                'paymentType',
                'assetId',
                'assetSpv',
                'assetName',
                'status',
                'scheduledFor',
                'description',
                'totalPayments',
                'approvedBy',
            ],
        ];
        yield 'Payment Requests' => [
            '/admin/payment-order/1/export',
            [
                'id',
                'paymentOrderId',
                'paymentType',
                'status',
                'payeeId',
                'payeeName',
                'payeeUsername',
                'amount',
                'shareholding',
                'payoutId',
            ],
        ];
    }
}
