<?php

declare(strict_types=1);

namespace Tests\ClientBundle\Service;

use AppBundle\Entity\AssetProduct;
use AppBundle\Entity\Enum\AssetStatus;
use AppBundle\Entity\Enum\KycReviewStatus;
use AppBundle\Entity\Enum\KycReviewType;
use AppBundle\Entity\Enum\ScaStatus;
use AppBundle\Entity\Enum\TradeDirection;
use AppBundle\Entity\Enum\TradeOrderStatus;
use AppBundle\Entity\Enum\TradeOrderType;
use AppBundle\Entity\Enum\TradeStatus;
use AppBundle\Entity\Enum\UserCategory;
use AppBundle\Entity\ScaAction;
use AppBundle\Entity\ScaOutcome;
use AppBundle\Entity\ShareTrade;
use AppBundle\Entity\TradeOrder;
use ClientBundle\Exception\InvestmentNotAllowedException;
use ClientBundle\Service\AssetProductService;
use ClientBundle\Service\InvestmentServiceV2;
use ClientBundle\Service\OnboardingService;
use ClientBundle\Service\PortfolioService;
use ClientBundle\Service\VerificationService;
use ClientBundle\Service\Yielders\ApiClient;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class InvestmentServiceTest extends AbstractApiServiceTest
{
    private InvestmentServiceV2 $service;
    private RequestStack $requestStack;
    private TagAwareCacheInterface $cache;

    private string $userId = '2';

    protected function setUp(): void
    {
        parent::setUp();

        // Create a simple request stack with a single request that has a session
        // Intended as a placeholder so we can set session parameters like the (wallet) balance
        $this->requestStack = new RequestStack();
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $this->requestStack->push($request);
        $this->requestStack->getSession()->set('userInfo', ['id' => $this->userId]);
        // $this->service = static::getContainer()->get(InvestmentServiceV2::class);
        $this->cache = static::getContainer()->get(TagAwareCacheInterface::class);

        $verificationSerice = new VerificationService(
            static::getContainer()->get(ApiClient::class),
            static::getContainer()->get(LoggerInterface::class),
            $this->requestStack,
            static::getContainer()->get(NormalizerInterface::class),
            static::getContainer()->get(DenormalizerInterface::class),
        );

        $onboardingService = new OnboardingService(
            static::getContainer()->get(ApiClient::class),
            static::getContainer()->get(LoggerInterface::class),
            $this->requestStack,
            static::getContainer()->get(NormalizerInterface::class),
            static::getContainer()->get(DenormalizerInterface::class),
        );

        $portfolioService = new PortfolioService(
            static::getContainer()->get(ApiClient::class),
            static::getContainer()->get(LoggerInterface::class),
            $this->requestStack,
            static::getContainer()->get(NormalizerInterface::class),
            static::getContainer()->get(DenormalizerInterface::class),
            $this->cache,
        );

        $this->service = new InvestmentServiceV2(
            static::getContainer()->get(ApiClient::class),
            static::getContainer()->get(LoggerInterface::class),
            $this->requestStack,
            $onboardingService,
            $verificationSerice,
            $portfolioService,
            static::getContainer()->get(AssetProductService::class),
            static::getContainer()->get(DenormalizerInterface::class),
        );

        // Clear the cache in between each test as we're mocking API calls
        $this->cache->invalidateTags([PortfolioService::PORTFOLIO_PREFIX_CACHE_TAG . $this->userId]);
    }

    /**
     * @dataProvider buyOrderProvider
     */
    public function testCreateBuyOrder(TradeOrderType $expectedType, bool $prefunding): void
    {
        $sampleId = (string)mt_rand(0, 16);
        $sampleAmount = mt_rand(100, 1000);
        $sampleStampDuty = (string)mt_rand(0, 25);

        $asset = new AssetProduct(
            id: $sampleId,
            pricePerShare: '1.49',
        );
        $tradeOrder = new TradeOrder(id: '5172', pricePerShare: '1.49');

        $expectedBody = [
            "assetId" => $sampleId,
            "direction" => TradeDirection::Buy->value,
            "numberOfShares" => $sampleAmount,
            "pricePerShare" => '1.49',
            "type" => $expectedType->value,
            "status" => TradeOrderStatus::Submitted->value,
            "counterpartyOrderId" => '5172',
            "reserveShares" => true,
            "fees" => "0",
            "taxes" => $sampleStampDuty,
        ];

        $this->mockHandler->append(new Response(200, [], '[]'));
        $actual = $this->service->createBuyOrder(
            $asset,
            $sampleAmount,
            $sampleStampDuty,
            $tradeOrder,
            $prefunding,
        );

        $this->assertCount(1, $this->history);
        $this->assertEquals('POST', $this->history[0]['request']->getMethod());
        $this->assertEquals('/v1/yielders/trade-orders', $this->history[0]['request']->getRequestTarget());
        $actualBody = json_decode($this->history[0]['request']->getBody()->getContents(), true);
        $this->assertEquals($expectedBody, $actualBody);

        // Check conversion into TradeOrder instance
        $this->assertInstanceOf(TradeOrder::class, $actual);
    }

    public static function buyOrderProvider(): \Generator
    {
        yield 'Default - market' => [TradeOrderType::Market, false];
        yield 'Prefunding - market' => [TradeOrderType::Prefunding, true];
    }

    public function testCreateBuyOrderFailed(): void
    {
        $asset = new AssetProduct(
            id: '1',
            pricePerShare: '1.49',
        );

        $this->expectException(InvestmentNotAllowedException::class);
        $this->expectExceptionMessage('Investment could not be made. Please try again');

        $this->mockHandler->append(new Response(400, [], '[]'));
        $this->service->createBuyOrder($asset, 1);
        $this->assertCount(1, $this->history);
    }

    /**
     * @dataProvider sellOrderProvider
     */
    public function testCreateSellOrder(TradeOrderType $expectedType, bool $prefunding): void
    {
        $sampleId = (string)mt_rand(0, 16);
        $sampleAmount = mt_rand(100, 1000);
        $sampleFee = (string)mt_rand(0, 25);

        $asset = new AssetProduct(
            id: $sampleId,
            pricePerShare: '1.49',
        );
        $tradeOrder = new TradeOrder(id: '5172', pricePerShare: '1.49');

        $expectedBody = [
            "assetId" => $sampleId,
            "direction" => TradeDirection::Sell->value,
            "numberOfShares" => $sampleAmount,
            "pricePerShare" => '1.49',
            "type" => $expectedType->value,
            "status" => TradeOrderStatus::Submitted->value,
            "complementaryOrderId" => $prefunding ? '5172' : null,
            "fees" => $sampleFee,
            "taxes" => "0",
        ];

        $this->mockHandler->append(new Response(200, [], '[]'));
        $actual = $this->service->createSellOrder(
            $asset,
            $sampleAmount,
            $sampleFee,
            $prefunding ? $tradeOrder : null,
            $prefunding,
        );

        $this->assertCount(1, $this->history);
        $this->assertEquals('POST', $this->history[0]['request']->getMethod());
        $this->assertEquals('/v1/yielders/trade-orders', $this->history[0]['request']->getRequestTarget());
        $actualBody = json_decode($this->history[0]['request']->getBody()->getContents(), true);
        $this->assertEquals($expectedBody, $actualBody);

        // Check conversion into TradeOrder instance
        $this->assertInstanceOf(TradeOrder::class, $actual);
    }

    public static function sellOrderProvider(): \Generator
    {
        yield 'Default - market' => [TradeOrderType::Market, false];
        yield 'Prefunding - market' => [TradeOrderType::Prefunding, true];
    }

    public function testPrefundInvestOpportunity(): void
    {
        $sampleId = (string)mt_rand(0, 16);
        // This guarantees a trade value between 1k-2k
        $sampleAmount = mt_rand(1001, 1500);
        $sampleRetention = mt_rand(0, 200);
        $sharePriceToUse = 1.28;

        $asset = new AssetProduct(
            id: $sampleId,
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 18500,
            status: AssetStatus::Acquiring,
        );
        // The original sell order being bought from
        $tradeOrder = new TradeOrder(
            id: '5172',
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 18500,
            direction: TradeDirection::Sell,
            type: TradeOrderType::Initial,
        );

        /**
         * Should be 3 requests sent
         * - POST/CREATE trade order - createBuyOrder
         * - POST/CREATE trade order - createSellOrder
         * - POST/CREATE trade order payment - takeOrderPayment
         */
        $tradeOrderResponse = json_encode(new TradeOrder(id: '24', pricePerShare: '1.28'));
        $sellOrderResponse = json_encode(new TradeOrder(id: '25'));
        $scaActionResponse = json_encode(new ScaAction(
            id: '24',
            providerStatus: 'CREATED',
            pendingUserAction: ['redirectUrl' => 'https://sca.sandbox.mangopay.com/?token=sca_testexample'],
        ));

        $this->mockHandler->append(new Response(200, [], $tradeOrderResponse));
        $this->mockHandler->append(new Response(200, [], $sellOrderResponse));
        $this->mockHandler->append(new Response(200, [], $scaActionResponse));
        $this->requestStack->getSession()->set('balance', '20000');
        $this->service->prefundInvestAsset(
            asset: $asset,
            numberOfShares: $sampleAmount,
            sharesToKeep: $sampleRetention,
            tradeOrder: $tradeOrder,
            sca: true,
        );

        $expectedTradeOrderBody = [
            "assetId" => $sampleId,
            "direction" => TradeDirection::Buy->value,
            "numberOfShares" => $sampleAmount,
            "pricePerShare" => '1.28',
            "type" => TradeOrderType::Prefunding->value,
            "status" => TradeOrderStatus::Submitted->value,
            "counterpartyOrderId" => '5172',
            "reserveShares" => true,
            "fees" => "0",
            "taxes" => "0",
        ];

        $expectedSellOrderBody = [
            "assetId" => $sampleId,
            "direction" => TradeDirection::Sell->value,
            "numberOfShares" => $sampleAmount - $sampleRetention,
            "pricePerShare" => '1.28',
            "type" => TradeOrderType::Prefunding->value,
            "status" => TradeOrderStatus::Submitted->value,
            "complementaryOrderId" => '24',
            "fees" => "0",
            "taxes" => "0",
        ];

        $expectedScaActionBody = [
            "amount" => round($sampleAmount * $sharePriceToUse, 2),
            "sca" => true,
        ];

        $this->assertCount(3, $this->history);
        foreach ($this->history as $key => $record) {
            $body = json_decode($record['request']->getBody()->getContents(), true);
            if (0 === $key) {
                $this->assertEquals('POST', $record['request']->getMethod());
                $this->assertEquals('/v1/yielders/trade-orders', $record['request']->getRequestTarget());
                $this->assertEquals($expectedTradeOrderBody, $body);
            }
            if (1 === $key) {
                $this->assertEquals('POST', $record['request']->getMethod());
                $this->assertEquals('/v1/yielders/trade-orders', $record['request']->getRequestTarget());
                $this->assertEquals($expectedSellOrderBody, $body);
            }
            if (2 === $key) {
                $this->assertEquals('POST', $record['request']->getMethod());
                $this->assertEquals('/v1/yielders/trade-orders/24/payments', $record['request']->getRequestTarget());
                $this->assertEquals($expectedScaActionBody, $body);
            }
        }
    }

    public function testPrefundInvestOpportunityNoSca(): void
    {
        $sampleId = (string)mt_rand(0, 16);
        // This guarantees a trade value between 1k-2k
        $sampleAmount = mt_rand(1001, 1500);
        $sampleRetention = mt_rand(0, 200);
        $sharePriceToUse = 1.28;

        $asset = new AssetProduct(
            id: $sampleId,
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 18500,
            status: AssetStatus::Acquiring,
        );
        // The original sell order being bought from
        $tradeOrder = new TradeOrder(
            id: '5172',
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 18500,
            direction: TradeDirection::Sell,
            type: TradeOrderType::Initial,
        );

        /**
         * Should be 4 requests sent
         * - POST/CREATE trade order - createBuyOrder
         * - POST/CREATE trade order - createSellOrder
         * - POST/CREATE trade order payment - takeOrderPayment
         * - POST/CREATE trade order payment outcome - processOrderPaymentOutcome
         */
        $tradeOrderResponse = json_encode(new TradeOrder(id: '24', pricePerShare: '1.28'));
        $sellOrderResponse = json_encode(new TradeOrder(id: '25'));
        $scaActionResponse = json_encode(new ScaAction(
            id: '24',
            providerStatus: 'SUCCEEDED',
            pendingUserAction: [],
        ));
        $scaOutcomeResponse = json_encode(new ScaOutcome(
            id: '24',
            success: true,
        ));

        $this->mockHandler->append(new Response(200, [], $tradeOrderResponse));
        $this->mockHandler->append(new Response(200, [], $sellOrderResponse));
        $this->mockHandler->append(new Response(200, [], $scaActionResponse));
        $this->mockHandler->append(new Response(200, [], $scaOutcomeResponse));
        $this->requestStack->getSession()->set('balance', '20000');
        $this->service->prefundInvestAsset(
            asset: $asset,
            numberOfShares: $sampleAmount,
            sharesToKeep: $sampleRetention,
            tradeOrder: $tradeOrder,
            sca: true,
        );

        $expectedTradeOrderBody = [
            "assetId" => $sampleId,
            "direction" => TradeDirection::Buy->value,
            "numberOfShares" => $sampleAmount,
            "pricePerShare" => '1.28',
            "type" => TradeOrderType::Prefunding->value,
            "status" => TradeOrderStatus::Submitted->value,
            "counterpartyOrderId" => '5172',
            "reserveShares" => true,
            "fees" => "0",
            "taxes" => "0",
        ];

        $expectedSellOrderBody = [
            "assetId" => $sampleId,
            "direction" => TradeDirection::Sell->value,
            "numberOfShares" => $sampleAmount - $sampleRetention,
            "pricePerShare" => '1.28',
            "type" => TradeOrderType::Prefunding->value,
            "status" => TradeOrderStatus::Submitted->value,
            "complementaryOrderId" => '24',
            "fees" => "0",
            "taxes" => "0",
        ];

        $expectedScaActionBody = [
            "amount" => round($sampleAmount * $sharePriceToUse, 2),
            "sca" => true,
        ];

        $expectedScaOutcomeResponse = [
            "success" => true,
            "verify" => true,
        ];

        $this->assertCount(4, $this->history);
        foreach ($this->history as $key => $record) {
            $body = json_decode($record['request']->getBody()->getContents(), true);
            if (0 === $key) {
                $this->assertEquals('POST', $record['request']->getMethod());
                $this->assertEquals('/v1/yielders/trade-orders', $record['request']->getRequestTarget());
                $this->assertEquals($expectedTradeOrderBody, $body);
            }
            if (1 === $key) {
                $this->assertEquals('POST', $record['request']->getMethod());
                $this->assertEquals('/v1/yielders/trade-orders', $record['request']->getRequestTarget());
                $this->assertEquals($expectedSellOrderBody, $body);
            }
            if (2 === $key) {
                $this->assertEquals('POST', $record['request']->getMethod());
                $this->assertEquals('/v1/yielders/trade-orders/24/payments', $record['request']->getRequestTarget());
                $this->assertEquals($expectedScaActionBody, $body);
            }
            if (3 === $key) {
                $this->assertEquals('POST', $record['request']->getMethod());
                $this->assertEquals('/v1/yielders/trade-orders/24/payment-outcome', $record['request']->getRequestTarget());
                $this->assertEquals($expectedScaOutcomeResponse, $body);
            }
        }
    }

    public function testPrefundInvestApiErrorOnBuy(): void
    {
        $sampleId = (string)mt_rand(0, 16);
        $sampleAmount = mt_rand(1001, 1500);

        $asset = new AssetProduct(
            id: $sampleId,
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 18500,
            status: AssetStatus::Acquiring,
        );
        $tradeOrder = new TradeOrder(
            id: '5172',
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 18500,
            direction: TradeDirection::Sell,
            type: TradeOrderType::Initial,
        );

        /**
         * Should be 1 requests sent
         * - POST/CREATE trade order - createBuyOrder
         */
        $tradeOrderResponse = json_encode(['details' => '']);

        $this->expectException(InvestmentNotAllowedException::class);
        $this->expectExceptionMessage('Investment could not be made');

        $this->mockHandler->append(new Response(400, [], $tradeOrderResponse));
        $this->requestStack->getSession()->set('balance', '20000');
        $this->service->prefundInvestAsset(
            $asset,
            $sampleAmount,
            0,
            $tradeOrder,
        );

        // Check the request has been sent
        $this->assertCount(1, $this->history);
        foreach ($this->history as $key => $record) {
            $body = json_decode($record['request']->getBody()->getContents(), true);
            if (0 === $key) {
                $this->assertEquals('POST', $record['request']->getMethod());
                $this->assertEquals('/v1/yielders/trade-orders', $record['request']->getRequestTarget());
            }
        }
    }

    public function testPrefundInvestApiErrorOnSell(): void
    {
        $sampleId = (string)mt_rand(0, 16);
        $sampleAmount = mt_rand(1001, 1500);

        $asset = new AssetProduct(
            id: $sampleId,
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 18500,
            status: AssetStatus::Acquiring,
        );
        $tradeOrder = new TradeOrder(
            id: '5172',
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 18500,
            direction: TradeDirection::Sell,
            type: TradeOrderType::Initial,
        );

        /**
         * Should be 2 requests sent
         * - POST/CREATE trade order - createBuyOrder
         * - POST/CREATE trade order - createSellOrder
         */
        $tradeOrderResponse = json_encode(new TradeOrder(id: '24'));
        $sellOrderResponse = json_encode(['details' => '']);

        $this->expectException(InvestmentNotAllowedException::class);
        $this->expectExceptionMessage('Sell order could not be made');

        $this->mockHandler->append(new Response(200, [], $tradeOrderResponse));
        $this->mockHandler->append(new Response(400, [], $sellOrderResponse));
        $this->requestStack->getSession()->set('balance', '20000');
        $this->service->prefundInvestAsset(
            $asset,
            $sampleAmount,
            0,
            $tradeOrder,
        );

        // Check the 2 requests have been sent
        $this->assertCount(2, $this->history);
        foreach ($this->history as $key => $record) {
            $body = json_decode($record['request']->getBody()->getContents(), true);
            if (0 === $key) {
                $this->assertEquals('POST', $record['request']->getMethod());
                $this->assertEquals('/v1/yielders/trade-orders', $record['request']->getRequestTarget());
            }
            if (1 === $key) {
                $this->assertEquals('POST', $record['request']->getMethod());
                $this->assertEquals('/v1/yielders/trade-orders', $record['request']->getRequestTarget());
            }
        }
    }

    public function testPrefundInvestInsufficientShares(): void
    {
        $sampleId = (string)mt_rand(0, 16);
        $sampleAmount = mt_rand(1001, 1500);

        $asset = new AssetProduct(
            id: $sampleId,
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 18500,
            status: AssetStatus::Acquiring,
        );
        $tradeOrder = new TradeOrder(
            id: '5172',
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 1000,
            direction: TradeDirection::Sell,
            type: TradeOrderType::Initial,
        );

        /**
         * Should be 1 requests sent
         * - POST/CREATE trade order - createBuyOrder
         */
        $tradeOrderResponse = json_encode(['details' => '']);

        $this->expectException(InvestmentNotAllowedException::class);
        $this->expectExceptionMessage('Not enough shares available');

        $this->mockHandler->append(new Response(400, [], $tradeOrderResponse));
        $this->requestStack->getSession()->set('balance', '20000');
        $this->service->prefundInvestAsset(
            $asset,
            $sampleAmount,
            0,
            $tradeOrder,
        );

        // No requests sent
        $this->assertCount(0, $this->history);
    }

    public function testPrefundInvestZero(): void
    {
        $asset = new AssetProduct(
            id: '1',
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 18500,
            status: AssetStatus::Acquiring,
        );
        $tradeOrder = new TradeOrder(
            id: '5172',
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 1000,
            direction: TradeDirection::Sell,
            type: TradeOrderType::Initial,
        );

        $this->expectException(InvestmentNotAllowedException::class);
        $this->expectExceptionMessage('Investments must be for at least 1 share');
        $this->service->prefundInvestAsset(
            $asset,
            0,
            0,
            $tradeOrder,
        );

        // No requests sent
        $this->assertCount(0, $this->history);
    }

    public function testPrefundInvestTooMuchRetention(): void
    {
        $asset = new AssetProduct(
            id: '2',
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 18500,
            status: AssetStatus::Acquiring,
        );
        $tradeOrder = new TradeOrder(
            id: '5172',
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 10000,
            direction: TradeDirection::Sell,
            type: TradeOrderType::Initial,
        );

        $this->expectException(InvestmentNotAllowedException::class);
        $this->expectExceptionMessage('Cannot keep/retain more than 25% of total prefunding amount');
        $this->service->prefundInvestAsset(
            $asset,
            4000,
            1001,
            $tradeOrder,
        );

        // No requests sent
        $this->assertCount(0, $this->history);
    }

    public function testPrefundInvestInsufficientBalancePreCheck(): void
    {
        $sampleId = (string)mt_rand(0, 16);
        $sampleAmount = mt_rand(1001, 1500);

        $asset = new AssetProduct(
            id: $sampleId,
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 18500,
            status: AssetStatus::Acquiring,
        );
        $tradeOrder = new TradeOrder(
            id: '5172',
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 1000,
            direction: TradeDirection::Sell,
            type: TradeOrderType::Initial,
        );

        $this->expectException(InvestmentNotAllowedException::class);
        $this->expectExceptionMessage('Insufficient wallet balance');

        $this->requestStack->getSession()->set('balance', '100');
        $this->requestStack->getSession()->set('walletScaRequired', false);

        $this->service->prefundInvestAsset(
            $asset,
            1000,
            0,
            $tradeOrder,
        );

        // No requests sent
        $this->assertCount(0, $this->history);
    }

    public function testPrefundInvestInsufficientBalanceOnTransfer(): void
    {
        $sampleId = (string)mt_rand(0, 16);
        $sampleAmount = mt_rand(1001, 1500);

        $asset = new AssetProduct(
            id: $sampleId,
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 18500,
            status: AssetStatus::Acquiring,
        );
        $tradeOrder = new TradeOrder(
            id: '5172',
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 18500,
            direction: TradeDirection::Sell,
            type: TradeOrderType::Initial,
        );

        /**
         * Should be 2 requests sent
         * - POST/CREATE trade order - createBuyOrder
         * - POST/CREATE trade order - createSellOrder
         */
        $tradeOrderResponse = json_encode(new TradeOrder(id: '24'));
        $sellOrderResponse = json_encode(new TradeOrder(id: '25'));
        $scaActionResponse = json_encode([
            'detail' => 'Insufficient funds in wallet to cover the payment',
        ]);
        $scaOutcomeResponse = json_encode(new ScaOutcome(
            id: '24',
            success: false,
        ));

        $this->expectException(InvestmentNotAllowedException::class);
        $this->expectExceptionMessage('Insufficient wallet balance to make payment');

        $this->mockHandler->append(new Response(200, [], $tradeOrderResponse));
        $this->mockHandler->append(new Response(200, [], $sellOrderResponse));
        $this->mockHandler->append(new Response(400, [], $scaActionResponse));
        $this->mockHandler->append(new Response(200, [], $scaOutcomeResponse));
        $this->requestStack->getSession()->set('balance', '20000');
        $this->service->prefundInvestAsset(
            $asset,
            $sampleAmount,
            0,
            $tradeOrder,
        );

        $expectedScaOutcomeResponse = [
            "success" => false,
            "verify" => false,
        ];

        // Check the 2 requests have been sent
        $this->assertCount(4, $this->history);
        foreach ($this->history as $key => $record) {
            $body = json_decode($record['request']->getBody()->getContents(), true);
            if (0 === $key) {
                $this->assertEquals('POST', $record['request']->getMethod());
                $this->assertEquals('/v1/yielders/trade-orders', $record['request']->getRequestTarget());
            }
            if (1 === $key) {
                $this->assertEquals('POST', $record['request']->getMethod());
                $this->assertEquals('/v1/yielders/trade-orders', $record['request']->getRequestTarget());
            }
            if (2 === $key) {
                $this->assertEquals('POST', $record['request']->getMethod());
                $this->assertEquals('/v1/yielders/trade-orders/24/payments', $record['request']->getRequestTarget());
            }
            if (3 === $key) {
                $this->assertEquals('POST', $record['request']->getMethod());
                $this->assertEquals('/v1/yielders/trade-orders/24/payment-outcome', $record['request']->getRequestTarget());
                $this->assertEquals($expectedScaOutcomeResponse, $body);
            }
        }
    }

    public function testPrefundInvestOpportunityNotAcquiringState(): void
    {
        $asset = new AssetProduct(
            id: '1',
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 18500,
            status: AssetStatus::Active,
        );
        // The original sell order being bought from
        $tradeOrder = new TradeOrder(
            id: '5172',
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 18500,
            direction: TradeDirection::Sell,
        );

        $this->expectException(InvestmentNotAllowedException::class);
        $this->expectExceptionMessage('not currently open for prefunding');
        $this->service->prefundInvestAsset(
            asset: $asset,
            numberOfShares: 1,
            sharesToKeep: 0,
            tradeOrder: $tradeOrder,
            sca: true,
        );

        // No requests sent
        $this->assertCount(0, $this->history);
    }

    public function testPrefundInvestOpportunityNotInitialOrder(): void
    {
        $asset = new AssetProduct(
            id: '1',
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 18500,
            status: AssetStatus::Acquiring,
        );
        // The original sell order being bought from
        $tradeOrder = new TradeOrder(
            id: '5172',
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 18500,
            direction: TradeDirection::Sell,
            type: TradeOrderType::Market,
        );

        $this->expectException(InvestmentNotAllowedException::class);
        $this->expectExceptionMessage('chosen asset listing does not support prefunding');
        $this->service->prefundInvestAsset(
            asset: $asset,
            numberOfShares: 1,
            sharesToKeep: 0,
            tradeOrder: $tradeOrder,
            sca: true,
        );

        // No requests sent
        $this->assertCount(0, $this->history);
    }

    public function testRetailInvestOpportunity(): void
    {
        $sampleId = (string)mt_rand(0, 16);
        // This guarantees a trade value between 1k-2k
        $sampleAmount = mt_rand(1001, 1500);
        $sharePriceToUse = 1.28;

        $asset = new AssetProduct(
            id: $sampleId,
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 18500,
            status: AssetStatus::Active,
        );
        $tradeOrder = new TradeOrder(
            id: '5172',
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 18500,
            direction: TradeDirection::Sell,
        );

        /**
         * Should be 3 requests sent
         * - GET self portfolio unsettled - part of pre-checks - mainly to figure out stamp duty due
         * - POST/CREATE trade order - createBuyOrder
         * - POST/CREATE trade order payment - takeOrderPayment
         */
        $portfolioUnsettled = json_encode([]);
        $tradeOrderResponse = json_encode(new TradeOrder(id: '24'));
        $scaActionResponse = json_encode(new ScaAction(
            id: '24',
            providerStatus: 'CREATED',
            pendingUserAction: ['redirectUrl' => 'https://sca.sandbox.mangopay.com/?token=sca_testexample'],
        ));

        $this->mockHandler->append(new Response(200, [], $portfolioUnsettled));
        $this->mockHandler->append(new Response(200, [], $tradeOrderResponse));
        $this->mockHandler->append(new Response(200, [], $scaActionResponse));
        $this->requestStack->getSession()->set('balance', '20000');
        $this->service->retailInvestAsset(
            $asset,
            $sampleAmount,
            $tradeOrder,
            true,
        );

        $expectedTradeOrderBody = [
            "assetId" => $sampleId,
            "direction" => TradeDirection::Buy->value,
            "numberOfShares" => $sampleAmount,
            "pricePerShare" => '1.28',
            "type" => TradeOrderType::Market->value,
            "status" => TradeOrderStatus::Submitted->value,
            "counterpartyOrderId" => '5172',
            "reserveShares" => true,
            "fees" => "0",
            "taxes" => "10",
        ];

        $expectedScaActionBody = [
            "amount" => round(10 + ($sampleAmount * $sharePriceToUse), 2),
            "sca" => true,
        ];

        $this->assertCount(3, $this->history);
        foreach ($this->history as $key => $record) {
            $body = json_decode($record['request']->getBody()->getContents(), true);
            if (0 === $key) {
                $this->assertEquals('GET', $record['request']->getMethod());
                $this->assertEquals('/v1/yielders/self/portfolio/unsettled?currentMonthOnly=1', $record['request']->getRequestTarget());
            }
            if (1 === $key) {
                $this->assertEquals('POST', $record['request']->getMethod());
                $this->assertEquals('/v1/yielders/trade-orders', $record['request']->getRequestTarget());
                $this->assertEquals($expectedTradeOrderBody, $body);
            }
            if (2 === $key) {
                $this->assertEquals('POST', $record['request']->getMethod());
                $this->assertEquals('/v1/yielders/trade-orders/24/payments', $record['request']->getRequestTarget());
                $this->assertEquals($expectedScaActionBody, $body);
            }
        }
    }

    /**
     * @group check
     */
    public function testRetailInvestOpportunityStackedStampDuty(): void
    {
        $sampleId = (string)mt_rand(0, 16);
        $sharePriceToUse = 1.28; // more than the share price we'll use (i.e. paying a premium)
        // We're using 1280 as previously invested in the asset
        // So invest just enough to tip us into next stamp duty bracket
        $sampleAmount = (int)ceil(720 / $sharePriceToUse);

        $asset = new AssetProduct(
            id: $sampleId,
            pricePerShare: '1.08',
            numberOfShares: 20000,
            sharesAvailable: 18500,
            status: AssetStatus::Active,
        );
        $tradeOrder = new TradeOrder(
            id: '5172',
            pricePerShare: (string)$sharePriceToUse,
            numberOfShares: 20000,
            sharesAvailable: 18500,
            direction: TradeDirection::Sell,
        );

        /**
         * Should be 3 requests sent
         * - GET self portfolio unsettled - part of pre-checks - mainly to figure out stamp duty due
         * - POST/CREATE trade order - createBuyOrder
         * - POST/CREATE trade order payment - takeOrderPayment
         */
        $portfolioUnsettled = json_encode([
            'data' => [
                [
                    "id" => '15615',
                    "assetId" => $asset->id,
                    "tradeValue" => '1280.00',
                    "status" => TradeStatus::Unsettled->value,
                    "createdAt" => new \DateTime()->format(\DateTime::ATOM),
                ],
            ],
        ]);
        $tradeOrderResponse = json_encode(new TradeOrder(id: '24'));
        $scaActionResponse = json_encode(new ScaAction(
            id: '24',
            providerStatus: 'CREATED',
            pendingUserAction: ['redirectUrl' => 'https://sca.sandbox.mangopay.com/?token=sca_testexample'],
        ));

        $this->mockHandler->append(new Response(200, [], $portfolioUnsettled));
        $this->mockHandler->append(new Response(200, [], $tradeOrderResponse));
        $this->mockHandler->append(new Response(200, [], $scaActionResponse));
        $this->requestStack->getSession()->set('balance', '20000');
        $this->service->retailInvestAsset(
            $asset,
            $sampleAmount,
            $tradeOrder,
            true,
        );

        $expectedTradeOrderBody = [
            "assetId" => $sampleId,
            "direction" => TradeDirection::Buy->value,
            "numberOfShares" => $sampleAmount,
            "pricePerShare" => '1.28',
            "type" => TradeOrderType::Market->value,
            "status" => TradeOrderStatus::Submitted->value,
            "counterpartyOrderId" => '5172',
            "reserveShares" => true,
            "fees" => "0",
            "taxes" => "5",
        ];

        $expectedScaActionBody = [
            "amount" => round(5 + ($sampleAmount * $sharePriceToUse), 2),
            "sca" => true,
        ];

        $this->assertCount(3, $this->history);
        foreach ($this->history as $key => $record) {
            $body = json_decode($record['request']->getBody()->getContents(), true);
            if (0 === $key) {
                $this->assertEquals('GET', $record['request']->getMethod());
                $this->assertEquals('/v1/yielders/self/portfolio/unsettled?currentMonthOnly=1', $record['request']->getRequestTarget());
            }
            if (1 === $key) {
                $this->assertEquals('POST', $record['request']->getMethod());
                $this->assertEquals('/v1/yielders/trade-orders', $record['request']->getRequestTarget());
                $this->assertEquals($expectedTradeOrderBody, $body);
            }
            if (2 === $key) {
                $this->assertEquals('POST', $record['request']->getMethod());
                $this->assertEquals('/v1/yielders/trade-orders/24/payments', $record['request']->getRequestTarget());
                $this->assertEquals($expectedScaActionBody, $body);
            }
        }
    }

    public function testRetailInvestOpportunityNoSca(): void
    {
        $sampleId = (string)mt_rand(0, 16);
        // This guarantees a trade value between 1k-2k
        $sampleAmount = mt_rand(1001, 1500);
        $sharePriceToUse = 1.28;

        $asset = new AssetProduct(
            id: $sampleId,
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 18500,
            status: AssetStatus::Active,
        );
        $tradeOrder = new TradeOrder(
            id: '5172',
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 18500,
            direction: TradeDirection::Sell,
        );

        /**
         * Should be 4 requests sent
         * - GET self portfolio unsettled (on APIv1) - part of pre-checks - mainly to figure out stamp duty due
         * - POST/CREATE trade order - createBuyOrder
         * - POST/CREATE trade order payment - takeOrderPayment
         * - POST/CREATE trade order payment outcome - processOrderPaymentOutcome
         */
        $portfolioUnsettled = json_encode([]);
        $tradeOrderResponse = json_encode(new TradeOrder(id: '24'));
        $scaActionResponse = json_encode(new ScaAction(
            id: '24',
            providerStatus: 'SUCCEEDED',
            pendingUserAction: [],
        ));
        $scaOutcomeResponse = json_encode(new ScaOutcome(
            id: '24',
            success: true,
        ));

        $this->mockHandler->append(new Response(200, [], $portfolioUnsettled));
        $this->mockHandler->append(new Response(200, [], $tradeOrderResponse));
        $this->mockHandler->append(new Response(200, [], $scaActionResponse));
        $this->mockHandler->append(new Response(200, [], $scaOutcomeResponse));
        $this->requestStack->getSession()->set('balance', '20000');
        $this->service->retailInvestAsset(
            $asset,
            $sampleAmount,
            $tradeOrder,
            true,
        );

        $expectedTradeOrderBody = [
            "assetId" => $sampleId,
            "direction" => TradeDirection::Buy->value,
            "numberOfShares" => $sampleAmount,
            "pricePerShare" => '1.28',
            "type" => TradeOrderType::Market->value,
            "status" => TradeOrderStatus::Submitted->value,
            "counterpartyOrderId" => '5172',
            "reserveShares" => true,
            "fees" => "0",
            "taxes" => "10",
        ];

        $expectedScaActionBody = [
            "amount" => round(10 + ($sampleAmount * $sharePriceToUse), 2),
            "sca" => true,
        ];

        $expectedScaActionBody = [
            "amount" => round(10 + ($sampleAmount * $sharePriceToUse), 2),
            "sca" => true,
        ];

        $expectedScaOutcomeResponse = [
            "success" => true,
            "verify" => true,
        ];

        $this->assertCount(4, $this->history);
        foreach ($this->history as $key => $record) {
            $body = json_decode($record['request']->getBody()->getContents(), true);
            if (0 === $key) {
                $this->assertEquals('GET', $record['request']->getMethod());
                $this->assertEquals('/v1/yielders/self/portfolio/unsettled?currentMonthOnly=1', $record['request']->getRequestTarget());
            }
            if (1 === $key) {
                $this->assertEquals('POST', $record['request']->getMethod());
                $this->assertEquals('/v1/yielders/trade-orders', $record['request']->getRequestTarget());
                $this->assertEquals($expectedTradeOrderBody, $body);
            }
            if (2 === $key) {
                $this->assertEquals('POST', $record['request']->getMethod());
                $this->assertEquals('/v1/yielders/trade-orders/24/payments', $record['request']->getRequestTarget());
                $this->assertEquals($expectedScaActionBody, $body);
            }
            if (3 === $key) {
                $this->assertEquals('POST', $record['request']->getMethod());
                $this->assertEquals('/v1/yielders/trade-orders/24/payment-outcome', $record['request']->getRequestTarget());
                $this->assertEquals($expectedScaOutcomeResponse, $body);
            }
        }
    }

    public function testRetailInvestApiError(): void
    {
        $sampleId = (string)mt_rand(0, 16);
        $sampleAmount = mt_rand(1001, 1500);

        $asset = new AssetProduct(
            id: $sampleId,
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 18500,
            status: AssetStatus::Active,
        );
        $tradeOrder = new TradeOrder(
            id: '5172',
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 18500,
            direction: TradeDirection::Sell,
        );

        /**
         * Should be 2 requests sent
         * - GET self portfolio unsettled - part of pre-checks - mainly to figure out stamp duty due
         * - POST/CREATE trade order - createBuyOrder
         */
        $portfolioUnsettled = json_encode([]);
        $tradeOrderResponse = json_encode(['details' => '']);

        $this->expectException(InvestmentNotAllowedException::class);
        $this->expectExceptionMessage('Investment could not be made');

        $this->mockHandler->append(new Response(200, [], $portfolioUnsettled));
        $this->mockHandler->append(new Response(400, [], $tradeOrderResponse));
        $this->requestStack->getSession()->set('balance', '20000');
        $this->service->retailInvestAsset(
            $asset,
            $sampleAmount,
            $tradeOrder,
        );

        $this->assertCount(2, $this->history);
        foreach ($this->history as $key => $record) {
            $body = json_decode($record['request']->getBody()->getContents(), true);
            if (0 === $key) {
                $this->assertEquals('GET', $record['request']->getMethod());
                $this->assertEquals('/v1/yielders/self/portfolio/unsettled?currentMonthOnly=1', $record['request']->getRequestTarget());
            }
            if (1 === $key) {
                $this->assertEquals('POST', $record['request']->getMethod());
                $this->assertEquals('/v1/yielders/trade-orders', $record['request']->getRequestTarget());
            }
        }
    }

    public function testRetailInvestInsufficientShares(): void
    {
        $sampleId = (string)mt_rand(0, 16);
        $sampleAmount = mt_rand(1001, 1500);

        $asset = new AssetProduct(
            id: $sampleId,
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 400,
            status: AssetStatus::Active,
        );
        $tradeOrder = new TradeOrder(
            id: '5172',
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 400,
            direction: TradeDirection::Sell,
        );

        $this->expectException(InvestmentNotAllowedException::class);
        $this->expectExceptionMessage('Not enough shares available');
        $this->service->retailInvestAsset(
            $asset,
            $sampleAmount,
            $tradeOrder,
        );
    }

    public function testRetailInvestOwnListing(): void
    {
        $sampleId = (string)mt_rand(0, 16);
        $sampleAmount = mt_rand(1001, 1500);

        $asset = new AssetProduct(
            id: $sampleId,
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 400,
            status: AssetStatus::Active,
        );
        $tradeOrder = new TradeOrder(
            id: '5172',
            userId: $this->userId,
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 400,
            direction: TradeDirection::Sell,
        );

        $this->expectException(InvestmentNotAllowedException::class);
        $this->expectExceptionMessage('Cannot invest through your own listing');
        $this->service->retailInvestAsset(
            $asset,
            $sampleAmount,
            $tradeOrder,
        );
    }

    /**
     * @dataProvider minMaxProvider
     */
    public function testRetailInvestOutsideMinMax(string $message, int $investmentShares, int $minShares, ?int $maxShares): void
    {
        $sampleId = (string)mt_rand(0, 16);

        $asset = new AssetProduct(
            id: $sampleId,
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 14000,
            status: AssetStatus::Active,
        );
        $tradeOrder = new TradeOrder(
            id: '5172',
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 14000,
            direction: TradeDirection::Sell,
            minimumShares: $minShares,
            maximumShares: $maxShares,
        );

        $this->expectException(InvestmentNotAllowedException::class);
        $this->expectExceptionMessage($message);
        $this->service->retailInvestAsset(
            $asset,
            $investmentShares,
            $tradeOrder,
        );
    }

    public static function minMaxProvider(): \Generator
    {
        yield 'Too few' => [
            "investment value must be between £1,280.00 (1000 shares) and £17,920.00 (14000 shares)",
            5,
            1000,
            null,
        ];
        yield 'Too many implicit cap' => [
            "Not enough shares available",
            50000,
            100,
            null,
        ];
        yield 'Too many explicit cap' => [
            "investment value must be between £1.28 (1 shares) and £1,280.00 (1000 shares)",
            1001,
            1,
            1000,
        ];
    }

    public function testRetailInvestZero(): void
    {
        $asset = new AssetProduct(
            id: '14',
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 14000,
            status: AssetStatus::Active,
        );
        $this->expectException(InvestmentNotAllowedException::class);
        $this->expectExceptionMessage('Investments must be for at least 1 share');
        $this->service->retailInvestAsset(
            $asset,
            0,
        );
    }

    public function testRetailInvestInsufficientBalancePreCheck(): void
    {
        $sampleId = (string)mt_rand(0, 16);
        // This guarantees a trade value between 1k-2k
        $sampleAmount = mt_rand(1001, 1500);
        $sharePriceToUse = 1.28;

        $asset = new AssetProduct(
            id: $sampleId,
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 18500,
            status: AssetStatus::Active,
        );
        $tradeOrder = new TradeOrder(
            id: '5172',
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 18500,
            direction: TradeDirection::Sell,
        );

        /**
         * Should be 1 requests sent
         * - GET self portfolio unsettled - part of pre-checks - mainly to figure out stamp duty due
         */
        $portfolioUnsettled = json_encode([]);

        $this->expectException(InvestmentNotAllowedException::class);
        $this->expectExceptionMessage('Insufficient wallet balance');

        $this->mockHandler->append(new Response(200, [], $portfolioUnsettled));
        $this->requestStack->getSession()->set('balance', '100');
        $this->requestStack->getSession()->set('walletScaRequired', false);
        $this->service->retailInvestAsset(
            $asset,
            $sampleAmount,
            $tradeOrder,
        );

        $this->assertCount(3, $this->history);
        foreach ($this->history as $key => $record) {
            $body = json_decode($record['request']->getBody()->getContents(), true);
            if (0 === $key) {
                $this->assertEquals('GET', $record['request']->getMethod());
                $this->assertEquals('/v1/yielders/self/portfolio/unsettled?currentMonthOnly=1', $record['request']->getRequestTarget());
            }
        }
    }

    public function testRetailInvestInsufficientBalanceOnTransfer(): void
    {
        $sampleId = (string)mt_rand(0, 16);
        // This guarantees a trade value between 1k-2k
        $sampleAmount = mt_rand(1001, 1500);
        $sharePriceToUse = 1.28;

        $asset = new AssetProduct(
            id: $sampleId,
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 18500,
            status: AssetStatus::Active,
        );
        $tradeOrder = new TradeOrder(
            id: '5172',
            pricePerShare: '1.28',
            numberOfShares: 20000,
            sharesAvailable: 18500,
            direction: TradeDirection::Sell,
        );

        /**
         * Should be 4 requests sent
         * - GET self portfolio unsettled (on APIv1) - part of pre-checks - mainly to figure out stamp duty due
         * - POST/CREATE trade order - createBuyOrder
         * - POST/CREATE trade order payment - takeOrderPayment
         * - POST/CREATE trade order payment outcome - processOrderPaymentOutcome
         */
        $portfolioUnsettled = json_encode([]);
        $tradeOrderResponse = json_encode(new TradeOrder(id: '24'));
        $scaActionResponse = json_encode([
            'detail' => 'Insufficient funds in wallet to cover the payment',
        ]);
        $scaOutcomeResponse = json_encode(new ScaOutcome(
            id: '24',
            success: false,
        ));

        $this->expectException(InvestmentNotAllowedException::class);
        $this->expectExceptionMessage('Insufficient wallet balance to make payment');

        $this->mockHandler->append(new Response(200, [], $portfolioUnsettled));
        $this->mockHandler->append(new Response(200, [], $tradeOrderResponse));
        $this->mockHandler->append(new Response(400, [], $scaActionResponse));
        $this->mockHandler->append(new Response(200, [], $scaOutcomeResponse));
        $this->requestStack->getSession()->set('balance', null);
        $this->service->retailInvestAsset(
            $asset,
            $sampleAmount,
            $tradeOrder,
            true,
        );

        $expectedTradeOrderBody = [
            "assetId" => $sampleId,
            "direction" => TradeDirection::Buy->value,
            "numberOfShares" => $sampleAmount,
            "pricePerShare" => '1.28',
            "type" => TradeOrderType::Market->value,
            "status" => TradeOrderStatus::Submitted->value,
            "counterpartyOrderId" => '5172',
            "reserveShares" => true,
            "fees" => "0",
            "taxes" => "10",
        ];

        $expectedScaActionBody = [
            "amount" => round(10 + ($sampleAmount * $sharePriceToUse), 2),
            "sca" => true,
        ];

        $expectedScaOutcomeResponse = [
            "success" => false,
            "verify" => false,
        ];

        $this->assertCount(4, $this->history);
        foreach ($this->history as $key => $record) {
            $body = json_decode($record['request']->getBody()->getContents(), true);
            if (0 === $key) {
                $this->assertEquals('GET', $record['request']->getMethod());
                $this->assertEquals('/v1/yielders/self/portfolio/unsettled?currentMonthOnly=1', $record['request']->getRequestTarget());
            }
            if (1 === $key) {
                $this->assertEquals('POST', $record['request']->getMethod());
                $this->assertEquals('/v1/yielders/trade-orders', $record['request']->getRequestTarget());
                $this->assertEquals($expectedTradeOrderBody, $body);
            }
            if (2 === $key) {
                $this->assertEquals('POST', $record['request']->getMethod());
                $this->assertEquals('/v1/yielders/trade-orders/24/payments', $record['request']->getRequestTarget());
                $this->assertEquals($expectedScaActionBody, $body);
            }
            if (3 === $key) {
                $this->assertEquals('POST', $record['request']->getMethod());
                $this->assertEquals('/v1/yielders/trade-orders/24/payment-outcome', $record['request']->getRequestTarget());
                $this->assertEquals($expectedScaOutcomeResponse, $body);
            }
        }
    }

    public function testCheckBalanceAvailable(): void
    {
        $this->requestStack->getSession()->set('balance', '1 248.16');
        $this->requestStack->getSession()->set('walletScaRequired', false);
        $this->assertFalse($this->service->checkBalanceAvailable(32000.0));
        $this->assertTrue($this->service->checkBalanceAvailable(1248.16));
        $this->assertTrue($this->service->checkBalanceAvailable(512.32));
    }

    /**
     * @dataProvider retentionCustomsProvider
     */
    public function testCheckRetentionAllowed(bool $expected, int $total, int $keep, int $min, int $max): void
    {
        $this->assertSame($expected, $this->service->checkRetentionAllowed($total, $keep, $min, $max));
    }

    /**
     * @dataProvider eligibleInvestorProvider
     */
    public function testUserCanInvest(?string $exceptionType, bool $isAuthenticated, array $userInfo): void
    {
        if ($isAuthenticated) {
            $this->requestStack->getSession()->set('authenticated', true);
        }
        $this->requestStack->getSession()->set('userInfo', $userInfo);
        if ($exceptionType !== null) {
            $this->expectException($exceptionType);
            $this->service->checkUserCanInvest();
        } else {
            $this->assertTrue($this->service->checkUserCanInvest());
        }
    }

    public function retentionCustomsProvider(): \Generator
    {
        $min = 11;
        $max = 67;
        $total = 864;
        $lowerKeep = 96;
        $upperKeep = 578;

        yield 'Exact max' => [true, $total, $upperKeep, $min, $max];
        yield 'Below max' => [true, $total, $upperKeep - 1, $min, $max];
        yield 'Above max' => [false, $total, $upperKeep + 1, $min, $max];
        yield 'Exact min' => [true, $total, $lowerKeep, $min, $max];
        yield 'Below min' => [false, $total, $lowerKeep - 1, $min, $max];
        yield 'Above min' => [true, $total, $lowerKeep + 1, $min, $max];
        yield 'Keep > total' => [false, $total, $total + 1, $min, $max];
        yield 'No keep' => [true, $total, 0, $min, $max];
    }

    public function testUnsettledTradesThisMonthPerAsset(): void
    {
        $shareTrades = [
            // old and settled
            new ShareTrade(assetId: '5', status: TradeStatus::Settled, tradeValue: '1789.22', createdAt: new \DateTime('-3 month')),
            // new and settled
            new ShareTrade(assetId: '41', status: TradeStatus::Settled, tradeValue: '989.22', createdAt: new \DateTime()),
            // old but approved (not yet settled)
            new ShareTrade(assetId: '6', status: TradeStatus::Unsettled, tradeValue: '289.22', createdAt: new \DateTime('first friday of -1 month')),
            // 3 new in same asset
            new ShareTrade(assetId: '5', status: TradeStatus::Unsettled, tradeValue: '823.21', createdAt: new \DateTime()),
            new ShareTrade(assetId: '5', status: TradeStatus::Unsettled, tradeValue: '886.55', createdAt: new \DateTime()),
            new ShareTrade(assetId: '5', status: TradeStatus::Unsettled, tradeValue: '788.56', createdAt: new \DateTime()),
            // 1 in another asset
            new ShareTrade(assetId: '6', status: TradeStatus::Unsettled, tradeValue: '2215.65', createdAt: new \DateTime()),
        ];
        $expected = [
            '6' => '2215.65',
            '5' => '2498.32',
        ];
        $actual = $this->service->unsettledTradesThisMonthPerAsset($shareTrades);
        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    /**
     * @dataProvider stampDutySimpleProvider
     */
    public function testCalculateStampDuty(string|float $amount, int $expected): void
    {
        $actual = $this->service->calculateStampDuty($amount);
        $this->assertSame($expected, $actual);
    }

    public function stampDutySimpleProvider(): \Generator
    {
        yield 'Sub 1k' => [999.99, 0];
        yield 'Exactly 1k' => ['1000', 5];
        yield 'Over 1k' => [1000.01, 10];
        yield 'Multi-k' => ['13003.54', 70];
    }

    /**
     * @dataProvider stampDutyScenarioProvider
     */
    public function testCalculateStampDutyDue(string|float $existing, string|float $new, int $expected): void
    {
        $actual = $this->service->calculateStampDutyDue($existing, $new);
        $this->assertSame($expected, $actual);
    }

    public function stampDutyScenarioProvider(): \Generator
    {
        // scenarios
        yield 'Fresh sub 1k' => [0, 999.99, 0];
        yield 'Exactly 1k with new sub 1k' => ['888.54', 111.46, 5];
        yield 'Total over 1k with new sub 1k' => ['888.54', '111.47', 10];
        yield 'Total over 2k with new sub 1k' => [1250, '750.01', 5];
        yield 'Multi-k stack' => [1200, 4900, 25];
    }

    public function eligibleInvestorProvider(): \Generator
    {
        $notOnboarded = [
            'ob_step' => 4,
            'has_been_approved' => false,
            'registration_complete' => false,
        ];
        $notApproved = [
            'ob_step' => 5,
            'has_been_approved' => false,
            'registration_complete' => true,
            'sca_status' => ScaStatus::Active->value,
            'onboarding_profile' => [
                'category' => UserCategory::Restricted->value,
                'categoryReviewedAt' => (new \DateTime())->format(\DateTime::ATOM),
                'assessmentPassed' => true,
                'cooloffAccepted' => true,
                'riskWarningAccepted' => true,
                'cooloffEnd' => (new \DateTime("-2 days"))->format(\DateTime::ATOM),
            ],
            'open_kyc_reviews' => [],
        ];
        $inactiveSca = [
            'ob_step' => 5,
            'has_been_approved' => true,
            'registration_complete' => true,
            'sca_status' => ScaStatus::Inactive->value,
            'onboarding_profile' => [
                'category' => UserCategory::Restricted->value,
                'categoryReviewedAt' => (new \DateTime())->format(\DateTime::ATOM),
                'assessmentPassed' => true,
                'cooloffAccepted' => true,
                'riskWarningAccepted' => true,
                'cooloffEnd' => (new \DateTime("-2 days"))->format(\DateTime::ATOM),
            ],
            'open_kyc_reviews' => [],
        ];
        $incompleteOnboardingProfile = [
            'ob_step' => 5,
            'has_been_approved' => true,
            'registration_complete' => true,
            'sca_status' => ScaStatus::Active->value,
            'onboarding_profile' => [],
            'open_kyc_reviews' => [],
        ];
        $hasOpenKycReview = [
            'ob_step' => 5,
            'has_been_approved' => true,
            'registration_complete' => true,
            'sca_status' => ScaStatus::Active->value,
            'onboarding_profile' => [
                'category' => UserCategory::Restricted->value,
                'categoryReviewedAt' => (new \DateTime())->format(\DateTime::ATOM),
                'assessmentPassed' => true,
                'cooloffAccepted' => true,
                'riskWarningAccepted' => true,
                'cooloffEnd' => (new \DateTime("-2 days"))->format(\DateTime::ATOM),
            ],
            'open_kyc_reviews' => [
                [
                    'id' => 2,
                    'status' => KycReviewStatus::PendingSubjectAction->value,
                    'identityReview' => true,
                    'reviewType' => KycReviewType::Recurring->value,
                ],
            ],
        ];
        $eligibleUser = [
            'ob_step' => 5,
            'has_been_approved' => true,
            'registration_complete' => true,
            'sca_status' => ScaStatus::Active->value,
            'onboarding_profile' => [
                'category' => UserCategory::Restricted->value,
                'categoryReviewedAt' => (new \DateTime())->format(\DateTime::ATOM),
                'assessmentPassed' => true,
                'cooloffAccepted' => true,
                'riskWarningAccepted' => true,
                'cooloffEnd' => (new \DateTime("-2 days"))->format(\DateTime::ATOM),
            ],
            'open_kyc_reviews' => [],
        ];
        $eligibleUserWithoutObStep = [
            'ob_step' => 0,
            'has_been_approved' => true,
            'registration_complete' => true,
            'sca_status' => ScaStatus::Active->value,
            'onboarding_profile' => [
                'category' => UserCategory::Restricted->value,
                'categoryReviewedAt' => (new \DateTime())->format(\DateTime::ATOM),
                'assessmentPassed' => true,
                'cooloffAccepted' => true,
                'riskWarningAccepted' => true,
                'cooloffEnd' => (new \DateTime("-2 days"))->format(\DateTime::ATOM),
            ],
            'open_kyc_reviews' => [],
        ];
        $cooloffNotEnded = [
            'ob_step' => 5,
            'has_been_approved' => true,
            'registration_complete' => true,
            'sca_status' => ScaStatus::Active->value,
            'onboarding_profile' => [
                'category' => UserCategory::Restricted->value,
                'categoryReviewedAt' => (new \DateTime())->format(\DateTime::ATOM),
                'assessmentPassed' => true,
                'cooloffAccepted' => true,
                'riskWarningAccepted' => true,
                'cooloffEnd' => (new \DateTime("+1 days"))->format(\DateTime::ATOM),
            ],
            'open_kyc_reviews' => [],
        ];
        $noObStepCannotBypassObp = [
            'ob_step' => 0,
            'has_been_approved' => true,
            'registration_complete' => true,
            'sca_status' => ScaStatus::Active->value,
            'onboarding_profile' => [
                'category' => UserCategory::Restricted->value,
                'categoryReviewedAt' => (new \DateTime())->format(\DateTime::ATOM),
                'assessmentPassed' => true,
                'cooloffAccepted' => false,
                'riskWarningAccepted' => true,
                'cooloffEnd' => (new \DateTime("-2 days"))->format(\DateTime::ATOM),
            ],
            'open_kyc_reviews' => [],
        ];
        yield 'Not logged in' => [\Exception::class, false, []];
        yield 'Missing user info' => [\Exception::class, true, []];
        yield 'Incomplete onboarding' => [InvestmentNotAllowedException::class, true, $notOnboarded];
        yield 'Not approved' => [InvestmentNotAllowedException::class, true, $notApproved];
        yield 'Inactive SCA' => [InvestmentNotAllowedException::class, true, $inactiveSca];
        yield 'PS22/10 incomplete' => [InvestmentNotAllowedException::class, true, $incompleteOnboardingProfile];
        yield 'Has open kyc review' => [InvestmentNotAllowedException::class, true, $hasOpenKycReview];
        yield 'Cooloff not ended' => [InvestmentNotAllowedException::class, true, $cooloffNotEnded];
        yield 'Prevent legacy users bypassing PS22/10' => [InvestmentNotAllowedException::class, true, $noObStepCannotBypassObp];
        yield 'Can invest' => [null, true, $eligibleUser];
        yield 'Can invest without ob_step' => [null, true, $eligibleUserWithoutObStep];
    }
}
