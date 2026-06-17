<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\Entity\AbstractOrder;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeStatus;
use App\Entity\Enum\TransferType;
use App\Entity\ShareTrade;
use App\Entity\TradeOrder;
use App\Entity\TransferOrder;
use App\Entity\TransferRequest;
use App\Event\TransferOrder\TransferOrderCompletedEvent;
use App\EventSubscriber\TransferOrderSubscriber;
use App\Test\Util\EntityIdTestUtil;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class TransferOrderSubscriberTest extends KernelTestCase
{
    private TransferOrderSubscriber $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(TransferOrderSubscriber::class);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('settlementOrderCompletionProvider')]
    public function testProcessOrderCompletionDividend(
        TradeOrderStatus $expectedBuy,
        TradeOrderStatus $expectedSell,
        int $buyAvailable = 0,
        int $sellAvailable = 0,
        TradeStatus $tradeStatus = TradeStatus::Settled,
        TradeOrderStatus $buyStart = TradeOrderStatus::Active,
        TradeOrderStatus $sellStart = TradeOrderStatus::Active,
    ): void {
        $buyOrder = EntityIdTestUtil::setEntityId(
            new TradeOrder(direction: TradeDirection::Buy, numberOfShares: 100),
            444,
        );
        $buyOrder->setStatus($buyStart);
        $buyOrder->setSharesTraded(100 - $buyAvailable);
        $sellOrder = EntityIdTestUtil::setEntityId(
            new TradeOrder(direction: TradeDirection::Sell, numberOfShares: 100),
            777,
        );
        $sellOrder->setStatus($sellStart);
        $sellOrder->setSharesTraded(100 - $sellAvailable);

        $shareTrade = EntityIdTestUtil::setEntityId(
            new ShareTrade(buyOrder: $buyOrder, sellOrder: $sellOrder),
            5167,
        );
        $shareTrade->setStatus($tradeStatus);
        $buyOrder->addShareTrade($shareTrade);
        $sellOrder->addShareTrade($shareTrade);

        $transferRequest = new TransferRequest();
        $transferRequest->setShareTrade($shareTrade);

        $transferOrder = new TransferOrder();
        $transferOrder->setStatus(AbstractOrder::STATE_COMPLETED);
        $transferOrder->setTransferType(TransferType::InvestmentSettlement);
        $transferOrder->addTransfer($transferRequest);

        $event = new TransferOrderCompletedEvent($transferOrder);
        $this->service->processOrderCompletion($event);

        $this->assertEquals($expectedBuy, $buyOrder->getStatus());
        $this->assertEquals($expectedSell, $sellOrder->getStatus());
    }

    public static function settlementOrderCompletionProvider(): \Generator
    {
        foreach (TradeOrderStatus::tradeExecutionStates() as $status) {
            yield "Both {$status->name} to completed" => [
                'expectedBuy' => TradeOrderStatus::Completed,
                'expectedSell' => TradeOrderStatus::Completed,
                'buyAvailable' => 0,
                'sellAvailable' => 0,
                'tradeStatus' => TradeStatus::Settled,
                'buyStart' => $status,
                'sellStart' => $status,
            ];
        }
        foreach (array_udiff(
            TradeOrderStatus::cases(),
            TradeOrderStatus::tradeExecutionStates(),
            fn($r1, $r2) => $r1->value <=> $r2->value,
        ) as $status) {
            yield "Both {$status->name} so ignored" => [
                'expectedBuy' => $status,
                'expectedSell' => $status,
                'buyAvailable' => 0,
                'sellAvailable' => 0,
                'tradeStatus' => TradeStatus::Settled,
                'buyStart' => $status,
                'sellStart' => $status,
            ];
        }
        yield 'Unsettled ignored' => [
            'expectedBuy' => TradeOrderStatus::Active,
            'expectedSell' => TradeOrderStatus::Active,
            'buyAvailable' => 0,
            'sellAvailable' => 0,
            'tradeStatus' => TradeStatus::Unsettled,
            'buyStart' => TradeOrderStatus::Active,
            'sellStart' => TradeOrderStatus::Active,
        ];
        yield 'Buy incomplete, sell complete' => [
            'expectedBuy' => TradeOrderStatus::Active,
            'expectedSell' => TradeOrderStatus::Completed,
            'buyAvailable' => 1,
            'sellAvailable' => 0,
            'tradeStatus' => TradeStatus::Settled,
            'buyStart' => TradeOrderStatus::Active,
            'sellStart' => TradeOrderStatus::Active,
        ];
        yield 'Both incomplete' => [
            'expectedBuy' => TradeOrderStatus::Active,
            'expectedSell' => TradeOrderStatus::Active,
            'buyAvailable' => 1,
            'sellAvailable' => 1,
            'tradeStatus' => TradeStatus::Settled,
            'buyStart' => TradeOrderStatus::Active,
            'sellStart' => TradeOrderStatus::Active,
        ];
    }
}
