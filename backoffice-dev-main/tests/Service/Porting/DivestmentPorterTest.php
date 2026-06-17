<?php

namespace App\Tests\Service\Porting;

use App\Entity\Asset;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TradeStatus;
use App\Entity\Investment;
use App\Entity\Lifecycle\OfferingLifecycle;
use App\Entity\Offering;
use App\Entity\PaymentOrder;
use App\Entity\PaymentRequest;
use App\Entity\Payout;
use App\Entity\ShareTrade;
use App\Entity\ShareTradeStatusLog;
use App\Entity\TradeOrder;
use App\Entity\TradeOrderStatusLog;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\PaymentService;
use App\Service\Porting\DivestmentPorter;
use App\Test\Util\EntityIdTestUtil;
use BcMath\Number;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DivestmentPorterTest extends KernelTestCase
{
    private DivestmentPorter $service;

    private UserRepository|MockObject $userRepositoryMock;

    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testPortPayoutOrder(): void
    {
        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        static::getContainer()->set(UserRepository::class, $this->userRepositoryMock);
        $this->service = static::getContainer()->get(DivestmentPorter::class);

        $asset = EntityIdTestUtil::setEntityId(new Asset(), 76);
        $offering = EntityIdTestUtil::setEntityId(new Offering(), 441);
        $offering->setAsset($asset);
        $buyer = EntityIdTestUtil::setEntityId(new User(), 412);
        $seller = EntityIdTestUtil::setEntityId(new User(), 4);
        $seller->setUsername('TestUser' . bin2hex(random_bytes(6)));

        $investment = EntityIdTestUtil::setEntityId(new Investment(), 1784);
        $investment->setUser($buyer);
        $investment->setShareAmount(1047);
        $investment->setOrgPricePerShare('3.17');
        $investment->setType('prefunding');
        $investment->setOffering($offering);
        $investment->setCreatedAt(new \DateTime('2023-10-18 19:56:44'));
        $investment->setComments(
            'Test investment payout porting ' . bin2hex(random_bytes(6)),
        );
        $investment->setTransactionId('trns_test_' . bin2hex(random_bytes(6)));

        $input = EntityIdTestUtil::setEntityId(new Payout(), 67187);
        $input->setInvestment($investment);
        $input->setPayoutType(1);
        // 3.23594078319 pricePerShare equivalent, rounds down to 6dp 3.235940
        $input->setPayoutAmount('3388.03');
        $input->setAsset($asset);
        $input->setCreditedUser($buyer);
        $input->setTransactionId('trns_pyt_test_' . bin2hex(random_bytes(6)));
        $input->setCreatedAt(new \DateTime('2024-05-16 18:04:19'));
        $input->setCreatedBy($seller->getUserIdentifier());

        $expected = new TradeOrder(
            TradeDirection::Sell,
            $asset,
            $buyer, // the investor who got the payout
            $investment->getShareAmount(),
            new Number('3.235940'),
            TradeOrderType::BuyBack,
        );
        $expected->setTransactionReference($input->getTransactionId());
        $expected->setCreatedAt($input->getCreatedAt());
        $expected->setCreatedBy($seller);
        $expected->setNotes('port:i1784:p67187 ' . $investment->getComments());
        $completedLog1 = new TradeOrderStatusLog(
            $expected,
            TradeOrderStatus::Completed,
            $input->getCreatedAt(),
        );
        $expected->addStatusLog($completedLog1);

        $this->userRepositoryMock
            ->expects(self::exactly(1))
            ->method('findOneBy')
            ->with(['username' => $input->getCreatedBy()])
            ->willReturn($seller);

        $actual = $this->service->portPayoutOrder($input);

        // Note that the uuid will always be different so need to nullify before comparison
        $reflection = new \ReflectionClass($expected);
        $uuidProp = $reflection->getProperty('uuid');
        $uuidProp->setValue($expected, null);
        $reflection = new \ReflectionClass($actual);
        $uuidProp = $reflection->getProperty('uuid');
        $uuidProp->setValue($actual, null);

        $this->assertEquals($expected, $actual);
    }

    public function testPortPayoutOrderNoInvestment(): void
    {
        $this->service = static::getContainer()->get(DivestmentPorter::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Payout 25467 is missing an investment relation.',
        );

        $payout = EntityIdTestUtil::setEntityId(new payout(), 25467);
        $this->service->portPayoutOrder($payout);
    }

    public function testPortPaymentRequestOrder(): void
    {
        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        static::getContainer()->set(UserRepository::class, $this->userRepositoryMock);
        $this->service = static::getContainer()->get(DivestmentPorter::class);

        $asset = EntityIdTestUtil::setEntityId(new Asset(), 76);
        $offering = EntityIdTestUtil::setEntityId(new Offering(), 441);
        $offering->setAsset($asset);
        $buyer = EntityIdTestUtil::setEntityId(new User(), 412);
        $seller = EntityIdTestUtil::setEntityId(new User(), 4);
        $seller->setUsername('TestUser' . bin2hex(random_bytes(6)));

        $payout = EntityIdTestUtil::setEntityId(new Payout(), 98572);
        $payout->setPayoutType(1);
        // 3.23594078319 pricePerShare equivalent, rounds down to 6dp 3.235940
        $payout->setPayoutAmount('3388.03');
        $payout->setAsset($asset);
        $payout->setCreditedUser($buyer);
        $payout->setTransactionId('trns_pyt_test_' . bin2hex(random_bytes(6)));
        $payout->setCreatedAt(new \DateTime('2024-05-16 18:04:19'));
        $payout->setCreatedBy($seller->getUserIdentifier());

        $paymentOrder = EntityIdTestUtil::setEntityId(new PaymentOrder(), 146);
        $paymentOrder->setPaymentType(PaymentService::TYPE_INVESTMENT_EXIT); // method doesn't actually check
        $paymentOrder->setAsset($asset);

        $input = EntityIdTestUtil::setEntityId(new PaymentRequest(), 4262);
        $input->setShareholding(1047);
        $input->setAmount('3388.03');
        $input->setPayee($buyer);
        $input->setStatus(PaymentRequest::STATE_PAID); // method doesn't actually check
        $input->setPayout($payout);
        $paymentOrder->addPayment($input);

        $expected = new TradeOrder(
            TradeDirection::Sell,
            $asset,
            $buyer, // the investor who got the payout
            $input->getShareholding(),
            new Number('3.235940'),
            TradeOrderType::BuyBack,
        );
        $expected->setTransactionReference($payout->getTransactionId());
        $expected->setCreatedAt($payout->getCreatedAt());
        $expected->setCreatedBy($seller);
        $expected->setNotes('port:pr4262:p98572');
        $completedLog1 = new TradeOrderStatusLog(
            $expected,
            TradeOrderStatus::Completed,
            $payout->getCreatedAt(),
        );
        $expected->addStatusLog($completedLog1);

        $this->userRepositoryMock
            ->expects(self::exactly(1))
            ->method('findOneBy')
            ->with(['username' => $payout->getCreatedBy()])
            ->willReturn($seller);

        $actual = $this->service->portPaymentRequestOrder($input);

        // Note that the uuid will always be different so need to nullify before comparison
        $reflection = new \ReflectionClass($expected);
        $uuidProp = $reflection->getProperty('uuid');
        $uuidProp->setValue($expected, null);
        $reflection = new \ReflectionClass($actual);
        $uuidProp = $reflection->getProperty('uuid');
        $uuidProp->setValue($actual, null);

        $this->assertEquals($expected, $actual);
    }

    public function testPortPayoutTrade(): void
    {
        $this->service = static::getContainer()->get(DivestmentPorter::class);

        $asset = EntityIdTestUtil::setEntityId(new Asset(), 76);
        $buyer = EntityIdTestUtil::setEntityId(new User(), 412);
        $seller = EntityIdTestUtil::setEntityId(new User(), 4);

        $input = EntityIdTestUtil::setEntityId(new Payout(), 67187);
        $input->setPayoutType(1);
        // 3.23594078319 pricePerShare equivalent, rounds down to 6dp 3.235940
        $input->setPayoutAmount('3388.03');
        $input->setAsset($asset);
        $input->setCreditedUser($buyer);
        $input->setTransactionId('trns_pyt_test_' . bin2hex(random_bytes(6)));
        $input->setCreatedAt(new \DateTime('2024-05-16 18:04:19'));
        $input->setCreatedBy($seller->getUserIdentifier());

        // Sell order is from the investor
        $buyBackSellOrder = new TradeOrder(
            TradeDirection::Sell,
            $asset,
            $buyer, // the investor who got the payout
            $input->getShareholding(),
            new Number('3.235940'),
            TradeOrderType::BuyBack,
        );
        $buyBackSellOrder->setCreatedAt($input->getCreatedAt());
        $buyBackSellOrder->setCreatedBy($seller); // Seller is Yielders

        // Buy order is from the platform (i.e. Yielders)
        $buyBackBuyOrder = new TradeOrder(
            TradeDirection::Buy,
            $asset,
            $seller, // the platform buying back the shares
            105250,
            new Number('3.235940'),
            TradeOrderType::BuyBack,
        );
        $buyBackSellOrder->setCreatedAt($input->getCreatedAt());
        $buyBackSellOrder->setCreatedBy($seller); // Seller is Yielders

        $expected = new ShareTrade(
            $buyBackBuyOrder,
            $buyBackSellOrder,
            $input->getShareholding(), // should be the same as the buyback sell order
            new Number('3.235940'),
            new Number('3388.03'),
        );
        $expected->setCreatedAt($input->getCreatedAt());
        // createdBy the platform, but should be the same for all orders, trades, payouts
        $expected->setCreatedBy($seller);
        $settledLog = new ShareTradeStatusLog(
            $expected,
            TradeStatus::Settled,
            // Note that approved on is slightly later, so should be chosen
            $buyBackSellOrder->getCreatedAt(),
        );
        $expected->addStatusLog($settledLog);

        $actual = $this->service->portPayoutTrade(
            $input,
            $buyBackSellOrder,
            $buyBackBuyOrder,
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

    public function testCreateBuyBackOrderFromPaymentOrder(): void
    {
        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        static::getContainer()->set(UserRepository::class, $this->userRepositoryMock);
        $this->service = static::getContainer()->get(DivestmentPorter::class);

        $asset = EntityIdTestUtil::setEntityId(new Asset(), 76);
        $seller = EntityIdTestUtil::setEntityId(new User(), 4);
        $staff = EntityIdTestUtil::setEntityId(new User(), 6671);

        // The launch listing, just used to extract the asset and platform user/seller
        // While you could sort of derive it from the PaymentOrder
        // Having the original launch order is much more reliable
        // as you're making the reverse in this method
        $initialOrder = new TradeOrder(
            TradeDirection::Sell,
            $asset,
            $seller,
            8500,
            new Number('3.44'),
            TradeOrderType::Initial,
        );

        $paymentOrder = EntityIdTestUtil::setEntityId(new PaymentOrder(), 146);
        // method doesn't actually check
        $paymentOrder->setPaymentType(PaymentService::TYPE_INVESTMENT_EXIT);
        $paymentOrder->setAsset($asset);
        $paymentOrder->setCreatedAt(new \DateTime('2025-02-12 19:33:33'));
        $paymentOrder->setCreatedBy($staff);
        $paymentOrder->setDescription('test_exit_' . bin2hex(random_bytes(6)));

        $shareholdings = [
            1080 => '3494.82', // rounded up
            687 => '2223.09',
            884 => '2860.57',
            82 => '265.34', // rounded down
            1867 => '6041.50',
            544 => '1760.35',
            976 => '3158.28', // rounded up
            185 => '598.65', // rounded up
            2195 => '7102.89',
        ];
        // $totalPay = 27505.49;

        foreach ($shareholdings as $shares => $value) {
            $buyer = EntityIdTestUtil::setEntityId(new User(), 10 + $shares);
            $payment = EntityIdTestUtil::setEntityId(new PaymentRequest(), $shares);
            $payment->setPayee($buyer);
            $payment->setAmount($value);
            $payment->setShareholding($shares);
            $payment->setCreatedAt(new \DateTime('2025-02-12 20:45:12'));
            $payment->setUpdatedAt(new \DateTime('2025-02-12 20:45:12')->modify(
                "+{$shares} seconds",
            ));
            $paymentOrder->addPayment($payment);

            // $totalPay += $payment->getAmount();
        }

        $expected = new TradeOrder(
            TradeDirection::Buy,
            $asset,
            $seller,
            8500,
            new Number('3.235940'),
            TradeOrderType::BuyBack,
        );
        $expected->setCreatedAt($paymentOrder->getCreatedAt());
        $expected->setCreatedBy($staff);
        $expected->setNotes('port:po146 ' . $paymentOrder->getDescription());
        $activeLog = new TradeOrderStatusLog(
            $expected,
            TradeOrderStatus::Active,
            $expected->getCreatedAt(),
        );
        $completedLog = new TradeOrderStatusLog(
            $expected,
            TradeOrderStatus::Completed,
            // Should be the created time + 2195 seconds (based on the foreach loop)
            new \DateTime('2025-02-12 21:21:47'),
        );
        $expected->addStatusLog($activeLog);
        $expected->addStatusLog($completedLog);

        $this->userRepositoryMock
            ->expects(self::exactly(1))
            ->method('findOneBy')
            ->with(['username' => $paymentOrder->getCreatedBy()])
            ->willReturn($staff);

        $actual = $this->service->createBuyBackOrderFromPaymentOrder(
            $initialOrder,
            $paymentOrder,
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

    public function testcreateBuyBackOrder(): void
    {
        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        static::getContainer()->set(UserRepository::class, $this->userRepositoryMock);
        $this->service = static::getContainer()->get(DivestmentPorter::class);

        $asset = EntityIdTestUtil::setEntityId(new Asset(), 76);
        $seller = EntityIdTestUtil::setEntityId(new User(), 4);
        $staff = EntityIdTestUtil::setEntityId(new User(), 6671);

        // The launch listing, just used to extract the asset and platform user/seller
        // While you could sort of derive it from the PaymentOrder
        // Having the original launch order is much more reliable
        // as you're making the reverse in this method
        $initialOrder = new TradeOrder(
            TradeDirection::Sell,
            $asset,
            $seller,
            8500,
            new Number('3.44'),
            TradeOrderType::Initial,
        );

        $shareholdings = [
            1080 => '3494.82', // rounded up
            687 => '2223.09',
            884 => '2860.57',
            82 => '265.34', // rounded down
            1867 => '6041.50',
            544 => '1760.35',
            976 => '3158.28', // rounded up
            185 => '598.65', // rounded up
            2195 => '7102.89',
        ];
        $totalPay = \number_format(array_sum($shareholdings), 2, '.', '');
        $payouts = [];

        foreach ($shareholdings as $shares => $value) {
            $buyer = EntityIdTestUtil::setEntityId(new User(), 10 + $shares);
            $payout = EntityIdTestUtil::setEntityId(new Payout(), $shares);
            $payout->setPayoutType(1);
            // 3.23594078319 pricePerShare equivalent, rounds down to 6dp 3.235940
            $payout->setPayoutAmount($value);
            $payout->setAsset($asset);
            $payout->setCreditedUser($buyer);
            $payout->setCreatedAt(new \DateTime('2025-02-12 20:45:12')->modify(
                "+{$shares} seconds",
            ));
            $payout->setCreatedBy($staff);
            $payouts[] = $payout;
        }

        $expected = new TradeOrder(
            TradeDirection::Buy,
            $asset,
            $seller,
            8500,
            new Number('3.235940'),
            TradeOrderType::BuyBack,
        );
        // Should be the created time + 82 seconds (based on the foreach loop)
        $expected->setCreatedAt(new \DateTime('2025-02-12 20:46:34'));
        $expected->setCreatedBy($staff);
        $activeLog = new TradeOrderStatusLog(
            $expected,
            TradeOrderStatus::Active,
            // Should be the created time + 82 seconds (based on the foreach loop)
            $expected->getCreatedAt(),
        );
        $completedLog = new TradeOrderStatusLog(
            $expected,
            TradeOrderStatus::Completed,
            // Should be the created time + 2195 seconds (based on the foreach loop)
            new \DateTime('2025-02-12 21:21:47'),
        );
        $expected->addStatusLog($activeLog);
        $expected->addStatusLog($completedLog);

        $this->userRepositoryMock
            ->expects(self::exactly(1))
            ->method('findOneBy')
            ->with(['username' => $payouts[0]->getCreatedBy()])
            ->willReturn($staff);

        $actual = $this->service->createBuyBackOrder(
            $initialOrder,
            8500,
            $totalPay,
            $payouts,
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
}
