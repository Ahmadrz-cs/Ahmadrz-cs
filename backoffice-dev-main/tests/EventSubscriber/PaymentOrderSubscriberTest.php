<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\Entity\Asset;
use App\Entity\Enum\AssetStatus;
use App\Entity\Enum\PaymentType;
use App\Entity\PaymentOrder;
use App\Event\PaymentOrder\PaymentOrderCompletedEvent;
use App\EventSubscriber\PaymentOrderSubscriber;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class PaymentOrderSubscriberTest extends KernelTestCase
{
    private PaymentOrderSubscriber $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(PaymentOrderSubscriber::class);
    }

    public function testProcessOrderCompletionDividend(): void
    {
        $asset = new Asset();
        $paymentOrder = new PaymentOrder();
        $paymentOrder->setAsset($asset);
        $paymentOrder->setPaymentType(PaymentType::Dividend->value);
        $event = new PaymentOrderCompletedEvent($paymentOrder);
        $this->service->processOrderCompletion($event);

        $logs = $paymentOrder->getAsset()->getStatusLogs()->getValues();
        $this->assertCount(0, $logs);
    }

    public function testProcessOrderCompletionInvestmentExit(): void
    {
        $asset = new Asset();
        $paymentOrder = new PaymentOrder();
        $paymentOrder->setAsset($asset);
        $paymentOrder->setPaymentType(PaymentType::InvestmentExit->value);
        $event = new PaymentOrderCompletedEvent($paymentOrder);
        $this->service->processOrderCompletion($event);

        $logs = $paymentOrder->getAsset()->getStatusLogs()->getValues();
        $this->assertCount(1, $logs);
        $this->assertEquals(AssetStatus::Archived, $logs[0]->getStatus());
    }
}
