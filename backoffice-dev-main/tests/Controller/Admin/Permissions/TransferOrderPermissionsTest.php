<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Entity\TransferOrder;
use App\Test\PermissionsWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
class TransferOrderPermissionsTest extends PermissionsWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('minAnalystProvider')]
    public function testTransferOrderRead(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $readPaths = [
            '/admin/transfer-orders',
            '/admin/transfer-orders/1/manage',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minAnalystProvider')]
    public function testTransferOrderExport(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        // prevent output from being dumped into console with buffer
        ob_start();
        $exportPaths = [
            '/admin/transfer-orders/export',
            '/admin/transfer-orders/1/export',
        ];
        foreach ($exportPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
        ob_end_clean();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testTransferOrderCreate(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->request('GET', '/admin/transfer-orders/create');
        $this->assertResponseStatusCodeSame($expected);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testTransferOrderUpdate(string $user, int $expected): void
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
            '/admin/transfer-orders/1/edit',
            '/admin/transfer-orders/1/add-transfer',
            '/admin/transfer-orders/1/add-asset-transfer',
            '/admin/transfer-orders/1/clear-transfers',
            '/admin/transfer-requests/' . $requestId . '/edit',
            '/admin/transfer-requests/' . $requestId . '/delete',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minFinopsProvider')]
    public function testTransferOrderStateTransitions(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $draftOrder = $this->searchFixtures(TransferOrder::class, [
            'status' => TransferOrder::STATE_DRAFT,
        ])[0];
        $approvedOrder = $this->searchFixtures(TransferOrder::class, [
            'status' => TransferOrder::STATE_APPROVED,
        ])[0];
        $closedOrder = $this->searchFixtures(TransferOrder::class, [
            'status' => TransferOrder::STATE_CLOSED,
        ])[0];
        $inProgressOrder = $this->searchFixtures(TransferOrder::class, [
            'status' => TransferOrder::STATE_IN_PROGRESS,
        ])[0];
        $readPaths = [
            '/admin/transfer-orders/' . $draftOrder->getId() . '/approve',
            '/admin/transfer-orders/' . $approvedOrder->getId() . '/request_change',
            '/admin/transfer-orders/' . $approvedOrder->getId() . '/reject',
            '/admin/transfer-orders/' . $closedOrder->getId() . '/reopen',
            '/admin/transfer-orders/' . $inProgressOrder->getId() . '/abandon',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minFinopsProvider')]
    public function testTransferOrderRun(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        /** @var TransferOrder $zeroOrder */
        $zeroOrder = $this->searchFixtures(TransferOrder::class, [
            'status' => TransferOrder::STATE_APPROVED,
            'hasTransfers' => true,
            'description' => 'SingleZeroTransferRequest',
        ])[0];
        $requestId = $zeroOrder->getTransfers()[0]->getId();
        $readPaths = [
            '/admin/transfer-orders/' . $zeroOrder->getId() . '/run',
            '/admin/transfer-requests/' . $requestId . '/run',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }
}
