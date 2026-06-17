<?php

namespace App\Tests\Service;

use App\Dto\AccountSummary;
use App\Dto\AssetSummary;
use App\Entity\Asset;
use App\Entity\Investment;
use App\Entity\Offering;
use App\Entity\Payout;
use App\Service\ReportsService;
use App\Service\Util\Helper;
use App\Test\FixtureTestCase;
use App\Test\Util\EntityIdTestUtil;

class ReportsServiceTest extends FixtureTestCase
{
    /** @var \App\Service\ReportsService */
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(ReportsService::class);
    }

    /**
     * @psalm-return \Generator<string, array{0: bool, 1: 500|float, 2: 2782|3000|float}, mixed, void>
     */
    public static function totalPayoutSummaryProviderExclMonthsProvider(): \Generator
    {
        yield 'sold-off-asset-two-ints' => [true, 500, 3000];
        yield 'active-asset-two-ints' => [false, 500, 3000];
        yield 'sold-off-two-floats' => [true, 500.66, 2852.88];
        yield 'active-two-floats' => [false, 500.66, 2852.88];
        yield 'sold-off-type-mixture' => [true, 500.66, 2782];
        yield 'active-type-mixture' => [false, 500.66, 2782];
    }

    /**
     * @psalm-return \Generator<'two-floats'|'two-ints'|'type-mixture', array{0: 500|float, 1: 2782|3000|float}, mixed, void>
     */
    public static function totalAmountProvider(): \Generator
    {
        yield 'two-ints' => [500, 3000];
        yield 'two-floats' => [500.66, 2852.88];
        yield 'type-mixture' => [500.66, 2782];
    }

    /**
     * @psalm-return \Generator<string, array{0: string, 1: bool}, mixed, void>
     */
    public static function isAssetSoldOffProvider(): \Generator
    {
        yield 'true-as-string' => ['true', true];
        yield 'true-as-int-string' => ['1', true];
        yield 'false-as-string' => ['false', false];
        yield 'false-as-int-string' => ['0', false];
        yield 'caps-mixture' => ['tRuE', true];
        yield 'no-added-field' => ['', false];
    }

    /**
     * @psalm-return \Generator<string, array{0: bool, 1: float, 2: 0|70|100, 3: 0|1|2, 4: 0|float}, mixed, void>
     */
    public static function totalInvestmentStatsProvider(): \Generator
    {
        yield 'sold-off-asset' => [true, 1.20, 0, 0, 0];
        yield 'one-fully-divested-investment' => [false, 1.75, 100, 1, 1.75 * 100];
        yield 'one-partially-divested-investment' => [false, 2.81, 70, 2, 365.3];
        yield 'no-divested-investments' => [false, 1.39, 0, 2, 1.39 * 100 * 2];
    }

    /**
     * @psalm-return \Generator<string, array{0: bool, 1: float|int, 2: float|int, 3: float|int, 4: float|int}, mixed, void>
     */
    public static function reduceCapitalAppreciationProvider(): \Generator
    {
        yield 'active-asset' => [false, 0, 1002, 0, 0];
        yield 'active-asset-partially-divested-investments' => [false, 0, 1002, 300, 0];
        yield 'sold-off-asset-no-divestment-profit' => [
            true,
            3121,
            1450,
            0,
            3121 - (1450 * 2),
        ];
        yield 'sold-off-asset-no-divestment-loss' => [
            true,
            2503,
            1381,
            0,
            2503 - (1381 * 2),
        ];
        yield 'sold-off-asset-partially-divested-profit' => [
            true,
            2600,
            1500.29,
            250.87,
            2600 - ((1500.29 - 250.87) * 2),
        ];
        yield 'sold-off-asset-partially-divested-investments-loss' => [
            true,
            2953.8,
            1891,
            250,
            2953.8 - ((1891 * 2) - 500),
        ];
        yield 'sold-off-asset-fully-divested-investments-even' => [
            true,
            0,
            1100,
            1100,
            0,
        ];
        yield 'sold-off-asset-fully-divested-investments-profit' => [
            true,
            0,
            1100,
            1200,
            200,
        ];
    }

    public function testGetTotalCapitalAppreciationAmount(): void
    {
        $user = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => self::USER_REGULAR],
            false,
            false,
        )[0];
        $payouts = $this->searchFixtures(
            \App\Entity\Payout::class,
            ['payoutType' => '1'],
            false,
            false,
        );
        $investments = $this->searchFixtures(
            \App\Entity\Investment::class,
            ['user' => $user->getId()],
            false,
            false,
        );

        //manually check total capital appreciation
        $totalCapitalAppreciation = 0;
        foreach ($payouts as $payout) {
            $payoutUser = $payout->getCreditedUser();
            if (empty($payoutUser)) {
                if ($payout->getInvestment()) {
                    $payoutUser = $payout->getInvestment()->getUser();
                }
            }
            if ($user == $payoutUser) {
                $totalCapitalAppreciation += $payout->getPayoutAmount();
            }
        }
        foreach ($investments as $inv) {
            $asset = $inv->getOffering()->getAsset();
            if ($this->service->isAssetSoldOff($asset)) {
                $remainingValue =
                    $inv->getInvestmentValue() - $inv->getDivestedAmount();
                $totalCapitalAppreciation -= $remainingValue;
            }
        }
        $this->assertEqualsWithDelta(
            $totalCapitalAppreciation,
            $this->service->getTotalCapitalAppreciationAmount($user),
            0.001,
        );
    }

    public function testGetTotalDividendAmount(): void
    {
        $user = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => self::USER_REGULAR],
            false,
            false,
        )[0];

        $payouts = $this->searchFixtures(
            \App\Entity\Payout::class,
            ['payoutType' => '0'],
            false,
            false,
        );

        //manually check total div payout
        $totalDividendAmount = 0;
        foreach ($payouts as $payout) {
            $payoutUser = $payout->getCreditedUser();
            if (empty($payoutUser)) {
                $payoutUser = $payout->getInvestment()->getUser();
            }
            if ($user == $payoutUser) {
                $totalDividendAmount += $payout->getPayoutAmount();
            }
        }
        $this->assertGreaterThan(0, $totalDividendAmount); // Should not be zero for this test
        $this->assertEqualsWithDelta(
            $totalDividendAmount,
            $this->service->getTotalDividendAmount($user),
            0.001,
        );

        // $this->assertEquals($totalDividendAmount, $this->service->getTotalDividendAmount($user));
    }

    public function testGetUnfilteredTotalReturnAmount(): void
    {
        $user = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => self::USER_REGULAR],
            false,
            false,
        )[0];

        $divPayouts = $this->searchFixtures(
            \App\Entity\Payout::class,
            ['payoutType' => '0'],
            false,
            false,
        );
        $capitalPayouts = $this->searchFixtures(
            \App\Entity\Payout::class,
            ['payoutType' => '1'],
            false,
            false,
        );

        $payouts = array_merge($divPayouts, $capitalPayouts);

        //manually check total return
        $totalReturn = 0;
        foreach ($payouts as $payout) {
            $payoutUser = $payout->getCreditedUser();
            if (empty($payoutUser)) {
                if ($payout->getInvestment()) {
                    $payoutUser = $payout->getInvestment()->getUser();
                }
            }
            if ($user == $payoutUser) {
                $totalReturn += $payout->getPayoutAmount();
            }
        }
        $this->assertGreaterThan(0, $totalReturn); // Should not be zero for this test
        $this->assertEqualsWithDelta(
            $totalReturn,
            $this->service->getUnfilteredTotalReturnAmount($user),
            0.001,
        );

        // $this->assertEquals($totalReturn, $this->service->getUnfilteredTotalReturnAmount($user));
    }

    public function testGetTotalInvestedAmount(): void
    {
        $user = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => self::USER_REGULAR],
            false,
            false,
        )[0];

        $investments = $this->searchFixtures(
            \App\Entity\Investment::class,
            ['user' => $user->getId()],
            false,
            false,
        );

        //manually check total invested
        $totalInvested = 0;
        foreach ($investments as $inv) {
            if (
                $inv->getLifecycleStatus() == 'settled'
                or $inv->getLifecycleStatus() == 'approved'
            ) {
                $totalInvested += $inv->getInvestmentValue();
            }
        }
        $this->assertGreaterThan(0, $totalInvested); // Should not be zero for this test
        $this->assertEqualsWithDelta(
            $totalInvested,
            $this->service->getTotalInvestedAmount($user),
            0.001,
        );
    }

    public function testGetUserAccountSummaryExclMonths(): void
    {
        $user = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => self::USER_REGULAR],
            false,
            false,
        )[0];

        $result = $this->service->getUserAccountSummary($user);
        $this->assertTrue($result instanceof AccountSummary);
        $this->assertTrue($result->getTotalInvestmentCount() > 0);
        $this->assertTrue($result->getTotalReturn() > 0);
        $this->assertTrue($result->getTotalDividend() > 0);
        $this->assertTrue($result->getTotalCapitalAppreciation() >= 0);
        $this->assertTrue($result->getMonthlyPayouts() == null);
    }

    public function testGetUserAccountSummaryIncMonths(): void
    {
        $user = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => self::USER_REGULAR],
            false,
            false,
        )[0];

        $result = $this->service->getUserAccountSummary($user, 'cumulative');
        $this->assertTrue(!empty($result->getMonthlyPayouts()));
    }

    public function testGetAccountSummaryExclMonths(): void
    {
        $mockInvestments = $this->createMockInvestments();
        $result = $this->service->getAccountSummary(
            $mockInvestments['investments'],
            $mockInvestments['payouts'],
        );
        $this->assertTrue($result instanceof AccountSummary);
        $this->assertTrue($result->getTotalInvestmentCount() > 0);
        $this->assertTrue($result->getTotalReturn() > 0);
        $this->assertTrue($result->getTotalDividend() > 0);
        $this->assertTrue($result->getTotalCapitalAppreciation() > 0);
        $this->assertTrue($result->getMonthlyPayouts() == null);
    }

    public function testGetAccountSummaryIncMonths(): void
    {
        $mockInvestments = $this->createMockInvestments();
        $result = $this->service->getAccountSummary(
            $mockInvestments['investments'],
            $mockInvestments['payouts'],
            'cumulative',
        );
        $this->assertTrue(!empty($result->getMonthlyPayouts()));
    }

    public function testGetMonthlyPayoutSummary(): void
    {
        $user = $this->searchFixtures(
            \App\Entity\User::class,
            ['id' => '1'],
            false,
            false,
        )[0];

        $divPayouts = $this->searchFixtures(
            \App\Entity\Payout::class,
            ['payoutType' => '0'],
            false,
            false,
        );
        $capitalPayouts = $this->searchFixtures(
            \App\Entity\Payout::class,
            ['payoutType' => '1'],
            false,
            false,
        );

        $payouts = array_merge($divPayouts, $capitalPayouts);

        $now = new \DateTime();
        $oneYearAgo = $now->modify('-1 year');

        //manually calc total received from payouts
        $totalReceived = 0;
        foreach ($payouts as $payout) {
            $payoutUser = $payout->getCreditedUser();
            //exclude payouts over a year ago
            if ($payout->getDueDate() < $oneYearAgo) {
                continue;
            }
            if (empty($payoutUser)) {
                if ($payout->getInvestment()) {
                    $payoutUser = $payout->getInvestment()->getUser();
                }
            }
            if ($user == $payoutUser) {
                $totalReceived += $payout->getPayoutAmount();
            }
        }

        $result = $this->service->getMonthlyPayoutSummary($user);
        $this->assertNotEmpty($result);
        $this->assertEqualsWithDelta($totalReceived, $result[date('Y-m')], 0.001);

        // $this->assertEquals($totalReceived, $result[date('Y-m')]);
    }

    public function testGetRunningTotal(): void
    {
        $example = [23, 18, 5, 8, 10, 16];
        $expected = [23, 41, 46, 54, 64, 80];

        $this->assertEquals($expected, $this->service->getRunningTotal($example));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('totalAmountProvider')]
    public function testGetTotalInvestmentValue(
        float $investmentValue1,
        $investmentValue2,
    ): void {
        $investment1 = new Investment();
        $investment1->setInvestmentValue($investmentValue1);

        $investment2 = new Investment();
        $investment2->setInvestmentValue($investmentValue2);

        $investments = [$investment1, $investment2];
        $totalInvested = $investmentValue1 + $investmentValue2;
        $this->assertEquals(
            $totalInvested,
            $this->service->getTotalInvestmentValue($investments),
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider(
        'totalPayoutSummaryProviderExclMonthsProvider',
    )]
    public function testGetTotalPayoutSummaryExclMonths(
        bool $soldOff,
        float $divPayoutAmount,
        float $capitalPayoutAmount,
    ): void {
        $asset = new Asset();
        if ($soldOff) {
            $addField = new \App\Entity\AssetAddFields();
            $addField->setFieldKey('sold_off');
            $addField->setValue('true');
            $asset->addAddField($addField);
        }

        $offering = new Offering();
        $offering->setAsset($asset);

        $investment1 = new Investment();
        $investment1->setInvestmentValue(1000);
        $investment1->setOffering($offering);

        $investment2 = new Investment();
        $investment2->setInvestmentValue(800);
        $investment2->setOffering($offering);

        $divPayout1 = new Payout();
        $divPayout1->setPayoutType(0);
        $divPayout1->setPayoutAmount($divPayoutAmount);

        $divPayout2 = new Payout();
        $divPayout2->setPayoutType(0);
        $divPayout2->setPayoutAmount($divPayoutAmount);

        $totalCapitalAppreciation = 0;
        $payouts = [$divPayout1, $divPayout2];
        $totalReturn = $divPayoutAmount * 2;

        if ($soldOff) {
            $capitalPayout1 = new Payout();
            $capitalPayout1->setPayoutType(1);
            $capitalPayout1->setPayoutAmount($capitalPayoutAmount);

            $capitalPayout2 = new Payout();
            $capitalPayout2->setPayoutType(1);
            $capitalPayout2->setPayoutAmount($capitalPayoutAmount);
            $totalCapitalAppreciation =
                ($capitalPayoutAmount * 2) - $investment1->getInvestmentValue()
                - $investment2->getInvestmentValue();
            $payouts = [$divPayout1, $divPayout2, $capitalPayout1, $capitalPayout2];
        }

        $investments = [$investment1, $investment2];
        $totalDividend = $divPayoutAmount * 2;
        $totalReturn = $totalCapitalAppreciation + $totalDividend;

        $result = $this->service->getTotalPayoutSummary($payouts, $investments);
        $this->assertEquals($totalReturn, $result['totalReturn']);
        $this->assertEquals($totalDividend, $result['totalDividend']);
        $this->assertEquals(
            $totalCapitalAppreciation,
            $result['totalCapitalAppreciation'],
        );
        $this->assertTrue(empty($result['montlySummmary']));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('totalAmountProvider')]
    public function testGetTotalPayoutSummaryIncMonths(
        float $divPayoutAmount,
        float $capitalPayoutAmount,
    ): void {
        $dueDate = new \DateTime();
        $dueDate->modify('-1 year');
        $dueDate->modify('-1 month');

        $divPayout1 = new Payout();
        $divPayout1->setPayoutType(0);
        $divPayout1->setPayoutAmount($divPayoutAmount);

        $divPayout2 = new Payout();
        $divPayout2->setPayoutType(0);
        $divPayout2->setPayoutAmount($divPayoutAmount);
        //set due date to over a year ago (won't be included in months)
        $divPayout2->setDueDate($dueDate);

        $capitalPayout1 = new Payout();
        $capitalPayout1->setPayoutType(1);
        $capitalPayout1->setPayoutAmount($capitalPayoutAmount);

        $capitalPayout2 = new Payout();
        $capitalPayout2->setPayoutType(1);
        $capitalPayout2->setPayoutAmount($capitalPayoutAmount);

        $payouts = [$divPayout1, $divPayout2, $capitalPayout1, $capitalPayout2];
        $totalReceived = $divPayoutAmount + ($capitalPayoutAmount * 2);

        $months = Helper::generatePastMonthsStrings();
        $result = $this->service->getTotalPayoutSummary($payouts, [], $months);
        $this->assertEquals($totalReceived, $result['montlySummmary'][date('Y-m')]);
    }

    public function testGetTotalPayoutSummaryDivestments(): void
    {
        /**
         * Mix of old and new style payouts
         * - Old profit share (full divestment) - asset sold
         * - New full divestment - asset sold
         * - New full divestment - asset partially sold
         * - New partial divestment - asset partially sold
         * - Multi-investment divestment - asset partially sold
         */

        $addField = new \App\Entity\AssetAddFields();
        $addField->setFieldKey('sold_off');
        $addField->setValue('true');
        $assetSold = new Asset();
        $assetSold->setPricePerShare(1.55);
        $assetSold->addAddField($addField);
        $offeringSold = new Offering();
        $offeringSold->setAsset($assetSold);

        $investmentOld = new Investment();
        $investmentOld->setInvestmentValue(852.5);
        $investmentOld->setOffering($offeringSold);

        $investmentNewSold = new Investment();
        $investmentNewSold->setInvestmentValue(155);
        $investmentNewSold->setOffering($offeringSold);
        $investmentNewSold->setExtraSharesDivested(100);
        $reflection = new \ReflectionProperty(
            get_class($investmentNewSold),
            'divested_amount',
        );
        $reflection->setValue($investmentNewSold, '155');

        $divestmentOld = new Payout();
        $divestmentOld->setPayoutType(1);
        $divestmentOld->setPayoutAmount(895.23); // profit of 42.73

        $divestmentNewSold = new Payout();
        $divestmentNewSold->setPayoutType(1);
        $divestmentNewSold->setPayoutAmount(145.87); // loss of 9.13
        $divestmentNewSold->setAsset($assetSold);
        $divestmentNewSold->setShareholding(100);

        $assetActive = new Asset();
        $assetActive->setPricePerShare(1.68);
        $offeringActive = new Offering();
        $offeringActive->setAsset($assetActive);

        $activeAssetInvestments = [];
        $investmentValues = [430.08, 168, 1458.24, 860.16];
        foreach ($investmentValues as $v) {
            $i = new Investment();
            $i->setOffering($offeringActive);
            $i->setInvestmentValue($v);
            $activeAssetInvestments[] = $i;
        }

        $activeAssetPayouts = [];
        $divestments = [
            '256' => 435.62, // profit of 5.54
            '80' => 148.62, // profit of 14.22
            '1024' => 1758.91, // profit of 38.59 - payout that covers 2 investments
        ];
        foreach ($divestments as $sh => $v) {
            $p = new Payout();
            $p->setPayoutType(1);
            $p->setAsset($assetActive);
            $p->setShareholding((int) $sh);
            $p->setPayoutAmount($v);
            $activeAssetPayouts[] = $p;
        }

        $result = $this->service->getTotalPayoutSummary([
            $divestmentOld,
            $divestmentNewSold,
            ...$activeAssetPayouts,
        ], [
            $investmentOld,
            $investmentNewSold,
            ...$activeAssetInvestments,
        ]);
        $this->assertEqualsWithDelta(91.95, $result['totalReturn'], 0.001);
        $this->assertEqualsWithDelta(91.95, $result['totalCapitalAppreciation'], 0.001);
        // $this->assertEquals(91.95, $result['totalReturn']);
        $this->assertEquals(0, $result['totalDividend']);

        // $this->assertEquals(91.95, $result['totalCapitalAppreciation']);
    }

    public function testMapAssetIdsToInvestments(): void
    {
        $investment = $this->createPartialMock(Investment::class, ['getAssetId']);
        $investment->expects($this->any())->method('getAssetId')->willReturn(1);

        $investment2 = $this->createPartialMock(Investment::class, ['getAssetId']);
        $investment2->expects($this->any())->method('getAssetId')->willReturn(2);

        $investment3 = $this->createPartialMock(Investment::class, ['getAssetId']);
        $investment3->expects($this->any())->method('getAssetId')->willReturn(1);

        $investments = [$investment, $investment2, $investment3];
        $result = $this->service->mapAssetIdsToInvestments($investments);
        $this->assertEquals(2, count($result[1]));
        $this->assertEquals(1, count($result[2]));
    }

    public function testMapAssetIdsToPayouts(): void
    {
        /** @var Asset $asset */
        $asset = EntityIdTestUtil::setEntityId(new Asset(), 1);

        /** @var Asset $asset2 */
        $asset2 = EntityIdTestUtil::setEntityId(new Asset(), 2);

        /** @var Offering $offering */
        $offering = EntityIdTestUtil::setEntityId(new Offering(), 2);
        $offering->setAsset($asset);

        $investment = new Investment();
        $investment->setOffering($offering);

        $payout = new Payout();
        $payout->setInvestment($investment);

        $payout2 = new Payout();
        $payout2->setAsset($asset);

        $payout3 = new Payout();
        $payout3->setAsset($asset2);

        $payouts = [$payout, $payout2, $payout3];
        $result = $this->service->mapAssetIdsToPayouts($payouts);
        $this->assertEquals(2, count($result[1]));
        $this->assertEquals(1, count($result[2]));
    }

    public function testGetAssetSummariesExclMonths(): void
    {
        $mockInvestments = $this->createMockInvestments();
        $result = $this->service->getAssetSummaries(
            $mockInvestments['investments'],
            $mockInvestments['payouts'],
        );
        $this->assertTrue($result[1] instanceof AssetSummary);
        $this->assertEquals(2, $result[1]->getAssetInvestmentCount());
        $this->assertEquals(200, $result[1]->getAssetInvestmentValue());
        $this->assertEquals(100, $result[1]->getAssetDividend());
        $this->assertEquals(0, $result[1]->getAssetCapitalAppreciation());
        $this->assertTrue(empty($result[1]->getMonthlyPayouts()));
        $this->assertEquals(
            $result[1]->getAssetDividend() + $result[1]->getAssetCapitalAppreciation(),
            $result[1]->getAssetReturn(),
        );

        $this->assertTrue($result[2] instanceof AssetSummary);
        $this->assertEquals(0, $result[2]->getAssetInvestmentCount());
        $this->assertEquals(0, $result[2]->getAssetInvestmentValue());
        $this->assertEquals(0, $result[2]->getAssetDividend());
        $this->assertEquals(10, $result[2]->getAssetCapitalAppreciation());
        $this->assertTrue(empty($result[2]->getMonthlyPayouts()));
        $this->assertEquals(
            $result[2]->getAssetDividend() + $result[2]->getAssetCapitalAppreciation(),
            $result[2]->getAssetReturn(),
        );
    }

    public function testGetAssetSummariesIncMonths(): void
    {
        $mockInvestments = $this->createMockInvestments();
        $result = $this->service->getAssetSummaries(
            $mockInvestments['investments'],
            $mockInvestments['payouts'],
            'cumulative',
        );
        $this->assertTrue(!empty($result[1]->getMonthlyPayouts()));
    }

    public function testGetAssetSummaryExclMonths(): void
    {
        $mockInvestments = $this->createMockInvestments();
        $result = $this->service->getAssetSummary(
            1,
            $mockInvestments['investments'],
            $mockInvestments['payouts'],
        );
        $this->assertTrue($result instanceof AssetSummary);
        $this->assertEquals(2, $result->getAssetInvestmentCount());
        $this->assertEquals(200, $result->getAssetInvestmentValue());
        $this->assertEquals(100, $result->getAssetReturn());
        $this->assertEquals(100, $result->getAssetDividend());
        $this->assertEquals(0, $result->getAssetCapitalAppreciation());
        $this->assertTrue(empty($result->getMonthlyPayouts()));
    }

    public function testGetAssetSummaryIncMonths(): void
    {
        $mockInvestments = $this->createMockInvestments();
        $result = $this->service->getAssetSummary(
            1,
            $mockInvestments['investments'],
            $mockInvestments['payouts'],
            'cumulative',
        );
        $this->assertTrue(!empty($result->getMonthlyPayouts()));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('isAssetSoldOffProvider')]
    public function testIsAssetSoldOff(string $value, bool $expected): void
    {
        $addedField = new \App\Entity\AssetAddFields();

        if ($value != '') {
            $addedField->setFieldKey('sold_off');
            $addedField->setValue($value);
        }

        $asset = new Asset();
        $asset->addAddField($addedField);

        $this->assertEquals($expected, $this->service->isAssetSoldOff($asset));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('totalInvestmentStatsProvider')]
    public function testGetTotalInvestmentStats(
        bool $soldOff,
        float $pricePerShare,
        int $divested,
        int $expectedCount,
        float $expectedValue,
    ): void {
        /** @var Asset $asset */
        $asset = EntityIdTestUtil::setEntityId(new Asset(), 1);

        if ($soldOff) {
            $addedField = new \App\Entity\AssetAddFields();
            $addedField->setFieldKey('sold_off');
            $addedField->setValue('true');
            $asset->addAddField($addedField);
        }

        $offering = new Offering();
        $offering->setAsset($asset);
        $offering->setNoOfShares(300);
        $offering->setPricePerShare($pricePerShare);

        //two investments in the asset
        $investment1 = $this->createPartialMock(Investment::class, [
            'getDivestedShares',
        ]);
        $investment1->setShareAmount(100);
        $investment1->setOffering($offering);
        $investment1->setInvestmentValue(
            $offering->getPricePerShare() * $investment1->getShareAmount(),
        );
        $investment1->expects($this->any())->method('getDivestedShares')->willReturn(0);

        $investment2 = $this->createPartialMock(Investment::class, [
            'getDivestedShares',
            'getDivestedAmount',
        ]);
        $investment2->setShareAmount(100);
        $investment2->setOffering($offering);
        $investment2->setInvestmentValue(
            $offering->getPricePerShare() * $investment2->getShareAmount(),
        );
        $investment2
            ->expects($this->any())
            ->method('getDivestedShares')
            ->willReturn($divested);
        $investment2
            ->expects($this->any())
            ->method('getDivestedAmount')
            ->willReturn($offering->getPricePerShare() * $divested);

        $investments = [$investment1, $investment2];
        $investmentStats = $this->service->getTotalInvestmentStats($investments);

        $this->assertEquals($expectedCount, $investmentStats['totalInvestmentCount']);
        $this->assertEqualsWithDelta(
            $expectedValue,
            $investmentStats['totalInvestmentValue'],
            0.001,
        );

        // $this->assertEquals($expectedValue, $investmentStats["totalInvestmentValue"]);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('reduceCapitalAppreciationProvider')]
    public function testReduceCapitalAppreciation(
        bool $soldOff,
        float $capApp,
        float $investmentValue,
        float $investmentDivestedAmount,
        float $sanitizedCapApp,
    ): void {
        $asset = new Asset();

        if ($soldOff) {
            $addField = new \App\Entity\AssetAddFields();
            $addField->setFieldKey('sold_off');
            $addField->setValue('true');
            $asset->addAddField($addField);
        }

        $offering = new Offering();
        $offering->setAsset($asset);

        $investment1 = $this->createPartialMock(Investment::class, [
            'getDivestedAmount',
        ]);
        $investment1->setOffering($offering);
        $investment1->setInvestmentValue($investmentValue);
        $investment1
            ->expects($this->any())
            ->method('getDivestedAmount')
            ->willReturn($investmentDivestedAmount);

        $investment2 = $this->createPartialMock(Investment::class, [
            'getDivestedAmount',
        ]);
        $investment2->setOffering($offering);
        $investment2->setInvestmentValue($investmentValue);
        $investment2
            ->expects($this->any())
            ->method('getDivestedAmount')
            ->willReturn($investmentDivestedAmount);

        $investments = [$investment1, $investment2];
        $this->assertEquals($sanitizedCapApp, $this->service->reduceCapitalAppreciation(
            $capApp,
            $investments,
            $investmentDivestedAmount,
        ));
    }

    public function testGetAssetSummariesScenarioNoPayouts(): void
    {
        //test for checking that the asset summary values are not duplicated if one asset has no payouts

        /** @var Asset $asset */
        $asset = EntityIdTestUtil::setEntityId(new Asset(), 1);

        /** @var Asset $asset2 */
        $asset2 = EntityIdTestUtil::setEntityId(new Asset(), 2);

        $offering = new Offering();
        $offering->setAsset($asset);
        $offering->setNoOfShares(300);

        $offering2 = new Offering();
        $offering2->setAsset($asset2);
        $offering2->setNoOfShares(300);

        //two investments in seperate investments
        $investment1 = $this->createPartialMock(Investment::class, [
            'getDivestedShares',
        ]);
        $investment1->setInvestmentValue(100);
        $investment1->setShareAmount(100);
        $investment1->setOffering($offering);
        $investment1->expects($this->any())->method('getDivestedShares')->willReturn(0);

        $investment2 = $this->createPartialMock(Investment::class, [
            'getDivestedShares',
        ]);
        $investment2->setInvestmentValue(100);
        $investment2->setShareAmount(100);
        $investment2->setOffering($offering2);
        $investment2->expects($this->any())->method('getDivestedShares')->willReturn(0);

        //only one payout
        $payout1 = new Payout();
        $payout1->setPayoutType(0);
        $payout1->setPayoutAmount(50);
        $payout1->setAsset($asset);

        $investments = [$investment1, $investment2];
        $result = $this->service->getAssetSummaries($investments, [$payout1]);

        $this->assertEquals(50, $result[1]->getAssetReturn());
        $this->assertEquals(50, $result[1]->getAssetDividend());
        $this->assertEquals(0, $result[1]->getAssetCapitalAppreciation());

        $this->assertEquals(0, $result[2]->getAssetReturn());
        $this->assertEquals(0, $result[2]->getAssetDividend());
        $this->assertEquals(0, $result[2]->getAssetCapitalAppreciation());
    }

    public function createMockInvestments(): array
    {
        $addedField = new \App\Entity\AssetAddFields();
        $addedField->setFieldKey('sold_off');
        $addedField->setValue('true');

        /** @var Asset $activeAsset */
        $activeAsset = EntityIdTestUtil::setEntityId(new Asset(), 1);

        /** @var Asset $soldAsset */
        $soldAsset = EntityIdTestUtil::setEntityId(new Asset(), 2);
        //sold off asset
        $soldAsset->addAddField($addedField);

        //active asset offering
        $activeOffering = new Offering();
        $activeOffering->setAsset($activeAsset);
        $activeOffering->setNoOfShares(300);

        //sold off asset offering
        $offeringSoldAsset = new Offering();
        $offeringSoldAsset->setAsset($soldAsset);
        $offeringSoldAsset->setNoOfShares(300);

        //two investments in active asset
        $investment1 = $this->createPartialMock(Investment::class, [
            'getDivestedShares',
        ]);
        $investment1->setInvestmentValue(100);
        $investment1->setShareAmount(100);
        $investment1->setOffering($activeOffering);
        $investment1
            ->expects($this->any())
            ->method('getDivestedShares')
            ->willReturn(50);

        $investment2 = $this->createPartialMock(Investment::class, [
            'getDivestedShares',
        ]);
        $investment2->setInvestmentValue(100);
        $investment2->setShareAmount(100);
        $investment2->setOffering($activeOffering);
        $investment2
            ->expects($this->any())
            ->method('getDivestedShares')
            ->willReturn(50);

        //investment in sold asset
        $investment3 = $this->createPartialMock(Investment::class, [
            'getDivestedShares',
            'getDivestedAmount',
        ]);
        $investment3->setInvestmentValue(100);
        $investment3->setShareAmount(100);
        $investment3->setOffering($offeringSoldAsset);
        $investment3->expects($this->any())->method('getDivestedShares')->willReturn(0);
        $investment3->expects($this->any())->method('getDivestedAmount')->willReturn(0);

        //two payouts for the active asset
        $payout1 = new Payout();
        $payout1->setPayoutType(0);
        $payout1->setPayoutAmount(50);
        $payout1->setAsset($activeAsset);

        $payout2 = new Payout();
        $payout2->setPayoutType(0);
        $payout2->setPayoutAmount(50);
        $payout2->setInvestment($investment2);

        //profit share payout for sold asset
        $payout3 = new Payout();
        $payout3->setPayoutType(1);
        $payout3->setPayoutAmount(110);
        $payout3->setAsset($soldAsset);

        $payouts = [$payout1, $payout2, $payout3];
        $investments = [$investment1, $investment2, $investment3];

        return [
            'payouts' => $payouts,
            'investments' => $investments,
        ];
    }
}
