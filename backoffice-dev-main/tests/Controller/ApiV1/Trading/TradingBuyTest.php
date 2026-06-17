<?php

namespace App\Tests\Controller\ApiV1\SelfPortfolio;

use App\Entity\Asset;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TradeStatus;
use App\Entity\TradeOrder;
use App\Entity\User;
use App\Test\FixtureTestCase;
use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV1\ApiV1ResponseFields;
use BcMath\Number;
use Symfony\Component\HttpFoundation\Response;

class TradingBuyTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testBuyWithReserve(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR);

        // Create the initial buy order with reservation
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => FixtureTestCase::USER_REGULAR]);
        $asset = $this->entityManager
            ->getRepository(Asset::class)
            ->findOneBy(['name' => 'Royal Way Gardens - Cambridge']);
        $sellOrder = $this->entityManager
            ->getRepository(TradeOrder::class)
            ->findOneBy([
                'asset' => $asset->getId(),
                'direction' => TradeDirection::Sell,
                'type' => TradeOrderType::Initial,
            ]);
        $testId = bin2hex(random_bytes(8));
        $headers = ['CONTENT_TYPE' => 'application/json'];

        $content = json_encode([
            'assetId' => (string) $asset->getId(),
            'direction' => TradeDirection::Buy,
            'numberOfShares' => 8,
            'pricePerShare' => $asset->getPricePerShare(),
            'type' => TradeOrderType::Market,
            'status' => TradeOrderStatus::Submitted,
            'counterpartyOrderId' => (string) $sellOrder->getId(),
            'reserveShares' => true,
            'notes' => $testId,
            'fees' => '0',
            'taxes' => '5',
        ]);
        $uri = self::API_PATH_PREFIX_V1 . '/trade-orders';
        $this->client->request('POST', $uri, server: $headers, content: $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::TRADE_ORDER,
            array_keys($apiResponse),
        );

        // Check that the trade order has been created
        /**
         * @var TradeOrder $buyOrder
         */
        $buyOrder = $this->entityManager
            ->getRepository(TradeOrder::class)
            ->findOneBy([
                'asset' => $asset->getId(),
                'user' => $user->getId(),
                'direction' => TradeDirection::Buy,
                'type' => TradeOrderType::Market,
                'notes' => $testId,
            ]);
        $this->assertNotNull($buyOrder);
        $this->assertEquals($apiResponse['id'], $buyOrder->getId());
        $this->assertEquals(TradeOrderStatus::Submitted, $buyOrder->getStatus());

        // And with a share trade
        $this->assertNotEmpty($buyOrder->getShareTrades());
        $shareTrade = $buyOrder->getShareTrades()->first();
        $this->assertEquals(
            new Number((string) (8 * $asset->getPricePerShare())),
            $shareTrade->getTradeValue(),
        );
        $this->assertTrue($shareTrade->isDerived());
        $this->assertEquals(
            new Number($asset->getPricePerShare()),
            $shareTrade->getPricePerShare(),
        );
        $this->assertEquals(8, $shareTrade->getNumberOfShares());
        $this->assertEquals($buyOrder->getId(), $shareTrade->getBuyOrder()->getId());
        $this->assertEquals($sellOrder->getId(), $shareTrade->getSellOrder()->getId());
        $this->assertEquals(TradeStatus::Reserved, $shareTrade->getStatus());

        // Take a payment
        $content = json_encode([
            'amount' => '0.01', // must be at least 1p
            'sca' => true,
        ]);
        $uri = self::API_PATH_PREFIX_V1 . "/trade-orders/{$buyOrder->getId()}/payments";
        $this->client->request('POST', $uri, server: $headers, content: $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::SCA_ACTION,
            array_keys($apiResponse),
        );
        // Check a transaction has been attached to the buyOrder - need to refresh
        $this->entityManager->refresh($buyOrder);
        $this->assertNotEmpty($buyOrder->getTransactionReference());
        $this->assertNotEmpty($buyOrder->getTransaction());

        // Submit outcome
        $content = json_encode([
            'success' => true,
            'verify' => true, // this is true by default, but explicitly set here to clarity
        ]);
        $uri =
            self::API_PATH_PREFIX_V1
            . "/trade-orders/{$buyOrder->getId()}/payment-outcome";
        $this->client->request('POST', $uri, server: $headers, content: $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::SCA_OUTCOME,
            array_keys($apiResponse),
        );
        // Check status updates - again, refresh the entities so you have the changes
        $this->entityManager->refresh($buyOrder);
        $this->entityManager->refresh($shareTrade);
        $this->assertEquals(TradeOrderStatus::Completed, $buyOrder->getStatus());
        $this->assertEquals(TradeStatus::Unsettled, $shareTrade->getStatus());
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    #[\PHPUnit\Framework\Attributes\Group('check')]
    public function testPrefundingBuyWithReserve(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_VIP);

        // Create the initial buy order with reservation
        // Neptunis Quays is one of our prefunding test assets
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => FixtureTestCase::USER_VIP]);
        $asset = $this->entityManager
            ->getRepository(Asset::class)
            ->findOneBy(['name' => 'Neptunis Quays - Bristol']);
        $sellOrder = $this->entityManager
            ->getRepository(TradeOrder::class)
            ->findOneBy([
                'asset' => $asset->getId(),
                'direction' => TradeDirection::Sell,
                'type' => TradeOrderType::Initial,
            ]);
        $testId = bin2hex(random_bytes(8));
        $headers = ['CONTENT_TYPE' => 'application/json'];

        $content = json_encode([
            'assetId' => (string) $asset->getId(),
            'direction' => TradeDirection::Buy,
            'numberOfShares' => 8,
            'pricePerShare' => $asset->getPricePerShare(),
            'type' => TradeOrderType::Prefunding,
            'status' => TradeOrderStatus::Submitted,
            'counterpartyOrderId' => (string) $sellOrder->getId(),
            'reserveShares' => true,
            'notes' => $testId,
            'fees' => '0',
            'taxes' => '5',
        ]);
        $uri = self::API_PATH_PREFIX_V1 . '/trade-orders';
        $this->client->request('POST', $uri, server: $headers, content: $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::TRADE_ORDER,
            array_keys($apiResponse),
        );

        // Check that the trade order has been created
        /**
         * @var TradeOrder $buyOrder
         */
        $buyOrder = $this->entityManager
            ->getRepository(TradeOrder::class)
            ->findOneBy([
                'asset' => $asset->getId(),
                'user' => $user->getId(),
                'direction' => TradeDirection::Buy,
                'type' => TradeOrderType::Prefunding,
                'notes' => $testId,
            ]);
        $this->assertNotNull($buyOrder);
        $this->assertEquals($apiResponse['id'], $buyOrder->getId());
        $this->assertEquals(TradeOrderStatus::Submitted, $buyOrder->getStatus());

        // And with a share trade
        $this->assertNotEmpty($buyOrder->getShareTrades());
        $shareTrade = $buyOrder->getShareTrades()->first();
        $this->assertEquals(
            new Number((string) (8 * $asset->getPricePerShare())),
            $shareTrade->getTradeValue(),
        );
        $this->assertTrue($shareTrade->isDerived());
        $this->assertEquals(
            new Number($asset->getPricePerShare()),
            $shareTrade->getPricePerShare(),
        );
        $this->assertEquals(8, $shareTrade->getNumberOfShares());
        $this->assertEquals($buyOrder->getId(), $shareTrade->getBuyOrder()->getId());
        $this->assertEquals($sellOrder->getId(), $shareTrade->getSellOrder()->getId());
        $this->assertEquals(TradeStatus::Reserved, $shareTrade->getStatus());

        // Create the complementary liquidation sell order
        $content = json_encode([
            'assetId' => (string) $asset->getId(),
            'direction' => TradeDirection::Sell,
            'numberOfShares' => 6, // slightly less than the buyOrder to represent partial retention
            'pricePerShare' => $asset->getPricePerShare(),
            'type' => TradeOrderType::Prefunding,
            'status' => TradeOrderStatus::Submitted,
            'complementaryOrderId' => (string) $buyOrder->getId(),
        ]);
        $uri = self::API_PATH_PREFIX_V1 . '/trade-orders';
        $this->client->request('POST', $uri, server: $headers, content: $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::TRADE_ORDER,
            array_keys($apiResponse),
        );
        // Check a transaction has been attached to the buyOrder - need to refresh
        $this->entityManager->refresh($buyOrder);
        $this->assertEmpty($buyOrder->getTransactionReference());
        $this->assertEmpty($buyOrder->getTransaction());
        $complementaryOrder = $buyOrder->getComplementaryOrder();
        $this->assertNotEmpty($complementaryOrder);
        $this->assertEquals($apiResponse['id'], $complementaryOrder->getId());
        $this->assertEquals(6, $complementaryOrder->getNumberOfShares());
        $this->assertEquals(TradeDirection::Sell, $complementaryOrder->getDirection());
        $this->assertEquals(TradeOrderType::Prefunding, $complementaryOrder->getType());
        $this->assertEquals(
            TradeOrderStatus::Submitted,
            $complementaryOrder->getStatus(),
        );
        // Relation set both ways
        $this->assertEquals(
            $buyOrder->getId(),
            $complementaryOrder->getComplementaryOrder()->getId(),
        );

        // Submit outcome - we can skip verification to avoid the need to create a Mangopay transfer
        $content = json_encode([
            'success' => true,
            'verify' => false,
        ]);
        $uri =
            self::API_PATH_PREFIX_V1
            . "/trade-orders/{$buyOrder->getId()}/payment-outcome";
        $this->client->request('POST', $uri, server: $headers, content: $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::SCA_OUTCOME,
            array_keys($apiResponse),
        );
        // Check status updates - again, refresh the entities so you have the changes
        $this->entityManager->refresh($buyOrder);
        $this->entityManager->refresh($complementaryOrder);
        $this->entityManager->refresh($shareTrade);
        $this->assertEquals(TradeOrderStatus::Active, $complementaryOrder->getStatus());
        $this->assertEquals(TradeOrderStatus::Completed, $buyOrder->getStatus());
        $this->assertEquals(TradeStatus::Unsettled, $shareTrade->getStatus());
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    #[\PHPUnit\Framework\Attributes\Group('check')]
    public function testPrefundingBuyWithReserveNewHolding(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_VIP);

        // Create the initial buy order with reservation
        // Novaplatz is one of our prefunding test assets that VIP user has no invested in
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => FixtureTestCase::USER_VIP]);
        $asset = $this->entityManager
            ->getRepository(Asset::class)
            ->findOneBy(['name' => 'Novaplatz - London']);
        $sellOrder = $this->entityManager
            ->getRepository(TradeOrder::class)
            ->findOneBy([
                'asset' => $asset->getId(),
                'direction' => TradeDirection::Sell,
                'type' => TradeOrderType::Initial,
            ]);
        $testId = bin2hex(random_bytes(8));
        $headers = ['CONTENT_TYPE' => 'application/json'];

        $content = json_encode([
            'assetId' => (string) $asset->getId(),
            'direction' => TradeDirection::Buy,
            'numberOfShares' => 8,
            'pricePerShare' => $asset->getPricePerShare(),
            'type' => TradeOrderType::Prefunding,
            'status' => TradeOrderStatus::Submitted,
            'counterpartyOrderId' => (string) $sellOrder->getId(),
            'reserveShares' => true,
            'notes' => $testId,
            'fees' => '0',
            'taxes' => '5',
        ]);
        $uri = self::API_PATH_PREFIX_V1 . '/trade-orders';
        $this->client->request('POST', $uri, server: $headers, content: $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::TRADE_ORDER,
            array_keys($apiResponse),
        );

        // Check that the trade order has been created
        /**
         * @var TradeOrder $buyOrder
         */
        $buyOrder = $this->entityManager
            ->getRepository(TradeOrder::class)
            ->findOneBy([
                'asset' => $asset->getId(),
                'user' => $user->getId(),
                'direction' => TradeDirection::Buy,
                'type' => TradeOrderType::Prefunding,
                'notes' => $testId,
            ]);
        $this->assertNotNull($buyOrder);
        $this->assertEquals($apiResponse['id'], $buyOrder->getId());
        $this->assertEquals(TradeOrderStatus::Submitted, $buyOrder->getStatus());

        // And with a share trade
        $this->assertNotEmpty($buyOrder->getShareTrades());
        $shareTrade = $buyOrder->getShareTrades()->first();
        $this->assertEquals(
            new Number((string) (8 * $asset->getPricePerShare())),
            $shareTrade->getTradeValue(),
        );
        $this->assertTrue($shareTrade->isDerived());
        $this->assertEquals(
            new Number($asset->getPricePerShare()),
            $shareTrade->getPricePerShare(),
        );
        $this->assertEquals(8, $shareTrade->getNumberOfShares());
        $this->assertEquals($buyOrder->getId(), $shareTrade->getBuyOrder()->getId());
        $this->assertEquals($sellOrder->getId(), $shareTrade->getSellOrder()->getId());
        $this->assertEquals(TradeStatus::Reserved, $shareTrade->getStatus());

        // Create the complementary liquidation sell order
        $content = json_encode([
            'assetId' => (string) $asset->getId(),
            'direction' => TradeDirection::Sell,
            'numberOfShares' => 6, // slightly less than the buyOrder to represent partial retention
            'pricePerShare' => $asset->getPricePerShare(),
            'type' => TradeOrderType::Prefunding,
            'status' => TradeOrderStatus::Submitted,
            'complementaryOrderId' => (string) $buyOrder->getId(),
        ]);
        $uri = self::API_PATH_PREFIX_V1 . '/trade-orders';
        $this->client->request('POST', $uri, server: $headers, content: $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::TRADE_ORDER,
            array_keys($apiResponse),
        );
        // Check a transaction has been attached to the buyOrder - need to refresh
        $this->entityManager->refresh($buyOrder);
        $this->assertEmpty($buyOrder->getTransactionReference());
        $this->assertEmpty($buyOrder->getTransaction());
        $complementaryOrder = $buyOrder->getComplementaryOrder();
        $this->assertNotEmpty($complementaryOrder);
        $this->assertEquals($apiResponse['id'], $complementaryOrder->getId());
        $this->assertEquals(6, $complementaryOrder->getNumberOfShares());
        $this->assertEquals(TradeDirection::Sell, $complementaryOrder->getDirection());
        $this->assertEquals(TradeOrderType::Prefunding, $complementaryOrder->getType());
        $this->assertEquals(
            TradeOrderStatus::Submitted,
            $complementaryOrder->getStatus(),
        );
        // Relation set both ways
        $this->assertEquals(
            $buyOrder->getId(),
            $complementaryOrder->getComplementaryOrder()->getId(),
        );

        // Submit outcome - we can skip verification to avoid the need to create a Mangopay transfer
        $content = json_encode([
            'success' => true,
            'verify' => false,
        ]);
        $uri =
            self::API_PATH_PREFIX_V1
            . "/trade-orders/{$buyOrder->getId()}/payment-outcome";
        $this->client->request('POST', $uri, server: $headers, content: $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::SCA_OUTCOME,
            array_keys($apiResponse),
        );
        // Check status updates - again, refresh the entities so you have the changes
        $this->entityManager->refresh($buyOrder);
        $this->entityManager->refresh($complementaryOrder);
        $this->entityManager->refresh($shareTrade);
        $this->assertEquals(TradeOrderStatus::Active, $complementaryOrder->getStatus());
        $this->assertEquals(TradeOrderStatus::Completed, $buyOrder->getStatus());
        $this->assertEquals(TradeStatus::Unsettled, $shareTrade->getStatus());
    }
}
