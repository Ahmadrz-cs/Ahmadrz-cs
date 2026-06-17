<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Entity\TransferOrder;
use App\Test\PermissionsWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
class MonthendPermissionsTest extends PermissionsWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('minAnalystProvider')]
    public function testMonthendRead(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $readPaths = [
            '/admin/monthend',
            '/admin/monthend/assets',
            '/admin/monthend/income-disaggregations',
            '/admin/monthend/dividends',
            '/admin/monthend/settlements',
            '/admin/monthend/settlements/list',
            '/admin/monthend/repayments',
            '/admin/monthend/repayments/list',
            '/admin/monthend/divestments',
            '/admin/monthend/fee-collections',
            '/admin/monthend/review',
            '/admin/monthend/1',
            '/admin/monthend/1/income-transfers',
            '/admin/monthend/1/dividends',
            '/admin/monthend/1/settlements',
            '/admin/monthend/1/repayments',
            '/admin/monthend/1/divestments',
            '/admin/monthend/1/share-transfers',
            '/admin/monthend/1/review',
            '/admin/monthend/1/wallet-checker',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testMonthendUpdate(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $readPaths = [
            '/admin/monthend/1/update-checklist',
            '/admin/monthend/payments/1/notifications',
            '/admin/monthend/payments/1/send-notification',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }
}
