<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Test\PermissionsWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
class UpgradesPermissionsTest extends PermissionsWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('minTechopsProvider')]
    public function testUpgradesRead(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $paths = [
            '/admin/upgrades/mangopay-user-category',
            '/admin/upgrades/mangopay-user-category/review',
        ];
        foreach ($paths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }
}
