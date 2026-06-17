<?php

namespace App\Tests\Service;

use App\Entity\Asset;
use App\Entity\Enum\AllocationMethod;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderType;
use App\Entity\PaymentOrder;
use App\Entity\PaymentRequest;
use App\Entity\TradeOrder;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\PaymentGeneratorService;
use App\Service\PaymentService;
use App\Test\Util\EntityIdTestUtil;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class PaymentGeneratorServiceTest extends KernelTestCase
{
    private PaymentGeneratorService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(PaymentGeneratorService::class);
    }

    public function testGenerateDividends(): void
    {
        // Create user objects for the payee relation
        /** @var User $userStub1 */
        $userStub1 = EntityIdTestUtil::setEntityId(new User(), 178);
        /** @var User $userStub2 */
        $userStub2 = EntityIdTestUtil::setEntityId(new User(), 389);
        /** @var User $userStub3 */
        $userStub3 = EntityIdTestUtil::setEntityId(new User(), 854);
        /** @var User $userStub4 */
        $userStub4 = EntityIdTestUtil::setEntityId(new User(), 1134);

        $userRepositoryStub = $this->createStub(UserRepository::class);
        $userRepositoryStub->method('find')->willReturnOnConsecutiveCalls(
            $userStub1,
            $userStub2,
            $userStub3,
        );

        $shareholdings = [
            ['userid' => 178, 'shares' => 350],
            ['userid' => 389, 'shares' => 166],
            ['userid' => 854, 'shares' => 491],
        ];

        $paymentsExpected = [
            'payoutInfo' => [
                178 => ['userId' => 178, 'payout' => 250.70, 'shares' => 350],
                389 => ['userId' => 389, 'payout' => 118.90, 'shares' => 166],
                854 => ['userId' => 854, 'payout' => 351.70, 'shares' => 491],
            ],
        ];

        $service = new PaymentGeneratorService(
            static::getContainer()->get(\Psr\Log\LoggerInterface::class),
            $userRepositoryStub,
        );

        /** @var Asset $assetStub */
        $assetStub = EntityIdTestUtil::setEntityId(new Asset(), 10);
        $paymentOrder = new PaymentOrder();
        $paymentOrder->setPaymentType(PaymentService::TYPE_DIVIDEND);
        $paymentOrder->setAsset($assetStub);

        // Payments with no matching payee from generator should be removed
        $paymentRequest = new PaymentRequest();
        $paymentRequest->setPayee($userStub4);
        $paymentRequest->setAmount('77.42');
        $paymentRequest->setShareholding(120);
        $paymentOrder->addPayment($paymentRequest);

        // Generate the payment requests, then check the values that have been set
        $paymentOrder = $service->generateDividends(
            $paymentOrder,
            $shareholdings,
            '721.32',
        );
        $this->assertCount(3, $paymentOrder->getPayments());
        foreach ($paymentOrder->getPayments() as $payment) {
            $this->assertEquals(
                $paymentsExpected['payoutInfo'][$payment
                    ->getPayee()
                    ->getId()]['userId'],
                $payment->getPayee()->getId(),
            );
            $this->assertEquals(
                $paymentsExpected['payoutInfo'][$payment
                    ->getPayee()
                    ->getId()]['payout'],
                $payment->getAmount(),
            );
            $this->assertEquals(
                $paymentsExpected['payoutInfo'][$payment
                    ->getPayee()
                    ->getId()]['shares'],
                $payment->getShareholding(),
            );
            $this->assertEquals(PaymentRequest::STATE_PENDING, $payment->getStatus());
        }
    }

    public function testGenerateDivestments(): void
    {
        // Create user objects for the payee relation
        $userStub1 = EntityIdTestUtil::setEntityId(new User(), 178);
        $userStub2 = EntityIdTestUtil::setEntityId(new User(), 389);
        $userStub3 = EntityIdTestUtil::setEntityId(new User(), 854);
        $userStub4 = EntityIdTestUtil::setEntityId(new User(), 42);
        $userStub5 = EntityIdTestUtil::setEntityId(new User(), 612);
        $userStub6 = EntityIdTestUtil::setEntityId(new User(), 1134);

        $shareholdings = [
            ['userid' => 178, 'shares' => 2500],
            ['userid' => 389, 'shares' => 15000],
            ['userid' => 854, 'shares' => 10500],
            ['userid' => 42, 'shares' => 8500],
            ['userid' => 612, 'shares' => 3500],
        ];

        $userRepositoryStub = $this->createStub(UserRepository::class);
        // Note the ordering based on $shareholdings input is preserved
        $userRepositoryStub->method('find')->willReturnOnConsecutiveCalls(
            $userStub1,
            $userStub2,
            $userStub3,
            $userStub4,
            $userStub5,
        );

        $paymentsExpected = [
            'payoutInfo' => [
                178 => [
                    'userid' => 178,
                    'shares' => 2500,
                    'sharesDivesting' => 2500,
                    'payout' => '6146.44',
                ],
                389 => [
                    'userid' => 389,
                    'shares' => 15000,
                    'sharesDivesting' => 15000,
                    'payout' => '36878.65',
                ],
                854 => [
                    'userid' => 854,
                    'shares' => 10500,
                    'sharesDivesting' => 10500,
                    'payout' => '25815.05',
                ],
                42 => [
                    'userid' => 42,
                    'shares' => 8500,
                    'sharesDivesting' => 8500,
                    'payout' => '20897.90',
                ],
                612 => [
                    'userid' => 612,
                    'shares' => 3500,
                    'sharesDivesting' => 3500,
                    'payout' => '8605.02',
                ],
            ],
        ];

        $service = new PaymentGeneratorService(
            static::getContainer()->get(\Psr\Log\LoggerInterface::class),
            $userRepositoryStub,
        );

        /** @var Asset $assetStub */
        $assetStub = EntityIdTestUtil::setEntityId(new Asset(), 10);
        $paymentOrder = new PaymentOrder();
        $paymentOrder->setPaymentType(PaymentService::TYPE_INVESTMENT_EXIT);
        $paymentOrder->setAsset($assetStub);

        // Payments with no matching payee from generator should be removed
        $paymentRequest = new PaymentRequest();
        $paymentRequest->setPayee($userStub6);
        $paymentRequest->setAmount('77.42');
        $paymentRequest->setShareholding(120);
        $paymentOrder->addPayment($paymentRequest);

        // Generate the payment requests, then check the values that have been set
        $paymentOrder = $service->generateDivestments(
            $paymentOrder,
            $shareholdings,
            '98343.06',
            40000,
        );
        $this->assertCount(5, $paymentOrder->getPayments());
        foreach ($paymentOrder->getPayments() as $payment) {
            $this->assertEquals(
                $paymentsExpected['payoutInfo'][$payment
                    ->getPayee()
                    ->getId()]['userid'],
                $payment->getPayee()->getId(),
            );
            $this->assertEquals(
                $paymentsExpected['payoutInfo'][$payment
                    ->getPayee()
                    ->getId()]['payout'],
                $payment->getAmount(),
            );
            $this->assertEquals(
                $paymentsExpected['payoutInfo'][$payment
                    ->getPayee()
                    ->getId()]['sharesDivesting'],
                $payment->getShareholding(),
            );
            $this->assertEquals(PaymentRequest::STATE_PENDING, $payment->getStatus());
        }
    }

    public function testGenerateDivestmentsPartial(): void
    {
        // Create user objects for the payee relation
        $userStub1 = EntityIdTestUtil::setEntityId(new User(), 178);
        $userStub2 = EntityIdTestUtil::setEntityId(new User(), 389);
        $userStub3 = EntityIdTestUtil::setEntityId(new User(), 854);
        $userStub4 = EntityIdTestUtil::setEntityId(new User(), 42);
        $userStub5 = EntityIdTestUtil::setEntityId(new User(), 612);
        $userStub6 = EntityIdTestUtil::setEntityId(new User(), 1134);

        $shareholdings = [
            ['userid' => 178, 'shares' => 2500],
            ['userid' => 389, 'shares' => 15000],
            ['userid' => 854, 'shares' => 10500],
            ['userid' => 42, 'shares' => 8500],
            ['userid' => 612, 'shares' => 3500],
        ];

        $userRepositoryStub = $this->createStub(UserRepository::class);
        // Note the ordering based on $shareholdings input is preserved
        $userRepositoryStub->method('find')->willReturnOnConsecutiveCalls(
            $userStub1,
            $userStub2,
            $userStub3,
            $userStub4,
            $userStub5,
        );

        $paymentsExpected = [
            'payoutInfo' => [
                178 => [
                    'userid' => 178,
                    'shares' => 2500,
                    'sharesDivesting' => 1555,
                    'payout' => '3823.09',
                ],
                389 => [
                    'userid' => 389,
                    'shares' => 15000,
                    'sharesDivesting' => 9334,
                    'payout' => '22948.35',
                ],
                854 => [
                    'userid' => 854,
                    'shares' => 10500,
                    'sharesDivesting' => 6534,
                    'payout' => '16064.34',
                ],
                42 => [
                    'userid' => 42,
                    'shares' => 8500,
                    'sharesDivesting' => 5290,
                    'payout' => '13005.87',
                ],
                612 => [
                    'userid' => 612,
                    'shares' => 3500,
                    'sharesDivesting' => 2177,
                    'payout' => '5352.32',
                ],
            ],
        ];

        $service = new PaymentGeneratorService(
            static::getContainer()->get(\Psr\Log\LoggerInterface::class),
            $userRepositoryStub,
        );

        /** @var Asset $assetStub */
        $assetStub = EntityIdTestUtil::setEntityId(new Asset(), 10);
        $paymentOrder = new PaymentOrder();
        $paymentOrder->setPaymentType(PaymentService::TYPE_INVESTMENT_EXIT);
        $paymentOrder->setAsset($assetStub);

        // Payments with no matching payee from generator should be removed
        $paymentRequest = new PaymentRequest();
        $paymentRequest->setPayee($userStub6);
        $paymentRequest->setAmount('77.42');
        $paymentRequest->setShareholding(120);
        $paymentOrder->addPayment($paymentRequest);

        // Generate the payment requests, then check the values that have been set
        $paymentOrder = $service->generateDivestments(
            $paymentOrder,
            $shareholdings,
            '61193.97',
            24890,
        );
        $this->assertCount(5, $paymentOrder->getPayments());
        foreach ($paymentOrder->getPayments() as $payment) {
            $this->assertEquals(
                $paymentsExpected['payoutInfo'][$payment
                    ->getPayee()
                    ->getId()]['userid'],
                $payment->getPayee()->getId(),
            );
            $this->assertEquals(
                $paymentsExpected['payoutInfo'][$payment
                    ->getPayee()
                    ->getId()]['payout'],
                $payment->getAmount(),
            );
            $this->assertEquals(
                $paymentsExpected['payoutInfo'][$payment
                    ->getPayee()
                    ->getId()]['sharesDivesting'],
                $payment->getShareholding(),
            );
            $this->assertEquals(PaymentRequest::STATE_PENDING, $payment->getStatus());
        }
    }

    public function testGenerateRepayments(): void
    {
        // Create user objects for the payee relation
        $userStub1 = EntityIdTestUtil::setEntityId(new User(), 178);
        $userStub2 = EntityIdTestUtil::setEntityId(new User(), 389);

        $sellOrderU1_1 = EntityIdTestUtil::setEntityId(
            new TradeOrder(
                direction: TradeDirection::Sell,
                user: $userStub1,
                numberOfShares: 500,
                type: TradeOrderType::Prefunding,
            ),
            687,
        );
        $sellOrderU1_2 = EntityIdTestUtil::setEntityId(
            new TradeOrder(
                direction: TradeDirection::Sell,
                user: $userStub1,
                numberOfShares: 2000,
                type: TradeOrderType::Prefunding,
            ),
            991,
        );
        $sellOrderU2_1 = EntityIdTestUtil::setEntityId(
            new TradeOrder(
                direction: TradeDirection::Sell,
                user: $userStub2,
                numberOfShares: 15000,
                type: TradeOrderType::Prefunding,
            ),
            1259,
        );

        $repaymentHoldings = [
            178 => [
                'userid' => 178,
                'initialShares' => 2500,
                'repaidShares' => 500,
                'shares' => 2000,
                'sellOrders' => [$sellOrderU1_1, $sellOrderU1_2],
                'openSellOrders' => [$sellOrderU1_1, $sellOrderU1_2],
            ],
            389 => [
                'userid' => 389,
                'initialShares' => 15000,
                'repaidShares' => 4500,
                'shares' => 10500,
                'sellOrders' => [$sellOrderU2_1],
                'openSellOrders' => [$sellOrderU2_1],
            ],
        ];

        $userRepositoryStub = $this->createStub(UserRepository::class);
        // Note the ordering based on $shareholdings input is preserved
        $userRepositoryStub->method('find')->willReturnOnConsecutiveCalls(
            $userStub1,
            $userStub1,
            $userStub2,
        );

        $paymentsExpected = [
            687 => [
                'userid' => 178,
                'sharesDivesting' => 500,
                'payout' => '625.00',
            ],
            991 => [
                'userid' => 178,
                'sharesDivesting' => 1100,
                'payout' => '1375.00',
            ],
            1259 => [
                'userid' => 389,
                'sharesDivesting' => 8400,
                'payout' => '10500.00',
            ],
        ];

        $service = new PaymentGeneratorService(
            static::getContainer()->get(\Psr\Log\LoggerInterface::class),
            $userRepositoryStub,
        );

        $assetStub = EntityIdTestUtil::setEntityId(new Asset(), 10);
        $assetStub->setPricePerShare('1.25');
        $paymentOrder = new PaymentOrder();
        $paymentOrder->setPaymentType(PaymentService::TYPE_REPAYMENT);
        $paymentOrder->setAsset($assetStub);

        // Generate the payment requests, then check the values that have been set
        $paymentOrder = $service->generateRepayments(
            $paymentOrder,
            $repaymentHoldings,
            10000,
        );
        $this->assertCount(3, $paymentOrder->getPayments());
        foreach ($paymentOrder->getPayments() as $payment) {
            $this->assertEquals(
                $paymentsExpected[$payment->getTradeOrder()->getId()]['userid'],
                $payment->getPayee()->getId(),
            );
            $this->assertEquals(
                $paymentsExpected[$payment->getTradeOrder()->getId()]['payout'],
                $payment->getAmount(),
            );
            $this->assertEquals(
                $paymentsExpected[$payment
                    ->getTradeOrder()
                    ->getId()]['sharesDivesting'],
                $payment->getShareholding(),
            );
            $this->assertEquals(PaymentRequest::STATE_PENDING, $payment->getStatus());
        }
    }

    public function testAllocateDividends(): void
    {
        // Note that the input will typically be longer
        // But the method only uses these 2 fields, any others are ignored
        $input = [
            ['userid' => 178, 'shares' => 350],
            ['userid' => 389, 'shares' => 166],
            ['userid' => 854, 'shares' => 491],
        ];
        $methodAccrueExpected = [
            'payoutInfo' => [
                178 => ['userid' => 178, 'shares' => 350, 'payout' => '250.70'],
                389 => ['userid' => 389, 'shares' => 166, 'payout' => '118.90'],
                854 => ['userid' => 854, 'shares' => 491, 'payout' => '351.70'],
            ],
            'totalPayout' => '721.30',
            'totalShares' => '1007',
        ];

        $methodDistributeExpected = [
            'payoutInfo' => [
                178 => ['userid' => 178, 'shares' => 350, 'payout' => '250.71'],
                389 => ['userid' => 389, 'shares' => 166, 'payout' => '118.91'],
                854 => ['userid' => 854, 'shares' => 491, 'payout' => '351.70'],
            ],
            'totalPayout' => '721.32',
            'totalShares' => '1007',
        ];
        $actual = $this->service->allocateDividends($input, '721.32');

        $this->assertEquals($methodAccrueExpected, $actual);

        $actual = $this->service->allocateDividends(
            $input,
            '721.32',
            AllocationMethod::Distribute,
        );
        // Strip out rawPayouts so we can simply do assertEquals
        // The rawPayout is an intermeditate if using Distribute method
        // It is ignored when finally used
        foreach ($actual['payoutInfo'] as &$info) {
            unset($info['rawPayout']);
        }
        $this->assertEquals($methodDistributeExpected, $actual);
    }

    public function testAllocateDividendsExtremeProportions(): void
    {
        // For extremely small proportions, PHP will cast floats to string in scientific notation
        // Check handling for this
        $input = [
            ['userid' => 178, 'shares' => 3],
            ['userid' => 389, 'shares' => 86997],
            ['userid' => 854, 'shares' => 153000],
        ];
        $methodAccrueExpected = [
            'payoutInfo' => [
                178 => ['userid' => 178, 'shares' => 3, 'payout' => '0.01'],
                389 => ['userid' => 389, 'shares' => 86997, 'payout' => '289.99'],
                854 => ['userid' => 854, 'shares' => 153000, 'payout' => '510.00'],
            ],
            'totalPayout' => '800.00',
            'totalShares' => '240000',
        ];

        $actual = $this->service->allocateDividends($input, '800');
        $this->assertEquals($methodAccrueExpected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\Group('check')]
    public function testAllocateDivestmentsWhole(): void
    {
        // Note that the input will typically be longer
        // But the method only uses these 2 fields, any others are ignored
        $input = [
            ['userid' => 178, 'shares' => 2500],
            ['userid' => 389, 'shares' => 15000],
            ['userid' => 854, 'shares' => 10500],
            ['userid' => 42, 'shares' => 8500],
            ['userid' => 612, 'shares' => 3500],
        ];

        // Price per share equivalent of around 2.4585765 (7dp, beyond the 6 we store)
        // As the share price is 7dp decimal, the rawPayout will be the same 7dp
        $expected = [
            'payoutInfo' => [
                178 => [
                    'userid' => 178,
                    'shares' => 2500,
                    'sharesDivesting' => 2500,
                    'payout' => '6146.44',
                    'rawPayout' => '6146.4412500',
                ],
                // Expecting 1p to be left over, this one is the closest to next penny
                389 => [
                    'userid' => 389,
                    'shares' => 15000,
                    'sharesDivesting' => 15000,
                    'payout' => '36878.65',
                    'rawPayout' => '36878.6475000',
                ],
                854 => [
                    'userid' => 854,
                    'shares' => 10500,
                    'sharesDivesting' => 10500,
                    'payout' => '25815.05',
                    'rawPayout' => '25815.0532500',
                ],
                42 => [
                    'userid' => 42,
                    'shares' => 8500,
                    'sharesDivesting' => 8500,
                    'payout' => '20897.90',
                    'rawPayout' => '20897.9002500',
                ],
                612 => [
                    'userid' => 612,
                    'shares' => 3500,
                    'sharesDivesting' => 3500,
                    'payout' => '8605.02',
                    'rawPayout' => '8605.0177500',
                ],
            ],
            'totalPayout' => '98343.06',
            'totalShares' => '40000',
        ];
        $actual = $this->service->allocateDivestments($input, '98343.06', 40000);

        $this->assertEquals($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\Group('check')]
    public function testAllocateDivestmentsPartial(): void
    {
        // Note that the input will typically be longer
        // But the method only uses these 2 fields, any others are ignored
        $input = [
            ['userid' => 178, 'shares' => 2500],
            ['userid' => 389, 'shares' => 15000],
            ['userid' => 854, 'shares' => 10500],
            ['userid' => 42, 'shares' => 8500],
            ['userid' => 612, 'shares' => 3500],
        ];

        // As the share price is 7dp decimal, the rawPayout will be the same 7dp
        $expected = [
            'payoutInfo' => [
                178 => [
                    'userid' => 178,
                    'shares' => 2500,
                    'sharesDivesting' => 1555,
                    'payout' => '3823.09',
                    // 'rawPayout' => '3823.08 6514664523905182804339092', 3rd place
                ],
                // Largest shareholder gets the remaining shares after proportional split - gets a penny
                389 => [
                    'userid' => 389,
                    'shares' => 15000,
                    'sharesDivesting' => 9334,
                    'payout' => '22948.35',
                    // 'rawPayout' => '22948.35 3394134190437926878264363',
                ],
                854 => [
                    'userid' => 854,
                    'shares' => 10500,
                    'sharesDivesting' => 6534,
                    'payout' => '16064.34',
                    // 'rawPayout' => '16064.33 9091201285656890317396545', // 2nd place - gets a penny
                ],
                42 => [
                    'userid' => 42,
                    'shares' => 8500,
                    'sharesDivesting' => 5290,
                    'payout' => '13005.87',
                    // 'rawPayout' => '13005.86 9879469666532744073925271', // 1st place - gets a penny
                ],
                612 => [
                    'userid' => 612,
                    'shares' => 3500,
                    'sharesDivesting' => 2177,
                    'payout' => '5352.32',
                    // 'rawPayout' => '5352.32 11205303334672559260747288',
                ],
            ],
            'totalPayout' => '61193.97',
            'totalShares' => '24890',
        ];
        // 0.62225 of all shares being liquidated
        // equivalent share price of 2.4585765367617...
        // 24,887 shares initially allocated, so top 3 shareholders get additional shares
        // 61193.94 initially allocated for payment, so 3p is distributed to closest rounds

        $actual = $this->service->allocateDivestments($input, '61193.97', 24890);

        // Strip out rawPayouts so we can simply do assertEquals
        // It is ignored when finally used
        foreach ($actual['payoutInfo'] as &$info) {
            unset($info['rawPayout']);
        }
        $this->assertEquals($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\Group('check')]
    public function testAllocateDivestmentsExtremes(): void
    {
        // Note that the input will typically be longer
        // But the method only uses these 2 fields, any others are ignored
        $input = [
            ['userid' => 178, 'shares' => 2500],
            ['userid' => 389, 'shares' => 15000],
            ['userid' => 854, 'shares' => 10500],
            ['userid' => 42, 'shares' => 8500],
            ['userid' => 612, 'shares' => 3500],
        ];

        $expectedSmall = [
            'payoutInfo' => [
                178 => [
                    'userid' => 178,
                    'shares' => 2500,
                    'sharesDivesting' => '0',
                    'payout' => '0.00',
                ],
                389 => [
                    'userid' => 389,
                    'shares' => 15000,
                    'sharesDivesting' => '2',
                    // payout amounts are just given in array order if tie-breaker
                    // So largest shareholder gets the penny
                    'payout' => '5.94',
                ],
                854 => [
                    'userid' => 854,
                    'shares' => 10500,
                    'sharesDivesting' => '2',
                    'payout' => '5.93',
                ],
                42 => [
                    'userid' => 42,
                    'shares' => 8500,
                    'sharesDivesting' => '0',
                    'payout' => '0.00',
                ],
                612 => [
                    'userid' => 612,
                    'shares' => 3500,
                    'sharesDivesting' => '0',
                    'payout' => '0.00',
                ],
            ],
            'totalPayout' => '11.87',
            'totalShares' => '4',
        ];
        $expectedNearFull = [
            'payoutInfo' => [
                178 => [
                    'userid' => 178,
                    'shares' => 2500,
                    'sharesDivesting' => '2499', // 2499 + 0
                    'payout' => '6247.50',
                ],
                389 => [
                    'userid' => 389,
                    'shares' => 15000,
                    'sharesDivesting' => '14999', // 14998 + 1
                    'payout' => '37497.50',
                ],
                854 => [
                    'userid' => 854,
                    'shares' => 10500,
                    'sharesDivesting' => '10500', // 10499 + 1
                    'payout' => '26250.00',
                ],
                42 => [
                    'userid' => 42,
                    'shares' => 8500,
                    'sharesDivesting' => '8500', // 8499 + 1
                    'payout' => '21250.00',
                ],
                612 => [
                    'userid' => 612,
                    'shares' => 3500,
                    'sharesDivesting' => '3499', // 3499 + 0
                    'payout' => '8747.50',
                ],
            ],
            'totalPayout' => '99992.50', // 2.5 a share
            'totalShares' => '39997', // 39994 allocated on initial run, 3 leftover
        ];

        $actual = $this->service->allocateDivestments($input, '11.87', 4);
        // Strip out rawPayouts so we can simply do assertEquals
        foreach ($actual['payoutInfo'] as &$info) {
            unset($info['rawPayout']);
        }
        $this->assertEquals($expectedSmall, $actual);

        $actual = $this->service->allocateDivestments($input, '99992.50', 39997);
        // Strip out rawPayouts so we can simply do assertEquals
        foreach ($actual['payoutInfo'] as &$info) {
            unset($info['rawPayout']);
        }
        $this->assertEquals($expectedNearFull, $actual);
    }

    #[\PHPUnit\Framework\Attributes\Group('check')]
    #[\PHPUnit\Framework\Attributes\DataProvider('splitPartialDivestmentProvider')]
    public function testSplitPartialDivestments(
        int $sharesToSplit,
        ?array $expected = null,
    ): void {
        // 72455 is the total shares in circulation
        $shareholdings = [
            '581' => '12052',
            '6780' => '45000',
            '823' => '6784',
            '8752' => '3530',
            '2388' => '5089',
        ];
        if ($expected === null) {
            $expected = $shareholdings;
            $expectedTotal = array_sum($shareholdings);
        } else {
            $expectedTotal = $sharesToSplit;
        }
        $actual = $this->service->splitPartialDivestments(
            $shareholdings,
            $sharesToSplit,
        );
        $this->assertEquals($expected, $actual);
        $this->assertEquals($expectedTotal, array_sum($actual));
    }

    public static function splitPartialDivestmentProvider(): \Generator
    {
        $extremeOneLeft = [
            '581' => '12052',
            '6780' => '45000',
            '823' => '6784',
            // Distributions in order of shareholding, smallest loses out
            '8752' => '3529',
            '2388' => '5089',
        ];
        $extremeOneSplit = [
            '581' => '0',
            // Distributions in order of shareholding, largest gets the share
            '6780' => '1',
            '823' => '0',
            '8752' => '0',
            '2388' => '0',
        ];
        $typicalMidSplit = [
            // 2 is left over, 2 largest get 1 each
            '581' => '5966',
            '6780' => '22276',
            '823' => '3358',
            '8752' => '1747',
            '2388' => '2519',
        ];
        yield 'One remaining' => [72454, $extremeOneLeft];
        yield 'Split too many' => [72456, null];
        yield 'Extreme low split' => [1, $extremeOneSplit];
        yield 'Typical midway split' => [35866, $typicalMidSplit];
    }

    public function testDistributeLeftovers(): void
    {
        // Distributing 7p, 1p to 7 different people
        // generate a float with tenths of a penny to simulate imprecise floats
        // This checks that the conversion to penny integer is correct
        $leftoverPot = 70000000 / 1000000001;
        // echo PHP_EOL . $leftoverPot;                     // 0.06999999993
        // echo PHP_EOL . (int) ($leftoverPot * 100);       // 6
        // echo PHP_EOL . (int) round($leftoverPot * 100);  // 7

        // The rawPayouts will be padded to the same length before comparison
        // This ensures they're ordered correctly
        $assetPayouts = [
            ['payout' => 0.71, 'rawPayout' => '0.707052'], // 4
            ['payout' => 0.42, 'rawPayout' => '0.42562'], // 7
            ['payout' => 0.56, 'rawPayout' => '0.5657641'], // 6
            ['payout' => 0.12, 'rawPayout' => '0.12112'], // 10
            ['payout' => 0.29, 'rawPayout' => '0.29643'], // 5
            ['payout' => 0.90, 'rawPayout' => '0.90964661'], // 1
            ['payout' => 0.27, 'rawPayout' => '0.27555'], // 8
            ['payout' => 0.64, 'rawPayout' => '0.643672'], // 9
            ['payout' => 0.83, 'rawPayout' => '0.837856'], // 2
            ['payout' => 0.42, 'rawPayout' => '0.42784'], // 3
        ];

        // Top 7 will get extra penny
        $expected = [
            ['payout' => 0.72, 'rawPayout' => '0.707052'],
            ['payout' => 0.43, 'rawPayout' => '0.42562'],
            ['payout' => 0.57, 'rawPayout' => '0.5657641'],
            ['payout' => 0.12, 'rawPayout' => '0.12112'],
            ['payout' => 0.30, 'rawPayout' => '0.29643'],
            ['payout' => 0.91, 'rawPayout' => '0.90964661'],
            ['payout' => 0.27, 'rawPayout' => '0.27555'],
            ['payout' => 0.64, 'rawPayout' => '0.643672'],
            ['payout' => 0.84, 'rawPayout' => '0.837856'],
            ['payout' => 0.43, 'rawPayout' => '0.42784'],
        ];
        $actual = $this->service->distributeLeftovers($leftoverPot, $assetPayouts);
        foreach ($actual as $index => $item) {
            $this->assertEqualsWithDelta(
                $expected[$index]['payout'],
                $item['payout'],
                0.001,
            );
            $this->assertEquals($expected[$index]['rawPayout'], $item['rawPayout']);
        }

        // $this->assertEquals($expected, $actual);
    }

    public function testDistributeLeftoversTooMuchLeftover(): void
    {
        // You cannot distribute 7p across 4 payments where each payment gets 1p
        // Implies something went wrong during initial payment calculations
        $leftoverPot = 0.07;
        $assetPayouts = [
            ['payout' => 0.71, 'rawPayout' => '0.70705'],
            ['payout' => 0.42, 'rawPayout' => '0.42562'],
            ['payout' => 0.56, 'rawPayout' => '0.56576'],
            ['payout' => 0.12, 'rawPayout' => '0.12112'],
        ];
        $this->expectExceptionMessage('Not enough payments to distribute 7p');
        $actual = $this->service->distributeLeftovers($leftoverPot, $assetPayouts);
    }
}
