<?php

namespace App\Tests\Service\Porting;

use App\Entity\Asset;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TradeStatus;
use App\Entity\Investment;
use App\Entity\InvestmentStatus;
use App\Entity\Lifecycle\InvestmentLifecycle;
use App\Entity\Offering;
use App\Entity\ShareTrade;
use App\Entity\ShareTradeStatusLog;
use App\Entity\TradeOrder;
use App\Entity\TradeOrderStatusLog;
use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\TransactionRepository;
use App\Service\Porting\InvestmentPorter;
use App\Test\Util\EntityIdTestUtil;
use BcMath\Number;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class InvestmentPorterTest extends KernelTestCase
{
    private InvestmentPorter $service;

    private TransactionRepository|MockObject $transactionRepositoryMock;

    protected function setUp(): void
    {
        self::bootKernel();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('portInvestmentOrderGenerator')]
    public function testPortInvestmentOrder(
        TradeOrder $expected,
        Investment $input,
        ?Transaction $transaction = null,
    ): void {
        $this->transactionRepositoryMock = $this->createMock(TransactionRepository::class);
        static::getContainer()->set(
            TransactionRepository::class,
            $this->transactionRepositoryMock,
        );
        $this->service = static::getContainer()->get(InvestmentPorter::class);

        $this->transactionRepositoryMock
            ->expects(self::exactly(1))
            ->method('findOneBy')
            ->with(['external_id' => $input->getTransactionId()])
            ->willReturn($transaction);

        $actual = $this->service->portInvestmentOrder($input);

        // Note that the uuid will always be different so need to nullify before comparison
        $reflection = new \ReflectionClass($expected);
        $uuidProp = $reflection->getProperty('uuid');
        $uuidProp->setValue($expected, null);
        $reflection = new \ReflectionClass($actual);
        $uuidProp = $reflection->getProperty('uuid');
        $uuidProp->setValue($actual, null);

        $this->assertEquals($expected, $actual);

        // Check offering has relation set
        $this->assertEquals($input->getTradeOrder(), $actual);
    }

    public static function portInvestmentOrderGenerator(): \Generator
    {
        // Some common entities
        $asset = EntityIdTestUtil::setEntityId(new Asset(), 76);
        $offering = EntityIdTestUtil::setEntityId(new Offering(), 441);
        $offering->setAsset($asset);
        $buyer = EntityIdTestUtil::setEntityId(new User(), 412);

        // Prefunding investment with transaction
        $prefundingInvestment = EntityIdTestUtil::setEntityId(new Investment(), 1784);
        $prefundingInvestment->setUser($buyer);
        $prefundingInvestment->setShareAmount(1047);
        $prefundingInvestment->setOrgPricePerShare('3.17');
        $prefundingInvestment->setType('prefunding');
        $prefundingInvestment->setOffering($offering);
        $prefundingInvestment->setCreatedAt(new \DateTime('2023-10-18 19:56:44'));
        $prefundingInvestment->setTransactionId(
            'trns_test_' . bin2hex(random_bytes(6)),
        );

        $prefundingBuyOrder = new TradeOrder(
            TradeDirection::Buy,
            $asset,
            $buyer,
            $prefundingInvestment->getShareAmount(),
            new Number($prefundingInvestment->getOrgPricePerShare()),
            TradeOrderType::Prefunding,
        );
        $prefundingBuyOrder->setTransactionReference($prefundingInvestment->getTransactionId());
        $prefundingBuyOrder->setCreatedAt($prefundingInvestment->getCreatedAt());
        $prefundingBuyOrder->setCreatedBy($buyer);
        $prefundingBuyOrder->setNotes('port:o441:i1784 ');
        $completedLog1 = new TradeOrderStatusLog(
            $prefundingBuyOrder,
            TradeOrderStatus::Completed,
            $prefundingInvestment->getCreatedAt(),
        );
        $prefundingBuyOrder->addStatusLog($completedLog1);

        $transaction = EntityIdTestUtil::setEntityId(new Transaction(), id: 421);
        $transaction->setExternalId($prefundingInvestment->getTransactionId());
        $prefundingBuyOrder->setTransaction($transaction);

        yield 'Prefunding investment' => [
            $prefundingBuyOrder,
            $prefundingInvestment,
            $transaction,
        ];

        // Offmarket investment with only transaction reference
        $offmarketInvestment = EntityIdTestUtil::setEntityId(new Investment(), 3367);
        $offmarketInvestment->setUser($buyer);
        $offmarketInvestment->setShareAmount(440);
        $offmarketInvestment->setOrgPricePerShare('8.24');
        $offmarketInvestment->setType('off-market');
        $offmarketInvestment->setOffering($offering);
        $offmarketInvestment->setCreatedAt(new \DateTime('2023-12-23 14:22:24'));
        $offmarketInvestment->setComments(
            'Offmarket test port' . bin2hex(random_bytes(6)),
        );
        $offmarketInvestment->setTransactionId('trns_test_' . bin2hex(random_bytes(6)));

        $offmarketBuyOrder = new TradeOrder(
            TradeDirection::Buy,
            $asset,
            $buyer,
            $offmarketInvestment->getShareAmount(),
            new Number($offmarketInvestment->getOrgPricePerShare()),
            TradeOrderType::OffMarket,
        );
        $offmarketBuyOrder->setTransactionReference($offmarketInvestment->getTransactionId());
        $offmarketBuyOrder->setCreatedAt($offmarketInvestment->getCreatedAt());
        $offmarketBuyOrder->setCreatedBy($buyer);
        $offmarketBuyOrder->setNotes(
            'port:o441:i3367 ' . $offmarketInvestment->getComments(),
        );
        $completedLog1 = new TradeOrderStatusLog(
            $offmarketBuyOrder,
            TradeOrderStatus::Completed,
            $offmarketInvestment->getCreatedAt(),
        );
        $offmarketBuyOrder->addStatusLog($completedLog1);

        yield 'Off market investment with comment' => [
            $offmarketBuyOrder,
            $offmarketInvestment,
        ];

        // Normal investment
        $normalInvestment = EntityIdTestUtil::setEntityId(new Investment(), 3367);
        $normalInvestment->setUser($buyer);
        $normalInvestment->setShareAmount(440);
        $normalInvestment->setOrgPricePerShare('8.24');
        $normalInvestment->setType('normal');
        $normalInvestment->setOffering($offering);
        $normalInvestment->setCreatedAt(new \DateTime('2024-05-23 12:29:11'));

        $normalBuyOrder = new TradeOrder(
            TradeDirection::Buy,
            $asset,
            $buyer,
            $normalInvestment->getShareAmount(),
            new Number($normalInvestment->getOrgPricePerShare()),
            TradeOrderType::Market,
        );
        $normalBuyOrder->setCreatedAt($normalInvestment->getCreatedAt());
        $normalBuyOrder->setCreatedBy($buyer);
        $normalBuyOrder->setNotes('port:o441:i3367 ');
        $completedLog1 = new TradeOrderStatusLog(
            $normalBuyOrder,
            TradeOrderStatus::Completed,
            $normalInvestment->getCreatedAt(),
        );
        $normalBuyOrder->addStatusLog($completedLog1);

        yield 'Normal investment' => [
            $normalBuyOrder,
            $normalInvestment,
        ];

        $otherInvestment = clone $normalInvestment;
        $otherInvestment->setUser($buyer);
        $otherInvestment->setType('other');

        yield 'Unknown type investment' => [
            $normalBuyOrder,
            $otherInvestment,
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('portInvestmentTradeGenerator')]
    public function testPortInvestmentTrade(
        ShareTrade $expected,
        TradeOrder $buyOrder,
        Investment $input,
    ): void {
        $this->service = static::getContainer()->get(InvestmentPorter::class);

        $actual = $this->service->portInvestmentTrade($input, $buyOrder);

        // Note that the uuid will always be different so need to nullify before comparison
        $reflection = new \ReflectionClass($expected);
        $uuidProp = $reflection->getProperty('uuid');
        $uuidProp->setValue($expected, null);
        $reflection = new \ReflectionClass($actual);
        $uuidProp = $reflection->getProperty('uuid');
        $uuidProp->setValue($actual, null);

        $this->assertEquals($expected, $actual);

        // Check offering has relation set
        $this->assertEquals($input->getShareTrade(), $actual);
    }

    public static function portInvestmentTradeGenerator(): \Generator
    {
        // Some common entities
        $asset = EntityIdTestUtil::setEntityId(new Asset(), 76);
        $offering = EntityIdTestUtil::setEntityId(new Offering(), 441);
        $offering->setAsset($asset);
        $seller = EntityIdTestUtil::setEntityId(new User(), 4);
        $buyer = EntityIdTestUtil::setEntityId(new User(), 412);

        $approvedStatus = new InvestmentStatus();
        $approvedStatus->setLifecycleStatus(InvestmentLifecycle::STATE_APPROVED);
        $approvedStatus->setApprovedOn(new \DateTime('2024-05-23 12:30:11'));

        $settledStatus = new InvestmentStatus();
        $settledStatus->setLifecycleStatus(InvestmentLifecycle::STATE_SETTLED);
        // Note the settledOn is AFTER the approvedOn, this should be corrected by the porter
        $settledStatus->setOpenOn(new \DateTime('2024-01-01 17:00:32'));
        $settledStatus->setApprovedOn(new \DateTime('2024-06-01 17:00:32'));
        $settledStatus->setSettledOn(new \DateTime('2024-05-23 12:30:11'));

        $rejectedStatus = new InvestmentStatus();
        $rejectedStatus->setLifecycleStatus(InvestmentLifecycle::STATE_REJECTED);
        $rejectedStatus->setOpenOn(new \DateTime('2024-01-01 17:00:32'));
        $rejectedStatus->setApprovedOn(new \DateTime('2024-12-12 10:10:45'));
        $rejectedStatus->setSettledOn(new \DateTime('2024-12-12 15:10:45'));
        $rejectedStatus->setRejectedOn(new \DateTime('2024-05-23 12:30:11'));

        $withdrawnStatus = new InvestmentStatus();
        $withdrawnStatus->setLifecycleStatus(InvestmentLifecycle::STATE_WITHDRAWN);
        $withdrawnStatus->setOpenOn(new \DateTime('2024-01-01 17:00:32'));
        $withdrawnStatus->setWithdrawnOn(new \DateTime('2024-02-27 14:22:14'));

        $settledIshStatus = new InvestmentStatus();
        $settledIshStatus->setLifecycleStatus(InvestmentLifecycle::STATE_SETTLED);
        $settledIshStatus->setOpenOn(new \DateTime('2024-09-01 17:00:32'));
        $settledIshStatus->setApprovedOn(new \DateTime('2024-05-23 12:30:11'));
        $reflection = new \ReflectionClass($settledIshStatus);
        $settledOnProp = $reflection->getProperty('settledOn');
        $settledOnProp->setValue($settledIshStatus, null);

        $sellOrderInitial = new TradeOrder(
            TradeDirection::Sell,
            $asset,
            $seller,
            125000,
            new Number('3.78'),
            TradeOrderType::Initial,
        );
        $offering->setTradeOrder($sellOrderInitial);

        // Draft at slightly lower price
        $draftInvestment = EntityIdTestUtil::setEntityId(new Investment(), 3367);
        $draftInvestment->setUser($buyer);
        $draftInvestment->setShareAmount(440);
        $draftInvestment->setOrgPricePerShare('3.65');
        $draftInvestment->setType('normal');
        $draftInvestment->setInvestmentValue('1606');
        $draftInvestment->setOffering($offering);
        $draftInvestment->setCreatedAt(new \DateTime('2024-05-23 12:29:11'));

        // Approved and pending settlement investment
        $unsettledInvestment = EntityIdTestUtil::setEntityId(new Investment(), 3367);
        $unsettledInvestment->setUser($buyer);
        $unsettledInvestment->setShareAmount(440);
        $unsettledInvestment->setOrgPricePerShare((string) $sellOrderInitial->getPricePerShare());
        $unsettledInvestment->setType('normal');
        $unsettledInvestment->setInvestmentValue('1663.2');
        $unsettledInvestment->setOffering($offering);
        $unsettledInvestment->setCreatedAt(new \DateTime('2024-05-23 12:29:11'));
        $unsettledInvestment->setStatus($approvedStatus);

        // Settled investment - not full buy order
        $settledInvestment = EntityIdTestUtil::setEntityId(new Investment(), 3367);
        $settledInvestment->setUser($buyer);
        $settledInvestment->setShareAmount(224);
        $settledInvestment->setOrgPricePerShare((string) $sellOrderInitial->getPricePerShare());
        $settledInvestment->setType('normal');
        $settledInvestment->setInvestmentValue('846.72');
        $settledInvestment->setOffering($offering);
        $settledInvestment->setCreatedAt(new \DateTime('2024-05-23 12:29:11'));
        $settledInvestment->setStatus($settledStatus);

        // rejected investment
        $rejectedInvestment = EntityIdTestUtil::setEntityId(new Investment(), 3367);
        $rejectedInvestment->setUser($buyer);
        $rejectedInvestment->setShareAmount(440);
        $rejectedInvestment->setOrgPricePerShare((string) $sellOrderInitial->getPricePerShare());
        $rejectedInvestment->setType('off-market');
        $rejectedInvestment->setInvestmentValue('1663.2');
        $rejectedInvestment->setOffering($offering);
        $rejectedInvestment->setCreatedAt(new \DateTime('2024-03-23 17:45:13'));
        $rejectedInvestment->setStatus($rejectedStatus);

        // withdrawn investment
        $withdrawnInvestment = EntityIdTestUtil::setEntityId(new Investment(), 3367);
        $withdrawnInvestment->setUser($buyer);
        $withdrawnInvestment->setShareAmount(440);
        $withdrawnInvestment->setOrgPricePerShare((string) $sellOrderInitial->getPricePerShare());
        $withdrawnInvestment->setType('prefunding');
        $withdrawnInvestment->setInvestmentValue('1663.2');
        $withdrawnInvestment->setOffering($offering);
        $withdrawnInvestment->setCreatedAt(new \DateTime('2024-02-23 17:45:13'));
        $withdrawnInvestment->setStatus($withdrawnStatus);

        // settled investment but missing settlement date - will backfill a monthend like date
        $settledIshInvestment = EntityIdTestUtil::setEntityId(new Investment(), 3367);
        $settledIshInvestment->setUser($buyer);
        $settledIshInvestment->setShareAmount(440);
        $settledIshInvestment->setOrgPricePerShare((string) $sellOrderInitial->getPricePerShare());
        $settledIshInvestment->setType('prefunding');
        $settledIshInvestment->setInvestmentValue('1663.2');
        $settledIshInvestment->setOffering($offering);
        $settledIshInvestment->setCreatedAt(new \DateTime('2024-02-23 17:45:13'));
        $settledIshInvestment->setStatus($settledIshStatus);

        $normalBuyOrder = new TradeOrder(
            TradeDirection::Buy,
            $asset,
            $buyer,
            $unsettledInvestment->getShareAmount(),
            new Number($unsettledInvestment->getOrgPricePerShare()),
            TradeOrderType::Market,
        );

        $draftShareTrade = new ShareTrade(
            $normalBuyOrder,
            $sellOrderInitial,
            $draftInvestment->getShareAmount(),
            new Number($draftInvestment->getOrgPricePerShare()),
            new Number('1606'),
        );
        $draftShareTrade->setCreatedAt($draftInvestment->getCreatedAt());
        $draftShareTrade->setCreatedBy($buyer);

        $unsettledShareTrade = new ShareTrade(
            $normalBuyOrder,
            $sellOrderInitial,
            $unsettledInvestment->getShareAmount(),
            new Number($unsettledInvestment->getOrgPricePerShare()),
            new Number('1663.2'),
        );
        $unsettledShareTrade->setCreatedAt($unsettledInvestment->getCreatedAt());
        $unsettledShareTrade->setCreatedBy($buyer);
        $unsettledLog1 = new ShareTradeStatusLog(
            $unsettledShareTrade,
            TradeStatus::Unsettled,
            $approvedStatus->getApprovedOn(),
        );
        $unsettledShareTrade->addStatusLog($unsettledLog1);

        $settledShareTrade = new ShareTrade(
            $normalBuyOrder,
            $sellOrderInitial,
            $settledInvestment->getShareAmount(),
            new Number($settledInvestment->getOrgPricePerShare()),
            new Number('846.72'),
        );
        $settledShareTrade->setCreatedAt($settledInvestment->getCreatedAt());
        $settledShareTrade->setCreatedBy($buyer);
        $unsettledLog2 = new ShareTradeStatusLog(
            $settledShareTrade,
            TradeStatus::Unsettled,
            $settledStatus->getApprovedOn(),
        );
        $settledShareTrade->addStatusLog($unsettledLog2);
        $settledLog1 = new ShareTradeStatusLog(
            $settledShareTrade,
            TradeStatus::Settled,
            // Note that approved on is slightly later, so should be chosen
            $settledStatus->getApprovedOn(),
        );
        $settledShareTrade->addStatusLog($settledLog1);

        $rejectedShareTrade = new ShareTrade(
            $normalBuyOrder,
            $sellOrderInitial,
            $rejectedInvestment->getShareAmount(),
            new Number($rejectedInvestment->getOrgPricePerShare()),
            new Number('1663.2'),
        );
        $rejectedShareTrade->setCreatedAt($rejectedInvestment->getCreatedAt());
        $rejectedShareTrade->setCreatedBy($buyer);
        $unsettledLog3 = new ShareTradeStatusLog(
            $rejectedShareTrade,
            TradeStatus::Unsettled,
            $rejectedStatus->getApprovedOn(),
        );
        $rejectedShareTrade->addStatusLog($unsettledLog3);
        // Note that the settled status is skipped, as it isn't current, despite it being "newer"
        $cancelledLog1 = new ShareTradeStatusLog(
            $rejectedShareTrade,
            TradeStatus::Cancelled,
            // Note that settled on is latest, so should be chosen
            $rejectedStatus->getSettledOn(),
        );
        $rejectedShareTrade->addStatusLog($cancelledLog1);

        $withdrawnShareTrade = new ShareTrade(
            $normalBuyOrder,
            $sellOrderInitial,
            $withdrawnInvestment->getShareAmount(),
            new Number($withdrawnInvestment->getOrgPricePerShare()),
            new Number('1663.2'),
        );
        $withdrawnShareTrade->setCreatedAt($withdrawnInvestment->getCreatedAt());
        $withdrawnShareTrade->setCreatedBy($buyer);
        $cancelledLog2 = new ShareTradeStatusLog(
            $withdrawnShareTrade,
            TradeStatus::Cancelled,
            $withdrawnStatus->getWithdrawnOn(),
        );
        $withdrawnShareTrade->addStatusLog($cancelledLog2);

        $settledIshShareTrade = new ShareTrade(
            $normalBuyOrder,
            $sellOrderInitial,
            $settledIshInvestment->getShareAmount(),
            new Number($settledIshInvestment->getOrgPricePerShare()),
            new Number('1663.2'),
        );
        $settledIshShareTrade->setCreatedAt($withdrawnInvestment->getCreatedAt());
        $settledIshShareTrade->setCreatedBy($buyer);
        $unsettledLog3 = new ShareTradeStatusLog(
            $settledIshShareTrade,
            TradeStatus::Unsettled,
            $settledIshStatus->getApprovedOn(),
        );
        $settledIshShareTrade->addStatusLog($unsettledLog3);
        $settledLog2 = new ShareTradeStatusLog(
            $settledIshShareTrade,
            TradeStatus::Settled,
            // Note that open on is latest, so should be chosen
            // and then a month or so added on to represent monthend
            // This is a very rare case in staging/prod for manually added investments
            new \DateTime('2024-10-03 17:00:32'),
        );
        $settledIshShareTrade->addStatusLog($settledLog2);

        // Note that neither of the sell or buy orders have any bearing on what is set for the transaction
        // Not price or quantity
        yield 'draft investment - full amount at reduced price - no status logs' => [
            $draftShareTrade,
            $normalBuyOrder,
            $draftInvestment,
        ];

        yield 'Unsettled investment - full amount at list price' => [
            $unsettledShareTrade,
            $normalBuyOrder,
            $unsettledInvestment,
        ];

        yield 'Settled - partial amount at list price' => [
            $settledShareTrade,
            $normalBuyOrder,
            $settledInvestment,
        ];

        yield 'Rejected - multi status' => [
            $rejectedShareTrade,
            $normalBuyOrder,
            $rejectedInvestment,
        ];

        yield 'Withdrawn - never approved for settled' => [
            $withdrawnShareTrade,
            $normalBuyOrder,
            $withdrawnInvestment,
        ];

        yield 'Settled - missing date' => [
            $settledIshShareTrade,
            $normalBuyOrder,
            $settledIshInvestment,
        ];
    }
}
