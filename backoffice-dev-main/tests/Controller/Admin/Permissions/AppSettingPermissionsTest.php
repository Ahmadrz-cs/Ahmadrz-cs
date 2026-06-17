<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Test\PermissionsWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
class AppSettingPermissionsTest extends PermissionsWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('minTechopsProvider')]
    public function testAppSettingRoutes(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $readPaths = [
            '/admin/settings',
            '/admin/settings/mangopay-client',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minAdminProvider')]
    public function testAppSettingAdminRoutes(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $readPaths = [
            '/admin/settings/superadmin/mangopay-sca',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }
}
