<?php

namespace App\Tests\Service;

use App\Entity\Asset;
use App\Entity\Communication;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TradeStatus;
use App\Entity\Enum\TransferMode;
use App\Entity\Investment;
use App\Entity\Lifecycle\InvestmentLifecycle;
use App\Entity\Offering;
use App\Entity\ShareTrade;
use App\Entity\ShareTradeStatusLog;
use App\Entity\TradeOrder;
use App\Entity\TransferOrder;
use App\Entity\TransferRequest;
use App\Entity\User;
use App\Repository\CommunicationRepository;
use App\Repository\UserRepository;
use App\Service\Manager\InvestmentManagerV2;
use App\Service\MonthEndService;
use App\Service\SettlementService;
use App\Test\Util\EntityIdTestUtil;
use BcMath\Number;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class SettlementServiceTest extends KernelTestCase
{
    /** @var SettlementService */
    private $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(SettlementService::class);
    }

    public static function tradeClassificationProvider(): \Generator
    {
        $sellOrderInitial = new TradeOrder(
            direction: TradeDirection::Sell,
            type: TradeOrderType::Initial,
        );
        $sellOrderMarket = new TradeOrder(
            direction: TradeDirection::Sell,
            type: TradeOrderType::Market,
        );
        $sellOrderLimit = new TradeOrder(
            direction: TradeDirection::Sell,
            type: TradeOrderType::Limit,
        );
        $sellOrderStopLoss = new TradeOrder(
            direction: TradeDirection::Sell,
            type: TradeOrderType::StopLoss,
        );
        $buyOrderMarket = new TradeOrder(
            direction: TradeDirection::Buy,
            type: TradeOrderType::Market,
        );
        $buyOrderPrefunding = new TradeOrder(
            direction: TradeDirection::Buy,
            type: TradeOrderType::Prefunding,
        );

        yield 'relisted market' => [
            'retailRelisted',
            $sellOrderMarket,
            $buyOrderMarket,
        ];
        yield 'relisted limit' => ['retailRelisted', $sellOrderLimit, $buyOrderMarket];
        yield 'relisted stoploss' => [
            'retailRelisted',
            $sellOrderStopLoss,
            $buyOrderMarket,
        ];
        yield 'prefunding' => ['prefunding', $sellOrderInitial, $buyOrderPrefunding];
        yield 'first party' => ['retailFirstParty', $sellOrderInitial, $buyOrderMarket];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('tradeClassificationProvider')]
    public function testGetTradeClassification(
        string $expected,
        TradeOrder $sellorder,
        TradeOrder $buyOrder,
    ): void {
        $shareTrade = new ShareTrade(sellOrder: $sellorder, buyOrder: $buyOrder);
        $actual = $this->service->getTradeClassification($shareTrade);
        $this->assertEquals($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\Group('check')]
    public function testGetSettlementOverview(): void
    {
        $asset1 = EntityIdTestUtil::setEntityId(new Asset(), 124);
        $asset2 = EntityIdTestUtil::setEntityId(new Asset(), 821);
        $asset3 = EntityIdTestUtil::setEntityId(new Asset(), 2884);

        $sellOrderInitial1 = new TradeOrder(
            asset: $asset1,
            direction: TradeDirection::Sell,
            type: TradeOrderType::Initial,
        );
        $sellOrderInitial2 = new TradeOrder(
            asset: $asset2,
            direction: TradeDirection::Sell,
            type: TradeOrderType::Initial,
        );
        $sellOrderRelisted = new TradeOrder(
            asset: $asset1,
            direction: TradeDirection::Sell,
            type: TradeOrderType::Market,
        );
        $sellOrderForPefunding = new TradeOrder(
            asset: $asset3,
            direction: TradeDirection::Sell,
            type: TradeOrderType::Initial,
        );

        /**
         * Mathematically generate investments by multiple
         */
        $shareTrades = [];
        for ($i = 0; $i <= 31; $i++) {
            // Expect 2 of these
            if ($i <= 1) {
                // first party asset 124
                $buyOrder1 = new TradeOrder(
                    asset: $asset1,
                    direction: TradeDirection::Buy,
                    type: TradeOrderType::Market,
                );
                $shareTrade = new ShareTrade(
                    buyOrder: $buyOrder1,
                    sellOrder: $sellOrderInitial1,
                );
                $shareTrade->setStatus(TradeStatus::Unsettled);
                $shareTrades[] = $shareTrade;
            }
            // Expected 16 of these
            if (0 === ($i % 2)) {
                // first party asset 821
                $buyOrder2 = new TradeOrder(
                    asset: $asset2,
                    direction: TradeDirection::Buy,
                    type: TradeOrderType::Market,
                );
                $shareTrade = new ShareTrade(
                    buyOrder: $buyOrder2,
                    sellOrder: $sellOrderInitial2,
                );
                $shareTrade->setStatus(TradeStatus::Unsettled);
                $shareTrades[] = $shareTrade;
            }
            // Expect 11 of these
            if (0 === ($i % 3)) {
                // relisted asset 124
                $buyOrder3 = new TradeOrder(
                    asset: $asset1,
                    direction: TradeDirection::Buy,
                    type: TradeOrderType::Market,
                );
                $shareTrade = new ShareTrade(
                    buyOrder: $buyOrder3,
                    sellOrder: $sellOrderRelisted,
                );
                $shareTrade->setStatus(TradeStatus::Unsettled);
                $shareTrades[] = $shareTrade;
            }
            // Expect 8 of these
            if (0 === ($i % 4)) {
                // prefunding asset 2884
                $buyOrder4 = new TradeOrder(
                    asset: $asset3,
                    direction: TradeDirection::Buy,
                    type: TradeOrderType::Prefunding,
                );
                $shareTrade = new ShareTrade(
                    buyOrder: $buyOrder4,
                    sellOrder: $sellOrderForPefunding,
                );
                $shareTrade->setStatus(TradeStatus::Unsettled);
                $shareTrades[] = $shareTrade;
            }
            // Expect 3 of these, but don't count towards totals
            if (0 === ($i % 8)) {
                // not for settlement - already settled
                $buyOrder1 = new TradeOrder(
                    asset: $asset1,
                    direction: TradeDirection::Buy,
                    type: TradeOrderType::Market,
                );
                $shareTrade = new ShareTrade(
                    buyOrder: $buyOrder1,
                    sellOrder: $sellOrderInitial1,
                );
                $shareTrade->setStatus(TradeStatus::Settled);
                $shareTrades[] = $shareTrade;
            }
        }

        $expected = [
            'prefunding' => [
                'count' => 8,
            ],
            'retailFirstParty' => [
                'count' => 18,
            ],
            'retailRelisted' => [
                'count' => 11,
            ],
            'assetSummary' => [
                '124' => 13,
                '821' => 16,
                '2884' => 8,
            ],
        ];
        $actual = $this->service->getSettlementOverview($shareTrades);
        // $this->assertEquals($expected, $actual);
        $this->assertEqualsCanonicalizing(
            $expected['prefunding'],
            $actual['prefunding'],
        );
        $this->assertEqualsCanonicalizing(
            $expected['retailFirstParty'],
            $actual['retailFirstParty'],
        );
        $this->assertEqualsCanonicalizing(
            $expected['retailRelisted'],
            $actual['retailRelisted'],
        );
        $this->assertCount(
            $expected['assetSummary']['124'],
            $actual['assetSummary']['124'],
        );
        $this->assertCount(
            $expected['assetSummary']['821'],
            $actual['assetSummary']['821'],
        );
        $this->assertCount(
            $expected['assetSummary']['2884'],
            $actual['assetSummary']['2884'],
        );
        $this->assertLessThan(count($shareTrades), 37);
    }

    public function testGetSettlementOverviewEmpty(): void
    {
        $expected = [
            'prefunding' => [
                'count' => 0,
            ],
            'retailFirstParty' => [
                'count' => 0,
            ],
            'retailRelisted' => [
                'count' => 0,
            ],
            'assetSummary' => [],
        ];
        $actual = $this->service->getSettlementOverview([]);
        $this->assertEquals($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('tradeSettlementSummaryProvider')]
    public function testGetTradeSettlementSummary(
        iterable $investments,
        array $expected,
    ): void {
        $actual = $this->service->getTradeSettlementSummary($investments);
        $this->assertEquals($expected, $actual);
    }

    public static function tradeSettlementSummaryProvider(): \Generator
    {
        $emptySummary = [
            'tradeCount' => 0,
            'tradeValueTotal' => 0,
            'tradeSharesTotal' => 0,
        ];

        $asset = new Asset();
        $sellOrderInitial = new TradeOrder(
            asset: $asset,
            direction: TradeDirection::Sell,
            type: TradeOrderType::Initial,
        );

        $sharePrice = '4.77';
        $shareTradesWithDuty = [];
        $shareTradesWithNoDuty = [];
        foreach (range(1, 8) as $iteration) {
            $buyOrderMarket = new TradeOrder(
                asset: $asset,
                direction: TradeDirection::Buy,
                type: TradeOrderType::Market,
            );
            $buyOrderPrefunding = new TradeOrder(
                asset: $asset,
                direction: TradeDirection::Buy,
                type: TradeOrderType::Prefunding,
            );
            $shareTradeRetail = EntityIdTestUtil::setEntityId(
                new ShareTrade(
                    buyOrder: $buyOrderMarket,
                    sellOrder: $sellOrderInitial,
                    numberOfShares: 161 * $iteration,
                    pricePerShare: new Number($sharePrice),
                ),
                $iteration,
            );
            $shareTradesWithDuty[] = $shareTradeRetail;

            $shareTradePrefunding = EntityIdTestUtil::setEntityId(
                new ShareTrade(
                    buyOrder: $buyOrderPrefunding,
                    sellOrder: $sellOrderInitial,
                    numberOfShares: 161 * $iteration,
                    pricePerShare: new Number($sharePrice),
                ),
                $iteration,
            );
            $shareTradesWithNoDuty[] = $shareTradePrefunding;
        }
        $summaryWithDuty = [
            'tradeCount' => 8,
            'tradeValueTotal' => '27646.92',
            'tradeSharesTotal' => 5796,
        ];

        $summaryWithNoDuty = [
            'tradeCount' => 8,
            'tradeValueTotal' => 27646.92,
            'tradeSharesTotal' => 5796,
        ];

        yield 'No trades' => [[], $emptySummary];
        yield 'With stamp duty' => [$shareTradesWithDuty, $summaryWithDuty];
        yield 'With stamp duty exemption' => [
            $shareTradesWithNoDuty,
            $summaryWithNoDuty,
        ];
    }

    public function testGetTradeSettlementOrderSummary(): void
    {
        $transferOrder = new TransferOrder();
        $expected = [
            'tradeCount' => 0,
            'tradeValueTotal' => 0,
            'tradeSharesTotal' => 0,
            'stampDutyCount' => 0,
            'stampDutyValue' => 0,
            'tradesToSettle' => [],
        ];
        $actual = $this->service->getTradeSettlementOrderSummary($transferOrder);
        $this->assertEqualsCanonicalizing($expected, $actual);

        $asset = new Asset();
        $sellOrderInitial = new TradeOrder(
            asset: $asset,
            direction: TradeDirection::Sell,
            type: TradeOrderType::Initial,
        );

        $sharePrice = '4.77';
        $debitWallet = bin2hex(random_bytes(16));
        $creditWallet = bin2hex(random_bytes(16));
        foreach (range(1, 8) as $iteration) {
            $buyOrderMarket = new TradeOrder(
                asset: $asset,
                direction: TradeDirection::Buy,
                type: TradeOrderType::Market,
            );
            $shareTrade = EntityIdTestUtil::setEntityId(
                new ShareTrade(
                    buyOrder: $buyOrderMarket,
                    sellOrder: $sellOrderInitial,
                    numberOfShares: 161 * $iteration,
                    pricePerShare: new Number($sharePrice),
                ),
                $iteration * 3,
            );

            $settlement = new TransferRequest();
            $settlement->setShareTrade($shareTrade);
            $settlement->setDebitWalletId($debitWallet);
            $settlement->setCreditWalletId($creditWallet);
            $settlement->setDescription(
                MonthEndService::DESCRIPTION_PRESETS['settlement'] . 'extra info',
            );
            $settlement->setAmount((string) $shareTrade->getTradeValue());
            $transferOrder->addTransfer($settlement);

            // Note that we don't necessarily need to care if the stamp duty amount is correct
            // Only that the function correctly identifies a stamp duty transfer and adds it to the summary
            if ($iteration > 5) {
                $stampDuty = new TransferRequest();
                $stampDuty->setShareTrade($shareTrade);
                $stampDuty->setDebitWalletId($debitWallet);
                $stampDuty->setCreditWalletId($creditWallet . 'sdw');
                $stampDuty->setDescription(
                    MonthEndService::DESCRIPTION_PRESETS['stamp duty'] . 'extra info',
                );
                $stampDuty->setAmount((string) $iteration * 5);
                $transferOrder->addTransfer($stampDuty);
            }
        }

        $expected = [
            'tradeCount' => 8,
            'tradeValueTotal' => 27646.92,
            'tradeSharesTotal' => 5796,
            'stampDutyCount' => 3,
            'stampDutyValue' => '105',
            'tradesToSettle' => [3, 6, 9, 12, 15, 18, 21, 24],
        ];
        $actual = $this->service->getTradeSettlementOrderSummary($transferOrder);
        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    public function testGetTradeStampDutyOverview(): void
    {
        $defaultExpected = [
            'stampDutyCount' => 0,
            'stampDutyValue' => 0,
            'userSummary' => [],
        ];
        $actual = $this->service->getTradeStampDutyOverview([]);
        $this->assertEqualsCanonicalizing($defaultExpected, $actual);

        $asset = new Asset();
        $sellOrderInitial = new TradeOrder(
            asset: $asset,
            direction: TradeDirection::Sell,
            type: TradeOrderType::Initial,
        );

        $user1 = EntityIdTestUtil::setEntityId(new User(), 1241);
        $user2 = EntityIdTestUtil::setEntityId(new User(), 6621);
        $user3 = EntityIdTestUtil::setEntityId(new User(), 7142);

        $groupedTrades = [];

        // User1 will have 3 share trades totalling over £1k, but each below 1k
        foreach (range(1, 3) as $iteration) {
            $buyOrderMarket = new TradeOrder(
                asset: $asset,
                user: $user1,
                direction: TradeDirection::Buy,
                type: TradeOrderType::Market,
            );
            $shareTrade = EntityIdTestUtil::setEntityId(
                new ShareTrade(
                    buyOrder: $buyOrderMarket,
                    sellOrder: $sellOrderInitial,
                    tradeValue: new Number((string) 893.27),
                ),
                111 + $iteration,
            );
            $groupedTrades[$user1->getId()][] = $shareTrade;
        }

        // User2 will have 2 share trades totalling under £1k
        foreach (range(1, 2) as $iteration) {
            $buyOrderMarket = new TradeOrder(
                asset: $asset,
                user: $user2,
                direction: TradeDirection::Buy,
                type: TradeOrderType::Market,
            );
            $shareTrade = EntityIdTestUtil::setEntityId(
                new ShareTrade(
                    buyOrder: $buyOrderMarket,
                    sellOrder: $sellOrderInitial,
                    tradeValue: new Number((string) (244.67 * $iteration)),
                ),
                222 + $iteration,
            );
            $groupedTrades[$user2->getId()][] = $shareTrade;
        }

        // User3 will have a single share trades over £1k
        $buyOrderMarket = new TradeOrder(
            asset: $asset,
            user: $user3,
            direction: TradeDirection::Buy,
            type: TradeOrderType::Market,
        );
        $shareTrade = EntityIdTestUtil::setEntityId(
            new ShareTrade(
                buyOrder: $buyOrderMarket,
                sellOrder: $sellOrderInitial,
                tradeValue: new Number('9674.08'),
            ),
            334,
        );
        $groupedTrades[$user3->getId()][] = $shareTrade;

        $expected = [
            'stampDutyCount' => 2,
            'stampDutyValue' => '65',
            'userSummary' => [
                1241 => [
                    'settlementValueTotal' => '2679.81',
                    'stampDutyDue' => '15',
                ],
                6621 => [
                    'settlementValueTotal' => '734.01',
                    'stampDutyDue' => '0',
                ],
                7142 => [
                    'settlementValueTotal' => '9674.08',
                    'stampDutyDue' => '50',
                ],
            ],
        ];
        $actual = $this->service->getTradeStampDutyOverview($groupedTrades);
        $this->assertEquals($expected, $actual);

        // Exemption for prefunding
        $expected = [
            'stampDutyCount' => 0,
            'stampDutyValue' => '0',
            'userSummary' => [
                1241 => [
                    'settlementValueTotal' => '2679.81',
                    'stampDutyDue' => '0',
                ],
                6621 => [
                    'settlementValueTotal' => '734.01',
                    'stampDutyDue' => '0',
                ],
                7142 => [
                    'settlementValueTotal' => '9674.08',
                    'stampDutyDue' => '0',
                ],
            ],
        ];
        foreach ($groupedTrades as $userShareTrades) {
            foreach ($userShareTrades as &$shareTrade) {
                $shareTrade->getBuyOrder()->setType(TradeOrderType::Prefunding);
            }
        }
        $actual = $this->service->getTradeStampDutyOverview($groupedTrades);
        $this->assertEquals($expected, $actual);
    }

    public function testGenerateSettlementTransfers(): void
    {
        $asset = EntityIdTestUtil::setEntityId(new Asset(), 445);
        $asset->setHoldWalletId(bin2hex(random_bytes(16)));
        $asset->setSettlementWalletId(bin2hex(random_bytes(16)));
        $asset->setStampDutyUser('stampDutyUserToBeMocked');
        $asset->setPricePerShare('4.77');

        $sellOrderInitial = new TradeOrder(
            asset: $asset,
            pricePerShare: new Number($asset->getPricePerShare()),
            direction: TradeDirection::Sell,
            type: TradeOrderType::Initial,
        );

        $transferOrder = new TransferOrder();
        $transferOrder->setAsset($asset);

        /**
         * Create share trades to test filtering and generation behaviour
         * - Share trade not in approved state
         * - Share trade in wrong asset
         * - Share trade without stamp duty
         * - Share trade with stamp duty
         *
         * 4 input share trades
         * 2 output transfers (2 settlements) - stamp duty is not generated at the same time
         */
        $buyOrder1 = new TradeOrder(
            asset: $asset,
            direction: TradeDirection::Buy,
            type: TradeOrderType::Market,
        );
        $shareTradeSettled = EntityIdTestUtil::setEntityId(
            new ShareTrade(
                buyOrder: $buyOrder1,
                sellOrder: $sellOrderInitial,
                tradeValue: new Number('9674.08'),
            ),
            4591,
        );
        $shareTradeSettled->setStatus(TradeStatus::Settled);

        $assetAlt = EntityIdTestUtil::setEntityId(new Asset(), 66);
        $assetAlt->setPricePerShare('1.67');
        $sellOrderOther = new TradeOrder(
            asset: $assetAlt,
            pricePerShare: new Number($assetAlt->getPricePerShare()),
            direction: TradeDirection::Sell,
            type: TradeOrderType::Initial,
        );
        $buyOrder2 = new TradeOrder(
            asset: $assetAlt,
            direction: TradeDirection::Buy,
            type: TradeOrderType::Market,
        );
        $shareTradeWrongAsset = EntityIdTestUtil::setEntityId(
            new ShareTrade(
                buyOrder: $buyOrder2,
                sellOrder: $sellOrderOther,
                tradeValue: new Number('1464.78'),
            ),
            4591,
        );
        $shareTradeWrongAsset->setStatus(TradeStatus::Unsettled);

        $buyOrder3 = new TradeOrder(
            asset: $asset,
            direction: TradeDirection::Buy,
            type: TradeOrderType::Market,
        );
        $shareTradeNoStampDuty = EntityIdTestUtil::setEntityId(
            new ShareTrade(
                buyOrder: $buyOrder3,
                sellOrder: $sellOrderInitial,
                tradeValue: new Number('464.78'),
            ),
            5591,
        );
        $shareTradeNoStampDuty->setStatus(TradeStatus::Unsettled);

        $buyOrder4 = new TradeOrder(
            asset: $asset,
            direction: TradeDirection::Buy,
            type: TradeOrderType::Market,
        );
        $shareTradeWithStampDuty = EntityIdTestUtil::setEntityId(
            new ShareTrade(
                buyOrder: $buyOrder4,
                sellOrder: $sellOrderInitial,
                tradeValue: new Number('2464.78'),
            ),
            3785,
        );
        $shareTradeWithStampDuty->setStatus(TradeStatus::Unsettled);

        $shareTrades = [
            $shareTradeSettled,
            $shareTradeWrongAsset,
            $shareTradeNoStampDuty,
            $shareTradeWithStampDuty,
        ];
        $actual = $this->service->generateSettlementTransfers(
            $transferOrder,
            $shareTrades,
        );

        /** @var TransferRequest[] $transfers */
        $transfers = $actual->getTransfers()->toArray();
        $this->assertCount(2, $transfers);

        $this->assertEquals(
            $asset->getHoldWalletId(),
            $transfers[0]->getDebitWalletId(),
        );
        $this->assertEquals(
            $asset->getSettlementWalletId(),
            $transfers[0]->getCreditWalletId(),
        );
        $this->assertEquals(
            MonthEndService::DESCRIPTION_PRESETS['settlement'] . ' #5591',
            $transfers[0]->getDescription(),
        );
        $this->assertEquals(
            (string) $shareTradeNoStampDuty->getTradeValue(),
            $transfers[0]->getAmount(),
        );
        $this->assertEquals($shareTradeNoStampDuty, $transfers[0]->getShareTrade());
        $this->assertEquals(TransferMode::Settlement, $transfers[0]->getMode());

        $this->assertEquals(
            $asset->getHoldWalletId(),
            $transfers[1]->getDebitWalletId(),
        );
        $this->assertEquals(
            $asset->getSettlementWalletId(),
            $transfers[1]->getCreditWalletId(),
        );
        $this->assertEquals(
            MonthEndService::DESCRIPTION_PRESETS['settlement'] . ' #3785',
            $transfers[1]->getDescription(),
        );
        $this->assertEquals(
            (string) $shareTradeWithStampDuty->getTradeValue(),
            $transfers[1]->getAmount(),
        );
        $this->assertEquals($shareTradeWithStampDuty, $transfers[1]->getShareTrade());
        $this->assertEquals(TransferMode::Settlement, $transfers[1]->getMode());
    }

    public function testGenerateStampDutyTransfers(): void
    {
        $stampDutyUser = new User();
        $stampDutyUser->setUsername('stampdutyUser');
        $stampDutyUser->setMangoPayWalletId(bin2hex(random_bytes(16)));

        /** @var MockObject $userRepositoryMock */
        $userRepositoryMock = $this->createMock(UserRepository::class);
        $userRepositoryMock
            ->expects($this->once())
            ->method('findByEmail')
            ->willReturn($stampDutyUser);

        /** @var UserRepository $userRepositoryMock */
        $service = new SettlementService(
            static::getContainer()->get(\Psr\Log\LoggerInterface::class),
            static::getContainer()->get(CommunicationRepository::class),
            static::getContainer()->get(\App\Repository\InvestmentRepository::class),
            $userRepositoryMock,
            static::getContainer()->get(\App\Service\MailerService::class),
            static::getContainer()->get(InvestmentManagerV2::class),
        );

        $asset = new Asset();
        $asset->setHoldWalletId(bin2hex(random_bytes(16)));
        $asset->setSettlementWalletId(bin2hex(random_bytes(16)));
        $asset->setStampDutyUser('stampDutyUserToBeMocked');
        $asset->setPricePerShare('4.77');

        $sellOrderInitial = new TradeOrder(
            asset: $asset,
            pricePerShare: new Number($asset->getPricePerShare()),
            direction: TradeDirection::Sell,
            type: TradeOrderType::Initial,
        );

        $user1 = EntityIdTestUtil::setEntityId(new User(), 1241);
        $user2 = EntityIdTestUtil::setEntityId(new User(), 6621);
        $user3 = EntityIdTestUtil::setEntityId(new User(), 7142);

        $groupedTrades = [];

        // User1 will have 3 share trades totalling over £1k
        foreach (range(1, 3) as $iteration) {
            $buyOrderMarket = new TradeOrder(
                asset: $asset,
                user: $user1,
                direction: TradeDirection::Buy,
                type: TradeOrderType::Market,
            );
            $shareTrade = EntityIdTestUtil::setEntityId(
                new ShareTrade(
                    buyOrder: $buyOrderMarket,
                    sellOrder: $sellOrderInitial,
                    tradeValue: new Number((string) (893.27 * $iteration)),
                ),
                111 + $iteration,
            );
            $groupedTrades[$user1->getId()][] = $shareTrade;
        }

        // User2 will have 2 share trades totalling under £1k
        foreach (range(1, 2) as $iteration) {
            $buyOrderMarket = new TradeOrder(
                asset: $asset,
                user: $user2,
                direction: TradeDirection::Buy,
                type: TradeOrderType::Market,
            );
            $shareTrade = EntityIdTestUtil::setEntityId(
                new ShareTrade(
                    buyOrder: $buyOrderMarket,
                    sellOrder: $sellOrderInitial,
                    tradeValue: new Number((string) (244.67 * $iteration)),
                ),
                222 + $iteration,
            );
            $groupedTrades[$user2->getId()][] = $shareTrade;
        }

        // User3 will have a single share trades over £1k
        $buyOrderMarket = new TradeOrder(
            asset: $asset,
            user: $user3,
            direction: TradeDirection::Buy,
            type: TradeOrderType::Market,
        );
        $shareTrade = EntityIdTestUtil::setEntityId(
            new ShareTrade(
                buyOrder: $buyOrderMarket,
                sellOrder: $sellOrderInitial,
                tradeValue: new Number('9674.08'),
            ),
            334,
        );
        $groupedTrades[$user3->getId()][] = $shareTrade;

        $transferOrder = new TransferOrder();
        $transferOrder->setAsset($asset);

        $actual = $service->generateStampDutyTransfers($transferOrder, $groupedTrades);

        /** @var TransferRequest[] $transfers */
        $transfers = $actual->getTransfers()->toArray();
        $this->assertCount(2, $transfers);

        $this->assertEquals(
            $asset->getHoldWalletId(),
            $transfers[0]->getDebitWalletId(),
        );
        $this->assertEquals(
            $stampDutyUser->getMangoPayWalletId(),
            $transfers[0]->getCreditWalletId(),
        );
        $this->assertEquals(
            MonthEndService::DESCRIPTION_PRESETS['stamp duty']
            . ' User#1241 on amount 5359.62',
            $transfers[0]->getDescription(),
        );
        $this->assertEquals('30', $transfers[0]->getAmount());
        // The first investment is used for the investment relation
        $this->assertEquals(
            $groupedTrades[$user1->getId()][0],
            $transfers[0]->getShareTrade(),
        );

        $this->assertEquals(
            $asset->getHoldWalletId(),
            $transfers[1]->getDebitWalletId(),
        );
        $this->assertEquals(
            $stampDutyUser->getMangoPayWalletId(),
            $transfers[1]->getCreditWalletId(),
        );
        $this->assertEquals(
            MonthEndService::DESCRIPTION_PRESETS['stamp duty']
            . ' User#7142 on amount 9674.08',
            $transfers[1]->getDescription(),
        );
        $this->assertEquals('50', $transfers[1]->getAmount());
        $this->assertEquals(
            $groupedTrades[$user3->getId()][0],
            $transfers[1]->getShareTrade(),
        );
        foreach ($transfers as $actual) {
            $this->assertEquals(TransferMode::StampDuty, $actual->getMode());
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('generateSettlementExceptionProvider')]
    public function testGenerateStampDutyTransfersExceptions(
        TransferOrder $transferOrder,
        string $exceptionMessage,
        ?User $stampDutyUser,
    ): void {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage($exceptionMessage);

        /** @var MockObject $userRepositoryMock */
        $userRepositoryMock = $this->createMock(UserRepository::class);
        $userRepositoryMock
            ->expects($this->atMost(1))
            ->method('findByEmail')
            ->willReturn($stampDutyUser);

        /** @var UserRepository $userRepositoryMock */
        $service = new SettlementService(
            static::getContainer()->get(\Psr\Log\LoggerInterface::class),
            static::getContainer()->get(CommunicationRepository::class),
            static::getContainer()->get(\App\Repository\InvestmentRepository::class),
            $userRepositoryMock,
            static::getContainer()->get(\App\Service\MailerService::class),
            static::getContainer()->get(InvestmentManagerV2::class),
        );
        $service->generateStampDutyTransfers($transferOrder, []);
    }

    public function testGenerateSettlementTransfersExceptions(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            'Cannot generate settlements if no asset linked to the order',
        );
        $this->service->generateSettlementTransfers(new TransferOrder(), []);
    }

    public static function generateSettlementExceptionProvider(): \Generator
    {
        $stampDutyUser = new User();
        $stampDutyUser->setUsername('stampdutyUser');
        $stampDutyUser->setMangoPayWalletId(bin2hex(random_bytes(16)));

        $stampDutyUserNoWallet = new User();
        $stampDutyUserNoWallet->setUsername('stampdutyUser');

        /** @var Asset $asset */
        $asset = EntityIdTestUtil::setEntityId(new Asset(), 445);
        $asset->setStampDutyUser($stampDutyUser);

        $transferOrder = new TransferOrder();
        $transferOrder->setAsset($asset);

        yield 'No asset linked' => [
            new TransferOrder(),
            'Cannot generate settlements if no asset linked to the order',
            $stampDutyUser,
        ];
        yield 'No stamp duty user' => [
            $transferOrder,
            'No stamp duty user found for asset #445',
            null,
        ];
        yield 'No stamp duty wallet' => [
            $transferOrder,
            'No wallet configured for stamp duty user stampdutyUser',
            $stampDutyUserNoWallet,
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('generateSettlementProvider')]
    public function testGenerateSettlement(
        ShareTrade $shareTrade,
        TransferRequest $expected,
    ): void {
        $actual = $this->service->generateSettlement($shareTrade);
        $this->assertEquals($expected->getDebitWalletId(), $actual->getDebitWalletId());
        $this->assertEquals(
            $expected->getCreditWalletId(),
            $actual->getCreditWalletId(),
        );
        $this->assertEquals($expected->getDescription(), $actual->getDescription());
        $this->assertEquals($expected->getAmount(), $actual->getAmount());
        $this->assertEquals($expected->getShareTrade(), $actual->getShareTrade());
        $this->assertEquals($expected->getMode(), $actual->getMode());
        $this->assertEquals(TransferMode::Settlement, $actual->getMode());
    }

    public static function generateSettlementProvider(): \Generator
    {
        $asset = new Asset();
        $asset->setPricePerShare('3.1');
        $asset->setHoldWalletId(bin2hex(random_bytes(16)));
        $asset->setSettlementWalletId(bin2hex(random_bytes(16)));

        $sellOrderInitial = new TradeOrder(
            asset: $asset,
            pricePerShare: new Number($asset->getPricePerShare()),
            direction: TradeDirection::Sell,
            type: TradeOrderType::Initial,
        );

        $seller = new User();
        $seller->setMangoPayWalletId(bin2hex(random_bytes(16)));

        $sellOrderMarket = new TradeOrder(
            asset: $asset,
            user: $seller,
            pricePerShare: new Number($asset->getPricePerShare()),
            direction: TradeDirection::Sell,
            type: TradeOrderType::Market,
        );

        $buyOrder1 = new TradeOrder(
            asset: $asset,
            direction: TradeDirection::Buy,
            type: TradeOrderType::Market,
        );
        $firstParty = EntityIdTestUtil::setEntityId(
            new ShareTrade(
                buyOrder: $buyOrder1,
                sellOrder: $sellOrderInitial,
                tradeValue: new Number('1180.24'),
            ),
            6784,
        );
        $firstPartyRequest = new TransferRequest();
        $firstPartyRequest->setMode(TransferMode::Settlement);
        $firstPartyRequest->setShareTrade($firstParty);
        $firstPartyRequest->setDebitWalletId($asset->getHoldWalletId());
        $firstPartyRequest->setCreditWalletId($asset->getSettlementWalletId());
        $firstPartyRequest->setDescription(
            MonthEndService::DESCRIPTION_PRESETS['settlement'] . ' #6784',
        );
        $firstPartyRequest->setAmount('1180.24');

        $buyOrder2 = new TradeOrder(
            asset: $asset,
            direction: TradeDirection::Buy,
            type: TradeOrderType::Market,
        );
        $relisting = EntityIdTestUtil::setEntityId(
            new ShareTrade(
                buyOrder: $buyOrder2,
                sellOrder: $sellOrderMarket,
                tradeValue: new Number('561.95'),
            ),
            5512,
        );
        $relistingRequest = new TransferRequest();
        $relistingRequest->setMode(TransferMode::Settlement);
        $relistingRequest->setShareTrade($relisting);
        $relistingRequest->setDebitWalletId($asset->getHoldWalletId());
        $relistingRequest->setCreditWalletId($seller->getMangoPayWalletId());
        $relistingRequest->setDescription(
            MonthEndService::DESCRIPTION_PRESETS['settlement'] . ' #5512',
        );
        $relistingRequest->setAmount('561.95');

        yield 'First party share trade' => [$firstParty, $firstPartyRequest];
        yield 'Relisting share trade' => [$relisting, $relistingRequest];
    }

    public function testGroupTradeSettlementsByUser(): void
    {
        $user1 = EntityIdTestUtil::setEntityId(new User(), 1241);
        $user2 = EntityIdTestUtil::setEntityId(new User(), 2133);

        $buyOrder1 = new TradeOrder(
            user: $user1,
            direction: TradeDirection::Buy,
            type: TradeOrderType::Market,
        );
        $shareTrade1 = EntityIdTestUtil::setEntityId(
            new ShareTrade(buyOrder: $buyOrder1, tradeValue: new Number('1180.24')),
            765,
        );

        $buyOrder2 = new TradeOrder(
            user: $user2,
            direction: TradeDirection::Buy,
            type: TradeOrderType::Market,
        );
        $shareTrade2 = EntityIdTestUtil::setEntityId(
            new ShareTrade(buyOrder: $buyOrder2, tradeValue: new Number('1180.24')),
            1551,
        );
        $buyOrder3 = new TradeOrder(
            user: $user2,
            direction: TradeDirection::Buy,
            type: TradeOrderType::Market,
        );
        $shareTrade3 = EntityIdTestUtil::setEntityId(
            new ShareTrade(buyOrder: $buyOrder3, tradeValue: new Number('1180.24')),
            2067,
        );
        $buyOrder4 = new TradeOrder(
            user: $user1,
            direction: TradeDirection::Buy,
            type: TradeOrderType::Market,
        );
        $shareTrade4 = EntityIdTestUtil::setEntityId(
            new ShareTrade(buyOrder: $buyOrder4, tradeValue: new Number('1180.24')),
            3271,
        );

        $transferOrder = new TransferOrder();
        foreach ([
            $shareTrade1,
            $shareTrade2,
            $shareTrade3,
            $shareTrade4,
        ] as $shareTrade) {
            $transferRequest = new TransferRequest();
            $transferRequest->setShareTrade($shareTrade);
            $transferOrder->addTransfer($transferRequest);
        }

        $expected = [
            1241 => [765, 3271],
            2133 => [1551, 2067],
        ];
        $actual = $this->service->groupTradeSettlementsByUser($transferOrder);
        $this->assertEmpty(array_diff(array_keys($expected), array_keys($actual)));
        foreach ($actual as $userId => $investments) {
            $actual[$userId] = EntityIdTestUtil::extractIds($investments);
        }
        $this->assertEquals($expected, $actual);
    }
}
