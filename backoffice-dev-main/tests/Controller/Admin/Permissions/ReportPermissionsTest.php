<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Test\PermissionsWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
class ReportPermissionsTest extends PermissionsWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testReportPages(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $readPaths = [
            '/admin/reports',
            '/admin/reports/1',
            '/admin/reports/sets',
            '/admin/reports/sets/1',
            '/admin/reports/create/mangopay',
            '/admin/reports/mangopay',
            '/admin/reports/mangopay/sets',
            '/admin/reports/mangopay/merger',
            '/admin/reports/mangopay/1',
            '/admin/reports/mangopay/transaction-report/create',
            '/admin/reports/mangopay/transaction-report/1',
            '/admin/reports/mangopay/transaction-report/1/edit',
            '/admin/reports/mangopay/transaction-report/1/wallet',
            '/admin/reports/mangopay/transaction-report/1/report-config',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }
}
