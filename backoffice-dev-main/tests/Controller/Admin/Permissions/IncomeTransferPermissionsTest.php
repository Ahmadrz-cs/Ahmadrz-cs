<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Entity\TransferOrder;
use App\Test\PermissionsWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
class IncomeTransferPermissionsTest extends PermissionsWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('minAnalystProvider')]
    public function testIncomeTransferRead(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $readPaths = [
            '/admin/monthend/income-transfers/1',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testIncomeTransferCreate(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $createPaths = [
            '/admin/monthend/income-transfers/create',
            '/admin/monthend/income-transfers/create/1',
        ];
        foreach ($createPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testIncomeTransferUpdate(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        /** @var TransferOrder $draftOrder */
        $draftOrder = $this->searchFixtures(TransferOrder::class, [
            'status' => TransferOrder::STATE_DRAFT,
            'hasTransfers' => true,
        ])[0];
        $requestId = $draftOrder->getTransfers()[0]->getId();
        $readPaths = [
            '/admin/monthend/income-transfers/1/edit',
            '/admin/monthend/income-transfers/1/add-transfer',
            "/admin/monthend/income-transfers/edit-transfer/{$requestId}",
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }
}
