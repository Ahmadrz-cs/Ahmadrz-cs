<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Test\PermissionsWebTestCase;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
class ShareTradePermissionsTest extends PermissionsWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('minAnalystProvider')]
    public function testShareTradesRead(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $paths = [
            '/admin/share-trades',
            '/admin/share-trades/1',
        ];
        foreach ($paths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testShareTradesUpdate(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $readPaths = [
            '/admin/share-trades/1/edit',
            '/admin/share-trades/status-logs/1',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testShareTradesCreate(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $paths = [
            '/admin/share-trades/create',
            '/admin/share-trades/1/status-logs/create',
        ];
        foreach ($paths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }
}
