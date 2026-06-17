<?php

namespace App\Tests\Service;

use App\Entity\AbstractOrder;
use App\Entity\Asset;
use App\Entity\Enum\PaymentType;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TransferMode;
use App\Entity\Enum\TransferOrderPreset;
use App\Entity\Enum\TransferType;
use App\Entity\Investment;
use App\Entity\Offering;
use App\Entity\PaymentOrder;
use App\Entity\PaymentRequest;
use App\Entity\ShareTrade;
use App\Entity\TradeOrder;
use App\Entity\TransferOrder;
use App\Entity\TransferRequest;
use App\Entity\User;
use App\Service\MonthEndService;
use App\Test\Util\EntityIdTestUtil;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MonthEndServiceTest extends KernelTestCase
{
    private MonthEndService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(MonthEndService::class);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('assetWithWalletsProvider')]
    public function testGetAssetWalletChoices(Asset $asset, array $expected): void
    {
        $actual = $this->service->getAssetWalletChoices($asset);
        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    public static function assetWithWalletsProvider(): \Generator
    {
        $assetOneWallet = new Asset();
        $assetOneWallet->setExpensesWalletId(bin2hex(random_bytes(16)));
        $assetMultiWallet = new Asset();
        $assetMultiWallet->setExpensesWalletId(bin2hex(random_bytes(16)));
        $assetMultiWallet->setTaxWalletId(bin2hex(random_bytes(16)));
        $assetMultiWallet->setTreasuryWalletId(bin2hex(random_bytes(16)));
        $assetAllWallet = new Asset();
        $assetAllWallet->setHoldWalletId(bin2hex(random_bytes(16)));
        $assetAllWallet->setSettlementWalletId(bin2hex(random_bytes(16)));
        $assetAllWallet->setDepositWalletId(bin2hex(random_bytes(16)));
        $assetAllWallet->setExpensesWalletId(bin2hex(random_bytes(16)));
        $assetAllWallet->setTaxWalletId(bin2hex(random_bytes(16)));
        $assetAllWallet->setDistributionWalletId(bin2hex(random_bytes(16)));
        $assetAllWallet->setTreasuryWalletId(bin2hex(random_bytes(16)));

        yield 'No new wallets' => [new Asset(), []];
        yield 'One new wallet' => [
            $assetOneWallet,
            ['expenses' => $assetOneWallet->getExpensesWalletId()],
        ];
        yield 'Multiple new wallets' => [
            $assetMultiWallet,
            [
                'expenses' => $assetMultiWallet->getExpensesWalletId(),
                'tax' => $assetMultiWallet->getTaxWalletId(),
                'treasury' => $assetMultiWallet->getTreasuryWalletId(),
            ],
        ];
        yield 'All new wallets' => [
            $assetAllWallet,
            [
                'hold' => $assetAllWallet->getHoldWalletId(),
                'settlement' => $assetAllWallet->getSettlementWalletId(),
                'deposit' => $assetAllWallet->getDepositWalletId(),
                'expenses' => $assetAllWallet->getExpensesWalletId(),
                'tax' => $assetAllWallet->getTaxWalletId(),
                'distribution' => $assetAllWallet->getDistributionWalletId(),
                'treasury' => $assetAllWallet->getTreasuryWalletId(),
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('walletChoicesProvider')]
    public function testCreateDefaultTransferOrderPlan(
        array $walletChoices,
        array $expected,
    ): void {
        $actual = $this->service->createDefaultIncomeTransferPlan($walletChoices);
        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    public static function walletChoicesProvider(): \Generator
    {
        $taxWalletId = bin2hex(random_bytes(16));
        $treasuryWalletId = bin2hex(random_bytes(16));
        $expensesWalletId = bin2hex(random_bytes(16));
        $distributionWalletId = bin2hex(random_bytes(16));
        yield 'No wallets' => [[], []];
        yield 'Some wallets' => [
            [
                'tax' => $taxWalletId,
                'expenses' => $expensesWalletId,
            ],
            [
                [
                    'creditWalletId' => $taxWalletId,
                    'description' => MonthEndService::DESCRIPTION_PRESETS['corptax'],
                ],
                [
                    'creditWalletId' => $expensesWalletId,
                    'description' =>
                        MonthEndService::DESCRIPTION_PRESETS['accountancy'],
                ],
                [
                    'creditWalletId' => $expensesWalletId,
                    'description' => MonthEndService::DESCRIPTION_PRESETS['insurance'],
                ],
                [
                    'creditWalletId' => $expensesWalletId,
                    'description' => MonthEndService::DESCRIPTION_PRESETS['management'],
                ],
            ],
        ];
        yield 'All wallets' => [
            [
                'tax' => $taxWalletId,
                'expenses' => $expensesWalletId,
                'treasury' => $treasuryWalletId,
                'distribution' => $distributionWalletId,
            ],
            [
                [
                    'creditWalletId' => $taxWalletId,
                    'description' => MonthEndService::DESCRIPTION_PRESETS['corptax'],
                ],
                [
                    'creditWalletId' => $expensesWalletId,
                    'description' =>
                        MonthEndService::DESCRIPTION_PRESETS['accountancy'],
                ],
                [
                    'creditWalletId' => $expensesWalletId,
                    'description' => MonthEndService::DESCRIPTION_PRESETS['insurance'],
                ],
                [
                    'creditWalletId' => $treasuryWalletId,
                    'description' =>
                        MonthEndService::DESCRIPTION_PRESETS['maintenance'],
                ],
                [
                    'creditWalletId' => $distributionWalletId,
                    'description' => MonthEndService::DESCRIPTION_PRESETS['dividend'],
                ],
                [
                    'creditWalletId' => $expensesWalletId,
                    'description' => MonthEndService::DESCRIPTION_PRESETS['management'],
                ],
            ],
        ];
    }

    public function testApplyTemplateToTransferOrder(): void
    {
        $taxWalletId = bin2hex(random_bytes(16));
        $treasuryWalletId = bin2hex(random_bytes(16));
        $expensesWalletId = bin2hex(random_bytes(16));
        $depositWalletId = bin2hex(random_bytes(16));
        $assetSpv = 'SPVT' . bin2hex(random_bytes(4));
        $date = new \DateTime('first day of this month');
        $dateAsYm = $date->format('Y-m');

        // Note that the pennies are not rounded to until stored in database
        // This method does NOT store into database, only creates the object
        $transfers = [
            MonthEndService::DESCRIPTION_PRESETS['corptax'] => '223.942',
            MonthEndService::DESCRIPTION_PRESETS['accountancy'] => '85.1',
            MonthEndService::DESCRIPTION_PRESETS['maintenance'] => '108.12',
            'Loan repayment' => '14',
        ];
        $transferPlan = [
            [
                'creditWalletId' => $taxWalletId,
                'description' => MonthEndService::DESCRIPTION_PRESETS['corptax'],
                'amount' => $transfers[MonthEndService::DESCRIPTION_PRESETS['corptax']],
            ],
            [
                'creditWalletId' => $expensesWalletId,
                'description' => MonthEndService::DESCRIPTION_PRESETS['accountancy'],
                'amount' =>
                    $transfers[MonthEndService::DESCRIPTION_PRESETS['accountancy']],
            ],
            [
                'creditWalletId' => $treasuryWalletId,
                'description' => MonthEndService::DESCRIPTION_PRESETS['maintenance'],
                'amount' =>
                    $transfers[MonthEndService::DESCRIPTION_PRESETS['maintenance']],
            ],
            // Custom one
            [
                'creditWalletId' => $expensesWalletId,
                'description' => 'Loan repayment',
                'amount' => $transfers['Loan repayment'],
            ],
        ];
        $asset = new Asset();
        $asset->setCompanyNumber($assetSpv);
        $asset->setDepositWalletId($depositWalletId);
        $transferOrder = new TransferOrder();
        $transferOrder->setAsset($asset);
        $actual = $this->service->applyTemplateToTransferOrder(
            $transferOrder,
            $transferPlan,
        );

        $this->assertNull($actual->getDescription());
        $this->assertNull($actual->getScheduledFor());
        $this->assertEquals($asset, $actual->getAsset());
        $this->assertEquals(AbstractOrder::STATE_DRAFT, $actual->getStatus());
        $this->assertCount(count($transferPlan), $actual->getTransfers());

        // Check each transfer generated
        foreach ($actual->getTransfers() as $transfer) {
            $this->assertContains($transfer->getDescription(), array_keys($transfers));
            $this->assertEquals($depositWalletId, $transfer->getDebitWalletId());
            $this->assertEquals(TransferRequest::STATE_PENDING, $transfer->getStatus());
            $this->assertEquals(TransferMode::Default, $transfer->getMode());
            if (
                MonthEndService::DESCRIPTION_PRESETS['corptax'] == $transfer->getDescription()
            ) {
                $this->assertEquals($taxWalletId, $transfer->getCreditWalletId());
                $this->assertEquals(
                    $transfers[MonthEndService::DESCRIPTION_PRESETS['corptax']],
                    $transfer->getAmount(),
                );
            }
            if (
                MonthEndService::DESCRIPTION_PRESETS['accountancy'] == $transfer->getDescription()
            ) {
                $this->assertEquals($expensesWalletId, $transfer->getCreditWalletId());
                $this->assertEquals(
                    $transfers[MonthEndService::DESCRIPTION_PRESETS['accountancy']],
                    $transfer->getAmount(),
                );
            }
            if (
                MonthEndService::DESCRIPTION_PRESETS['maintenance'] == $transfer->getDescription()
            ) {
                $this->assertEquals($treasuryWalletId, $transfer->getCreditWalletId());
                $this->assertEquals(
                    $transfers[MonthEndService::DESCRIPTION_PRESETS['maintenance']],
                    $transfer->getAmount(),
                );
            }
            if ('Loan repayment' == $transfer->getDescription()) {
                $this->assertEquals($expensesWalletId, $transfer->getCreditWalletId());
                $this->assertEquals(
                    $transfers['Loan repayment'],
                    $transfer->getAmount(),
                );
            }
        }
    }

    public function testGroupIncomeTransfers(): void
    {
        $walletIds = [
            'expenses' => bin2hex(random_bytes(16)),
            'tax' => bin2hex(random_bytes(16)),
            'treasury' => bin2hex(random_bytes(16)),
            'distribution' => bin2hex(random_bytes(16)),
            'deposit' => bin2hex(random_bytes(16)),
        ];
        // Generate a bunch of requests for a transfer order
        $transferOrder = new TransferOrder();
        foreach (range(1, 10) as $iteration) {
            $request = new TransferRequest();
            $transferOrder->addTransfer($request);
            $request->setDebitWalletId($walletIds['deposit']);
            if ($iteration < 4) {
                $request->setCreditWalletId($walletIds['expenses']);
                $request->setDescription('to expenses');
                $request->setAmount((string) 17.83 * $iteration);
                continue;
            }
            if ($iteration < 6) {
                $request->setCreditWalletId($walletIds['tax']);
                $request->setDescription('to tax');
                $request->setAmount((string) 64.76 * $iteration);
                continue;
            }
            if ($iteration < 8) {
                $request->setCreditWalletId($walletIds['treasury']);
                $request->setDescription('to treasury');
                $request->setAmount((string) 39.75 * $iteration);
                continue;
            }
            $request->setCreditWalletId($walletIds['distribution']);
            $request->setDescription('to distribution');
            $request->setAmount((string) 75.82 * $iteration);
        }
        $walletIdMap = array_flip($walletIds);
        $transfers = $transferOrder->getTransfers();
        // Note that slice preserves keys, which we will reset with array_values
        // As groupIncomeTransfers puts the transfer requests in a new array
        $expected = [
            'expenses' => array_values($transfers->slice(0, 3)),
            'tax' => array_values($transfers->slice(3, 2)),
            'treasury' => array_values($transfers->slice(5, 2)),
            'distribution' => array_values($transfers->slice(7, 3)),
        ];
        $actual = $this->service->groupIncomeTransfers($transferOrder, $walletIdMap);
        $this->assertEquals($expected, $actual);

        // Check aggregate mode
        $expected = [
            'expenses' => '106.98',
            'tax' => '582.84',
            'treasury' => '516.75',
            'distribution' => '2047.14',
        ];
        $actual = $this->service->groupIncomeTransfers(
            $transferOrder,
            $walletIdMap,
            true,
        );
        $this->assertEquals($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider(
        'transformPercentageToAbsoluteProvider',
    )]
    public function testTransformPercentageToAbsolute(
        TransferRequest $transferRequest,
        array $walletIdMap,
        string $expected,
    ): void {
        $actual = $this->service->transformPercentageToAbsolute(
            $transferRequest,
            $walletIdMap,
        );
        $this->assertEquals($expected, $actual->getAmount());
    }

    public static function transformPercentageToAbsoluteProvider(): \Generator
    {
        $walletIds = [
            'expenses' => bin2hex(random_bytes(16)),
            'tax' => bin2hex(random_bytes(16)),
            'treasury' => bin2hex(random_bytes(16)),
            'distribution' => bin2hex(random_bytes(16)),
            'deposit' => bin2hex(random_bytes(16)),
        ];
        $transferOrder = new TransferOrder();
        $transferOrder->setTargetTotal('895.27');
        foreach (range(1, 7) as $iteration) {
            $request = new TransferRequest();
            $transferOrder->addTransfer($request);
            $request->setDebitWalletId($walletIds['deposit']);
            if ($iteration < 4) {
                $request->setCreditWalletId($walletIds['expenses']);
                $request->setDescription('to expenses');
                $request->setAmount((string) 7.85 * $iteration);
                continue;
            }
            if ($iteration < 5) {
                $request->setCreditWalletId($walletIds['tax']);
                $request->setDescription('to tax');
                $request->setAmount('21');
                continue;
            }
            if ($iteration < 6) {
                $request->setCreditWalletId($walletIds['treasury']);
                $request->setDescription('to treasury');
                $request->setAmount('5.9');
                continue;
            }
            if ($iteration < 7) {
                $request->setCreditWalletId($walletIds['distribution']);
                $request->setDescription('to distribution');
                $request->setAmount('75.82');
                continue;
            }
            $request->setCreditWalletId($walletIds['distribution']);
            $request->setDescription('to distribution final');
            $request->setAmount('100');
        }
        $transfers = $transferOrder->getTransfers();
        $walletIdMap = array_flip($walletIds);
        // Note that we're retrieving by index which starts from 0 not 1!
        yield 'Expenses' => [$transfers->get(0), $walletIdMap, '70.28']; // 7.85% of 895.27
        yield 'Tax' => [$transfers->get(3), $walletIdMap, '165.01']; // 21% of 785.74 (859.27 - 70.28 - 39.25)
        yield 'Treasury' => [$transfers->get(4), $walletIdMap, '36.62']; // 5.8% of 620.73 (859.27 - 70.28 - 39.25 - 165.01)
        yield 'Distributon' => [$transfers->get(6), $walletIdMap, '508.29']; // the remainder (620.73 - 36.62 - 75.82)
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validateTransferOrderProvider')]
    public function testIsTransferOrderValid(
        TransferOrder $transferOrder,
        array $expected,
        ?TransferType $transferType = null,
    ): void {
        if ($transferType) {
            $this->assertSame($transferOrder->getTransferType()
            === $transferType, $this->service->isTransferOrderValid(
                $transferOrder,
                $transferType,
            ));
        } else {
            $this->assertSame(
                empty($expected),
                $this->service->isTransferOrderValid($transferOrder),
            );
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validateTransferOrderProvider')]
    public function testValidateTransferOrder(
        TransferOrder $transferOrder,
        array $expected,
        ?TransferType $transferType = null,
    ): void {
        $this->assertEquals(
            $expected,
            $this->service->validateTransferOrder($transferOrder),
        );
    }

    public static function validateTransferOrderProvider(): \Generator
    {
        $asset = new Asset();
        $asset->setTaxWalletId(bin2hex(random_bytes(16)));
        $asset->setTreasuryWalletId(bin2hex(random_bytes(16)));
        $asset->setExpensesWalletId(bin2hex(random_bytes(16)));
        $asset->setDepositWalletId(bin2hex(random_bytes(16)));

        $incomeProcessNoDeposit = new TransferOrder();
        $incomeProcessNoDeposit->setTransferType(TransferType::AssetIncomeProcessing);
        $incomeProcessNoDeposit->setAsset(new Asset());

        $incomeProcessInvalidTransfer = new TransferOrder();
        $incomeProcessInvalidTransfer->setTransferType(TransferType::AssetIncomeProcessing);
        $incomeProcessInvalidTransfer->setAsset($asset);

        $incomeProcessValid = new TransferOrder();
        $incomeProcessValid->setTransferType(TransferType::AssetIncomeProcessing);
        $incomeProcessValid->setAsset($asset);

        // Fill the incomeProcessInvalidTransfer with mix of valid and invalid requests
        foreach (range(1, 7) as $iteration) {
            $request = new TransferRequest();
            $request->setDebitWalletId('uhoh');
            $request->setCreditWalletId('err');
            // Every even number will have a suitable debit wallet
            if (0 === ($iteration % 2)) {
                $request->setDebitWalletId($asset->getDepositWalletId());
            }
            // Every multiple of 3 will have a suitable credit wallet
            // This means at least 1 of the requests will be valid
            if (0 === ($iteration % 3)) {
                $request->setCreditWalletId($asset->getExpensesWalletId());
                $incomeProcessInvalidTransfer->addTransfer($request);
                continue;
            }
            // Every multiple of 4 will have the debit wallet made unsuitable again
            if (0 === ($iteration % 4)) {
                $request->setDebitWalletId($asset->getTreasuryWalletId());
                $incomeProcessInvalidTransfer->addTransfer($request);
                continue;
            }
        }

        // Fill the incomeProcessValid with valid requests
        foreach (range(0, 4) as $iteration) {
            $request = new TransferRequest();
            $request->setDebitWalletId($asset->getDepositWalletId());
            $request->setCreditWalletId($asset->getExpensesWalletId());
            $incomeProcessValid->addTransfer($request);
        }

        $incomeProcessNoAsset = new TransferOrder();
        $incomeProcessNoAsset->setTransferType(TransferType::AssetIncomeProcessing);
        $incomeProcessWithAsset = new TransferOrder();
        $incomeProcessWithAsset->setTransferType(TransferType::AssetIncomeProcessing);
        $incomeProcessWithAsset->setAsset($asset);

        yield 'Income process no asset' => [
            $incomeProcessNoAsset,
            [
                'missingAsset' => 'No asset linked',
            ],
        ];
        yield 'Income process no deposit' => [
            $incomeProcessNoDeposit,
            [
                'missingDepositWallet' => 'No asset deposit wallet configured',
            ],
        ];
        yield 'Income process no suitable requests' => [
            $incomeProcessInvalidTransfer,
            [
                'invalidTransfer' => 'Some transfers are not debiting from deposit or crediting an invalid wallet',
            ],
        ];
        yield 'Income process valid' => [$incomeProcessValid, []];
        yield 'Income process valid different type' => [
            $incomeProcessValid,
            [],
            TransferType::FeeCollection,
        ];

        $feeCollectionNoAsset = new TransferOrder();
        $feeCollectionNoAsset->setTransferType(TransferType::FeeCollection);
        $feeCollectionWithAsset = new TransferOrder();
        $feeCollectionWithAsset->setTransferType(TransferType::FeeCollection);
        $feeCollectionWithAsset->setAsset($asset);
        yield 'Fee collection valid' => [$feeCollectionNoAsset, []];
        yield 'Fee collection unexpected asset' => [
            $feeCollectionWithAsset,
            [
                'unexpectedLinkedAsset' => 'Unexpected asset linked to order',
            ],
        ];

        $paymentAllocationNoAsset = new TransferOrder();
        $paymentAllocationNoAsset->setTransferType(TransferType::PaymentAllocation);
        $paymentAllocationWithAsset = new TransferOrder();
        $paymentAllocationWithAsset->setTransferType(TransferType::PaymentAllocation);
        $paymentAllocationWithAsset->setAsset($asset);
        yield 'Payment allocation no asset' => [
            $paymentAllocationNoAsset,
            [
                'missingAsset' => 'No asset linked',
            ],
        ];
        yield 'Payment allocation valid' => [$paymentAllocationWithAsset, []];

        $incomeDisaggregationNoAsset = new TransferOrder();
        $incomeDisaggregationNoAsset->setTransferType(TransferType::IncomeDisaggregation);
        $incomeDisaggregationWithAsset = new TransferOrder();
        $incomeDisaggregationWithAsset->setTransferType(TransferType::IncomeDisaggregation);
        $incomeDisaggregationWithAsset->setAsset($asset);
        yield 'Income disaggregation valid' => [$incomeDisaggregationNoAsset, []];
        yield 'Income disaggregation unexpected asset' => [
            $incomeDisaggregationWithAsset,
            [
                'unexpectedLinkedAsset' => 'Unexpected asset linked to order',
            ],
        ];
    }

    public function testGenerateRelayTransfers(): void
    {
        /**
         * Test scenarios
         * - Transfer order's existing requests are cleared before generation
         * - Transfer requests given must meet criteria (be the right class and have an asset linked)
         * - Generated transfers have correct wallets, amount, status
         * - Generated transfers have correct metadata attached to description
         */

        /** @var Asset $asset1 */
        $asset1 = EntityIdTestUtil::setEntityId(new Asset(), 221);
        $asset1->setCompanyNumber('SPVGRT0785');
        /** @var Asset $asset2 */
        $asset2 = EntityIdTestUtil::setEntityId(new Asset(), 623);
        $asset2->setCompanyNumber('SPVGRT7249');

        $relayToWallet = 'relayWallet_' . bin2hex(random_bytes(8));
        $transferOrder = new TransferOrder();
        $transferOrder->setScheduledFor(new \DateTime());
        $transferOrder->setAsset($asset1);

        $oldTransferRequest = new TransferRequest();
        $oldTransferRequest->setDebitWalletId(bin2hex(random_bytes(8)));
        $oldTransferRequest->setCreditWalletId(bin2hex(random_bytes(8)));
        $oldTransferRequest->setDescription('old transfer to be cleared');
        $oldTransferRequest->setAmount('14.87');
        $transferOrder->addTransfer($oldTransferRequest);

        $assetlessTransfer = new TransferRequest();
        $assetlessOrder = new TransferOrder();
        $assetlessOrder->setScheduledFor(new \DateTime());
        $assetlessTransfer->setDebitWalletId(bin2hex(random_bytes(8)));
        $assetlessTransfer->setCreditWalletId(bin2hex(random_bytes(8)));
        $assetlessTransfer->setDescription('assetless transfer');
        $assetlessTransfer->setAmount('414.87');
        $assetlessOrder->addTransfer($assetlessTransfer);

        /** @var TransferRequest $transfer1 */
        $transfer1 = EntityIdTestUtil::setEntityId(new TransferRequest(), 4476);
        $transfer1->setDebitWalletId('tr1d_' . bin2hex(random_bytes(8)));
        $transfer1->setCreditWalletId('tr1c_' . bin2hex(random_bytes(8)));
        $transfer1->setDescription('first relayed transfer');
        $transfer1->setAmount('67.92');
        $transfer1->setStatus(TransferRequest::STATE_COMPLETE);
        $oldTransferOrder1 = new TransferOrder();
        $oldTransferOrder1->setScheduledFor(new \DateTime());
        $oldTransferOrder1->setAsset($asset1);
        $oldTransferOrder1->addTransfer($transfer1);

        /** @var TransferRequest $transfer2 */
        $transfer2 = EntityIdTestUtil::setEntityId(new TransferRequest(), 7235);
        $transfer2->setDebitWalletId('tr1d_' . bin2hex(random_bytes(8)));
        $transfer2->setCreditWalletId('tr1c_' . bin2hex(random_bytes(8)));
        $transfer2->setDescription('second relayed transfer');
        $transfer2->setAmount('132.92');
        $transfer2->setStatus(TransferRequest::STATE_PENDING);
        $oldTransferOrder2 = new TransferOrder();
        $oldTransferOrder2->setScheduledFor(new \DateTime());
        $oldTransferOrder2->setAsset($asset2);
        $oldTransferOrder2->addTransfer($transfer2);

        // The monthend date to be added to the description
        $monthendPeriod = new \DateTime()
            ->modify('-1 month')
            ->format('Y-m');

        $actual = $this->service->generateRelayTransfers(
            $transferOrder,
            [$assetlessTransfer, $transfer1, new PaymentRequest(), $transfer2],
            $relayToWallet,
        );
        $generatedTransfers = $actual->getTransfers();
        $this->assertCount(3, $generatedTransfers);
        foreach ($generatedTransfers as $transfer) {
            $this->assertEquals(TransferMode::Default, $transfer->getMode());
        }

        $generatedTransfer = $generatedTransfers->first();
        $this->assertEquals(
            $assetlessTransfer->getCreditWalletId(),
            $generatedTransfer->getDebitWalletId(),
        );
        $this->assertEquals($relayToWallet, $generatedTransfer->getCreditWalletId());
        $this->assertEquals(
            "assetless transfer;TR#{$assetlessTransfer->getId()};For month {$monthendPeriod}",
            $generatedTransfer->getDescription(),
        );
        $this->assertNull($generatedTransfer->getAsset());
        $this->assertEquals(
            $assetlessTransfer->getAmount(),
            $generatedTransfer->getAmount(),
        );
        $this->assertEquals(
            TransferRequest::STATE_PENDING,
            $generatedTransfer->getStatus(),
        );
        $this->assertNull($generatedTransfer->getId());

        $generatedTransfer = $generatedTransfers->next();
        $this->assertEquals(
            $transfer1->getCreditWalletId(),
            $generatedTransfer->getDebitWalletId(),
        );
        $this->assertEquals($relayToWallet, $generatedTransfer->getCreditWalletId());
        $this->assertEquals(
            "first relayed transfer;TR#{$transfer1->getId()};{$asset1->getCompanyNumber()} {$asset1->getName()};For month {$monthendPeriod}",
            $generatedTransfer->getDescription(),
        );
        $this->assertSame($asset1->getId(), $generatedTransfer->getAsset()?->getId());
        $this->assertEquals($transfer1->getAmount(), $generatedTransfer->getAmount());
        $this->assertEquals(
            TransferRequest::STATE_PENDING,
            $generatedTransfer->getStatus(),
        );
        $this->assertNull($generatedTransfer->getId());

        $generatedTransfer = $generatedTransfers->next();
        $this->assertEquals(
            $transfer2->getCreditWalletId(),
            $generatedTransfer->getDebitWalletId(),
        );
        $this->assertEquals($relayToWallet, $generatedTransfer->getCreditWalletId());
        $this->assertEquals(
            "second relayed transfer;TR#{$transfer2->getId()};{$asset2->getCompanyNumber()} {$asset2->getName()};For month {$monthendPeriod}",
            $generatedTransfer->getDescription(),
        );
        $this->assertSame($asset2->getId(), $generatedTransfer->getAsset()?->getId());
        $this->assertEquals($transfer2->getAmount(), $generatedTransfer->getAmount());
        $this->assertEquals(
            TransferRequest::STATE_PENDING,
            $generatedTransfer->getStatus(),
        );
        $this->assertNull($generatedTransfer->getId());
    }

    public function testRelayTransferRequest(): void
    {
        $originalDebitWallet = 'ogDebit_' . bin2hex(random_bytes(8));
        $originalCreditWallet = 'ogCredit_' . bin2hex(random_bytes(8));
        $relayToWallet = 'relayWallet_' . bin2hex(random_bytes(8));
        $originalDescription = 'Test relay transfer request creation';
        $originalAmount = '742.91';

        $originalTransferRequest = new TransferRequest();
        $originalTransferRequest->setDebitWalletId($originalDebitWallet);
        $originalTransferRequest->setCreditWalletId($originalCreditWallet);
        $originalTransferRequest->setDescription($originalDescription);
        $originalTransferRequest->setAmount($originalAmount);
        $originalTransferRequest->setStatus(TransferRequest::STATE_COMPLETE);

        $actual = $this->service->relayTransferRequest(
            $originalTransferRequest,
            $relayToWallet,
        );
        $this->assertEquals($originalCreditWallet, $actual->getDebitWalletId());
        $this->assertEquals($relayToWallet, $actual->getCreditWalletId());
        $this->assertEquals($originalDescription, $actual->getDescription());
        $this->assertEquals($originalAmount, $actual->getAmount());
        $this->assertEquals(TransferRequest::STATE_PENDING, $actual->getStatus());
        $this->assertNull($actual->getId());

        // If asset relation set, also includes the asset relation
        $asset = new Asset();
        $asset->setCompanyNumber('SPVTRLY101');
        $originalTransferRequest->setAsset($asset);
        $actual = $this->service->relayTransferRequest(
            $originalTransferRequest,
            $relayToWallet,
        );
        $this->assertEquals($originalCreditWallet, $actual->getDebitWalletId());
        $this->assertEquals($relayToWallet, $actual->getCreditWalletId());
        $this->assertEquals($originalDescription, $actual->getDescription());
        $this->assertEquals($originalAmount, $actual->getAmount());
        $this->assertEquals(TransferRequest::STATE_PENDING, $actual->getStatus());
        $this->assertEquals($asset, $actual->getAsset());
        $this->assertNull($actual->getId());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('settlementOrderProvider')]
    public function testIsSettlementOrder(
        TransferOrder $transferOrder,
        ?string $stampDutyWallet,
        bool $expected,
    ): void {
        $this->assertSame($expected, $this->service->isSettlementOrder(
            $transferOrder,
            $stampDutyWallet,
        ));
    }

    public static function settlementOrderProvider(): \Generator
    {
        $stampDutyWallet = bin2hex(random_bytes(16));

        /** @var Asset $asset */
        $asset = EntityIdTestUtil::setEntityId(new Asset(), 445);
        $asset->setHoldWalletId(bin2hex(random_bytes(16)));
        $asset->setSettlementWalletId(bin2hex(random_bytes(16)));

        $sellOrderInitial = new TradeOrder(
            asset: $asset,
            direction: TradeDirection::Sell,
            type: TradeOrderType::Initial,
        );

        $assetAlt = EntityIdTestUtil::setEntityId(new Asset(), 883);
        $assetAlt->setPricePerShare('1.67');
        $sellOrderOther = new TradeOrder(
            asset: $assetAlt,
            direction: TradeDirection::Sell,
            type: TradeOrderType::Initial,
        );

        $offering = new Offering();
        $offering->setAsset($asset);

        $seller = new User();
        $seller->setMangoPayWalletId(bin2hex(random_bytes(16)));

        $sellOrderMarket = new TradeOrder(
            asset: $asset,
            user: $seller,
            direction: TradeDirection::Sell,
            type: TradeOrderType::Market,
        );
        $buyOrderRelist = new TradeOrder(
            asset: $asset,
            direction: TradeDirection::Buy,
            type: TradeOrderType::Market,
        );
        $shareTradeRelist = new ShareTrade(
            buyOrder: $buyOrderRelist,
            sellOrder: $sellOrderMarket,
        );

        /**
         * Scenarios:
         * - TransferRequest without shareTrade relation
         * - Settlement request with incorrect wallets
         * - Stamp duty request with incorrect wallets
         * - Linked investment not the same as the order's linked asset
         */
        /** @var TransferOrder[] $orderScenarios */
        $orderScenarios = [];
        foreach ([
            'noShareTradeRelation',
            'settlementDebit',
            'settlementCredit',
            'stampDutyDebit',
            'stampDutyCredit',
            'wrongAsset',
            'customDescription',
            'hasRelisting',
            'allValid',
        ] as $scenario) {
            $order = new TransferOrder();
            $order->setAsset($asset);
            $orderScenarios[$scenario] = $order;
        }

        $shareTrades = [];
        foreach (range(0, 2) as $iteration) {
            $buyOrderMarket = new TradeOrder(
                asset: $asset,
                direction: TradeDirection::Buy,
                type: TradeOrderType::Market,
            );
            $shareTrade = new ShareTrade(
                buyOrder: $buyOrderMarket,
                sellOrder: $sellOrderInitial,
            );
            $shareTrades[] = $shareTrade;
        }

        // Fill the scenarios with valid requests
        foreach ($orderScenarios as $order) {
            // Add 3 settlement transfers
            foreach (range(0, 2) as $iteration) {
                $request = new TransferRequest();
                $request->setShareTrade($shareTrades[$iteration]);
                $request->setDebitWalletId($asset->getHoldWalletId());
                $request->setCreditWalletId($asset->getSettlementWalletId());
                $request->setDescription(
                    MonthEndService::DESCRIPTION_PRESETS['settlement'] . ' extra info',
                );
                $order->addTransfer($request);
            }
            // Add 2 stamp duty transfers
            foreach (range(0, 1) as $iteration) {
                $request = new TransferRequest();
                $request->setShareTrade($shareTrades[$iteration]);
                $request->setDebitWalletId($asset->getHoldWalletId());
                $request->setCreditWalletId($stampDutyWallet);
                $request->setDescription(
                    MonthEndService::DESCRIPTION_PRESETS['stamp duty'] . ' extra info',
                );
                $order->addTransfer($request);
            }
        }
        $orderScenarios['noShareTradeRelation']->addTransfer(new TransferRequest());

        // Wrong settlement debit wallet
        /** @var TransferRequest $request */
        $request = $orderScenarios['settlementDebit']->getTransfers()->current();
        $request->setDebitWalletId('abc' . $request->getDebitWalletId());

        // Wrong settlement credit wallet
        /** @var TransferRequest $request */
        $request = $orderScenarios['settlementCredit']->getTransfers()->current();
        $request->setCreditWalletId('abc' . $request->getCreditWalletId());

        // Wrong stamp duty debit wallet
        /** @var TransferRequest $request */
        $request = $orderScenarios['stampDutyDebit']->getTransfers()->last();
        $request->setDebitWalletId('abc' . $request->getDebitWalletId());

        // Wrong stamp duty credit wallet
        /** @var TransferRequest $request */
        $request = $orderScenarios['stampDutyCredit']->getTransfers()->last();
        $request->setCreditWalletId('abc' . $request->getCreditWalletId());

        // Wrong asset for the linked share trade
        $buyOrderAlt = new TradeOrder(
            asset: $assetAlt,
            direction: TradeDirection::Buy,
            type: TradeOrderType::Market,
        );
        $shareTradeAlt = new ShareTrade(
            buyOrder: $buyOrderAlt,
            sellOrder: $sellOrderOther,
        );
        $request = $orderScenarios['wrongAsset']->getTransfers()->current();
        $request->setShareTrade($shareTradeAlt);

        // Custom description
        /** @var TransferRequest $request */
        $request = $orderScenarios['customDescription']->getTransfers()->current();
        $request->setDescription('non-standard settlement');
        $request = $orderScenarios['customDescription']->getTransfers()->last();
        $request->setDescription('non-standard stamp duty');

        // Relisting
        /** @var TransferRequest $request */
        $request = $orderScenarios['hasRelisting']->getTransfers()->current();
        $request->setCreditWalletId($seller->getMangoPayWalletId());
        $request->setShareTrade($shareTradeRelist);

        yield 'No asset' => [new TransferOrder(), null, false];
        yield 'No share trade relation' => [
            $orderScenarios['noShareTradeRelation'],
            $stampDutyWallet,
            false,
        ];
        yield 'Invalid settlement debit' => [
            $orderScenarios['settlementDebit'],
            $stampDutyWallet,
            false,
        ];
        yield 'Invalid settlement credit' => [
            $orderScenarios['settlementCredit'],
            $stampDutyWallet,
            false,
        ];
        yield 'Invalid stamp duty debit' => [
            $orderScenarios['stampDutyDebit'],
            $stampDutyWallet,
            false,
        ];
        yield 'Invalid stamp duty credit' => [
            $orderScenarios['stampDutyCredit'],
            $stampDutyWallet,
            false,
        ];
        yield 'Stamp duty null' => [$orderScenarios['allValid'], null, false];
        yield 'Incorrect asset' => [
            $orderScenarios['wrongAsset'],
            $stampDutyWallet,
            false,
        ];
        yield 'Custom description' => [
            $orderScenarios['customDescription'],
            $stampDutyWallet,
            true,
        ];
        yield 'With relisted investments' => [
            $orderScenarios['hasRelisting'],
            $stampDutyWallet,
            true,
        ];
        yield 'All suitable requests' => [
            $orderScenarios['allValid'],
            $stampDutyWallet,
            true,
        ];
    }

    public function testCreateTransferOrderByPreset(): void
    {
        // Note that you cannot use enums as a key
        // Hence need to get the string of the backed enum
        $expectedTypeByPreset = [
            TransferOrderPreset::IncomeTransfer->value =>
                TransferType::AssetIncomeProcessing,
            TransferOrderPreset::YieldersFees->value => TransferType::FeeCollection,
            TransferOrderPreset::InvestmentSettlement->value =>
                TransferType::InvestmentSettlement,
            TransferOrderPreset::PrefunderRepaymentTransfer->value =>
                TransferType::PaymentAllocation,
            TransferOrderPreset::IncomeDisaggregation->value =>
                TransferType::IncomeDisaggregation,
        ];
        foreach (TransferOrderPreset::cases() as $preset) {
            $actual = $this->service->createTransferOrderByPreset($preset);
            $this->assertNotNull($actual->getScheduledFor());
            $this->assertLessThanOrEqual(
                new \DateTime('first day of this month'),
                $actual->getScheduledFor(),
            );
            $this->assertEquals($preset->value, $actual->getDescription());
            $this->assertEquals(
                $expectedTypeByPreset[$preset->value],
                $actual->getTransferType(),
            );
        }
    }

    public function testCreatePaymentOrderByType(): void
    {
        foreach (PaymentType::cases() as $preset) {
            $actual = $this->service->createPaymentOrderByType($preset);
            $this->assertNotNull($actual->getScheduledFor());
            $this->assertLessThanOrEqual(
                new \DateTime('first day of this month'),
                $actual->getScheduledFor(),
            );
            $this->assertEquals($preset->value, $actual->getPaymentType());
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('divestmentPaymentTypeProvider')]
    public function testDetermineDivestmentType(
        int $assetFundingGoal,
        ?int $sharesInCirculation,
        array $paymentShares,
        PaymentType $initialType,
        bool $expected,
    ): void {
        $asset = new Asset();
        $asset->setAmountOfShares($assetFundingGoal);
        $paymentOrder = new PaymentOrder();
        $paymentOrder->setPaymentType($initialType->value);
        $paymentOrder->setAsset($asset);
        foreach ($paymentShares as $shares) {
            $paymentRequest = new PaymentRequest();
            $paymentRequest->setShareholding($shares);
            $paymentOrder->addPayment($paymentRequest);
        }
        $actual = $this->service->determineDivestmentType(
            $paymentOrder,
            $sharesInCirculation,
        );
        $this->assertSame($expected, $actual);
    }

    public static function divestmentPaymentTypeProvider(): \Generator
    {
        yield 'Empty payment order' => [
            10875,
            null,
            [],
            PaymentType::Divestment,
            false,
        ];
        yield 'Non-divestment payment order' => [
            10875,
            null,
            [100],
            PaymentType::Dividend,
            false,
        ];

        yield 'Asset shares divestment correct' => [
            10875,
            null,
            [10000, 500, 374],
            PaymentType::Divestment,
            false,
        ];
        yield 'Asset shares exit correct' => [
            10875,
            null,
            [10000, 500, 375],
            PaymentType::InvestmentExit,
            false,
        ];
        yield 'Asset shares divestment to exit' => [
            10875,
            null,
            [10000, 500, 374],
            PaymentType::InvestmentExit,
            true,
        ];
        yield 'Asset shares exit to divestment' => [
            10875,
            null,
            [10000, 500, 375],
            PaymentType::Divestment,
            true,
        ];
        yield 'Asset shares divestment to exit overpay' => [
            10875,
            null,
            [10000, 500, 376],
            PaymentType::Divestment,
            true,
        ];

        yield 'Custom shares divestment correct' => [
            10875,
            12750,
            [10000, 500, 374],
            PaymentType::Divestment,
            false,
        ];
        yield 'Custom shares exit correct' => [
            10875,
            12750,
            [12000, 500, 250],
            PaymentType::InvestmentExit,
            false,
        ];
        yield 'Custom shares divestment to exit' => [
            10875,
            12750,
            [12000, 500, 249],
            PaymentType::InvestmentExit,
            true,
        ];
        yield 'Custom shares exit to divestment' => [
            10875,
            12750,
            [12000, 500, 250],
            PaymentType::Divestment,
            true,
        ];
        yield 'Custom shares divestment to exit overpay' => [
            10875,
            12750,
            [12000, 500, 251],
            PaymentType::Divestment,
            true,
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider(
        'transferOrderWalletAggregationProvider',
    )]
    public function testTransferOrderAmountPerWallet(
        TransferOrder $transferOrder,
        array $expected,
    ): void {
        $actual = $this->service->transferOrderAmountPerWallet($transferOrder);
        $this->assertEquals(array_sum($actual['debit']), array_sum($actual['credit']));
        $this->assertEquals($expected, $actual);
    }

    public static function transferOrderWalletAggregationProvider(): \Generator
    {
        $transferOrder = new TransferOrder();
        // Completed transfers are ignored in totals
        foreach (range(1, 3) as $i) {
            $transfer = new TransferRequest();
            $transfer->setDebitWalletId('FirstDebitWallet');
            $transfer->setCreditWalletId('FirstCreditWallet');
            $transfer->setAmount(2.83 * $i);
            $transfer->setStatus(TransferRequest::STATE_COMPLETE);
            $transferOrder->addTransfer($transfer);
        }
        // 2.83 + 5.66 + 8.49 == 16.98
        foreach (range(1, 3) as $i) {
            $transfer = new TransferRequest();
            $transfer->setDebitWalletId('FirstDebitWallet');
            $transfer->setCreditWalletId('FirstCreditWallet');
            $transfer->setAmount(2.83 * $i);
            $transfer->setStatus(TransferRequest::STATE_PENDING);
            $transferOrder->addTransfer($transfer);
        }
        // 5.47 + 10.94 == 16.41
        foreach (range(1, 2) as $i) {
            $transfer = new TransferRequest();
            $transfer->setDebitWalletId('SecondDebitWallet');
            $transfer->setCreditWalletId('FirstCreditWallet');
            $transfer->setAmount(5.47 * $i);
            $transfer->setStatus(TransferRequest::STATE_PENDING);
            $transferOrder->addTransfer($transfer);
        }
        // 7.12 + 14.24 + 21.36 + 28.48 = 71.2
        foreach (range(1, 4) as $i) {
            $transfer = new TransferRequest();
            $transfer->setDebitWalletId('FirstDebitWallet');
            $transfer->setCreditWalletId('SecondCreditWallet');
            $transfer->setAmount(7.12 * $i);
            $transfer->setStatus(TransferRequest::STATE_PENDING);
            $transferOrder->addTransfer($transfer);
        }
        // 4.43 + 8.86 + 13.29 == 26.58
        foreach (range(1, 3) as $i) {
            $transfer = new TransferRequest();
            $transfer->setDebitWalletId('SecondDebitWallet');
            $transfer->setCreditWalletId('SecondCreditWallet');
            $transfer->setAmount(4.43 * $i);
            $transfer->setStatus(TransferRequest::STATE_PENDING);
            $transferOrder->addTransfer($transfer);
        }

        yield 'Empty transfer order' => [
            new TransferOrder(),
            [
                'debit' => [],
                'credit' => [],
            ],
        ];
        yield 'Multi wallet stacking' => [
            $transferOrder,
            [
                'debit' => [
                    'FirstDebitWallet' => 88.18, // 16.98 + 71.2
                    'SecondDebitWallet' => 42.99, // 16.41 + 26.58
                ],
                'credit' => [
                    'FirstCreditWallet' => 33.39, // 16.98 + 16.41
                    'SecondCreditWallet' => 97.78, // 26.58 + 71.2
                ],
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('monthEndDateRangeProvider')]
    public function testGetMonthEndDateRangeFromDateTime(
        \DateTimeInterface $dateTime,
        ?int $monthOffset,
        array $expected,
    ): void {
        if (is_null($monthOffset)) {
            $actual = $this->service->getMonthEndDateRangeFromDateTime($dateTime);
        } else {
            $actual = $this->service->getMonthEndDateRangeFromDateTime(
                $dateTime,
                $monthOffset,
            );
        }
        $this->assertEqualsCanonicalizing(['start', 'end'], array_keys($actual));
        // Start and end are formatted to be the first days of the relevant month at midnight
        $this->assertSame(
            $expected['start']->format(\DateTimeInterface::ATOM),
            $actual['start']->format(\DateTimeInterface::ATOM),
        );
        $this->assertSame(
            $expected['end']->format(\DateTimeInterface::ATOM),
            $actual['end']->format(\DateTimeInterface::ATOM),
        );
    }

    public static function monthEndDateRangeProvider(): \Generator
    {
        yield 'Start of the month' => [
            new \DateTimeImmutable('2020-04-01'),
            null,
            [
                'start' => new \DateTimeImmutable('2020-04-01'),
                'end' => new \DateTimeImmutable('2020-05-01'),
            ],
        ];
        yield 'end of the month' => [
            new \DateTimeImmutable('2020-08-31'),
            null,
            [
                'start' => new \DateTimeImmutable('2020-08-01'),
                'end' => new \DateTimeImmutable('2020-09-01'),
            ],
        ];
        yield 'middle of the month' => [
            new \DateTimeImmutable('2020-11-14'),
            null,
            [
                'start' => new \DateTimeImmutable('2020-11-01'),
                'end' => new \DateTimeImmutable('2020-12-01'),
            ],
        ];
        yield 'february leap year' => [
            new \DateTimeImmutable('2020-02-29'),
            null,
            [
                'start' => new \DateTimeImmutable('2020-02-01'),
                'end' => new \DateTimeImmutable('2020-03-01'),
            ],
        ];
        yield 'Not midnight' => [
            new \DateTimeImmutable('2020-04-12')->setTime(17, 54, 23),
            null,
            [
                'start' => new \DateTimeImmutable('2020-04-01')->setTime(0, 0),
                'end' => new \DateTimeImmutable('2020-05-01')->setTime(0, 0),
            ],
        ];
        yield 'year wrap' => [
            new \DateTimeImmutable('2020-12-01'),
            null,
            [
                'start' => new \DateTimeImmutable('2020-12-01'),
                'end' => new \DateTimeImmutable('2021-01-01'),
            ],
        ];
        yield 'february leap year with large offset' => [
            new \DateTimeImmutable('2020-02-29'),
            +3,
            [
                'start' => new \DateTimeImmutable('2020-05-01'),
                'end' => new \DateTimeImmutable('2020-06-01'),
            ],
        ];
        yield 'Not midnight with offset' => [
            new \DateTimeImmutable('2020-04-12')->setTime(17, 54, 23),
            -1,
            [
                'start' => new \DateTimeImmutable('2020-03-01')->setTime(0, 0),
                'end' => new \DateTimeImmutable('2020-04-01')->setTime(0, 0),
            ],
        ];
        yield 'year wrap with offset' => [
            new \DateTimeImmutable('2021-02-12'),
            -2,
            [
                'start' => new \DateTimeImmutable('2020-12-01'),
                'end' => new \DateTimeImmutable('2021-01-01'),
            ],
        ];
        yield 'multi year offset' => [
            new \DateTimeImmutable('2021-02-21'),
            -18,
            [
                'start' => new \DateTimeImmutable('2019-08-01'),
                'end' => new \DateTimeImmutable('2019-09-01'),
            ],
        ];
    }

    public function testCreateStructuredDescription(): void
    {
        $randomisedDescription = bin2hex(random_bytes(12));
        $asset = new Asset();
        $asset->setCompanyNumber('SPVAT001822');
        $asset->setName('Test structdesc asset name');
        $scheduledDatetime = new \DateTime('2020-09-29');
        $actual = $this->service->createStructuredDescription(
            $randomisedDescription,
            $asset,
            $scheduledDatetime,
        );
        $this->assertSame(
            $randomisedDescription
            . ';SPVAT001822 Test structdesc asset name;For month 2020-08',
            $actual,
        );
    }
}
