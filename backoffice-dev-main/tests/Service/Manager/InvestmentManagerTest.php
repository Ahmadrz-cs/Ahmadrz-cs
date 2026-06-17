<?php

/**
 * Created by PhpStorm.
 * User: Keesh
 * Date: 05/01/17
 * Time: 15:34
 */

namespace App\Tests\Service\Manager;

use App\Entity\Investment;
use App\Entity\Lifecycle\InvestmentLifecycle;
use App\Entity\User;
use App\Service\Manager\InvestmentManager;
use App\Test\FixtureTestCase;

class InvestmentManagerTest extends FixtureTestCase
{
    private InvestmentManager $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(InvestmentManager::class);
    }

    private $testQueryParams = [
        'offest' => '0',
        'limit' => '1',
        'sort' => '+id,-updatedAt',
        'id' => '1,3,4',
        'status' => '4,5',
        'type' => 'commercial,residential',
        'term' => '1,3,5',
        'biscuits' => 'digestive,shortbread',
        'user' => 'whodat,whatder',
    ];

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('collection-filter')]
    public function testGetCriteria(): void
    {
        $expected = ['id', 'type'];

        // only return allowed criteria
        $actual = $this->service->getCriteria($this->testQueryParams);
        $this->assertEmpty(array_diff($expected, array_keys($actual)));

        // pass empty array
        $actual = $this->service->getCriteria([]);
        $this->assertEmpty($actual);
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('collection-filter')]
    public function testGetAuxiliaryFilters(): void
    {
        $expected = ['status'];

        // only return allowed auxiliary filters
        $actual = $this->service->getAuxiliaryFilters($this->testQueryParams, true);
        $this->assertEmpty(array_diff($expected, array_keys($actual)));

        // pass default status null as admin - may want to improve it to handle empty arrays
        $actual = $this->service->getAuxiliaryFilters(['status' => null], true);
        $this->assertEmpty($actual);
    }

    public function testInvestmentLifecycleTransitionsApproveWithdraw(): void
    {
        /** @var User $user */
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_REGULAR]);

        $investment = new Investment();
        $investment->setInvestmentValue(200);
        $investment->setVisibility(0);
        $investment->setName('something');
        $investment->setUser($user);
        $investment->setCurrency('GBP');

        $this->service->approveInvestment($investment);
        $this->assertEquals(
            InvestmentLifecycle::STATE_APPROVED,
            $investment->getLifecycleStatus(),
        );

        $this->service->withdrawInvestment($investment);
        $this->assertEquals(
            InvestmentLifecycle::STATE_WITHDRAWN,
            $investment->getLifecycleStatus(),
        );
    }

    public function testInvestmentLifecycleTransitionApproveReject(): void
    {
        /** @var User $user */
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_REGULAR]);

        $investment = new Investment();
        $investment->setInvestmentValue(200);
        $investment->setVisibility(0);
        $investment->setName('something');
        $investment->setUser($user);
        $investment->setCurrency('GBP');

        $this->service->approveInvestment($investment);
        $this->assertEquals(
            InvestmentLifecycle::STATE_APPROVED,
            $investment->getLifecycleStatus(),
        );

        $this->service->rejectInvestment($investment);
        $this->assertEquals(
            InvestmentLifecycle::STATE_REJECTED,
            $investment->getLifecycleStatus(),
        );
    }
}
