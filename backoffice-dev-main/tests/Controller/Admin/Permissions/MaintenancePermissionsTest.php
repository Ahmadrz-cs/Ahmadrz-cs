<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Test\PermissionsWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
class MaintenancePermissionsTest extends PermissionsWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('minTechopsProvider')]
    public function testMaintenance(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $readPaths = [
            '/admin/maintenance/user-comms',
            '/admin/maintenance/user-comms/list',
            '/admin/maintenance/user-comms/cleanup',
            '/admin/maintenance/oauth2/cleanup',
            '/admin/maintenance/activity-logs/cleanup',
            '/admin/maintenance/card/cleanup?trackerOnly=1',
            '/admin/maintenance/jobs/card/cleanup',
            '/admin/maintenance/trade-system-porting',
            '/admin/maintenance/trade-system-porting/offerings',
            '/admin/maintenance/trade-system-porting/investments',
            '/admin/maintenance/trade-system-porting/offerings/1',
            '/admin/maintenance/trade-system-porting/investments/1',
            '/admin/maintenance/trade-system-porting/divestments',
            '/admin/maintenance/trade-system-porting/repayments',
            '/admin/maintenance/trade-system-porting/settlements',
            '/admin/maintenance/trade-system-porting/offering-documents',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minAdminProvider')]
    public function testAdminOnlyMaintenance(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $readPaths = [
            '/admin/investment/1/status',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }
}
