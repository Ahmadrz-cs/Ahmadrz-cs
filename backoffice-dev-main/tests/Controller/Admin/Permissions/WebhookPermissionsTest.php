<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Test\PermissionsWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
class WebhookPermissionsTest extends PermissionsWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('minTechopsProvider')]
    public function testMangopayHookRoutes(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $readPaths = [
            '/admin/webhooks/recent-events',
            '/admin/webhooks/mangopay',
            '/admin/webhooks/mangopay/create',
            '/admin/webhooks/mangopay/1',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }
}
