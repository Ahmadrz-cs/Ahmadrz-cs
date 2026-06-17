<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Test\PermissionsWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
class FeeCollectionPermissionsTest extends PermissionsWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('minAnalystProvider')]
    public function testFeeCollectionRead(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $readPaths = [
            '/admin/monthend/fee-collections',
            '/admin/monthend/fee-collections/1',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testFeeCollectionCreate(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->request('GET', '/admin/monthend/fee-collections/create');
        $this->assertResponseStatusCodeSame($expected);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testFeeCollectionUpdate(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $readPaths = [
            '/admin/monthend/fee-collections/1/edit',
            '/admin/monthend/fee-collections/1/setup',
            '/admin/monthend/fee-collections/1/add-transfer',
            '/admin/monthend/fee-collections/1/add-transfer/1',
            '/admin/monthend/fee-collections/1/generate',
            '/admin/monthend/fee-collections/1/generate/relisting',
            '/admin/monthend/fee-collections/1/generate',
            '/admin/monthend/fee-collections/1/income-deposits',
            '/admin/monthend/fee-collections/1/income-deposits/2',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }
}
