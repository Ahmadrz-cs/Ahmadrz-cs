<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Entity\PaymentOrder;
use App\Test\PermissionsWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
class PaymentOrderPermissionsTest extends PermissionsWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('minAnalystProvider')]
    public function testPaymentOrderRead(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $readPaths = [
            '/admin/payment-order',
            '/admin/payment-order/1/manage',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minAnalystProvider')]
    public function testPaymentOrderExport(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        // prevent output from being dumped into console with buffer
        ob_start();
        $exportPaths = [
            '/admin/payment-order/export',
            '/admin/payment-order/1/export',
        ];
        foreach ($exportPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
        ob_end_clean();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testPaymentOrderCreate(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->request('GET', '/admin/payment-order/create');
        $this->assertResponseStatusCodeSame($expected);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testPaymentOrderUpdate(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        /** @var PaymentOrder $draftOrder */
        $draftOrder = $this->searchFixtures(PaymentOrder::class, [
            'status' => PaymentOrder::STATE_DRAFT,
            'hasPayments' => true,
        ])[0];
        $requestId = $draftOrder->getPayments()[0]->getId();
        $readPaths = [
            '/admin/payment-order/1/edit',
            '/admin/payment-orders/1/date',
            '/admin/payment-orders/1/description',
            '/admin/payment-order/1/add-payment',
            '/admin/payment-order/1/clear-payments',
            '/admin/payment-request/' . $requestId . '/edit',
            '/admin/payment-request/' . $requestId . '/delete',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minFinopsProvider')]
    public function testPaymentOrderStateTransitions(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $draftOrder = $this->searchFixtures(PaymentOrder::class, [
            'status' => PaymentOrder::STATE_DRAFT,
        ])[0];
        $approvedOrder = $this->searchFixtures(PaymentOrder::class, [
            'status' => PaymentOrder::STATE_APPROVED,
        ])[0];
        $closedOrder = $this->searchFixtures(PaymentOrder::class, [
            'status' => PaymentOrder::STATE_CLOSED,
        ])[0];
        $inProgressOrder = $this->searchFixtures(PaymentOrder::class, [
            'status' => PaymentOrder::STATE_IN_PROGRESS,
        ])[0];
        $readPaths = [
            '/admin/payment-order/' . $draftOrder->getId() . '/approve',
            '/admin/payment-order/' . $approvedOrder->getId() . '/request-changes',
            '/admin/payment-order/' . $approvedOrder->getId() . '/close',
            '/admin/payment-order/' . $closedOrder->getId() . '/reopen',
            '/admin/payment-order/' . $inProgressOrder->getId() . '/abandon',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minFinopsProvider')]
    public function testPaymentOrderRun(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        /** @var PaymentOrder $zeroOrder */
        $zeroOrder = $this->searchFixtures(PaymentOrder::class, [
            'status' => PaymentOrder::STATE_APPROVED,
            'hasPayments' => true,
            'description' => 'SingleZeroPaymentRequest',
        ])[0];
        $requestId = $zeroOrder->getPayments()[0]->getId();
        $readPaths = [
            '/admin/payment-order/' . $zeroOrder->getId() . '/run',
            '/admin/payment-request/' . $requestId . '/pay',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }
}
