<?php

namespace App\Tests\Service;

use App\Dto\Struct\UserShares;
use App\Entity\Address;
use App\Entity\Asset;
use App\Entity\Enum\ShareTradeType;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TradeStatus;
use App\Entity\Investment;
use App\Entity\Offering;
use App\Entity\ShareTrade;
use App\Entity\ShareTransferOrder;
use App\Entity\ShareTransferRequest;
use App\Entity\TradeOrder;
use App\Entity\User;
use App\Entity\UserCustomFields;
use App\Service\ShareTransferService;
use App\Test\Util\EntityIdTestUtil;
use BcMath\Number;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ShareTransferServiceTest extends KernelTestCase
{
    private ShareTransferService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(ShareTransferService::class);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getShareTradeQueryFilterProvider')]
    public function testGetShareTradeQueryFilter(
        array $expected,
        ShareTransferOrder $shareTransferOrder,
        ShareTradeType $tradeType,
    ): void {
        $actual = $this->service->getShareTradeQueryFilter(
            $shareTransferOrder,
            $tradeType,
        );
        $this->assertEquals($expected, $actual);
    }

    public static function getShareTradeQueryFilterProvider(): \Generator
    {
        $asset = EntityIdTestUtil::setEntityId(new Asset(), 515);
        $shareTransferOrder = new ShareTransferOrder();
        $shareTransferOrder->setAsset($asset);
        $shareTransferOrder->setPeriodStart(new \DateTime('2021-07-01'));
        $shareTransferOrder->setPeriodEnd(new \DateTime('2021-08-16'));
        $shareTransferOrder->setRepaymentStart(new \DateTime('2021-08-06'));
        $shareTransferOrder->setRepaymentEnd(new \DateTime('2021-12-01'));

        $expectedFirstParty = [
            'assetId' => $asset->getId(),
            'status' => [TradeStatus::Settled],
            'createdAt_gte' => $shareTransferOrder->getPeriodStart(),
            'createdAt_lt' => $shareTransferOrder->getPeriodEnd(),
            'sellOrderType' => [TradeOrderType::Initial],
            'buyOrderType' => TradeOrderType::retailBuyTypes(),
        ];
        $expectedRelisted = [
            'assetId' => $asset->getId(),
            'status' => [TradeStatus::Settled],
            'createdAt_gte' => $shareTransferOrder->getPeriodStart(),
            'createdAt_lt' => $shareTransferOrder->getPeriodEnd(),
            'sellOrderType' => TradeOrderType::marketTradingTypes(),
            'buyOrderType' => TradeOrderType::retailBuyTypes(),
        ];
        $expectedProxy = [
            'assetId' => $asset->getId(),
            'status' => [TradeStatus::Settled],
            'createdAt_gte' => $shareTransferOrder->getRepaymentStart(),
            'createdAt_lt' => $shareTransferOrder->getRepaymentEnd(),
            'sellOrderType' => [TradeOrderType::Prefunding],
            'buyOrderType' => [TradeOrderType::Proxy],
        ];

        yield 'first party' => [
            $expectedFirstParty,
            $shareTransferOrder,
            ShareTradeType::FirstParty,
        ];
        yield 'relisted' => [
            $expectedRelisted,
            $shareTransferOrder,
            ShareTradeType::SecondaryMarket,
        ];
        yield 'repayments' => [
            $expectedProxy,
            $shareTransferOrder,
            ShareTradeType::Repayment,
        ];
    }

    public function testPoolShareTrades(): void
    {
        $issuer = EntityIdTestUtil::setEntityId(new User(), 8);
        $prefunder = EntityIdTestUtil::setEntityId(new User(), 613);
        $user1 = EntityIdTestUtil::setEntityId(new User(), 452);
        $user2 = EntityIdTestUtil::setEntityId(new User(), 6821);
        $user3 = EntityIdTestUtil::setEntityId(new User(), 3551);

        $shareTrades = [];
        $initialOrder = new TradeOrder(
            direction: TradeDirection::Sell,
            user: $issuer,
            numberOfShares: 8500,
            type: TradeOrderType::Initial,
        );
        $prefunderOrder = new TradeOrder(
            direction: TradeDirection::Sell,
            user: $prefunder,
            numberOfShares: 8500,
            type: TradeOrderType::Prefunding,
        );

        /**
         * 1 == u1 == 100
         * 2 == u3 == 200
         * 3 == u2 == 300
         * 4 == u3 == 400 + prefunder
         * 5 == u1 == 500
         * 6 == u2 == 600
         * 7 == u1 == 700
         * 8 == u3 == 800 + prefunder
         */
        foreach (range(1, 8) as $i) {
            $user = $user1;
            $sellOrder = $initialOrder;
            if (($i % 2) == 0) {
                $user = $user3;
            }
            if (($i % 3) == 0) {
                $user = $user2;
            }
            if (($i % 4) == 0) {
                $sellOrder = $prefunderOrder;
            }
            $buyOrder = new TradeOrder(
                direction: TradeDirection::Buy,
                user: $user,
                numberOfShares: 100 * $i,
                type: TradeOrderType::Market,
            );
            $shareTrade = new ShareTrade(
                sellOrder: $sellOrder,
                buyOrder: $buyOrder,
                numberOfShares: $buyOrder->getNumberOfShares(),
            );
            $shareTrades[] = $shareTrade;
        }

        $expectedInvestmentMode = [
            3551 => new UserShares($user3, 200 + 400 + 800),
            452 => new UserShares($user1, 100 + 500 + 700),
            6821 => new UserShares($user2, 300 + 600),
        ];
        // Seller is used for the pooling
        $expectedRepaymentMode = [
            8 => new UserShares($issuer, 2400),
            613 => new UserShares($prefunder, 1200),
        ];

        $actual = $this->service->poolShareTrades($shareTrades);
        $this->assertSame(array_keys($expectedInvestmentMode), array_keys($actual));
        $this->assertEquals($expectedInvestmentMode, $actual);

        $actual = $this->service->poolShareTrades(
            $shareTrades,
            ShareTradeType::Repayment,
        );
        $this->assertSame(array_keys($expectedRepaymentMode), array_keys($actual));
        $this->assertEquals($expectedRepaymentMode, $actual);
    }

    public function testGenerateDirectShareTransfers(): void
    {
        $issuer = EntityIdTestUtil::setEntityId(new User(), 8);
        $prefunder = EntityIdTestUtil::setEntityId(new User(), 613);
        $user1 = EntityIdTestUtil::setEntityId(new User(), 452);
        $user2 = EntityIdTestUtil::setEntityId(new User(), 6821);
        $initialOrder = new TradeOrder(
            direction: TradeDirection::Sell,
            user: $issuer,
            numberOfShares: 8500,
            type: TradeOrderType::Initial,
        );
        $prefunderOrder = new TradeOrder(
            direction: TradeDirection::Sell,
            user: $prefunder,
            numberOfShares: 8500,
            type: TradeOrderType::Prefunding,
        );
        $buyOrder1 = new TradeOrder(
            direction: TradeDirection::Buy,
            user: $user1,
            numberOfShares: 100,
            type: TradeOrderType::Market,
        );
        $shareTrade1 = new ShareTrade(
            sellOrder: $initialOrder,
            buyOrder: $buyOrder1,
            numberOfShares: 58,
        );
        $buyOrder2 = new TradeOrder(
            direction: TradeDirection::Buy,
            user: $user2,
            numberOfShares: 88,
            type: TradeOrderType::Market,
        );
        $shareTrade2 = new ShareTrade(
            sellOrder: $prefunderOrder,
            buyOrder: $buyOrder2,
            numberOfShares: 88,
        );
        $shareTransferOrder = new ShareTransferOrder();
        $this->service->generateDirectShareTransfers($shareTransferOrder, [
            $shareTrade1,
            $shareTrade2,
        ]);
        $this->assertCount(2, $shareTransferOrder->getShareTransfers());

        $this->assertEquals(
            $issuer,
            $shareTransferOrder->getShareTransfers()->first()->getSeller(),
        );
        $this->assertEquals(
            $user1,
            $shareTransferOrder->getShareTransfers()->first()->getBuyer(),
        );
        $this->assertEquals(
            58,
            $shareTransferOrder->getShareTransfers()->first()->getShares(),
        );
        $this->assertEquals(
            $prefunder,
            $shareTransferOrder->getShareTransfers()->last()->getSeller(),
        );
        $this->assertEquals(
            $user2,
            $shareTransferOrder->getShareTransfers()->last()->getBuyer(),
        );
        $this->assertEquals(
            88,
            $shareTransferOrder->getShareTransfers()->last()->getShares(),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('check')]
    public function testGeneratePooledShareTransfers(): void
    {
        $issuer = EntityIdTestUtil::setEntityId(new User(), 8);
        $prefunder1 = EntityIdTestUtil::setEntityId(new User(), 613);
        $prefunder2 = EntityIdTestUtil::setEntityId(new User(), 871);
        $user1 = EntityIdTestUtil::setEntityId(new User(), 452);
        $user2 = EntityIdTestUtil::setEntityId(new User(), 6821);
        $user3 = EntityIdTestUtil::setEntityId(new User(), 3551);

        $shareTrades = [];
        $initialOrder = new TradeOrder(
            direction: TradeDirection::Sell,
            user: $issuer,
            numberOfShares: 8500,
            type: TradeOrderType::Initial,
        );
        $prefunderOrderU1_1 = new TradeOrder(
            direction: TradeDirection::Sell,
            user: $prefunder1,
            numberOfShares: 2500,
            type: TradeOrderType::Prefunding,
        );
        $prefunderOrderU2_1 = new TradeOrder(
            direction: TradeDirection::Sell,
            user: $prefunder2,
            numberOfShares: 800,
            type: TradeOrderType::Prefunding,
        );
        $prefunderOrderU2_2 = new TradeOrder(
            direction: TradeDirection::Sell,
            user: $prefunder2,
            numberOfShares: 2200,
            type: TradeOrderType::Prefunding,
        );
        $proxyTrade1 = new ShareTrade(
            sellOrder: $prefunderOrderU1_1,
            numberOfShares: 1600,
        );
        $proxyTrade2 = new ShareTrade(
            sellOrder: $prefunderOrderU2_1,
            numberOfShares: 800,
        );
        $proxyTrade3 = new ShareTrade(
            sellOrder: $prefunderOrderU2_2,
            numberOfShares: 1200,
        );
        $proxyBuyBacks = [$proxyTrade1, $proxyTrade2, $proxyTrade3];

        /**
         * 1 == u1 == 100
         * 2 == u3 == 200
         * 3 == u2 == 300
         * 4 == u3 == 400
         * 5 == u1 == 500
         * 6 == u2 == 600
         * 7 == u1 == 700
         * 8 == u3 == 800
         */
        foreach (range(1, 8) as $i) {
            $user = $user1;
            if (($i % 2) == 0) {
                $user = $user3;
            }
            if (($i % 3) == 0) {
                $user = $user2;
            }
            $buyOrder = new TradeOrder(
                direction: TradeDirection::Buy,
                user: $user,
                numberOfShares: 100 * $i,
                type: TradeOrderType::Market,
            );
            $shareTrade = new ShareTrade(
                sellOrder: $initialOrder,
                buyOrder: $buyOrder,
                numberOfShares: $buyOrder->getNumberOfShares(),
            );
            $shareTrades[] = $shareTrade;
        }

        // $expectedInvestors and $expectedPrefunder just here to help guide you write $expected
        $expectedInvestors = [
            3551 => new UserShares($user3, 200 + 400 + 800), // 1400 total
            452 => new UserShares($user1, 100 + 500 + 700), // 1300 total
            6821 => new UserShares($user2, 300 + 600), // 900 total
        ];
        $expectedPrefunder = [
            871 => new UserShares($prefunder2, 800 + 1200), // 2000 total
            613 => new UserShares($prefunder1, 1600),
        ];
        $expected = [
            ['buyer' => 3551, 'seller' => 871, 'shares' => 1400],
            ['buyer' => 452, 'seller' => 871, 'shares' => 600],
            ['buyer' => 452, 'seller' => 613, 'shares' => 700],
            ['buyer' => 6821, 'seller' => 613, 'shares' => 900],
        ];

        $shareTransferOrder = new ShareTransferOrder();
        $actual = $this->service->generatePooledShareTransfers(
            $shareTransferOrder,
            $shareTrades,
            $proxyBuyBacks,
        );
        $this->assertCount(count($expected), $actual->getShareTransfers());
        foreach ($actual->getShareTransfers()->toArray() as $key => $shareTransfer) {
            // Check in order
            $this->assertEquals(
                $expected[$key]['buyer'],
                $shareTransfer->getBuyer()->getId(),
            );
            $this->assertEquals(
                $expected[$key]['seller'],
                $shareTransfer->getSeller()->getId(),
            );
            $this->assertEquals($expected[$key]['shares'], $shareTransfer->getShares());

            // print_r([
            //     'buyer' => $shareTransfer->getBuyer()->getId(),
            //     'seller' => $shareTransfer->getSeller()->getId(),
            //     'shares' => $shareTransfer->getShares(),
            // ]);
        }

        $this->expectExceptionMessage(
            'Imbalance of shares to allocate. From: 1600 To: 3600',
        );
        $this->service->generatePooledShareTransfers(
            $shareTransferOrder,
            $shareTrades,
            [$proxyTrade1],
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('formatShareTransferProvider')]
    public function testformatShareTransferCallable(
        array $expected,
        ShareTransferRequest $input,
    ): void {
        $actual = \call_user_func(
            $this->service->formatShareTransferCallable(),
            $input,
        );
        $this->assertEquals($expected, $actual);
    }

    public static function formatShareTransferProvider(): \Generator
    {
        $expectedWithData = [
            'id' => 24658,
            'orderId' => 551,
            'assetId' => 22,
            'assetName' => 'Automated test transfer order asset',
            'assetSpv' => 'SPVT000328',
            'assetSharePrice' => '2.57',
            'numberOfShares' => 857,
            'tradeValue' => '2202.49',
            'calculatedInvestmentValue' => '2202.49',
            'estimatedStampDuty' => '15',
            'investmentId' => null,
            'shareTradeId' => 15,

            'buyerId' => 256,
            'buyerUsername' => 'sharetransfer@test.com',
            'buyerContactEmail' => 'contact.sharetransfer@test.com',
            'buyerTitle' => 'Dr',
            'buyerFirstName' => 'Shaira',
            'buyerLastName' => 'Fernandes',
            'buyerAdressLine1' => '26 Walls House',
            'buyerAdressLine2' => 'Regents Road',
            'buyerAddressCity' => 'Brighton',
            'buyerAddressRegion' => 'East Sussex',
            'buyerAddressPostCode' => 'BR1 4AA',
            'buyerAddressCountry' => 'GB',

            'buyerCompanyName' => 'Share Transfer Corp',
            'buyerCompanyRegNumber' => '100010001',
            'buyerCompanyAddress1' => '1 Corning Way',
            'buyerCompanyPostCode' => 'SW11 8YG',
            'buyerCompanyCountry' => 'GB',
            'buyerCompanyApprovedOn' => '2020-04-14',

            'sellerId' => 613,
            'sellerUsername' => 'sellers@test.com',
            'sellerContactEmail' => 'contact.sellers@test.com',
            'sellerTitle' => 'Mr',
            'sellerFirstName' => 'Mark',
            'sellerLastName' => 'Sellers',
        ];

        $asset = EntityIdTestUtil::setEntityId(
            new Asset(),
            $expectedWithData['assetId'],
        );
        $asset->setCompanyNumber($expectedWithData['assetSpv']);
        $asset->setName($expectedWithData['assetName']);
        $asset->setPricePerShare($expectedWithData['assetSharePrice']);

        $buyer = EntityIdTestUtil::setEntityId(
            new User(),
            $expectedWithData['buyerId'],
        );
        $buyer->setUsername($expectedWithData['buyerUsername']);
        $buyer->setEmail($expectedWithData['buyerContactEmail']);
        $buyer->setHonoricPrefix($expectedWithData['buyerTitle']);
        $buyer->setFirstname($expectedWithData['buyerFirstName']);
        $buyer->setLastname($expectedWithData['buyerLastName']);

        $buyerAddress = new Address();
        $buyerAddress->setAddress1($expectedWithData['buyerAdressLine1']);
        $buyerAddress->setAddress2($expectedWithData['buyerAdressLine2']);
        $buyerAddress->setCity($expectedWithData['buyerAddressCity']);
        $buyerAddress->setRegion($expectedWithData['buyerAddressRegion']);
        $buyerAddress->setPostCode($expectedWithData['buyerAddressPostCode']);
        $buyerAddress->setCountry($expectedWithData['buyerAddressCountry']);
        $buyer->addAddress($buyerAddress);

        $buyer->getCompany()->setName($expectedWithData['buyerCompanyName']);
        $buyer
            ->getCompany()
            ->setRegistrationNumber($expectedWithData['buyerCompanyRegNumber']);
        $buyer->getCompany()->setRegAddress1($expectedWithData['buyerCompanyAddress1']);
        $buyer->getCompany()->setPostCode($expectedWithData['buyerCompanyPostCode']);
        $buyer->getCompany()->setRegCountry($expectedWithData['buyerCompanyCountry']);

        $companyApprovedField = new UserCustomFields();
        $companyApprovedField->setFieldKey('companyApprovedOn');
        $companyApprovedField->setFieldValue(
            $expectedWithData['buyerCompanyApprovedOn'],
        );
        $buyer->addCustomField($companyApprovedField);

        $seller = EntityIdTestUtil::setEntityId(
            new User(),
            $expectedWithData['sellerId'],
        );
        $seller->setUsername($expectedWithData['sellerUsername']);
        $seller->setEmail($expectedWithData['sellerContactEmail']);
        $seller->setHonoricPrefix($expectedWithData['sellerTitle']);
        $seller->setFirstname($expectedWithData['sellerFirstName']);
        $seller->setLastname($expectedWithData['sellerLastName']);

        $shareTransferOrder = EntityIdTestUtil::setEntityId(
            new ShareTransferOrder(),
            $expectedWithData['orderId'],
        );
        $shareTransferOrder->setAsset($asset);

        $buyOrder = new TradeOrder(
            direction: TradeDirection::Buy,
            numberOfShares: 1000,
            user: $buyer,
            type: TradeOrderType::Market,
        );
        $shareTrade = EntityIdTestUtil::setEntityId(
            new ShareTrade(
                buyOrder: $buyOrder,
                numberOfShares: 857,
                tradeValue: new Number('2202.49'),
            ),
            15,
        );

        $withInvestment = EntityIdTestUtil::setEntityId(
            new ShareTransferRequest(),
            $expectedWithData['id'],
        );
        $withInvestment->setShareTransferOrder($shareTransferOrder);
        $withInvestment->setSeller($seller);
        $withInvestment->setBuyer($buyer);
        $withInvestment->setShares($expectedWithData['numberOfShares']);
        $withInvestment->setShareTrade($shareTrade);

        $onlyShareholding = EntityIdTestUtil::setEntityId(
            new ShareTransferRequest(),
            $expectedWithData['id'],
        );
        $onlyShareholding->setShareTransferOrder($shareTransferOrder);
        $onlyShareholding->setSeller($seller);
        $onlyShareholding->setBuyer($buyer);
        $onlyShareholding->setShares($expectedWithData['numberOfShares']);

        $expectedOnlyShareholdings = $expectedWithData;
        $expectedOnlyShareholdings['shareTradeId'] = null;
        $expectedOnlyShareholdings['tradeValue'] = '';

        yield 'With investments' => [$expectedWithData, $withInvestment];
        yield 'Only shareholdings' => [$expectedOnlyShareholdings, $onlyShareholding];
    }
}
