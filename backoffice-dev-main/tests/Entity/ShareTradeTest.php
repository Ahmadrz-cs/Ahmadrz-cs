<?php

namespace App\Tests\Entity;

use App\Entity\Enum\ShareTradeType;
use App\Entity\Enum\TradeOrderType;
use App\Entity\ShareTrade;
use BcMath\Number;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ShareTradeTest extends TestCase
{
    public function testConstruct(): void
    {
        $actual = new ShareTrade();

        $this->assertNull($actual->getBuyOrder());
        $this->assertNull($actual->getSellOrder());
        $this->assertEquals(0, $actual->getNumberOfShares());
        $this->assertEquals(new Number(0), $actual->getPricePerShare());
        $this->assertEquals(new Number(0), $actual->getTradeValue());
        $this->assertTrue(Uuid::isValid($actual->getUuid()));
        $this->assertTrue($actual->isDerived());
    }

    public function testConstructWithTradeValue(): void
    {
        $actual = new ShareTrade(tradeValue: new Number('2000.50'));

        $this->assertNull($actual->getBuyOrder());
        $this->assertNull($actual->getSellOrder());
        $this->assertTrue(Uuid::isValid($actual->getUuid()));
        // Trade value is set even though no price or quantity was given
        $this->assertEquals(0, $actual->getNumberOfShares());
        $this->assertEquals(new Number(0), $actual->getPricePerShare());
        $this->assertEquals(new Number('2000.50'), $actual->getTradeValue());
        // Is derived should be false
        $this->assertFalse($actual->isDerived());
    }

    public function testSettingAndDerivingTradeValue(): void
    {
        $actual = new ShareTrade(
            numberOfShares: 1568,
            pricePerShare: new Number('8.19'),
            tradeValue: new Number('2000.50'),
        );

        $this->assertEquals(1568, $actual->getNumberOfShares());
        $this->assertEquals(new Number('8.19'), $actual->getPricePerShare());
        $this->assertEquals(new Number('2000.50'), $actual->getTradeValue());
        $this->assertFalse($actual->isDerived());

        $actual->deriveTradeValue();

        $this->assertEquals(1568, $actual->getNumberOfShares());
        $this->assertEquals(new Number('8.19'), $actual->getPricePerShare());
        $this->assertEquals(new Number('12841.92'), $actual->getTradeValue());
        $this->assertTrue($actual->isDerived());

        // Set as a string
        $actual->setTradeValue('10000.75');

        $this->assertEquals(1568, $actual->getNumberOfShares());
        $this->assertEquals(new Number('8.19'), $actual->getPricePerShare());
        $this->assertEquals(new Number('10000.75'), $actual->getTradeValue());
        $this->assertFalse($actual->isDerived());

        // Set as an integer
        $actual->setTradeValue(12000);

        $this->assertEquals(1568, $actual->getNumberOfShares());
        $this->assertEquals(new Number('8.19'), $actual->getPricePerShare());
        $this->assertEquals(new Number(12000), $actual->getTradeValue());
        $this->assertFalse($actual->isDerived());

        // Set as a number
        $actual->setTradeValue(new Number('18173.56'));

        $this->assertEquals(1568, $actual->getNumberOfShares());
        $this->assertEquals(new Number('8.19'), $actual->getPricePerShare());
        $this->assertEquals(new Number('18173.56'), $actual->getTradeValue());
        $this->assertFalse($actual->isDerived());

        // Try to set with greater scale than will be stored (2dp)
        // Rounding is the default RoundingMode::HalfAwayFromZero, so 5 is rounded UP

        // Manually setting the trade value
        $actual->setTradeValue(new Number('18173.567621'));

        $this->assertEquals(1568, $actual->getNumberOfShares());
        $this->assertEquals(new Number('8.19'), $actual->getPricePerShare());
        $this->assertEquals(new Number('18173.57'), $actual->getTradeValue());
        $this->assertFalse($actual->isDerived());

        // And the same if deriving

        $actual->setPricePerShare(new Number('8.190041561'));
        $actual->deriveTradeValue();
        $this->assertEquals(1568, $actual->getNumberOfShares());
        $this->assertEquals(new Number('8.190042'), $actual->getPricePerShare());
        $this->assertEquals(new Number('12841.99'), $actual->getTradeValue());
        $this->assertTrue($actual->isDerived());

        // Is still rounded even if inserted in constructor
        $actual = new ShareTrade(
            numberOfShares: 1568,
            pricePerShare: new Number('8.190041561'),
        );
        $this->assertEquals(1568, $actual->getNumberOfShares());
        $this->assertEquals(new Number('8.190042'), $actual->getPricePerShare());
        $this->assertEquals(new Number('12841.99'), $actual->getTradeValue());
        $this->assertTrue($actual->isDerived());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider(
        'shareTradeTypeBuySellPairingProvider',
    )]
    public function testShareTradeTypeMatching(
        ?ShareTradeType $expected,
        ?TradeOrderType $buyType,
        ?TradeOrderType $sellType,
    ): void {
        $actual = ShareTradeType::fromBuySellTypes($buyType, $sellType);
        $this->assertEquals($expected, $actual);
    }

    public static function shareTradeTypeBuySellPairingProvider(): \Generator
    {
        // Invalid combinations are not exhaustive
        // But all valid combinations are tested

        // First party
        yield 'First party - market' => [
            ShareTradeType::FirstParty,
            TradeOrderType::Market,
            TradeOrderType::Initial,
        ];
        yield 'First party - limit' => [
            ShareTradeType::FirstParty,
            TradeOrderType::Limit,
            TradeOrderType::Initial,
        ];
        yield 'First party - stop-loss' => [
            ShareTradeType::FirstParty,
            TradeOrderType::StopLoss,
            TradeOrderType::Initial,
        ];
        yield 'First party - off-market' => [
            ShareTradeType::FirstParty,
            TradeOrderType::OffMarket,
            TradeOrderType::Initial,
        ];

        // Secondary market, market seller
        yield 'Secondary market - market-market' => [
            ShareTradeType::SecondaryMarket,
            TradeOrderType::Market,
            TradeOrderType::Market,
        ];
        yield 'Secondary market - market-limit' => [
            ShareTradeType::SecondaryMarket,
            TradeOrderType::Limit,
            TradeOrderType::Market,
        ];
        yield 'Secondary market - market-stop-loss' => [
            ShareTradeType::SecondaryMarket,
            TradeOrderType::StopLoss,
            TradeOrderType::Market,
        ];
        yield 'Secondary market - market-off-market' => [
            ShareTradeType::SecondaryMarket,
            TradeOrderType::OffMarket,
            TradeOrderType::Market,
        ];

        // Secondary market, limit seller
        yield 'Secondary market - limit-market' => [
            ShareTradeType::SecondaryMarket,
            TradeOrderType::Market,
            TradeOrderType::Limit,
        ];
        yield 'Secondary market - limit-limit' => [
            ShareTradeType::SecondaryMarket,
            TradeOrderType::Market,
            TradeOrderType::Limit,
        ];
        yield 'Secondary market - limit-stop-loss' => [
            ShareTradeType::SecondaryMarket,
            TradeOrderType::StopLoss,
            TradeOrderType::Limit,
        ];
        yield 'Secondary market - limit-off-market' => [
            ShareTradeType::SecondaryMarket,
            TradeOrderType::OffMarket,
            TradeOrderType::Limit,
        ];

        // Secondary market, stop loss seller
        yield 'Secondary market - stop-loss-market' => [
            ShareTradeType::SecondaryMarket,
            TradeOrderType::Market,
            TradeOrderType::StopLoss,
        ];
        yield 'Secondary market - stop-loss-limit' => [
            ShareTradeType::SecondaryMarket,
            TradeOrderType::Limit,
            TradeOrderType::StopLoss,
        ];
        yield 'Secondary market - stop-loss-stop-loss' => [
            ShareTradeType::SecondaryMarket,
            TradeOrderType::StopLoss,
            TradeOrderType::StopLoss,
        ];
        yield 'Secondary market - stop-loss-off-market' => [
            ShareTradeType::SecondaryMarket,
            TradeOrderType::OffMarket,
            TradeOrderType::StopLoss,
        ];

        // Speciality
        yield 'Prefunding' => [
            ShareTradeType::Prefunding,
            TradeOrderType::Prefunding,
            TradeOrderType::Initial,
        ];
        yield 'Prefunding buy back' => [
            ShareTradeType::Repayment,
            TradeOrderType::Proxy,
            TradeOrderType::Prefunding,
        ];
        yield 'Divestment exit' => [
            ShareTradeType::Divestment,
            TradeOrderType::BuyBack,
            TradeOrderType::BuyBack,
        ];

        // Invalid combinations
        yield 'Null - prefunding both sides' => [
            null,
            TradeOrderType::Prefunding,
            TradeOrderType::Prefunding,
        ];
        yield 'Null - initial both sides' => [
            null,
            TradeOrderType::Initial,
            TradeOrderType::Initial,
        ];
        yield 'Null - Proxy both sides' => [
            null,
            TradeOrderType::Proxy,
            TradeOrderType::Proxy,
        ];
        yield 'Null - off market sell' => [
            null,
            TradeOrderType::Market,
            TradeOrderType::OffMarket,
        ];
        yield 'Null - Proxy sell' => [
            null,
            TradeOrderType::Market,
            TradeOrderType::Proxy,
        ];
        yield 'Null - Initial buy' => [
            null,
            TradeOrderType::Initial,
            TradeOrderType::Market,
        ];

        // Null combinations
        yield 'Null, all null' => [
            null,
            null,
            null,
        ];
        yield 'Null, buy null' => [
            null,
            null,
            TradeOrderType::Market,
        ];
        yield 'Null, sell null' => [
            null,
            TradeOrderType::Market,
            null,
        ];
    }
}
