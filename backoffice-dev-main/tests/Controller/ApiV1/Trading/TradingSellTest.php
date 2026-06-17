<?php

namespace App\Tests\Controller\ApiV1\SelfPortfolio;

use App\Entity\Asset;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\TradeOrder;
use App\Entity\User;
use App\Test\FixtureTestCase;
use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV1\ApiV1ResponseFields;
use BcMath\Number;

class TradingSellTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testRelistingSell(): void
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
            'numberOfShares' => 80,
            'pricePerShare' => '1.09', // we'll set a custom share price
            'type' => TradeOrderType::Market,
            'status' => TradeOrderStatus::Draft,
            'notes' => $testId,
            'fees' => '10',
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
         * @var TradeOrder $sellOrder
         */
        $sellOrder = $this->entityManager
            ->getRepository(TradeOrder::class)
            ->findOneBy([
                'asset' => $asset->getId(),
                'user' => $user->getId(),
                'direction' => TradeDirection::Sell,
                'type' => TradeOrderType::Market,
                'notes' => $testId,
            ]);
        $this->assertNotNull($sellOrder);
        $this->assertEquals($apiResponse['id'], $sellOrder->getId());
        $this->assertEquals(TradeOrderStatus::Draft, $sellOrder->getStatus());
        $this->assertEmpty($sellOrder->getShareTrades());
        $this->assertEquals(new Number('1.09'), $sellOrder->getPricePerShare());
        $this->assertEquals(80, $sellOrder->getNumberOfShares());
        $afterCount = $this->entityManager->getRepository(TradeOrder::class)->count();
        $this->assertEquals($beforeCount + 1, $afterCount);

        // Note that asset fixtures default to a minimum investment of £1
        // So typically, this means the minimumShares will end up set to 1 share in tests
        // Mainly checking that minimumShares is not null
        $expectedMinimum = (int) (string) $asset
            ->getMinimumInvestment()
            ->div($sellOrder->getPricePerShare())
            ->ceil();
        $this->assertNotNull($sellOrder->getMinimumShares());
        $this->assertEquals($expectedMinimum, $sellOrder->getMinimumShares());

        // Take a payment
        $content = json_encode([
            'amount' => '0.01', // must be at least 1p
            'sca' => true,
        ]);
        $uri =
            self::API_PATH_PREFIX_V1 . "/trade-orders/{$sellOrder->getId()}/payments";
        $this->client->request('POST', $uri, server: $headers, content: $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::SCA_ACTION,
            array_keys($apiResponse),
        );
        // Check a transaction has been attached to the sellOrder - need to refresh
        $this->entityManager->refresh($sellOrder);
        $this->assertNotEmpty($sellOrder->getTransactionReference());
        $this->assertNotEmpty($sellOrder->getTransaction());

        // Submit outcome
        $content = json_encode([
            'success' => true,
            'verify' => true, // this is true by default, but explicitly set here to clarity
        ]);
        $uri =
            self::API_PATH_PREFIX_V1
            . "/trade-orders/{$sellOrder->getId()}/payment-outcome";
        $this->client->request('POST', $uri, server: $headers, content: $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::SCA_OUTCOME,
            array_keys($apiResponse),
        );
        // Check status updates - again, refresh the entities so you have the changes
        $this->entityManager->refresh($sellOrder);
        // Note that relistings still require bizops to publish (make active)
        // So it'll be stuck in submitted status
        $this->assertEquals(TradeOrderStatus::Submitted, $sellOrder->getStatus());
    }
}
