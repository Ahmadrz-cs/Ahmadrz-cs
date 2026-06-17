<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Test\PermissionsWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
class DivestmentPermissionsTest extends PermissionsWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('minAnalystProvider')]
    public function testRepaymentPaymentRead(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $readPaths = [
            '/admin/monthend/divestments/1',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testRepaymentPaymentCreate(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->request('GET', '/admin/monthend/divestments/create');
        $this->assertResponseStatusCodeSame($expected);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testRepaymentPaymentUpdate(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $readPaths = [
            '/admin/monthend/divestments/1/generate',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }
}
