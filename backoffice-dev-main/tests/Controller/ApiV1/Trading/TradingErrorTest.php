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

class TradingErrorTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testBuyAssetNotReady(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR);

        $asset = $this->entityManager
            ->getRepository(Asset::class)
            ->findOneBy(['name' => 'Sagittarius Eystar - Horizon']);
        $headers = ['CONTENT_TYPE' => 'application/json'];

        $content = json_encode([
            'assetId' => (string) $asset->getId(),
            'direction' => TradeDirection::Buy,
            'numberOfShares' => 8,
            'pricePerShare' => $asset->getPricePerShare(),
            'type' => TradeOrderType::Market,
            'status' => TradeOrderStatus::Submitted,
        ]);
        $uri = self::API_PATH_PREFIX_V1 . '/trade-orders';
        $this->client->request('POST', $uri, server: $headers, content: $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString(
            'Asset must be in a tradeable status to invest',
            $apiResponse['detail'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testBuyCounterpartyNotFound(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR);

        $asset = $this->entityManager
            ->getRepository(Asset::class)
            ->findOneBy(['name' => 'Royal Way Gardens - Cambridge']);
        $headers = ['CONTENT_TYPE' => 'application/json'];

        $content = json_encode([
            'assetId' => (string) $asset->getId(),
            'direction' => TradeDirection::Buy,
            'numberOfShares' => 8,
            'pricePerShare' => $asset->getPricePerShare(),
            'type' => TradeOrderType::Market,
            'status' => TradeOrderStatus::Submitted,
            'counterpartyOrderId' => '4182abc',
            'reserveShares' => true,
        ]);
        $uri = self::API_PATH_PREFIX_V1 . '/trade-orders';
        $this->client->request('POST', $uri, server: $headers, content: $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString(
            'Could not find counterpartyOrderId',
            $apiResponse['detail'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testBuyComplementaryNotFound(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR);

        $asset = $this->entityManager
            ->getRepository(Asset::class)
            ->findOneBy(['name' => 'Royal Way Gardens - Cambridge']);
        $headers = ['CONTENT_TYPE' => 'application/json'];

        $content = json_encode([
            'assetId' => (string) $asset->getId(),
            'direction' => TradeDirection::Buy,
            'numberOfShares' => 8,
            'pricePerShare' => $asset->getPricePerShare(),
            'type' => TradeOrderType::Market,
            'status' => TradeOrderStatus::Submitted,
            'complementaryOrderId' => '4182abc',
        ]);
        $uri = self::API_PATH_PREFIX_V1 . '/trade-orders';
        $this->client->request('POST', $uri, server: $headers, content: $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString(
            'Could not find complementaryOrderId',
            $apiResponse['detail'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testBuyNoPriceOrQuantity(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR);

        $asset = $this->entityManager
            ->getRepository(Asset::class)
            ->findOneBy(['name' => 'Royal Way Gardens - Cambridge']);
        $headers = ['CONTENT_TYPE' => 'application/json'];

        $request = [
            'assetId' => (string) $asset->getId(),
            'direction' => TradeDirection::Buy,
            'numberOfShares' => 0,
            'pricePerShare' => $asset->getPricePerShare(),
            'type' => TradeOrderType::Market,
            'status' => TradeOrderStatus::Submitted,
        ];
        $uri = self::API_PATH_PREFIX_V1 . '/trade-orders';
        $this->client->request(
            'POST',
            $uri,
            server: $headers,
            content: json_encode($request),
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString(
            'Number of shares must be greater than zero',
            $apiResponse['detail'],
        );

        $request['numberOfShares'] = 1;
        $request['pricePerShare'] = '0';
        $this->client->request(
            'POST',
            $uri,
            server: $headers,
            content: json_encode($request),
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString(
            'Price per share must be greater than zero',
            $apiResponse['detail'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testBuyNonTradingType(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR);

        $asset = $this->entityManager
            ->getRepository(Asset::class)
            ->findOneBy(['name' => 'Royal Way Gardens - Cambridge']);
        $headers = ['CONTENT_TYPE' => 'application/json'];

        $request = [
            'assetId' => (string) $asset->getId(),
            'direction' => TradeDirection::Buy,
            'numberOfShares' => 1,
            'pricePerShare' => $asset->getPricePerShare(),
            'type' => TradeOrderType::Proxy,
            'status' => TradeOrderStatus::Submitted,
        ];
        $uri = self::API_PATH_PREFIX_V1 . '/trade-orders';
        $this->client->request(
            'POST',
            $uri,
            server: $headers,
            content: json_encode($request),
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString(
            'must be a valid trading type',
            $apiResponse['detail'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testTradeRestrictions(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR);

        $asset = $this->entityManager
            ->getRepository(Asset::class)
            ->findOneBy(['name' => 'Royal Way Gardens - Cambridge']);
        $asset->setBuyRestricted(true);
        $asset->setSellRestricted(true);
        $this->entityManager->flush();
        $headers = ['CONTENT_TYPE' => 'application/json'];

        $request = [
            'assetId' => (string) $asset->getId(),
            'direction' => TradeDirection::Buy,
            'numberOfShares' => 1,
            'pricePerShare' => $asset->getPricePerShare(),
            'type' => TradeOrderType::Market,
            'status' => TradeOrderStatus::Submitted,
        ];
        $uri = self::API_PATH_PREFIX_V1 . '/trade-orders';
        $this->client->request(
            'POST',
            $uri,
            server: $headers,
            content: json_encode($request),
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString(
            'Buying shares in this asset is currently restricted',
            $apiResponse['detail'],
        );

        $request['direction'] = TradeDirection::Sell;
        $this->client->request(
            'POST',
            $uri,
            server: $headers,
            content: json_encode($request),
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString(
            'Selling shares in this asset is currently restricted',
            $apiResponse['detail'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testBuyWithReserveDifferentAsset(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR);

        // Create the initial buy order with reservation
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => FixtureTestCase::USER_REGULAR]);
        $altAsset = $this->entityManager
            ->getRepository(Asset::class)
            ->findOneBy(['name' => 'Partingdale House - Reading']);
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
            'assetId' => (string) $altAsset->getId(),
            'direction' => TradeDirection::Buy,
            'numberOfShares' => 8,
            'pricePerShare' => $asset->getPricePerShare(),
            'type' => TradeOrderType::Market,
            'status' => TradeOrderStatus::Submitted,
            'counterpartyOrderId' => (string) $sellOrder->getId(),
            'reserveShares' => true,
        ]);
        $uri = self::API_PATH_PREFIX_V1 . '/trade-orders';
        $this->client->request('POST', $uri, server: $headers, content: $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString(
            'Buy-sell order pair cannot be for different assets',
            $apiResponse['detail'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testPrefundingAssetNotFundarising(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_VIP);

        $asset = $this->entityManager
            ->getRepository(Asset::class)
            ->findOneBy(['name' => 'Partingdale House - Reading']);
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
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString(
            'Asset must be in fundraising/acquiring status to prefund',
            $apiResponse['detail'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    #[\PHPUnit\Framework\Attributes\Group('check')]
    public function testPrefundingComplementaryIssues(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_VIP);

        $asset = $this->entityManager
            ->getRepository(Asset::class)
            ->findOneBy(['name' => 'Neptunis Quays - Bristol']);
        $altAsset = $this->entityManager
            ->getRepository(Asset::class)
            ->findOneBy(['name' => 'Nixis Plutona - Bristol']);
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
        ]);
        $uri = self::API_PATH_PREFIX_V1 . '/trade-orders';
        $this->client->request('POST', $uri, server: $headers, content: $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::TRADE_ORDER,
            array_keys($apiResponse),
        );
        $buyOrderId = $apiResponse['id'];

        // Wrong direction
        $request = [
            'assetId' => (string) $asset->getId(),
            'direction' => TradeDirection::Buy,
            'numberOfShares' => 8,
            'pricePerShare' => $asset->getPricePerShare(),
            'type' => TradeOrderType::Prefunding,
            'status' => TradeOrderStatus::Submitted,
            'complementaryOrderId' => $buyOrderId,
        ];
        $uri = self::API_PATH_PREFIX_V1 . '/trade-orders';
        $this->client->request(
            'POST',
            $uri,
            server: $headers,
            content: json_encode($request),
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString(
            'must be opposite directions',
            $apiResponse['detail'],
        );

        // Note prefunding type
        $request['direction'] = TradeDirection::Sell;
        $request['type'] = TradeOrderType::Market;
        $this->client->request(
            'POST',
            $uri,
            server: $headers,
            content: json_encode($request),
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString(
            'must both prefunding type',
            $apiResponse['detail'],
        );

        // Different asset
        $request['type'] = TradeOrderType::Prefunding;
        $request['assetId'] = (string) $altAsset->getId();
        $this->client->request(
            'POST',
            $uri,
            server: $headers,
            content: json_encode($request),
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString(
            'cannot be for different assets',
            $apiResponse['detail'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testPostTradeOrderAsPublic(): void
    {
        // Check public users cannot use this route
        $uri = self::API_PATH_PREFIX_V1 . '/trade-orders';
        $this->client->request('POST', $uri, content: json_encode([]));
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testPostTradeOrderPaymentAsPublic(): void
    {
        // Check public users cannot use this route
        $uri = self::API_PATH_PREFIX_V1 . '/trade-orders/1/payments';
        $this->client->request('POST', $uri, content: json_encode([]));
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testPostTradeOrderPaymentOutcomeAsPublic(): void
    {
        // Check public users cannot use this route
        $uri = self::API_PATH_PREFIX_V1 . '/trade-orders/1/payment-outcome';
        $this->client->request('POST', $uri, content: json_encode([]));
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    #[\PHPUnit\Framework\Attributes\Group('selling')]
    public function testRelistingSellNotEnoughShares(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR);

        // Create the sell order - Ben is a shareholder in Royal Way Gardens
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => FixtureTestCase::USER_REGULAR]);
        $asset = $this->entityManager
            ->getRepository(Asset::class)
            ->findOneBy(['name' => 'Royal Way Gardens - Cambridge']);
        $testId = bin2hex(random_bytes(8));

        $beforeCount = $this->entityManager->getRepository(TradeOrder::class)->count();

        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'assetId' => (string) $asset->getId(),
            'direction' => TradeDirection::Sell,
            'numberOfShares' => 80000,
            'pricePerShare' => '2.12',
            'type' => TradeOrderType::Market,
            'status' => TradeOrderStatus::Draft,
            'notes' => $testId,
            'fees' => '10',
        ]);
        $uri = self::API_PATH_PREFIX_V1 . '/trade-orders';
        $this->client->request('POST', $uri, server: $headers, content: $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString(
            'Not enough shares available to sell',
            $apiResponse['detail'],
        );

        // Check that the trade order has NOT been created
        $afterCount = $this->entityManager->getRepository(TradeOrder::class)->count();
        $this->assertEquals($beforeCount, $afterCount);
    }
}
