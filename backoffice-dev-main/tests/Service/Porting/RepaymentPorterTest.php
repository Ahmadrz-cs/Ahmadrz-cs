<?php

namespace App\Tests\Service\Porting;

use App\Entity\Asset;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TradeStatus;
use App\Entity\Investment;
use App\Entity\Offering;
use App\Entity\ShareTrade;
use App\Entity\ShareTradeStatusLog;
use App\Entity\TradeOrder;
use App\Entity\TradeOrderStatusLog;
use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\TransactionRepository;
use App\Service\Porting\RepaymentPorter;
use App\Test\Util\EntityIdTestUtil;
use BcMath\Number;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RepaymentPorterTest extends KernelTestCase
{
    private RepaymentPorter $service;

    private TransactionRepository|MockObject $transactionRepositoryMock;

    protected function setUp(): void
    {
        self::bootKernel();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('portInvestmentSellOrderGenerator')]
    public function testPortInvestmentSellOrder(
        bool $hasTransaction,
        bool $completed,
        \DateTime $investedAt,
        \DateTime $lastBuyBack,
        ?\DateTime $completedAt = null,
        ?int $sharesDivested = null,
    ): void {
        $asset = EntityIdTestUtil::setEntityId(new Asset(), 76);
        $offering = EntityIdTestUtil::setEntityId(new Offering(), 441);
        $offering->setAsset($asset);
        $buyer = EntityIdTestUtil::setEntityId(new User(), 412);
        $seller = EntityIdTestUtil::setEntityId(new User(), 4);
        // $seller->setUsername('TestUser' . bin2hex(random_bytes(6)));

        $input = EntityIdTestUtil::setEntityId(new Investment(), 1784);
        $input->setUser($buyer);
        $input->setShareAmount(1047);
        $input->setOrgPricePerShare('3.17');
        $input->setType('prefunding');
        $input->setOffering($offering);
        $input->setCreatedAt($investedAt);
        $input->setComments(
            'Test investment prefunding sell side porting ' . bin2hex(random_bytes(6)),
        );
        $input->setTransactionId('trns_test_' . bin2hex(random_bytes(6)));

        $transaction = EntityIdTestUtil::setEntityId(new Transaction(), id: 421);
        $transaction->setExternalId($input->getTransactionId());

        $expected = new TradeOrder(
            TradeDirection::Sell,
            $asset,
            $buyer, // this is the sell order for their liquidation portion
            $input->getShareAmount(),
            new Number($input->getOrgPricePerShare()),
            TradeOrderType::Prefunding,
        );
        $expected->setTransactionReference($input->getTransactionId());
        $expected->setCreatedAt($input->getCreatedAt());
        $expected->setCreatedBy($buyer);
        $expected->setNotes('port:o441:i1784 ' . $input->getComments());

        if ($hasTransaction) {
            $this->transactionRepositoryMock = $this->createMock(TransactionRepository::class);
            static::getContainer()->set(
                TransactionRepository::class,
                $this->transactionRepositoryMock,
            );
            $this->transactionRepositoryMock
                ->expects(self::exactly(1))
                ->method('findOneBy')
                ->with(['external_id' => $input->getTransactionId()])
                ->willReturn($transaction);
            $expected->setTransaction($transaction);
        }

        $activeLog = new TradeOrderStatusLog(
            $expected,
            TradeOrderStatus::Active,
            $input->getCreatedAt(),
        );
        $expected->addStatusLog($activeLog);

        if ($completed) {
            $reflection = new \ReflectionClass($input);
            $uuidProp = $reflection->getProperty('divested_shares');
            $uuidProp->setValue($input, $sharesDivested ?? $input->getShareAmount());

            $completeLog = new TradeOrderStatusLog(
                $expected,
                TradeOrderStatus::Completed,
                $completedAt,
            );
            $expected->addStatusLog($completeLog);
        }

        $this->service = static::getContainer()->get(RepaymentPorter::class);
        $actual = $this->service->portInvestmentSellOrder($input, $lastBuyBack);

        // Note that the uuid will always be different so need to nullify before comparison
        $reflection = new \ReflectionClass($expected);
        $uuidProp = $reflection->getProperty('uuid');
        $uuidProp->setValue($expected, null);
        $reflection = new \ReflectionClass($actual);
        $uuidProp = $reflection->getProperty('uuid');
        $uuidProp->setValue($actual, null);

        $this->assertEquals($expected, $actual);

        // If handling cases where prefunding investments are backported AFTER retail sellout
        // Ensure the completed is later than the active status logs (to make it easier to sort)
        if ($completed && $investedAt > $lastBuyBack) {
            $this->assertGreaterThan(
                $activeLog->getOccuredAt(),
                $completeLog->getOccuredAt(),
            );
        }
    }

    public static function portInvestmentSellOrderGenerator(): \Generator
    {
        yield 'No repayment has transaction' => [
            true,
            false,
            new \DateTime('2023-10-18 19:56:44'),
            new \DateTime('2024-06-12 12:05:42'),
            null,
            0,
        ];
        yield 'Partial repayment, no transaction' => [
            false,
            false,
            new \DateTime('2023-10-18 19:56:44'),
            new \DateTime('2024-06-12 12:05:42'),
            null,
            10,
        ];
        yield 'Full repayment, has transaction' => [
            true,
            true,
            new \DateTime('2023-10-18 19:56:44'),
            new \DateTime('2024-06-12 12:05:42'),
            new \DateTime('2024-06-12 12:05:42'),
            null,
        ];
        yield 'Full repayment, has transaction, backported record after last buyback' =>
            [
                true,
                true,
                new \DateTime('2025-10-18 19:56:44'),
                new \DateTime('2024-06-12 12:05:42'),
                // Note that the completion time is 1 second after the investment creation time
                // Mainly to help with any sorting
                new \DateTime('2025-10-18 19:56:45'),
                null,
            ];
    }

    public function testPortRepaymentTrade(): void
    {
        $this->service = static::getContainer()->get(RepaymentPorter::class);

        $asset = EntityIdTestUtil::setEntityId(new Asset(), 76);
        $asset->setAmountOfShares(12850);
        $asset->setPricePerShare('4.29');
        $offering = EntityIdTestUtil::setEntityId(new Offering(), 441);
        $offering->setAsset($asset);
        $seller = EntityIdTestUtil::setEntityId(new User(), 4);
        $buyer = EntityIdTestUtil::setEntityId(new User(), 412);
        $staff = EntityIdTestUtil::setEntityId(new User(), 6671);

        $lastBuyBack = new \DateTime('2025-06-19 16:17:28');

        $buyBackOrder = new TradeOrder(
            direction: TradeDirection::Buy,
            asset: $asset,
            user: $seller, // i.e. superadmin
            numberOfShares: $asset->getAmountOfShares(),
            pricePerShare: new Number($asset->getPricePerShare()),
            type: TradeOrderType::Proxy,
        );
        $buyBackOrder->setCreatedAt($lastBuyBack);
        // make createdBy different to the seller as it doesn't need to be the same
        $buyBackOrder->setCreatedBy($staff);

        $prefunderSellOrder = new TradeOrder(
            TradeDirection::Sell,
            $asset,
            $buyer, // this is the sell order for their liquidation portion
            1047,
            new Number($asset->getPricePerShare()),
            TradeOrderType::Prefunding,
        );
        $prefunderSellOrder->setCreatedAt(new \DateTime('2023-10-18 19:56:44'));
        $prefunderSellOrder->setCreatedBy($buyer);

        $expected = new ShareTrade(
            $buyBackOrder,
            $prefunderSellOrder,
            247,
            new Number($asset->getPricePerShare()),
            // Leave trade value empty, so it will be derived
        );
        $expected->setCreatedAt($buyBackOrder->getCreatedAt());
        $expected->setCreatedBy($buyBackOrder->getCreatedBy()); // should be the staffer
        $settledLog = new ShareTradeStatusLog(
            $expected,
            TradeStatus::Settled,
            // all the buyback records will usually be executed at a very similar time
            // For the porter, we just use the same time, no point off setting
            $buyBackOrder->getCreatedAt(),
        );
        $expected->addStatusLog($settledLog);

        $actual = $this->service->portRepaymentTrade(
            $expected->getNumberOfShares(),
            $prefunderSellOrder,
            $buyBackOrder,
        );

        // Note that the uuid will always be different so need to nullify before comparison
        $reflection = new \ReflectionClass($expected);
        $uuidProp = $reflection->getProperty('uuid');
        $uuidProp->setValue($expected, null);
        $reflection = new \ReflectionClass($actual);
        $uuidProp = $reflection->getProperty('uuid');
        $uuidProp->setValue($actual, null);

        $this->assertEquals($expected, $actual);
    }

    public function testPortRepaymentTradeRetrospective(): void
    {
        $this->service = static::getContainer()->get(RepaymentPorter::class);

        $asset = EntityIdTestUtil::setEntityId(new Asset(), 76);
        $asset->setAmountOfShares(12850);
        $asset->setPricePerShare('4.29');
        $offering = EntityIdTestUtil::setEntityId(new Offering(), 441);
        $offering->setAsset($asset);
        $seller = EntityIdTestUtil::setEntityId(new User(), 4);
        $buyer = EntityIdTestUtil::setEntityId(new User(), 412);
        $staff = EntityIdTestUtil::setEntityId(new User(), 6671);

        // Buy back which is based on the last investment is earlier than the sell order
        // Because the sell orders where added in afterwards in retrospect
        // These sell orders would be based on the "prefunding" type investment representing the liquidation portion
        $lastBuyBack = new \DateTime('2022-06-19 16:17:28');

        $buyBackOrder = new TradeOrder(
            direction: TradeDirection::Buy,
            asset: $asset,
            user: $seller, // i.e. superadmin
            numberOfShares: $asset->getAmountOfShares(),
            pricePerShare: new Number($asset->getPricePerShare()),
            type: TradeOrderType::Proxy,
        );
        $buyBackOrder->setCreatedAt($lastBuyBack);
        // make createdBy different to the seller as it doesn't need to be the same
        $buyBackOrder->setCreatedBy($staff);

        $prefunderSellOrder = new TradeOrder(
            TradeDirection::Sell,
            $asset,
            $buyer, // this is the sell order for their liquidation portion
            1047,
            new Number($asset->getPricePerShare()),
            TradeOrderType::Prefunding,
        );
        $prefunderSellOrder->setCreatedAt(new \DateTime('2023-10-18 19:56:44'));
        $prefunderSellOrder->setCreatedBy($buyer);

        $expected = new ShareTrade(
            $buyBackOrder,
            $prefunderSellOrder,
            247,
            new Number($asset->getPricePerShare()),
            // Leave trade value empty, so it will be derived
        );
        // As the sell order is newer, the share trade execution date will be the newer
        // of the buy and sell orders
        $expected->setCreatedAt($prefunderSellOrder->getCreatedAt());
        $expected->setCreatedBy($buyBackOrder->getCreatedBy()); // should be the staffer
        $settledLog = new ShareTradeStatusLog(
            $expected,
            TradeStatus::Settled,
            // all the buyback records will usually be executed at a very similar time
            // For the porter, we just use the same time, no point off setting
            $expected->getCreatedAt(),
        );
        $expected->addStatusLog($settledLog);

        $actual = $this->service->portRepaymentTrade(
            $expected->getNumberOfShares(),
            $prefunderSellOrder,
            $buyBackOrder,
        );

        // Note that the uuid will always be different so need to nullify before comparison
        $reflection = new \ReflectionClass($expected);
        $uuidProp = $reflection->getProperty('uuid');
        $uuidProp->setValue($expected, null);
        $reflection = new \ReflectionClass($actual);
        $uuidProp = $reflection->getProperty('uuid');
        $uuidProp->setValue($actual, null);

        $this->assertEquals($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('createBuyBackOrderGenerator')]
    public function testcreateBuyBackOrder(
        bool $offsetInitiation,
        \DateTime $expectedInitiationDate,
        \DateTime $inputInitiatonDate,
    ): void {
        ;
        $this->service = static::getContainer()->get(RepaymentPorter::class);
        $asset = EntityIdTestUtil::setEntityId(new Asset(), 76);
        $asset->setPricePerShare('3.44');
        $seller = EntityIdTestUtil::setEntityId(new User(), 4);
        // The launch listing, just used to extract the asset and platform user/seller
        // While you could sort of derive it from the PaymentOrder
        // Having the original launch order is much more reliable
        // as you're making the reverse in this method
        $initialOrder = new TradeOrder(
            TradeDirection::Sell,
            $asset,
            $seller,
            17800,
            new Number($asset->getPricePerShare()),
            TradeOrderType::Initial,
        );
        // $expected->setCreatedAt($expectedInitiationDate);
        // $expected->setCreatedBy($seller);

        $expected = new TradeOrder(
            TradeDirection::Buy,
            $asset,
            $seller,
            8500,
            new Number($asset->getPricePerShare()),
            TradeOrderType::Proxy,
        );
        $expected->setCreatedAt($expectedInitiationDate);
        $expected->setCreatedBy($seller);
        $expected->setNotes('port:aggregated_repayments');
        // Only need a completed log, as buybacks happen periodically
        // In this case, it's a single aggregate buyback done in one go
        $completedLog = new TradeOrderStatusLog(
            $expected,
            TradeOrderStatus::Completed,
            $expectedInitiationDate,
        );
        $expected->addStatusLog($completedLog);

        $actual = $this->service->createBuyBackOrder(
            $initialOrder,
            $expected->getNumberOfShares(),
            $inputInitiatonDate,
            $offsetInitiation,
        );
        // Note that the uuid will always be different so need to nullify before comparison
        $reflection = new \ReflectionClass($expected);
        $uuidProp = $reflection->getProperty('uuid');
        $uuidProp->setValue($expected, null);
        $reflection = new \ReflectionClass($actual);
        $uuidProp = $reflection->getProperty('uuid');
        $uuidProp->setValue($actual, null);
        $this->assertEquals($expected, $actual);
    }

    public static function createBuyBackOrderGenerator(): \Generator
    {
        yield 'Exact initiation date' => [
            false,
            new \DateTime('2025-02-12 20:46:34'),
            new \DateTime('2025-02-12 20:46:34'),
        ];
        yield 'Offset initiation date' => [
            true,
            new \DateTime('2025-03-03 20:46:34'),
            new \DateTime('2025-02-12 20:46:34'),
        ];
    }
}
