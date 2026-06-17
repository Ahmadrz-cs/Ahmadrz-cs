<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Test\PermissionsWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
class BankAccountPermissionsTest extends PermissionsWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testBankAccountRead(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $readPaths = [
            '/admin/bank-accounts',
            '/admin/bank-accounts/schema',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testBankAccountCreate(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->request('GET', '/admin/bank-accounts/create');
        $this->assertResponseStatusCodeSame($expected);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testBankAccountUpdate(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $readPaths = [
            '/admin/bank-accounts/1',
            '/admin/bank-accounts/1/edit',
            '/admin/bank-accounts/1/clear-custom',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minFinopsProvider')]
    public function testBankAccountStateTransitions(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $readPaths = [
            '/admin/bank-accounts/1/approve',
            '/admin/bank-accounts/1/unapprove',
            '/admin/bank-accounts/1/reject',
            '/admin/bank-accounts/1/enable',
            '/admin/bank-accounts/1/disable',
            '/admin/bank-accounts/1/reopen',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }
}
