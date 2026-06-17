<?php

namespace App\Tests\Service;

use App\Entity\Asset;
use App\Entity\Enum\TransferType;
use App\Entity\Investment;
use App\Entity\Offering;
use App\Entity\TransferOrder;
use App\Entity\TransferRequest;
use App\Entity\User;
use App\Repository\OfferingRepository;
use App\Service\AppSettingService;
use App\Service\FeeCollectionService;
use App\Service\MonthEndService;
use App\Test\Util\EntityIdTestUtil;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class FeeCollectionServiceTest extends KernelTestCase
{
    private FeeCollectionService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(FeeCollectionService::class);
    }

    public function testGetFeeWallets(): void
    {
        $feeWallets = [
            'yieldersFeeWallet' => bin2hex(random_bytes(8)),
            'ypmlFeeWallet' => bin2hex(random_bytes(8)),
        ];
        $appSettingServiceMock = $this->createMock(AppSettingService::class);
        $appSettingServiceMock
            ->expects($this->any())
            ->method('getMultiple')
            ->with(['yieldersFeeWallet', 'ypmlFeeWallet'])
            ->willReturn($feeWallets);
        $service = new FeeCollectionService(
            static::getContainer()->get(LoggerInterface::class),
            static::getContainer()->get(MonthEndService::class),
            $appSettingServiceMock,
            static::getContainer()->get(OfferingRepository::class),
        );
        $actual = $service->getFeeWallets();
        $this->assertEquals($feeWallets, $actual);

        // If additional wallets are provided, should append them to the end
        $additionalWallets = [
            'alt' => bin2hex(random_bytes(8)),
            'superadmin' => bin2hex(random_bytes(8)),
        ];
        $expected = array_merge($feeWallets, $additionalWallets);
        $actual = $service->getFeeWallets($additionalWallets);
        $this->assertEquals($expected, $actual);
    }

    public function testGenerateRelistingFeeTransfers(): void
    {
        $asset1 = EntityIdTestUtil::setEntityId(new Asset(), 45);
        $asset1->setHoldWalletId(bin2hex(random_bytes(8)));
        $asset1->setName('Relisting Test Asset 1');
        $asset1->setCompanyNumber('SPVRFT001');
        $asset2 = EntityIdTestUtil::setEntityId(new Asset(), 182);
        $asset2->setHoldWalletId(bin2hex(random_bytes(8)));
        $asset2->setName('Relisting Test Asset 2');
        $asset2->setCompanyNumber('SPVRFT002');

        $relisting1 = EntityIdTestUtil::setEntityId(new Offering(), 445);
        $relisting1->setAsset($asset1);
        $relisting2 = EntityIdTestUtil::setEntityId(new Offering(), 1241);
        $relisting2->setAsset($asset2);

        $feeSummary = [
            45 => [
                'relistings' => [
                    7825 => [$relisting1],
                ],
                'totalRelistingFees' => '15',
            ],
            182 => [
                'relistings' => [
                    4826 => [$relisting2],
                ],
                'totalRelistingFees' => '255',
            ],
        ];
        $transferOrder = new TransferOrder();
        $transferOrder->setScheduledFor(new \DateTime('2020-04-08'));
        $this->service->generateRelistingFeeTransfers(
            $transferOrder,
            $feeSummary,
            'testRelistingFeeWallet',
        );

        // Should end up with 2 transfers generated
        $transfer1 = $transferOrder->getTransfers()[0];
        $this->assertSame('15', $transfer1->getAmount());
        $this->assertSame($asset1->getHoldWalletId(), $transfer1->getDebitWalletId());
        $this->assertSame('testRelistingFeeWallet', $transfer1->getCreditWalletId());
        $this->assertSame(
            'Relisting fees;SPVRFT001 Relisting Test Asset 1;For month 2020-03',
            $transfer1->getDescription(),
        );
        $this->assertSame($asset1->getId(), $transfer1->getAsset()?->getId());

        $transfer2 = $transferOrder->getTransfers()[1];
        $this->assertSame('255', $transfer2->getAmount());
        $this->assertSame($asset2->getHoldWalletId(), $transfer2->getDebitWalletId());
        $this->assertSame('testRelistingFeeWallet', $transfer2->getCreditWalletId());
        $this->assertSame(
            'Relisting fees;SPVRFT002 Relisting Test Asset 2;For month 2020-03',
            $transfer2->getDescription(),
        );
        $this->assertSame($asset2->getId(), $transfer2->getAsset()?->getId());
    }

    public function testEstimateRelistingFees(): void
    {
        $user1 = EntityIdTestUtil::setEntityId(new User(), 7825);
        $user2 = EntityIdTestUtil::setEntityId(new User(), 4826);
        $user3 = EntityIdTestUtil::setEntityId(new User(), 2143);

        $investment1 = EntityIdTestUtil::setEntityId(new Investment(), 55713);
        $investment1->setUser($user1);
        $investment2 = EntityIdTestUtil::setEntityId(new Investment(), 77122);
        $investment2->setUser($user2);
        $investment3 = EntityIdTestUtil::setEntityId(new Investment(), 16923);
        $investment3->setUser($user3);

        $asset1 = EntityIdTestUtil::setEntityId(new Asset(), 45);
        $asset2 = EntityIdTestUtil::setEntityId(new Asset(), 182);

        $relisting1 = EntityIdTestUtil::setEntityId(new Offering(), 445);
        $relisting1->setAsset($asset1);
        $relisting1->setSellInvestment($investment1);
        $relisting1->setFundingGoal('578.87');
        $relisting2 = EntityIdTestUtil::setEntityId(new Offering(), 1241);
        $relisting2->setAsset($asset2);
        $relisting2->setSellInvestment($investment2);
        $relisting2->setFundingGoal('178.22');
        $relisting3 = EntityIdTestUtil::setEntityId(new Offering(), 5241);
        $relisting3->setAsset($asset2);
        $relisting3->setSellInvestment($investment3);
        $relisting3->setFundingGoal('2578.4');
        $relisting4 = EntityIdTestUtil::setEntityId(new Offering(), 5249);
        $relisting4->setAsset($asset2);
        $relisting4->setSellInvestment($investment3);
        $relisting4->setFundingGoal('478.11');

        $expected = [
            45 => [
                'relistings' => [
                    7825 => [$relisting1],
                ],
                'totalRelistingFees' => '15',
                'userSummary' => [
                    7825 => [
                        'amount' => '578.87',
                        'fee' => '15',
                    ],
                ],
            ],
            182 => [
                'relistings' => [
                    4826 => [$relisting2],
                    2143 => [$relisting3, $relisting4],
                ],
                'totalRelistingFees' => '50',
                'userSummary' => [
                    4826 => [
                        'amount' => '178.22',
                        'fee' => '10',
                    ],
                    2143 => [
                        'amount' => '3056.51',
                        'fee' => '40',
                    ],
                ],
            ],
        ];
        $actual = $this->service->estimateRelistingFees([
            $relisting1,
            $relisting2,
            $relisting3,
            $relisting4,
        ]);
        $this->assertSame($expected, $actual);
    }

    public function testGroupMonthlyRelistings(): void
    {
        $user1 = EntityIdTestUtil::setEntityId(new User(), 7825);
        $user2 = EntityIdTestUtil::setEntityId(new User(), 4826);
        $user3 = EntityIdTestUtil::setEntityId(new User(), 2143);

        $investment1 = EntityIdTestUtil::setEntityId(new Investment(), 55713);
        $investment1->setUser($user1);
        $investment2 = EntityIdTestUtil::setEntityId(new Investment(), 77122);
        $investment2->setUser($user2);
        $investment3 = EntityIdTestUtil::setEntityId(new Investment(), 16923);
        $investment3->setUser($user3);

        $asset1 = EntityIdTestUtil::setEntityId(new Asset(), 45);
        $asset2 = EntityIdTestUtil::setEntityId(new Asset(), 182);

        $relisting1 = EntityIdTestUtil::setEntityId(new Offering(), 445);
        $relisting1->setAsset($asset1);
        $relisting1->setSellInvestment($investment1);
        $relisting2 = EntityIdTestUtil::setEntityId(new Offering(), 1241);
        $relisting2->setAsset($asset2);
        $relisting2->setSellInvestment($investment2);
        $relisting3 = EntityIdTestUtil::setEntityId(new Offering(), 5241);
        $relisting3->setAsset($asset2);
        $relisting3->setSellInvestment($investment3);
        $relisting4 = EntityIdTestUtil::setEntityId(new Offering(), 5249);
        $relisting4->setAsset($asset2);
        $relisting4->setSellInvestment($investment3);

        $expected = [
            45 => [
                'relistings' => [
                    7825 => [$relisting1],
                ],
            ],
            182 => [
                'relistings' => [
                    4826 => [$relisting2],
                    2143 => [$relisting3, $relisting4],
                ],
            ],
        ];
        $actual = $this->service->groupMonthlyRelistings([
            $relisting1,
            $relisting2,
            $relisting3,
            $relisting4,
        ]);
        $this->assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('assetWithWalletsProvider')]
    public function testCalculateRelistingFee(
        array $feeBands,
        User $user,
        string $amountRelisted,
        string $expected,
    ): void {
        $actual = $this->service->calculateRelistingFee(
            $amountRelisted,
            $feeBands,
            $user,
        );
        $this->assertSame($expected, $actual);
    }

    public static function assetWithWalletsProvider(): \Generator
    {
        $nonVipUser = new User();
        $vipUser = new User();
        $vipUser->setisVIP(1);

        $defaultFees = Asset::DEFAULT_RELISTING_FEES;
        $nilFees = [0 => 0];
        $customFees = [
            0 => 12.5,
            1000 => 100,
        ];

        yield 'Default fee band 1' => [$defaultFees, $nonVipUser, '300', '10'];
        yield 'Default fee band 2' => [$defaultFees, $nonVipUser, '800', '15'];
        yield 'Default fee band 3' => [$defaultFees, $nonVipUser, '800.01', '40'];
        yield 'VIP band 1' => [$defaultFees, $vipUser, '300', '0'];
        yield 'VIP band 2' => [$defaultFees, $vipUser, '800', '0'];
        yield 'VIP band 3' => [$defaultFees, $vipUser, '800.01', '0'];
        yield 'Nil fee' => [$nilFees, $nonVipUser, '3000', '0'];
        yield 'Custom fee band 1' => [$customFees, $nonVipUser, '1000', '12.5'];
        yield 'Custom fee band 2' => [$customFees, $nonVipUser, '1000.01', '100'];
    }

    public function testRegenerateDescriptions(): void
    {
        $asset1 = new Asset();
        $asset1->setCompanyNumber('SPVAT001822');
        $asset1->setName('Test regendesc asset one');
        $asset2 = new Asset();
        $asset2->setCompanyNumber('SPVAT02500A');
        $asset2->setName('Test regendesc asset two');

        $transferOrder = new TransferOrder();
        $transferOrder->setScheduledFor(new \DateTime('2020-09-29'));

        $requests = [
            [
                'description' => 'Mystery to enigmatic',
                'asset' => null,
                'newDescription' => 'Mystery to enigmatic;SPVAT001822 Test regendesc asset one;For month 2020-08',
            ],
            [
                'description' => 'Relisting fee;For month 2020-05',
                'asset' => $asset1,
                'newDescription' => 'Relisting fee;SPVAT001822 Test regendesc asset one;For month 2020-08',
            ],
            [
                'description' => 'Deposit rental income;SPVAT02500A Test regendesc asset two;For month 2018-05',
                'asset' => $asset2,
                'newDescription' => 'Deposit rental income;SPVAT02500A Test regendesc asset two;For month 2020-08',
            ],
        ];
        foreach ($requests as $template) {
            $request = new TransferRequest();
            $request->setDescription($template['description']);
            if (!is_null($template['asset'])) {
                $request->setAsset($template['asset']);
            }
            $transferOrder->addTransfer($request);
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Transfer order or one or more requests missing an asset relation.',
        );
        $this->service->regenerateDescriptions($transferOrder);

        $transferOrder->setAsset($asset1);
        $this->service->regenerateDescriptions($transferOrder);

        foreach ($transferOrder->getTransfers() as $index => $transfer) {
            $this->assertEquals(
                $requests[$index]['newDescription'],
                $transfer->getDescription(),
            );
        }
    }

    public function testCollectFeesFromTransferOrder(): void
    {
        $asset1 = new Asset();
        $asset1->setName('Income to fee test asset 1');
        $asset1->setCompanyNumber('SPVTAF10101');
        $asset2 = new Asset();
        $asset2->setName('Income to fee test asset 2');
        $asset2->setCompanyNumber('SPVTAF20202');
        $feeCollection = new TransferOrder();
        $feeCollection->setTransferType(TransferType::FeeCollection);
        $feeCollection->setScheduledFor(new \DateTime('2022-01-05'));
        $incomeDisaggregation = new TransferOrder();
        $incomeDisaggregation->setTransferType(TransferType::IncomeDisaggregation);

        $incomeDeposits = [
            [
                'asset' => $asset1,
                'description' => 'Deposit rental income',
                'debit' => 'COMMONINCOMEWLT101',
                'credit' => 'AST1CRDT801',
                'amount' => '780.58',
                'fee' => '78.06',
                'feeDescription' => 'Yielders management fees;SPVTAF10101 Income to fee test asset 1;For month 2021-12',
            ],
            [
                'asset' => $asset2,
                'description' => 'Deposit rental income with reduction',
                'debit' => 'COMMONINCOMEWLT101',
                'credit' => 'AST2CRDT572',
                'amount' => '2818.75',
                'fee' => '281.88',
                'feeDescription' => 'Yielders management fees;SPVTAF20202 Income to fee test asset 2;For month 2021-12',
            ],
        ];
        foreach ($incomeDeposits as $deposit) {
            $grossIncome = new TransferRequest();
            $grossIncome->setAsset($deposit['asset']);
            $grossIncome->setDescription($deposit['description']);
            $grossIncome->setDebitWalletId($deposit['debit']);
            $grossIncome->setCreditWalletId($deposit['credit']);
            $grossIncome->setAmount($deposit['amount']);
            $incomeDisaggregation->addTransfer($grossIncome);
        }

        $actual = $this->service->collectFeesFromTransferOrder(
            $feeCollection,
            $incomeDisaggregation,
            'ITFTWLT100',
            0.1,
            'Yielders management fees',
        );

        foreach ($actual->getTransfers() as $index => $transfer) {
            $this->assertEquals(
                $incomeDeposits[$index]['asset'],
                $transfer->getAsset(),
            );
            $this->assertEquals(
                $incomeDeposits[$index]['feeDescription'],
                $transfer->getDescription(),
            );
            $this->assertEquals(
                $incomeDeposits[$index]['credit'],
                $transfer->getDebitWalletId(),
            );
            $this->assertEquals('ITFTWLT100', $transfer->getCreditWalletId());
            $this->assertEquals($incomeDeposits[$index]['fee'], $transfer->getAmount());
        }

        // exception if no asset set on any transfer request being relayed
        $grossIncome3 = new TransferRequest();
        $grossIncome3->setDescription('Legacy assetless deposit');
        $grossIncome3->setDebitWalletId('TSTDBT1');
        $grossIncome3->setCreditWalletId('TSTCRDT1');
        $grossIncome3->setAmount('100');
        $incomeDisaggregation->addTransfer($grossIncome3);
        $this->expectException(\InvalidArgumentException::class);
        $actual = $this->service->collectFeesFromTransferOrder(
            $feeCollection,
            $incomeDisaggregation,
            'ITFTWLT100',
            10,
            'Yielders management fees',
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('feeGuessProvider')]
    public function testGuessFeeBeingCollected(
        ?string $expected,
        array $descriptions,
    ): void {
        $asset1 = new Asset();
        $asset1->setName('Description guess asset');
        $asset1->setCompanyNumber('SPVTAF10101');
        $feeCollection = new TransferOrder();
        $feeCollection->setTransferType(TransferType::FeeCollection);
        $feeCollection->setScheduledFor(new \DateTime('2022-01-05'));

        foreach ($descriptions as $description) {
            $transfer = new TransferRequest();
            $transfer->setAsset($asset1);
            $transfer->setDescription(
                "{$description};SPVTAF10101 Description guess asset;For month 2021-12",
            );
            $transfer->setDebitWalletId('testDebit');
            $transfer->setCreditWalletId('testCredit');
            $transfer->setAmount('1');
            $feeCollection->addTransfer($transfer);
        }

        $actual = $this->service->guessFeeBeingCollected($feeCollection);

        $this->assertEquals($expected, $actual);
    }

    public static function feeGuessProvider(): \Generator
    {
        yield 'Empty' => [null, []];
        yield 'Custom' => [
            null,
            [
                'Yielders management fees',
                'Yielders management fees',
                'Relisting fees',
                'Custom Fees',
            ],
        ];
        yield 'Duo' => [
            'Collect Yielders fees',
            [
                'Yielders management fees',
                'Yielders management fees',
                'Relisting fees',
                'Relisting fees',
                'Yielders management fees',
            ],
        ];
        yield 'Only relisting' => [
            'Collect relisting fees',
            [
                'Relisting fees',
                'Relisting fees',
                'Relisting fees',
            ],
        ];
        yield 'Only management' => [
            'Collect Yielders management fees',
            [
                'Yielders management fees',
                'Yielders management fees',
                'Yielders management fees',
            ],
        ];
        yield 'Only ypml' => [
            'Collect YPML fees',
            [
                'Yielders Property Management Ltd fees',
                'Yielders Property Management Ltd fees',
            ],
        ];
    }
}
