<?php

namespace App\Tests\Service;

use App\Dto\Struct\Portfolio;
use App\Dto\Struct\PortfolioPosition;
use App\Entity\Asset;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TradeStatus;
use App\Entity\ShareTrade;
use App\Entity\TradeOrder;
use App\Entity\User;
use App\Repository\AssetRepository;
use App\Repository\PayoutRepository;
use App\Repository\ShareTradeRepository;
use App\Repository\TradeOrderRepository;
use App\Service\PortfolioService;
use App\Test\Util\EntityIdTestUtil;
use BcMath\Number;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class PortfolioServiceTest extends KernelTestCase
{
    private PortfolioService $service;

    private AssetRepository|MockObject $assetRepositoryMock;
    private PayoutRepository|MockObject $payoutRepositoryMock;
    private ShareTradeRepository|MockObject $shareTradeRepositoryMock;
    private TradeOrderRepository|MockObject $tradeOrderRepositoryMock;

    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testCompilePortfolio(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 415);
        $asset6 = EntityIdTestUtil::setEntityId(new Asset(), 6);
        $asset76 = EntityIdTestUtil::setEntityId(new Asset(), 76);
        $asset154 = EntityIdTestUtil::setEntityId(new Asset(), 154);
        $shareholdings = [
            [
                'buyTrades' => 5,
                'buyShares' => 10000,
                'buyValue' => '12500',
                'sellTrades' => 2,
                'sellShares' => 7580,
                'sellValue' => '10081.40',
                'trades' => '7',
                'shares' => '2420',
                'value' => '3025',
                'assetid' => 6,
            ],
            [
                'buyTrades' => 5,
                'buyShares' => 10000,
                'buyValue' => '13400',
                'sellTrades' => 4,
                'sellShares' => 10000,
                'sellValue' => '13340.56',
                'trades' => '9',
                'shares' => '0',
                'value' => '-59.44',
                'assetid' => 76,
            ],
            // This last asset has no dividends yet
            [
                'buyTrades' => 4,
                'buyShares' => 5800,
                'buyValue' => '15312',
                'sellTrades' => 0,
                'sellShares' => 0,
                'sellValue' => '0',
                'trades' => '4',
                'shares' => '5800',
                'value' => '15312',
                'assetid' => 154,
            ],
        ];
        $dividends = [
            [
                'assetId' => 6,
                'dividendsTotal' => '151.89',
                'dividendsThisMonth' => '12.23',
                'paymentPeriods' => 4,
                'lastDividendPaidAt' => '2026-03-01 18:12:37',
                'firstDividendPaidAt' => '2025-12-01 18:12:37',
            ],
            [
                'assetId' => 76,
                'dividendsTotal' => '588.92',
                'dividendsThisMonth' => '49.07',
                'paymentPeriods' => 12,
                'lastDividendPaidAt' => '2026-03-01 18:12:37',
                'firstDividendPaidAt' => '2025-12-01 18:12:37',
            ],
        ];
        $sellOrderAggregates = [
            [
                'assetId' => 6,
                'sharesListed' => '9580', // string or int shouldn't make a difference
                'shares' => '7580', // shares that have already been sold and settled, should be the same as shareholdings sellShares
                'sharesAvailable' => 2000, // listed but not sold-and-settled yet == sharesListed - shares(BoughtAndSettled)
                'count' => 1,
            ],
            [
                'assetId' => 76,
                'sharesListed' => 10000,
                'shares' => 10000,
                'sharesAvailable' => '0',
                'count' => 4,
            ],
        ];
        $expected = new Portfolio(
            userId: $user->getId(),
            value: new Number('18337.00'),
            dividends: new Number('740.81'),
            capitalGains: new Number('546.96'),
            positions: [
                new PortfolioPosition(
                    asset: $asset6,
                    averagePrice: new Number('1.25'),
                    shares: new Number('2420'),
                    value: new Number('3025.00'),
                    dividends: new Number('151.89'),
                    capitalGains: new Number('606.40'),
                    buyShares: new Number('10000'),
                    buyValue: new Number('12500.00'),
                    sellShares: new Number('7580'),
                    sellValue: new Number('10081.40'),
                    sharesAvailable: new Number('420'),
                ),
                new PortfolioPosition(
                    asset: $asset76,
                    averagePrice: new Number('1.34'),
                    shares: new Number('0'),
                    value: new Number('0.00'),
                    dividends: new Number('588.92'),
                    capitalGains: new Number('-59.44'),
                    buyShares: new Number('10000'),
                    buyValue: new Number('13400.00'),
                    sellShares: new Number('10000'),
                    sellValue: new Number('13340.56'),
                    sharesAvailable: new Number('0'),
                ),
                new PortfolioPosition(
                    asset: $asset154,
                    averagePrice: new Number('2.64'),
                    shares: new Number('5800'),
                    value: new Number('15312.00'),
                    dividends: new Number('0.00'),
                    capitalGains: new Number('0.00'),
                    buyShares: new Number('5800'),
                    buyValue: new Number('15312.00'),
                    sellShares: new Number('0'),
                    sellValue: new Number('0.00'),
                    sharesAvailable: new Number('5800'),
                ),
            ],
        );

        $this->assetRepositoryMock = $this->createMock(AssetRepository::class);
        $this->payoutRepositoryMock = $this->createMock(PayoutRepository::class);
        $this->shareTradeRepositoryMock = $this->createMock(ShareTradeRepository::class);
        static::getContainer()->set(AssetRepository::class, $this->assetRepositoryMock);
        static::getContainer()->set(
            PayoutRepository::class,
            $this->payoutRepositoryMock,
        );
        static::getContainer()->set(
            ShareTradeRepository::class,
            $this->shareTradeRepositoryMock,
        );
        $this->service = static::getContainer()->get(PortfolioService::class);

        $this->assetRepositoryMock
            ->expects(self::once())
            ->method('findBy')
            ->with(['id' => [6, 76, 154]])
            ->willReturn([$asset6, $asset76, $asset154]);
        $this->payoutRepositoryMock
            ->expects(self::once())
            ->method('getDividendSummaryByAsset')
            ->with($user->getId())
            ->willReturn($dividends);
        $this->shareTradeRepositoryMock
            ->expects(self::once())
            ->method('aggregateUserShareholdingsByAsset')
            ->with($user->getId())
            ->willReturn($shareholdings);
        $this->shareTradeRepositoryMock
            ->expects(self::once())
            ->method('aggregateUserTradeOrdersByAsset')
            ->with(
                $user->getId(),
                TradeDirection::Sell,
                TradeOrderStatus::nonCancelledStates(),
                TradeOrderType::circulatingSellTypes(),
            )
            ->willReturn($sellOrderAggregates);

        $actual = $this->service->compilePortfolio($user);
        $this->assertEquals($expected, $actual);
        // Do a string comparison check to check decimal places are correct
        // Bit hard to identify what's wrong if there are differences though
        // As json_encode will encode the asset as well!
        $this->assertSame(json_encode($expected), json_encode($actual));
    }

    public function testCompilePortfolioEmpty(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 415);

        $this->assetRepositoryMock = $this->createMock(AssetRepository::class);
        $this->payoutRepositoryMock = $this->createMock(PayoutRepository::class);
        $this->shareTradeRepositoryMock = $this->createMock(ShareTradeRepository::class);
        static::getContainer()->set(AssetRepository::class, $this->assetRepositoryMock);
        static::getContainer()->set(
            PayoutRepository::class,
            $this->payoutRepositoryMock,
        );
        static::getContainer()->set(
            ShareTradeRepository::class,
            $this->shareTradeRepositoryMock,
        );
        $this->service = static::getContainer()->get(PortfolioService::class);

        $this->assetRepositoryMock
            ->expects(self::once())
            ->method('findBy')
            ->with(['id' => []])
            ->willReturn([]);
        $this->payoutRepositoryMock
            ->expects(self::once())
            ->method('getDividendSummaryByAsset')
            ->with($user->getId())
            ->willReturn([]);
        $this->shareTradeRepositoryMock
            ->expects(self::once())
            ->method('aggregateUserShareholdingsByAsset')
            ->with($user->getId())
            ->willReturn([]);
        $this->shareTradeRepositoryMock
            ->expects(self::once())
            ->method('aggregateUserTradeOrdersByAsset')
            ->with(
                $user->getId(),
                TradeDirection::Sell,
                TradeOrderStatus::nonCancelledStates(),
                TradeOrderType::circulatingSellTypes(),
            )
            ->willReturn([]);

        $expected = new Portfolio(
            userId: $user->getId(),
            value: new Number('0.00'),
            dividends: new Number('0.00'),
            capitalGains: new Number('0.00'),
            positions: [],
        );

        $actual = $this->service->compilePortfolio($user);
        $this->assertEquals($expected, $actual);
        // Do a string comparison check to check decimal places are correct
        // Bit hard to identify what's wrong if there are differences though
        // As json_encode will encode the asset as well!
        $this->assertSame(json_encode($expected), json_encode($actual));
    }

    public function testCompilePrefundingPortfolio(): void
    {
        $issuer = EntityIdTestUtil::setEntityId(new User(), 5);
        $user = EntityIdTestUtil::setEntityId(new User(), 415);
        $asset6 = EntityIdTestUtil::setEntityId(new Asset(), 6);
        $asset6->setPricePerShare('1.25');
        $asset76 = EntityIdTestUtil::setEntityId(new Asset(), 76);
        $asset76->setPricePerShare('1.34');

        $buyBackOrderA6 = new TradeOrder(
            direction: TradeDirection::Buy,
            asset: $asset6,
            user: $issuer,
            numberOfShares: 4000,
            pricePerShare: new Number($asset6->getPricePerShare()),
            type: TradeOrderType::Proxy,
        );
        $buyBackOrderA76 = new TradeOrder(
            direction: TradeDirection::Buy,
            asset: $asset76,
            user: $issuer,
            numberOfShares: 3000,
            pricePerShare: new Number($asset6->getPricePerShare()),
            type: TradeOrderType::Proxy,
        );

        $sellOrderA6_1 = new TradeOrder(
            direction: TradeDirection::Sell,
            asset: $asset6,
            user: $user,
            numberOfShares: 8000,
            pricePerShare: new Number($asset6->getPricePerShare()),
            type: TradeOrderType::Prefunding,
        );
        $sellOrderA6_2 = new TradeOrder(
            direction: TradeDirection::Sell,
            asset: $asset6,
            user: $user,
            numberOfShares: 1200,
            pricePerShare: new Number($asset6->getPricePerShare()),
            type: TradeOrderType::Prefunding,
        );
        $sellOrderA76_1 = new TradeOrder(
            direction: TradeDirection::Sell,
            asset: $asset76,
            user: $user,
            numberOfShares: 3000,
            pricePerShare: new Number($asset76->getPricePerShare()),
            type: TradeOrderType::Prefunding,
        );
        $buyBackTradeA6_1 = new ShareTrade(
            buyOrder: $buyBackOrderA6,
            sellOrder: $sellOrderA6_2,
            numberOfShares: 1200,
        );
        $buyBackTradeA6_1->setStatus(TradeStatus::Settled);
        $sellOrderA6_2->addShareTrade($buyBackTradeA6_1);
        $buyBackOrderA6->addShareTrade($buyBackTradeA6_1);
        $buyBackTradeA6_2 = new ShareTrade(
            buyOrder: $buyBackOrderA6,
            sellOrder: $sellOrderA6_1,
            numberOfShares: 2800,
        );
        $buyBackTradeA6_2->setStatus(TradeStatus::Settled);
        $sellOrderA6_1->addShareTrade($buyBackTradeA6_2);
        $buyBackOrderA6->addShareTrade($buyBackTradeA6_2);
        $buyBackTradeA76_1 = new ShareTrade(
            buyOrder: $buyBackOrderA76,
            sellOrder: $sellOrderA76_1,
            numberOfShares: 3000,
        );
        $buyBackTradeA76_1->setStatus(TradeStatus::Settled);
        $sellOrderA76_1->addShareTrade($buyBackTradeA76_1);
        $buyBackOrderA76->addShareTrade($buyBackTradeA76_1);

        $sellOrders = [$sellOrderA6_1, $sellOrderA6_2, $sellOrderA76_1];

        $expected = new Portfolio(
            userId: $user->getId(),
            value: new Number('6500.00'),
            dividends: new Number('0.00'),
            capitalGains: new Number('0.00'),
            positions: [
                new PortfolioPosition(
                    asset: $asset6,
                    averagePrice: new Number('1.25'),
                    shares: new Number('5200'),
                    value: new Number('6500.00'),
                    dividends: new Number('0.00'),
                    capitalGains: new Number('0.00'),
                    buyShares: new Number('9200'),
                    buyValue: new Number('11500.00'),
                    sellShares: new Number('4000'),
                    sellValue: new Number('5000.00'),
                ),
                new PortfolioPosition(
                    asset: $asset76,
                    averagePrice: new Number('1.34'),
                    shares: new Number('0'),
                    value: new Number('0.00'),
                    dividends: new Number('0.00'),
                    capitalGains: new Number('0.00'),
                    buyShares: new Number('3000'),
                    buyValue: new Number('4020.00'),
                    sellShares: new Number('3000'),
                    sellValue: new Number('4020.00'),
                ),
            ],
        );

        $this->assetRepositoryMock = $this->createMock(AssetRepository::class);
        $this->tradeOrderRepositoryMock = $this->createMock(TradeOrderRepository::class);

        static::getContainer()->set(AssetRepository::class, $this->assetRepositoryMock);
        static::getContainer()->set(
            TradeOrderRepository::class,
            $this->tradeOrderRepositoryMock,
        );
        $this->service = static::getContainer()->get(PortfolioService::class);

        $this->assetRepositoryMock
            ->expects(self::once())
            ->method('findBy')
            ->with(['id' => [6, 76]])
            ->willReturn([$asset6, $asset76]);
        $this->tradeOrderRepositoryMock
            ->expects(self::once())
            ->method('findWithAssociations')
            ->with([
                'userId' => $user->getId(),
                'status' => [TradeOrderStatus::Active, TradeOrderStatus::Completed],
                'type' => TradeOrderType::Prefunding,
                'direction' => TradeDirection::Sell,
            ], ['numberOfShares' => 'ASC'])
            ->willReturn($sellOrders);

        $actual = $this->service->compilePrefundingPortfolio($user);
        $this->assertEquals($expected, $actual);
        // Do a string comparison check to check decimal places are correct
        // Bit hard to identify what's wrong if there are differences though
        // As json_encode will encode the asset as well!
        $this->assertSame(json_encode($expected), json_encode($actual));
    }

    public function testCompilePrefundingPortfolioEmpty(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 415);

        $this->assetRepositoryMock = $this->createMock(AssetRepository::class);
        $this->tradeOrderRepositoryMock = $this->createMock(TradeOrderRepository::class);

        static::getContainer()->set(AssetRepository::class, $this->assetRepositoryMock);
        static::getContainer()->set(
            TradeOrderRepository::class,
            $this->tradeOrderRepositoryMock,
        );
        $this->service = static::getContainer()->get(PortfolioService::class);

        $this->assetRepositoryMock
            ->expects(self::once())
            ->method('findBy')
            ->with(['id' => []])
            ->willReturn([]);
        $this->tradeOrderRepositoryMock
            ->expects(self::once())
            ->method('findWithAssociations')
            ->with([
                'userId' => $user->getId(),
                'status' => [TradeOrderStatus::Active, TradeOrderStatus::Completed],
                'type' => TradeOrderType::Prefunding,
                'direction' => TradeDirection::Sell,
            ], ['numberOfShares' => 'ASC'])
            ->willReturn([]);

        $expected = new Portfolio(
            userId: $user->getId(),
            value: new Number('0.00'),
            dividends: new Number('0.00'),
            capitalGains: new Number('0.00'),
            positions: [],
        );

        $actual = $this->service->compilePrefundingPortfolio($user);
        $this->assertEquals($expected, $actual);
        // Do a string comparison check to check decimal places are correct
        // Bit hard to identify what's wrong if there are differences though
        // As json_encode will encode the asset as well!
        $this->assertSame(json_encode($expected), json_encode($actual));
    }

    public function testGetSharesAvailableToSell(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 415);
        $asset6 = EntityIdTestUtil::setEntityId(new Asset(), 6);
        $asset76 = EntityIdTestUtil::setEntityId(new Asset(), 76);
        $asset154 = EntityIdTestUtil::setEntityId(new Asset(), 154);
        $shareholdings = [
            [
                'buyTrades' => 5,
                'buyShares' => 10000,
                'buyValue' => '12500',
                'sellTrades' => 2,
                'sellShares' => 7580,
                'sellValue' => '10081.40',
                'trades' => '7',
                'shares' => '2420',
                'value' => '3025',
                'assetid' => 6,
            ],
            [
                'buyTrades' => 5,
                'buyShares' => 10000,
                'buyValue' => '13400',
                'sellTrades' => 4,
                'sellShares' => 10000,
                'sellValue' => '13340.56',
                'trades' => '9',
                'shares' => '0',
                'value' => '-59.44',
                'assetid' => 76,
            ],
            // This last asset has no dividends yet
            [
                'buyTrades' => 4,
                'buyShares' => 5800,
                'buyValue' => '15312',
                'sellTrades' => 0,
                'sellShares' => 0,
                'sellValue' => '0',
                'trades' => '4',
                'shares' => '5800',
                'value' => '15312',
                'assetid' => 154,
            ],
        ];
        $sellOrderAggregates = [
            [
                'assetId' => 6,
                'sharesListed' => '9580', // string or int shouldn't make a difference
                'shares' => '7580', // shares that have already been sold and settled, should be the same as shareholdings sellShares
                'sharesAvailable' => 2000, // listed but not sold-and-settled yet == sharesListed - shares(BoughtAndSettled)
                'count' => 1,
            ],
            [
                'assetId' => 76,
                'sharesListed' => 10000,
                'shares' => 10000,
                'sharesAvailable' => '0',
                'count' => 4,
            ],
        ];

        $this->shareTradeRepositoryMock = $this->createMock(ShareTradeRepository::class);
        static::getContainer()->set(
            ShareTradeRepository::class,
            $this->shareTradeRepositoryMock,
        );
        $this->service = static::getContainer()->get(PortfolioService::class);

        // We'll be calling the method 3 times, once for each asset
        $this->shareTradeRepositoryMock
            ->expects(self::exactly(3))
            ->method('aggregateUserShareholdingsByAsset')
            ->with($user->getId())
            ->willReturn($shareholdings);
        // Asset76 will short circuit as user has no shares left in the asset
        // So the query for listings won't take place
        $this->shareTradeRepositoryMock
            ->expects(self::exactly(2))
            ->method('aggregateUserTradeOrdersByAsset')
            ->with(
                $user->getId(),
                TradeDirection::Sell,
                TradeOrderStatus::nonCancelledStates(),
                TradeOrderType::circulatingSellTypes(),
            )
            ->willReturn($sellOrderAggregates);

        // Some sold
        $actual = $this->service->getSharesAvailableToSell($user, $asset6);
        $this->assertEquals(420, $actual);

        // All sold
        $actual = $this->service->getSharesAvailableToSell($user, $asset76);
        $this->assertEquals(0, $actual);

        // No listings
        $actual = $this->service->getSharesAvailableToSell($user, $asset154);
        $this->assertEquals(5800, $actual);
    }

    public function testGetSharesAvailableToSellNotExistingInvestor(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 415);
        $asset550 = EntityIdTestUtil::setEntityId(new Asset(), 550);
        $shareholdings = [
            [
                'buyTrades' => 5,
                'buyShares' => 10000,
                'buyValue' => '12500',
                'sellTrades' => 2,
                'sellShares' => 7580,
                'sellValue' => '10081.40',
                'trades' => '7',
                'shares' => '2420',
                'value' => '3025',
                'assetid' => 6,
            ],
            [
                'buyTrades' => 5,
                'buyShares' => 10000,
                'buyValue' => '13400',
                'sellTrades' => 4,
                'sellShares' => 10000,
                'sellValue' => '13340.56',
                'trades' => '9',
                'shares' => '0',
                'value' => '-59.44',
                'assetid' => 76,
            ],
            // This last asset has no dividends yet
            [
                'buyTrades' => 4,
                'buyShares' => 5800,
                'buyValue' => '15312',
                'sellTrades' => 0,
                'sellShares' => 0,
                'sellValue' => '0',
                'trades' => '4',
                'shares' => '5800',
                'value' => '15312',
                'assetid' => 154,
            ],
        ];

        $this->shareTradeRepositoryMock = $this->createMock(ShareTradeRepository::class);
        static::getContainer()->set(
            ShareTradeRepository::class,
            $this->shareTradeRepositoryMock,
        );
        $this->service = static::getContainer()->get(PortfolioService::class);

        // We'll be calling the method 3 times, once for each asset
        $this->shareTradeRepositoryMock
            ->expects(self::once())
            ->method('aggregateUserShareholdingsByAsset')
            ->with($user->getId())
            ->willReturn($shareholdings);
        $this->shareTradeRepositoryMock
            ->expects(self::never())
            ->method('aggregateUserTradeOrdersByAsset');

        $actual = $this->service->getSharesAvailableToSell($user, $asset550);
        $this->assertEquals(0, $actual);
    }
}
