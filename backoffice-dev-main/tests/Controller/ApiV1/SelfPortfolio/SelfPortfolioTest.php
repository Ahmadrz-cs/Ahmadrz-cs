<?php

namespace App\Tests\Controller\ApiV1\SelfPortfolio;

use App\Entity\Asset;
use App\Entity\Enum\PayoutType;
use App\Entity\Enum\ShareTradeType;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TradeStatus;
use App\Entity\User;
use App\Test\FixtureTestCase;
use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV1\ApiV1ResponseFields;
use Symfony\Component\HttpFoundation\Response;

class SelfPortfolioTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetPortfolio(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR);
        $uri = FixtureWebTestCase::API_PATH_PREFIX_V1 . '/self/portfolio';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::USER_PORTFOLIO,
            array_keys($apiResponse),
        );
        // Ben user always has some shares and dividends, but not necessarily capital gains
        $this->assertGreaterThan(0, $apiResponse['value']);
        $this->assertGreaterThan(0, $apiResponse['dividends']);
        $this->assertNotEmpty($apiResponse['positions']);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::USER_PORTFOLIO_POSITION,
            array_keys($apiResponse['positions'][0]),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetPortfolioNoInvestments(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_ANALYST);
        $uri = FixtureWebTestCase::API_PATH_PREFIX_V1 . '/self/portfolio';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::USER_PORTFOLIO,
            array_keys($apiResponse),
        );
        // Analyst test user should never have investments
        $this->assertEquals(0, $apiResponse['value']);
        $this->assertEquals(0, $apiResponse['dividends']);
        $this->assertEquals(0, $apiResponse['capitalGains']);
        $this->assertEmpty($apiResponse['positions']);
    }

    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetPortfolioAsPublic(): void
    {
        // Check public users cannot use this route
        $uri = self::API_PATH_PREFIX_V1 . '/self/portfolio';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetPortfolioUnsettled(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR);
        $uri = FixtureWebTestCase::API_PATH_PREFIX_V1 . '/self/portfolio/unsettled';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($apiResponse['data']);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::SHARE_TRADE,
            array_keys($apiResponse['data'][0]),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetPortfolioUnsettledCurrentMonthOnly(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR);
        $uri =
            FixtureWebTestCase::API_PATH_PREFIX_V1
            . '/self/portfolio/unsettled?currentMonthOnly=1';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($apiResponse['data']);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::SHARE_TRADE,
            array_keys($apiResponse['data'][0]),
        );
        foreach ($apiResponse['data'] as $actual) {
            $this->assertGreaterThanOrEqual(
                new \DateTime('midnight first day of this month'),
                new \DateTime($actual['createdAt']),
            );
        }
    }

    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetPortfolioUnsettledAsPublic(): void
    {
        // Check public users cannot use this route
        $uri = self::API_PATH_PREFIX_V1 . '/self/portfolio/unsettled';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetPortfolioPrefunding(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_VIP);
        $uri = FixtureWebTestCase::API_PATH_PREFIX_V1 . '/self/portfolio/prefunding';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::USER_PORTFOLIO,
            array_keys($apiResponse),
        );
        // Freya user always has some prefunded shares outstanding
        $this->assertGreaterThan(0, $apiResponse['value']);
        $this->assertNotEmpty($apiResponse['positions']);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::USER_PORTFOLIO_POSITION,
            array_keys($apiResponse['positions'][0]),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetPortfolioNoPrefunding(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_ANALYST);
        $uri = FixtureWebTestCase::API_PATH_PREFIX_V1 . '/self/portfolio/prefunding';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::USER_PORTFOLIO,
            array_keys($apiResponse),
        );
        // Analyst test user should never have investments
        $this->assertEquals(0, $apiResponse['value']);
        $this->assertEquals(0, $apiResponse['dividends']);
        $this->assertEquals(0, $apiResponse['capitalGains']);
        $this->assertEmpty($apiResponse['positions']);
    }

    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetPortfolioPrefundingAsPublic(): void
    {
        // Check public users cannot use this route
        $uri = self::API_PATH_PREFIX_V1 . '/self/portfolio/prefunding';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetPortfolioShareTrades(): void
    {
        $statuses = [TradeStatus::Unsettled->value, TradeStatus::Cancelled->value];
        $this->loginApiClientUser(FixtureTestCase::USER_VIP);
        $uri = FixtureWebTestCase::API_PATH_PREFIX_V1 . '/self/portfolio/share-trades';
        $this->client->request('GET', $uri, [
            'status' => $statuses,
            'shareTradeType' => ShareTradeType::Prefunding->value,
        ]);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($apiResponse['data']);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::SHARE_TRADE,
            array_keys($apiResponse['data'][0]),
        );
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => FixtureTestCase::USER_VIP]);
        foreach ($apiResponse['data'] as $shareTrade) {
            $userIds = [$shareTrade['buyerId'], $shareTrade['sellerId']];
            $this->assertContains((string) $user->getId(), $userIds);
            $this->assertContains($shareTrade['status'], $statuses);
            $this->assertEquals(ShareTradeType::Prefunding->value, $shareTrade['type']);
        }
    }

    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetPortfolioShareTradesAsPublic(): void
    {
        // Check public users cannot use this route
        $uri = self::API_PATH_PREFIX_V1 . '/self/portfolio/share-trades';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetPortfolioTradeOrders(): void
    {
        $statuses = [
            TradeOrderStatus::Submitted->value,
            TradeOrderStatus::Active->value,
        ];
        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR);
        $uri = FixtureWebTestCase::API_PATH_PREFIX_V1 . '/self/portfolio/trade-orders';
        $this->client->request('GET', $uri, [
            'status' => $statuses,
            'type' => [TradeOrderType::Market->value],
        ]);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($apiResponse['data']);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::TRADE_ORDER,
            array_keys($apiResponse['data'][0]),
        );
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => FixtureTestCase::USER_REGULAR]);
        foreach ($apiResponse['data'] as $tradeOrder) {
            $this->assertEquals((string) $user->getId(), $tradeOrder['userId']);
            $this->assertContains($tradeOrder['status'], $statuses);
            $this->assertEquals(TradeOrderType::Market->value, $tradeOrder['type']);
        }
    }

    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetPortfolioTradeOrdersAsPublic(): void
    {
        // Check public users cannot use this route
        $uri = self::API_PATH_PREFIX_V1 . '/self/portfolio/share-trades';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetPortfolioPayouts(): void
    {
        $asset = $this->entityManager
            ->getRepository(Asset::class)
            ->findOneBy(['name' => 'Royal Eversea Glades - Cambridge']);
        $this->loginApiClientUser(FixtureTestCase::USER_VIP);
        $uri = FixtureWebTestCase::API_PATH_PREFIX_V1 . '/self/portfolio/payouts';
        $this->client->request('GET', $uri, [
            'assetId' => $asset->getId(),
            'payoutType' => PayoutType::Dividend->value,
        ]);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($apiResponse['data']);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::PAYOUT_MAPPED,
            array_keys($apiResponse['data'][0]),
        );
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => FixtureTestCase::USER_VIP]);
        foreach ($apiResponse['data'] as $payout) {
            $this->assertEquals((string) $user->getId(), $payout['userId']);
            $this->assertEquals((string) $asset->getId(), $payout['assetId']);
            $this->assertEquals(PayoutType::Dividend->value, $payout['type']);
        }
    }

    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetPortfolioPayoutsAsPublic(): void
    {
        // Check public users cannot use this route
        $uri = self::API_PATH_PREFIX_V1 . '/self/portfolio/payouts';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}
