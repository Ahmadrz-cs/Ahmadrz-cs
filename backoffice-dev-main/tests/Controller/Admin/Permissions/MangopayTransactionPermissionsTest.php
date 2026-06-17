<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Test\PermissionsWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
class MangopayTransactionPermissionsTest extends PermissionsWebTestCase
{
    // Comment out to reduce Mangopay API call load during tests
    // #[\PHPUnit\Framework\Attributes\DataProvider('minAnalystProvider')]
    // public function testGetMangopayTransfers(string $user, int $expected): void
    // {
    //     $this->loginWebClient($user);
    //     $readPaths = [
    //         "/admin/transactions/mangopay/transfers/xfer_m_01HR4YKAEV4M2ER2YA9ERPPR67",
    //         "/admin/transactions/mangopay/refunds/refund_m_01HR5B4VWTNBYGQZRYR2Z6QQ1D",
    //     ];
    //     foreach ($readPaths as $path) {
    //         $this->client->request('GET', $path);
    //         $this->assertResponseStatusCodeSame($expected);
    //     }
    // }

    #[\PHPUnit\Framework\Attributes\DataProvider('minFinopsProvider')]
    public function testCreateMangopayTransferRefund(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $readPaths = [
            '/admin/transactions/mangopay/transfers/xfer_m_01HW5R4MH8BZCBN9SEDVRR9XBJ/refund',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }
}
