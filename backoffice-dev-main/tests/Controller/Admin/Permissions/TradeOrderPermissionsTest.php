<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Test\PermissionsWebTestCase;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
class TradeOrderPermissionsTest extends PermissionsWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('minAnalystProvider')]
    public function testTradeOrdersRead(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $paths = [
            '/admin/trade-orders',
            '/admin/trade-orders/1',
        ];
        foreach ($paths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testTradeOrdersUpdate(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $readPaths = [
            '/admin/trade-orders/1/edit',
            '/admin/trade-orders/status-logs/1',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testTradeOrdersCreate(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $paths = [
            '/admin/trade-orders/create',
            '/admin/trade-orders/1/status-logs/create',
            '/admin/trade-orders/1/status-logs/create/submitted',
        ];
        foreach ($paths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }
}
