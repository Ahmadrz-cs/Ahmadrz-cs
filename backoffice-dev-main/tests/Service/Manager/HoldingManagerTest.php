<?php

namespace App\Tests\Service\Manager;

use App\Entity\Enum\AllocationMethod;
use App\Entity\Lifecycle\InvestmentLifecycle;
use App\Repository\HoldingRepository;
use App\Service\Manager\HoldingManager;
use App\Test\FixtureTestCase;

class HoldingManagerTest extends FixtureTestCase
{
    /** @var \App\Service\Manager\HoldingManager */
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(HoldingManager::class);
    }

    public function testGetAssetShareholdings(): void
    {
        $sample = $this->searchFixtures(
            \App\Entity\Asset::class,
            [
                'name' => 'Lodge de Lac - Cumbria', // must be an asset with shareholders, otherwise it will not return anything
            ],
            true,
        );
        /**
         * Check that passing an asset Id limits search results to single one
         */
        $actual = $this->service->getAssetShareholdings();
        $this->assertGreaterThan(1, count($actual));
        $actual = $this->service->getAssetShareholdings($sample[0]);
        $this->assertCount(1, $actual);
        $this->assertEquals($sample[0], $actual[0]['assetId']);
    }

    public function testGetShareholders(): void
    {
        $shareholdings = [
            [
                'userId' => '1',
                'currentHolding' => 10638,
            ],
            [
                'userId' => '4',
                'currentHolding' => 5398,
            ],
            [
                'userId' => '3',
                'currentHolding' => 4833,
            ],
            [
                'userId' => '2',
                'currentHolding' => 3103,
            ],
            [
                'userId' => '15',
                'currentHolding' => 1028,
            ],
        ];
        /** @var \PHPUnit\Framework\MockObject\Stub $holdingRepositoryStub */
        $holdingRepositoryStub = $this->createStub(HoldingRepository::class);
        $holdingRepositoryStub->method('getShareHoldings')->willReturn($shareholdings);

        /** @var HoldingRepository $holdingRepositoryStub */
        $service = new HoldingManager(
            $this->createStub(\Psr\Log\LoggerInterface::class),
            static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class),
            $holdingRepositoryStub,
        );
        $expected = [
            '1' => 10638,
            '4' => 5398,
            '3' => 4833,
            '2' => 3103,
            '15' => 1028,
        ];
        $actual = $service->getShareholders(14);
        $this->assertEmpty(array_diff_assoc($expected, $actual));
    }

    public function testGetPrefundingShareholders(): void
    {
        $filterRetail = $this->searchFixtures(\App\Entity\Asset::class, [
            'name' => 'Clarence Hold A - Camden',
        ]);
        $filterPrefunding = $this->searchFixtures(\App\Entity\Asset::class, [
            'name' => 'Nixis Plutona - Bristol',
        ]);
        /**
         * Check that only assets with prefunding investments return something
         */
        $actual = $this->service->getPrefundingShareholders($filterRetail[0]);
        $this->assertEmpty($actual);
        $actual = $this->service->getPrefundingShareholders($filterPrefunding[0]);
        $this->assertNotEmpty($actual);
    }

    public function testAggregateSettledInvestmentsByUser(): void
    {
        $sample = $this->searchFixtures(\App\Entity\User::class);
        $userInvestments = [];
        // Generate 8 investments for 2 users
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= 3) {
                $investment = new \App\Entity\Investment();
                $investment->setUser($sample[1]);
                $investment->setNumberOfShares(6547);
                $investment->setInvestmentValue(1);
                $userInvestments[] = $investment;
            }
            $investment = new \App\Entity\Investment();
            $investment->setUser($sample[0]);
            $investment->setNumberOfShares(492);
            $investment->setInvestmentValue(1);
            $userInvestments[] = $investment;
        }
        /**
         * One extra investment that checks
         * - share_amount also counts
         * - capital repayment accounted for
         * - extra shares divested accounted for
         */
        $investment = new \App\Entity\Investment();
        $investment->setUser($sample[2]);
        $investment->setShareAmount(1786);
        $investment->setInvestmentValue(1);
        $investment->setExtraSharesDivested(336);
        $investmentAddField = new \App\Entity\InvestmentAddFields();
        $investmentAddField->setFieldKey('capitalRepaid');
        $investmentAddField->setFieldValue('897');
        $investment->addAddField($investmentAddField);
        $userInvestments[] = $investment;

        /**
         * One extra investment that checks the guard clause for empty investments
         * - In case there are any investments in an invalid state
         * - Value of 0
         * - But non-zero shareholding
         * This investment should be ignored
         * Thus the user should not show up in the result
         */
        $investment = new \App\Entity\Investment();
        $investment->setUser($sample[3]);
        $investment->setShareAmount(6589);
        $investment->setInvestmentValue(0);
        $userInvestments[] = $investment;

        foreach ($userInvestments as $investment) {
            $investment->setLifecycleStatus(InvestmentLifecycle::STATE_SETTLED);
        }
        $actual = $this->service->aggregateSettledInvestmentsByUser($userInvestments);
        $expected = [
            $sample[1]->getId() => 19641, // 3 * 6547
            $sample[0]->getId() => 2460, // 5 * 492
            $sample[2]->getId() => 553, // 1786 - 897 - 336
        ];
        $this->assertEmpty(array_diff_assoc($expected, $actual));
    }

    public function testAggregateSettledInvestmentsNonSettledMix(): void
    {
        $sample = $this->searchFixtures(\App\Entity\User::class);
        $userInvestments = [];
        // Generate 8 investments for 2 users
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= 3) {
                $investment = new \App\Entity\Investment();
                $investment->setUser($sample[1]);
                $investment->setNumberOfShares(6547);
                $investment->setInvestmentValue(1);
                $userInvestments[] = $investment;
            }
            $investment = new \App\Entity\Investment();
            $investment->setUser($sample[0]);
            $investment->setNumberOfShares(492);
            $investment->setInvestmentValue(1);
            $userInvestments[] = $investment;
        }
        /**
         * One extra investment that checks
         * - share_amount also counts
         * - capital repayment accounted for
         * - extra shares divested accounted for
         */
        $investment = new \App\Entity\Investment();
        $investment->setUser($sample[2]);
        $investment->setShareAmount(1786);
        $investment->setInvestmentValue(1);
        $investment->setExtraSharesDivested(336);
        $investmentAddField = new \App\Entity\InvestmentAddFields();
        $investmentAddField->setFieldKey('capitalRepaid');
        $investmentAddField->setFieldValue('897');
        $investment->addAddField($investmentAddField);
        $userInvestments[] = $investment;

        /**
         * Set status by leveraging intAsState()
         * - 0 => draft 6547
         * - 1 => rejected 492
         * - 2 => approved 6547
         * - 3 => withdrawn 492
         * - 4-8 => settled 6547 492 492 492 (1786-336-897)
         */
        foreach ($userInvestments as $index => $investment) {
            if ($index <= 4) {
                $investment->setLifecycleStatus(InvestmentLifecycle::intAsState(
                    $index,
                ));
            } else {
                $investment->setLifecycleStatus(InvestmentLifecycle::STATE_SETTLED);
            }
        }

        $actual = $this->service->aggregateSettledInvestmentsByUser($userInvestments);
        $expected = [
            $sample[1]->getId() => 6547, // 1 * 6547
            $sample[0]->getId() => 1476, // 3 * 492
            $sample[2]->getId() => 553, // 1786 - 897 - 336
        ];
        $this->assertEmpty(array_diff_assoc($expected, $actual));
    }

    public function testAggregateSettledInvestmentsByAsset(): void
    {
        $sample1 = $this->searchFixtures(\App\Entity\Offering::class, [
            'status' => 'published',
            'name' => 'Kolness by the Moor - Okehampton',
        ])[0];
        $sample2 = $this->searchFixtures(\App\Entity\Offering::class, [
            'status' => 'published',
            'name' => 'Clarence Hold A - Camden',
        ])[0];
        $user = $this->searchFixtures(\App\Entity\User::class, [
            'username' => self::USER_REGULAR,
        ])[0];
        $assetInvestments = [];
        // Generate 8 investments for 2 assets
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= 3) {
                $investment = new \App\Entity\Investment();
                $investment->setOffering($sample1);
                $investment->setUser($user);
                $investment->setNumberOfShares(6547);
                $investment->setInvestmentValue(1);
                $assetInvestments[] = $investment;
            }
            $investment = new \App\Entity\Investment();
            $investment->setOffering($sample2);
            $investment->setUser($user);
            $investment->setNumberOfShares(492);
            $investment->setInvestmentValue(1);
            $assetInvestments[] = $investment;
        }
        /**
         * One extra investment that checks
         * - share_amount also counts
         * - capital repayment accounted for
         * - extra shares divested accounted for
         */
        $investment = new \App\Entity\Investment();
        $investment->setUser($user);
        $investment->setOffering($sample2);
        $investment->setShareAmount(1786);
        $investment->setInvestmentValue(1);
        $investment->setExtraSharesDivested(336);
        $investmentAddField = new \App\Entity\InvestmentAddFields();
        $investmentAddField->setFieldKey('capitalRepaid');
        $investmentAddField->setFieldValue('897');
        $investment->addAddField($investmentAddField);
        $assetInvestments[] = $investment;

        /**
         * One extra investment that checks the guard clause for empty investments
         * - In case there are any investments in an invalid state
         * - Value of 0
         * - But non-zero shareholding
         * This investment should be ignored
         * Thus the asset should not show up in the result
         */
        $investment = new \App\Entity\Investment();
        $investment->setUser($user);
        $investment->setOffering($sample2);
        $investment->setShareAmount(6589);
        $investment->setInvestmentValue(0);
        $assetInvestments[] = $investment;

        foreach ($assetInvestments as $investment) {
            $investment->setLifecycleStatus(InvestmentLifecycle::STATE_SETTLED);
        }
        $actual = $this->service->aggregateSettledInvestmentsByAsset($assetInvestments);
        $expected = [
            $sample1->getAsset()->getId() => 19641, // 3 * 6547
            $sample2->getAsset()->getId() => 3013, // (5 * 492) + 1786 - 336 - 897
        ];
        $this->assertEmpty(array_diff_assoc($expected, $actual));
    }

    public function testGetUserAssetShareHoldings(): void
    {
        // Asset and user combination must be a valid one where the user has invested in that asset
        $asset = $this->searchFixtures(
            \App\Entity\Asset::class,
            [
                'name' => 'Lodge de Lac - Cumbria',
            ],
            true,
        );
        $user = $this->searchFixtures(
            \App\Entity\User::class,
            [
                'username' => self::USER_REGULAR,
            ],
            true,
        );

        $actual = $this->service->getUserAssetShareHoldings($asset[0], $user[0]);
        $this->assertCount(1, $actual);
        $this->assertEquals($asset[0], $actual[0]['assetId']);
        $this->assertEquals($user[0], $actual[0]['userId']);
    }
}
