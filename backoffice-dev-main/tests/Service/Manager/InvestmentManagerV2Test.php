<?php

namespace App\Tests\Service\Manager;

use App\Entity\Asset;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Investment;
use App\Entity\InvestmentAddFields;
use App\Entity\InvestmentStatus;
use App\Entity\Lifecycle\InvestmentLifecycle;
use App\Entity\Offering;
use App\Entity\ShareTrade;
use App\Entity\TradeOrder;
use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\InvestmentDocumentRepository;
use App\Repository\InvestmentRepository;
use App\Repository\OfferingRepository;
use App\Repository\UserRepository;
use App\Service\Manager\InvestmentManagerV2;
use App\Test\FixtureTestCase;
use App\Test\Util\EntityIdTestUtil;
use BcMath\Number;
use MangoPay\TransactionStatus;
use PHPUnit\Framework\MockObject\MockObject;

class InvestmentManagerV2Test extends FixtureTestCase
{
    private InvestmentManagerV2 $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(InvestmentManagerV2::class);
    }

    /**
     * @psalm-return \Generator<string, array{0: 999|1000|5000|float, 1: 1|49, 2: int}, mixed, void>
     */
    public static function stampDutyProvider(): \Generator
    {
        yield 'below-boundary' => [999, 1, 0];
        yield 'on-boundary' => [1000, 1, 5];
        yield 'on-boundary no asset id' => [1000, null, 5];
        yield 'above-boundary' => [1000.01, 1, 10];
        yield 'significantly-above-boundary' => [5000.50, 1, 30];
        yield 'asset-exempt' => [5000, 49, 0];
    }

    /**
     * @psalm-return \Generator<string, array{0: float, 1: int, 2: float, 3: 250|float, 4: 0|15|40}, mixed, void>
     */
    public static function isValidInvestmentValueProvider(): \Generator
    {
        yield 'on-min-boundary' => [1.16, 87, 100.92, 250, 0];
        yield 'above-min-boundary' => [1.16, 88, 100.92, 250, 0];
        yield 'below-max-boundary' => [1.16, 215, 100.92, 250, 0];
        yield 'on-max-boundary' => [1.16, 215, 100.92, 249.4, 0];
        yield 'min with offset' => [1.16, 72, 100.92, 250, 15];
        yield 'max with offset' => [1.16, 175, 100.92, 250, 40];
    }

    /**
     * @psalm-return \Generator<'Negative'|'Zero float'|'Zero int', array{0: 0|float}, mixed, void>
     */
    public static function isValidInvestmentValueSharePriceProvider(): \Generator
    {
        yield 'Zero int' => [0];
        yield 'Zero float' => [0.00];
        yield 'Negative' => [-0.12];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('stampDutyProvider')]
    public function testCalulateStampDuty(
        float $invValue,
        ?int $assetId,
        int $expected,
    ): void {
        $security = $this->createMock(\Symfony\Bundle\SecurityBundle\Security::class);
        $documentManager = $this->createMock(\App\Service\Manager\DocumentManager::class);
        $investmentAssembler = $this->createMock(\App\Dto\InvestmentAssembler::class);

        $investmentManager = new InvestmentManagerV2(
            static::getContainer()->get(InvestmentRepository::class),
            static::getContainer()->get(InvestmentDocumentRepository::class),
            static::getContainer()->get(UserRepository::class),
            static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class),
            static::getContainer()->get(\Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface::class),
            $security,
            static::getContainer()->get(\App\Dto\DocumentAssembler::class),
            $documentManager,
            $investmentAssembler,
            static::getContainer()->get(\App\Service\MailerService::class),
            static::getContainer()->get(\App\Service\MangoPay::class),
            static::getContainer()->get(\Psr\Log\LoggerInterface::class),
        );

        $stampDutyAmount = $investmentManager->calculateStampDuty($invValue, $assetId);
        $this->assertEquals($expected, $stampDutyAmount);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('tradeValueStampDutyProvider')]
    public function testCalulateTradeValueStampDuty(
        string $tradeValue,
        int $expected,
    ): void {
        $tradeValue = new Number($tradeValue);
        $expected = new Number($expected);
        $stampDutyAmount = $this->service->calculateTradeValueStampDuty($tradeValue);
        $this->assertEquals($expected, $stampDutyAmount);
    }

    public static function tradeValueStampDutyProvider(): \Generator
    {
        yield 'below-boundary' => ['999', 0];
        yield 'on-boundary' => ['1000', 5];
        yield 'above-boundary' => ['1000.01', 10];
        yield 'high-boundary' => ['5000', 25];
        yield 'significantly-above-boundary' => ['5000.50', 30];
    }

    public function testMinCommitViolationException(): void
    {
        $this->expectException(\App\Exception\MinCommitViolationException::class);

        $offering = new Offering();
        $offering->setMinCommitUser(100);
        $offering->setMaxCommitUser(0);

        $investment = new Investment();
        $investment->setOffering($offering);
        $investment->setPricePerShare(1.10);
        $investment->setNumberOfShares(90);
        $investment->setInvestmentValue(
            $investment->getPricePerShare() * $investment->getNumberOfShares(),
        );
        $this->service->isValidInvestmentValue($investment);
    }

    public function testMaxCommitViolationException(): void
    {
        $this->expectException(\App\Exception\MaxCommitViolationException::class);

        $offering = new Offering();
        $offering->setMinCommitUser(100);
        $offering->setMaxCommitUser(1000);

        $investment = new Investment();
        $investment->setOffering($offering);
        $investment->setPricePerShare(1.10);
        $investment->setNumberOfShares(1000);
        $investment->setInvestmentValue(
            $investment->getPricePerShare() * $investment->getNumberOfShares(),
        );
        $this->service->isValidInvestmentValue($investment);
    }

    public function testIsValidInvestmentValueMissingFields(): void
    {
        $offering = new Offering();
        $investment = new Investment();
        $this->assertFalse($this->service->isValidInvestmentValue($investment));

        $investment->setOffering($offering);
        $this->assertFalse($this->service->isValidInvestmentValue($investment));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider(
        'isValidInvestmentValueSharePriceProvider',
    )]
    public function testIsValidInvestmentValueInvalidSharePrice(float $sharePrice): void
    {
        $investment = new Investment();
        $investment->setOffering(new Offering());
        $investment->setPricePerShare($sharePrice);
        $this->assertFalse($this->service->isValidInvestmentValue($investment));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('isValidInvestmentValueProvider')]
    public function testIsValidInvestmentValue(
        float $pricePerShare,
        int $numberOfShares,
        float $minCommit,
        float $maxCommit,
        int $shareOffset = 0,
    ): void {
        //offering without max commit
        $offering = new Offering();
        $offering->setMinCommitUser($minCommit);
        $offering->setMaxCommitUser(0);
        $offering->setNoOfShares(250);

        $investment = new Investment();
        $investment->setOffering($offering);
        $investment->setPricePerShare($pricePerShare);
        $investment->setNumberOfShares($numberOfShares);
        $investment->setInvestmentValue(
            $investment->getPricePerShare() * $investment->getNumberOfShares(),
        );
        $this->assertTrue($this->service->isValidInvestmentValue(
            $investment,
            $shareOffset,
        ));

        //offering with max commit
        $offering = new Offering();
        $offering->setMinCommitUser($minCommit);
        $offering->setMaxCommitUser($maxCommit);
        $investment->setOffering($offering);
        // shareOffset should be functionally equivalent to adding it to the numberOfShares
        $investment->setNumberOfShares($numberOfShares + $shareOffset);
        // isValidInvestmentValue allows calling with just the first argument
        $this->assertTrue($this->service->isValidInvestmentValue($investment));
    }

    public function testGetShareOffset(): void
    {
        $offering = $this->searchFixtures(\App\Entity\Offering::class, [
            'status' => 'published',
        ])[0];
        $user = $this->searchFixtures(\App\Entity\User::class, [
            'username' => 'ben.auto@test.yielderverse.co.uk',
        ])[0];

        $liquidation = 72;
        $retention = 18;

        // Check the liquidation investment
        $investment = new Investment();
        $investment->setOffering($offering);
        $investment->setUser($user);
        $investment->setName('splitInvestmentTest');
        $investment->setType('prefunding');
        $investment->setPricePerShare($offering->getPricePerShare());
        $investment->setNumberOfShares($liquidation);
        $investment->setInvestmentValue($liquidation * $offering->getPricePerShare());

        $investmentAddField = new InvestmentAddFields();
        $investmentAddField->setInvestment($investment);
        $investmentAddField->setFieldKey('sharesToKeep');
        $investmentAddField->setFieldValue($retention);
        $investment->addAddField($investmentAddField);

        $this->assertEquals($retention, $this->service->getShareOffset($investment));

        // Persist that investment so it can be referred to
        // $em = static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class);
        // $em->persist($investment);
        // $em->flush();

        $this->entityManager->persist($investment);
        $this->entityManager->flush();

        $prefundingId = $this->searchFixtures(
            \App\Entity\Investment::class,
            ['name' => 'splitInvestmentTest'],
            true,
        )[0];

        // Check the complementary retention investment
        $investment = new Investment();
        $investment->setOffering($offering);
        $investment->setPricePerShare($offering->getPricePerShare());
        $investment->setNumberOfShares($retention);

        $investmentAddField = new InvestmentAddFields();
        $investmentAddField->setInvestment($investment);
        $investmentAddField->setFieldKey('prefundingId');
        $investmentAddField->setFieldValue($prefundingId);
        $investment->addAddField($investmentAddField);

        $this->assertEquals($liquidation, $this->service->getShareOffset($investment));
    }

    public function testIsInvestmentStampDutyExempt(): void
    {
        // prefunding asset
        $prefundingAsset = new Asset();
        $prefundingAsset->setAdditionalType('prefunding');
        $prefundingOffering = new Offering();
        $prefundingOffering->setAsset($prefundingAsset);

        // normal asset
        $normalAsset = new Asset();
        $normalOffering = new Offering();
        $normalOffering->setAsset($normalAsset);

        //investment due stamp duty
        $investment = new Investment();
        $investment->setInvestmentValue(1000.01);
        $investment->setOffering($normalOffering);
        $this->assertFalse($this->service->isInvestmentStampDutyExempt($investment));

        //investment stamp duty exempt
        $investment = new Investment();
        $investment->setInvestmentValue(999.99);
        $investment->setOffering($normalOffering);
        $this->assertTrue($this->service->isInvestmentStampDutyExempt($investment));

        //prefunding investment stamp duty exempt
        $investment = new Investment();
        $investment->setInvestmentValue(1050);
        $investment->setOffering($prefundingOffering);
        $this->assertTrue($this->service->isInvestmentStampDutyExempt($investment));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('isTradeStampDutyExemptProvider')]
    public function testIsTradeStampDutyExempt(
        bool $expected,
        string $tradeValue,
        Asset $asset,
        TradeOrderType $buyOrderType = TradeOrderType::Market,
    ): void {
        $buyOrder = new TradeOrder(
            direction: TradeDirection::Buy,
            asset: $asset,
            type: $buyOrderType,
        );
        $shareTrade = new ShareTrade(
            buyOrder: $buyOrder,
            tradeValue: new Number($tradeValue),
        );

        $actual = $this->service->isTradeStampDutyExempt($shareTrade);
        $this->assertSame($expected, $actual);
    }

    public static function isTradeStampDutyExemptProvider(): \Generator
    {
        $dutyFreeAsset = new Asset();
        $dutyFreeAsset->setAdditionalType('duty-free');
        $developmentAsset = new Asset();
        $developmentAsset->setAdditionalType('development');
        $retailAsset = new Asset();

        // Note that this method does NOT check if the amount is below 1k
        // The below 1k exemptions is determined during calculation of the stamp duty
        // Which will be 0 if below 1k anyway
        yield 'custom asset duty free' => [true, '999', $dutyFreeAsset];
        yield 'custom asset development' => [true, '999', $developmentAsset];
        yield 'retail below boundary' => [false, '999', $retailAsset];
        yield 'retail on-boundary' => [false, '1000', $retailAsset];
        yield 'retail above-boundary' => [false, '1000.01', $retailAsset];
        yield 'prefunding investment' => [
            true,
            '25000',
            $retailAsset,
            TradeOrderType::Prefunding,
        ];
    }

    public function testIsOfferingStampDutyExempt(): void
    {
        // prefunding asset
        $prefundingAsset = new Asset();
        $prefundingAsset->setAdditionalType('prefunding');
        $prefundingOffering = new Offering();
        $prefundingOffering->setAsset($prefundingAsset);

        // normal asset
        $normalAsset = new Asset();
        $normalOffering = new Offering();
        $normalOffering->setAsset($normalAsset);

        // prefunding mode offering
        $normalAsset = new Asset();
        $prefundingModeOffering = new Offering();
        $prefundingModeOffering->setOfferingType('prefunding');
        $prefundingModeOffering->setAsset($normalAsset);

        $this->assertFalse($this->service->isOfferingStampDutyExempt($normalOffering));
        $this->assertTrue($this->service->isOfferingStampDutyExempt(
            $prefundingOffering,
        ));
        $this->assertTrue($this->service->isOfferingStampDutyExempt(
            $prefundingModeOffering,
        ));
    }

    public function testCreateNormalInvestment(): void
    {
        $offering = $this->searchFixtures(
            \App\Entity\Offering::class,
            ['type' => 'retail'],
            false,
            false,
        )[0];
        $user = $this->searchFixtures(
            \App\Entity\User::class,
            ['status' => 'approved'],
            false,
            false,
        )[0];

        $numOfShares = 100;
        $amount = $offering->getPricePerShare() * $numOfShares;

        $mpTag =
            'AstName:'
            . $offering->getAsset()->getName()
            . ';AstCode:'
            . $offering->getAsset()->getCompanyNumber()
            . ';Type:Investment';

        // /** @var MockObject $mangopayService */
        // $mangopayService = $this->createMock(\App\Service\MangoPay::class);
        // $mangopayService->expects($this->once())
        //     ->method('createGenericTransfer')
        //     ->with(
        //         $user->getMangoPayUserId(),
        //         $user->getMangoPayWalletId(),
        //         $offering->getAsset()->getMangoPayWalletId(),
        //         $amount,
        //         0,
        //         $mpTag
        //     )
        //     ->willReturn($this->createTranferSuccessObj());

        // $investmentManager = $this->getInvestmentManager($mangopayService);

        $investmentDTO = new \App\Dto\InvestmentPostDTO(
            $offering->getId(),
            $numOfShares,
            'normal',
            $user->getId(),
            0,
            0,
            null,
            null,
        );

        $actual = $this->service->addInvestment($investmentDTO);
        // Should be persisted to database
        $this->assertNotNull($actual->getId());
        $this->assertEquals(
            InvestmentLifecycle::STATE_OPEN,
            $actual->getLifecycleStatus(),
        );
        $this->assertEquals($user->getId(), $actual->getUser()->getId());
        $this->assertEquals($offering->getId(), $actual->getOffering()->getId());
        $this->assertEquals('normal', $actual->getType());
        $this->assertEquals($numOfShares, $actual->getShareAmount());
        $this->assertEquals($amount, $actual->getInvestmentValue());
        $this->assertEquals('GBP', $actual->getCurrency());
        $this->assertEquals($offering->getPricePerShare(), $actual->getPricePerShare());
        $this->assertEquals(
            $offering->getPricePerShare(),
            $actual->getOrgPricePerShare(),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('check')]
    public function testCreateNormalInvestmentFallbackValues(): void
    {
        // If offering is missing the share price and share amounts (as with really old offerings)
        $offering = $this->entityManager
            ->getRepository(Offering::class)
            ->findOneBy([
                'offeringType' => 'retail',
            ]);
        $offering->setPricePerShare('0.00');
        $offering->setNoOfShares(0);
        $this->entityManager->flush();
        $this->assertEquals('0.00', $offering->getPricePerShare());
        $this->assertEquals(0, $offering->getNoOfShares());

        $user = $this->searchFixtures(
            User::class,
            ['status' => 'approved'],
            false,
            false,
        )[0];

        $numOfShares = 100;
        $amount = $offering->getAsset()->getPricePerShare() * $numOfShares;

        $investmentDTO = new \App\Dto\InvestmentPostDTO(
            $offering->getId(),
            $numOfShares,
            'normal',
            $user->getId(),
            0,
            0,
            null,
            null,
        );

        $actual = $this->service->addInvestment($investmentDTO);
        // Should be persisted to database
        $this->assertNotNull($actual->getId());
        $this->assertEquals(
            InvestmentLifecycle::STATE_OPEN,
            $actual->getLifecycleStatus(),
        );
        $this->assertEquals($user->getId(), $actual->getUser()->getId());
        $this->assertEquals($offering->getId(), $actual->getOffering()->getId());
        $this->assertEquals('normal', $actual->getType());
        $this->assertEquals($numOfShares, $actual->getShareAmount());
        $this->assertEquals($amount, $actual->getInvestmentValue());
        $this->assertEquals('GBP', $actual->getCurrency());
        $this->assertEquals(
            $offering->getAsset()->getPricePerShare(),
            $actual->getPricePerShare(),
        );
        $this->assertEquals(
            $offering->getAsset()->getPricePerShare(),
            $actual->getOrgPricePerShare(),
        );
    }

    // public function testCreateInvestmentMpTagScenarioMissingAssetCompany(): void
    // {
    //     $offering = $this->searchFixtures(
    //         \App\Entity\Offering::class,
    //         ["type" => 'retail'],
    //         false,
    //         false
    //     )[0];
    //     $user = $this->searchFixtures(
    //         \App\Entity\User::class,
    //         ["status" => 'approved'],
    //         false,
    //         false
    //     )[0];

    //     //set company number to null
    //     $asset = $offering->getAsset();
    //     $asset->setCompanyNumber(null);

    //     $numOfShares = 100;
    //     $amount = $offering->getPricePerShare() * $numOfShares;

    //     $mpTag = "AstName:" . $offering->getAsset()->getName() . ';Type:Investment';

    //     /** @var MockObject $mangopayService */
    //     $mangopayService = $this->createMock(\App\Service\MangoPay::class);
    //     $mangopayService->expects($this->once())
    //         ->method('createGenericTransfer')
    //         ->with(
    //             $user->getMangoPayUserId(),
    //             $user->getMangoPayWalletId(),
    //             $offering->getAsset()->getMangoPayWalletId(),
    //             $amount,
    //             0,
    //             $mpTag
    //         )
    //         ->willReturn($this->createTranferSuccessObj());

    //     $investmentManager = $this->getInvestmentManager($mangopayService);

    //     $investmentDTO = new \App\Dto\InvestmentPostDTO(
    //         $offering->getId(),
    //         $numOfShares,
    //         'normal',
    //         $user->getId(),
    //         0,
    //         0,
    //         '',
    //         ''
    //     );

    //     $this->service->addInvestment($investmentDTO);
    // }

    public function createTranferSuccessObj(): \MangoPay\Transfer
    {
        $transferSuccess = new \MangoPay\Transfer();
        $transferSuccess->CreditedFunds = new \MangoPay\Money();
        $transferSuccess->CreditedFunds->Currency = 'GBP';
        $transferSuccess->Id = 1234;
        $transferSuccess->Status = 'SUCCEEDED';

        return $transferSuccess;
    }

    public function getInvestmentManager(?MockObject $mockedMangopayService = null): InvestmentManagerV2
    {
        $documentManager = $this->createMock(\App\Service\Manager\DocumentManager::class);
        $investmentAssembler = new \App\Dto\InvestmentAssembler(
            static::getContainer()->get(UserRepository::class),
            static::getContainer()->get(OfferingRepository::class),
            static::getContainer()->get(\Symfony\Bundle\SecurityBundle\Security::class),
        );

        $investmentManagerV2 = new InvestmentManagerV2(
            static::getContainer()->get(InvestmentRepository::class),
            static::getContainer()->get(InvestmentDocumentRepository::class),
            static::getContainer()->get(UserRepository::class),
            static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class),
            static::getContainer()->get(\Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface::class),
            static::getContainer()->get(\Symfony\Bundle\SecurityBundle\Security::class),
            static::getContainer()->get(\App\Dto\DocumentAssembler::class),
            $documentManager,
            $investmentAssembler,
            static::getContainer()->get(\App\Service\MailerService::class),
            $mockedMangopayService ?? static::getContainer()->get(\App\Service\MangoPay::class),
            static::getContainer()->get(\Psr\Log\LoggerInterface::class),
        );

        return $investmentManagerV2;
    }

    public function testAssetInvestmentsToShareholding(): void
    {
        $user1 = EntityIdTestUtil::setEntityId(new User(), 571);
        $user2 = EntityIdTestUtil::setEntityId(new User(), 1622);
        $user3 = EntityIdTestUtil::setEntityId(new User(), 923);

        // Generate some investments with loops
        $investments = [];
        foreach ([6 => $user1, 4 => $user2, 3 => $user3] as $iterations => $user) {
            foreach (range(1, $iterations) as $i) {
                $investment = new Investment();
                $investment->setUser($user);
                if (($i % 2) == 0) {
                    $investment->setNumberOfShares($i * $user->getId());
                } else {
                    $investment->setShareAmount($i * $user->getId());
                }
                $reflection = new \ReflectionClass($investment);
                $reflectionProperty = $reflection->getProperty('divested_shares');
                $reflectionProperty->setValue($investment, $i > 2 ? $i * 292 : 0);
                $investments[] = $investment;
            }
        }

        $expected = [
            571 => [
                'initial' => 11991, // (6+5+4+3+2+1) * 571
                'divested' => 5256, // (6+5+4+3) * 292
                'investments' => array_slice($investments, 0, 6),
            ],
            1622 => [
                'initial' => 16220, // (4+3+2+1) * 1622
                'divested' => 2044, // (4+3) * 292
                'investments' => array_slice($investments, 6, 4),
            ],
            923 => [
                'initial' => 5538, // (3+2+1) * 923
                'divested' => 876, // 3 * 292
                'investments' => array_slice($investments, 10, 3),
            ],
        ];
        $actual = $this->service->assetInvestmentsToShareholding($investments);
        $this->assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('paymentOutcomeProvider')]
    public function testProcessPaymentOutcome(
        bool $success,
        string $investmentStartStatus,
        string $investmentEndStatus,
        string $transactionStartStatus,
        string $transactionEndStatus,
    ): void {
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_REGULAR]);
        $offering = $this->entityManager
            ->getRepository(Offering::class)
            ->findOneBy(['name' => 'Clarence Hold A - Camden']);
        $transactionId = 'xfer_test_' . bin2hex(random_bytes(8));
        $investment = new Investment();
        $investment->setOffering($offering);
        $investment->setLifecycleStatus($investmentStartStatus);
        $investment->setTransactionId($transactionId);
        // Save the investment so we get a generated ID
        $this->entityManager->persist($investment);
        $this->entityManager->flush();

        $transaction = new Transaction();
        $transaction->setInvId($investment->getId());
        $transaction->setReferenceId($transactionId);
        $transaction->setPaymentStatus($transactionStartStatus);
        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        $this->service->processPaymentOutcome($investment, $success);
        $this->assertEquals($investmentEndStatus, $investment->getLifecycleStatus());
        $this->assertEquals($transactionEndStatus, $transaction->getPaymentStatus());
    }

    public static function paymentOutcomeProvider(): \Generator
    {
        yield 'success both change' => [
            true,
            InvestmentLifecycle::STATE_OPEN,
            InvestmentLifecycle::STATE_APPROVED,
            TransactionStatus::Created,
            TransactionStatus::Succeeded,
        ];
        yield 'success transaction change only' => [
            true,
            InvestmentLifecycle::STATE_APPROVED,
            InvestmentLifecycle::STATE_APPROVED,
            TransactionStatus::Failed,
            TransactionStatus::Failed,
        ];
        yield 'success investment change only' => [
            true,
            InvestmentLifecycle::STATE_OPEN,
            InvestmentLifecycle::STATE_APPROVED,
            TransactionStatus::Created,
            TransactionStatus::Succeeded,
        ];
        yield 'success no changes' => [
            true,
            InvestmentLifecycle::STATE_WITHDRAWN,
            InvestmentLifecycle::STATE_WITHDRAWN,
            TransactionStatus::Failed,
            TransactionStatus::Failed,
        ];

        yield 'fail both change' => [
            false,
            InvestmentLifecycle::STATE_OPEN,
            InvestmentLifecycle::STATE_WITHDRAWN,
            TransactionStatus::Created,
            TransactionStatus::Failed,
        ];
        yield 'fail transaction change only' => [
            false,
            InvestmentLifecycle::STATE_APPROVED,
            InvestmentLifecycle::STATE_APPROVED,
            TransactionStatus::Created,
            TransactionStatus::Failed,
        ];
        yield 'fail investment change only' => [
            false,
            InvestmentLifecycle::STATE_OPEN,
            InvestmentLifecycle::STATE_WITHDRAWN,
            TransactionStatus::Succeeded,
            TransactionStatus::Succeeded,
        ];
        yield 'fail no changes' => [
            false,
            InvestmentLifecycle::STATE_APPROVED,
            InvestmentLifecycle::STATE_APPROVED,
            TransactionStatus::Succeeded,
            TransactionStatus::Succeeded,
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('prefundingPaymentOutcomeProvider')]
    public function testProcessPaymentOutcomePrefunding(
        bool $success,
        string $liquidationStartStatus,
        string $liquidationEndStatus,
        string $retentionStartStatus,
        string $retentionEndStatus,
        string $transactionStartStatus,
        string $transactionEndStatus,
    ): void {
        $transactionId = 'xfer_test_' . bin2hex(random_bytes(8));
        $liquidation = new Investment();
        $liquidation->setLifecycleStatus($liquidationStartStatus);
        $liquidation->setTransactionId($transactionId);
        // Save the investment so we get a generated ID
        $this->entityManager->persist($liquidation);
        $this->entityManager->flush();

        $retention = new Investment();
        $retention->setLifecycleStatus($retentionStartStatus);
        $retention->setTransactionId($transactionId);
        $prefundingIdMetadata = new InvestmentAddFields();
        $prefundingIdMetadata->setFieldKey('prefundingId');
        $prefundingIdMetadata->setFieldValue($liquidation->getId());
        $retention->addAddField($prefundingIdMetadata);
        // Save the investment so we get a generated ID
        $this->entityManager->persist($retention);
        $this->entityManager->flush();

        $transaction = new Transaction();
        $transaction->setInvId($retention->getId());
        $transaction->setReferenceId($transactionId);
        $transaction->setPaymentStatus($transactionStartStatus);
        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        $this->service->processPaymentOutcome($retention, $success, 'prefunding');
        $this->assertEquals($liquidationEndStatus, $liquidation->getLifecycleStatus());
        $this->assertEquals($retentionEndStatus, $retention->getLifecycleStatus());
        $this->assertEquals($transactionEndStatus, $transaction->getPaymentStatus());
    }

    public static function prefundingPaymentOutcomeProvider(): \Generator
    {
        yield 'success both change' => [
            true,
            InvestmentLifecycle::STATE_OPEN,
            InvestmentLifecycle::STATE_APPROVED,
            InvestmentLifecycle::STATE_OPEN,
            InvestmentLifecycle::STATE_APPROVED,
            TransactionStatus::Created,
            TransactionStatus::Succeeded,
        ];
        yield 'success transaction change only' => [
            true,
            InvestmentLifecycle::STATE_APPROVED,
            InvestmentLifecycle::STATE_APPROVED,
            InvestmentLifecycle::STATE_APPROVED,
            InvestmentLifecycle::STATE_APPROVED,
            TransactionStatus::Failed,
            TransactionStatus::Failed,
        ];
        yield 'success investment change only' => [
            true,
            InvestmentLifecycle::STATE_OPEN,
            InvestmentLifecycle::STATE_APPROVED,
            InvestmentLifecycle::STATE_OPEN,
            InvestmentLifecycle::STATE_APPROVED,
            TransactionStatus::Created,
            TransactionStatus::Succeeded,
        ];
        yield 'success no changes' => [
            true,
            InvestmentLifecycle::STATE_WITHDRAWN,
            InvestmentLifecycle::STATE_WITHDRAWN,
            InvestmentLifecycle::STATE_WITHDRAWN,
            InvestmentLifecycle::STATE_WITHDRAWN,
            TransactionStatus::Failed,
            TransactionStatus::Failed,
        ];
        yield 'success no liquidation change only' => [
            true,
            InvestmentLifecycle::STATE_WITHDRAWN,
            InvestmentLifecycle::STATE_WITHDRAWN,
            InvestmentLifecycle::STATE_OPEN,
            InvestmentLifecycle::STATE_APPROVED,
            TransactionStatus::Created,
            TransactionStatus::Succeeded,
        ];

        yield 'fail both change' => [
            false,
            InvestmentLifecycle::STATE_OPEN,
            InvestmentLifecycle::STATE_WITHDRAWN,
            InvestmentLifecycle::STATE_OPEN,
            InvestmentLifecycle::STATE_WITHDRAWN,
            TransactionStatus::Created,
            TransactionStatus::Failed,
        ];
        yield 'fail transaction change only' => [
            false,
            InvestmentLifecycle::STATE_APPROVED,
            InvestmentLifecycle::STATE_APPROVED,
            InvestmentLifecycle::STATE_APPROVED,
            InvestmentLifecycle::STATE_APPROVED,
            TransactionStatus::Created,
            TransactionStatus::Failed,
        ];
        yield 'fail investment change only' => [
            false,
            InvestmentLifecycle::STATE_OPEN,
            InvestmentLifecycle::STATE_WITHDRAWN,
            InvestmentLifecycle::STATE_OPEN,
            InvestmentLifecycle::STATE_WITHDRAWN,
            TransactionStatus::Succeeded,
            TransactionStatus::Succeeded,
        ];
        yield 'fail no changes' => [
            false,
            InvestmentLifecycle::STATE_APPROVED,
            InvestmentLifecycle::STATE_APPROVED,
            InvestmentLifecycle::STATE_APPROVED,
            InvestmentLifecycle::STATE_APPROVED,
            TransactionStatus::Succeeded,
            TransactionStatus::Succeeded,
        ];
        yield 'fail no retention change' => [
            false,
            InvestmentLifecycle::STATE_OPEN,
            InvestmentLifecycle::STATE_WITHDRAWN,
            InvestmentLifecycle::STATE_APPROVED,
            InvestmentLifecycle::STATE_APPROVED,
            TransactionStatus::Created,
            TransactionStatus::Failed,
        ];
    }
}
