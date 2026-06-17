<?php

namespace App\Tests\Entity;

use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TradeStatus;
use App\Entity\ShareTrade;
use App\Entity\TradeOrder;
use BcMath\Number;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class TradeOrderTest extends TestCase
{
    public function testConstruct(): void
    {
        $actual = new TradeOrder();

        $this->assertNull($actual->getDirection());
        $this->assertNull($actual->getAsset());
        $this->assertNull($actual->getUser());
        $this->assertEquals(TradeOrderType::Market, $actual->getType());
        $this->assertEquals(0, $actual->getNumberOfShares());
        $this->assertEquals(new Number(0), $actual->getPricePerShare());
        $this->assertEquals(new Number(0), $actual->getFees());
        $this->assertEquals(new Number(0), $actual->getTaxes());
        $this->assertTrue(Uuid::isValid($actual->getUuid()));
        $this->assertNull($actual->getNotes());
    }

    public function testSettingNumberValues(): void
    {
        // Rounding is the default RoundingMode::HalfAwayFromZero, so 5 is rounded UP
        // From constructor
        $actual = new TradeOrder(
            pricePerShare: new Number('8.190041561'),
            fees: new Number('12.5058971'),
            taxes: new Number('5.54436'),
        );

        $this->assertEquals(new Number('8.190042'), $actual->getPricePerShare());
        $this->assertEquals(new Number('12.51'), $actual->getFees());
        $this->assertEquals(new Number('5.54'), $actual->getTaxes());

        // From setting individual fields
        $actual->setPricePerShare('8.190041561');
        $actual->setFees('12.5058971');
        $actual->setTaxes('5.54436');

        $this->assertEquals(new Number('8.190042'), $actual->getPricePerShare());
        $this->assertEquals(new Number('12.51'), $actual->getFees());
        $this->assertEquals(new Number('5.54'), $actual->getTaxes());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('stampDutyTestProvider')]
    public function testGetExpectedStampDuty(
        int $expected,
        string $sharePrice,
        int $shareQuantity,
        TradeDirection $direction,
        TradeOrderType $type,
    ): void {
        $tradeOrder = new TradeOrder(
            numberOfShares: $shareQuantity,
            pricePerShare: new Number($sharePrice),
            type: $type,
            direction: $direction,
        );
        $this->assertEquals(new Number($expected), $tradeOrder->getExpectedStampDuty());
    }

    public static function stampDutyTestProvider(): \Generator
    {
        yield 'Below 1k' => [
            0,
            '1.84',
            543,
            TradeDirection::Buy,
            TradeOrderType::Market,
        ];
        yield 'Sell order' => [
            0,
            '1.84',
            5621,
            TradeDirection::Sell,
            TradeOrderType::Market,
        ];
        yield 'Not market type' => [
            0,
            '1.84',
            4241,
            TradeDirection::Buy,
            TradeOrderType::Prefunding,
        ];
        yield 'Exactly 1k' => [
            5,
            '2.50',
            400,
            TradeDirection::Buy,
            TradeOrderType::Market,
        ];
        yield 'Between 1k and 2k' => [
            10,
            '1.84',
            843,
            TradeDirection::Buy,
            TradeOrderType::Market,
        ];
        yield '4k plus' => [
            25,
            '1.84',
            2500,
            TradeDirection::Buy,
            TradeOrderType::Market,
        ];
    }
}
