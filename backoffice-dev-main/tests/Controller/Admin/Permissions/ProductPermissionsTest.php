<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Test\PermissionsWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
class ProductPermissionsTest extends PermissionsWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('minAnalystProvider')]
    public function testProductRead(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $paths = [
            '/admin/products',
            '/admin/products/review/listings',
            '/admin/products/1',
            '/admin/products/1/shareholders',
            '/admin/products/1/investments',
            '/admin/products/1/listings',
            '/admin/products/1/payments',
            '/admin/products/1/payment-orders',
            '/admin/products/1/transfer-orders',
            '/admin/products/1/documents',
            '/admin/products/1/status-logs',
        ];
        foreach ($paths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testProductUpdate(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $paths = [
            '/admin/products/1/editor/wallets',
            '/admin/products/1/editor/about',
            '/admin/products/1/editor/location',
            '/admin/products/1/editor/financials',
            '/admin/products/1/editor/rules',
            '/admin/products/1/editor/status',
            '/admin/products/1/editor/status/toggle-selling',
            // '/admin/products/1/editor/status/toggle-featured',
            '/admin/products/1/editor/status/toggle-visibility',
            '/admin/products/1/editor/documents',
            '/admin/products/1/editor/launch',
            '/admin/products/1/editor/launch-prefunding',
            '/admin/products/1/editor/launch-retail',
        ];
        foreach ($paths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testProductCreate(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        // Document creation must provide a supported document type to avoid redirect
        $paths = [
            '/admin/products/create',
            '/admin/products/1/editor/documents/create?type=logo',
        ];
        foreach ($paths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }
}
