<?php

namespace App\Tests\Functional\Cms\Investment;

use App\Entity\Lifecycle\InvestmentLifecycle;
use App\Tests\Support\FunctionalTester;

class InvestmentListCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    /**
     * check that the action add payout to an investment
     * @group listview
     */
    public function checkAddInvestmentPayoutAction(FunctionalTester $I)
    {
        $I->amOnPage('/admin/investment');
        $I->seeLink('Add Payout');
    }

    /**
     * @group listview
     */
    public function checkListViewElements(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/investment');

        $elements = [
            '#',
            'Date',
            'Type',
            'Investor',
            'Amount Invested',
            'Original Number of Shares',
            'Shares Divested',
            'Offering ID',
            'Asset Name',
            'Status',
            'Actions',
        ];
        // check table headers
        $I->loopCheckElements($elements, 'thead th');

        $filterLabels = [
            'User id',
            'Username',
            'Asset id',
            'Asset name',
            'Offering id',
            'User is Vip',
            'Retail or Corporate',
            'Status',
            'Type',
            'CreatedAt Start',
            'CreatedAt End',
            'Items Per Page',
            'Order by',
            'Order Direction',
        ];
        // check table filters
        $I->loopCheckElements($filterLabels, 'form label');
        $I->seeLink('Add Investment', '/admin/investment/add');
        $I->see('Show/Hide Status', 'form button');
        $I->see('Show/Hide Type', 'form button');

        $I->amOnPage('/admin/investment');
        $I->seeNumberOfElements('#investmentlist tbody tr', 10);
        $I->selectOption('form select[name=perPage]', '5');
        $I->click('Apply Filters');
        $I->seeNumberOfElements('#investmentlist tbody tr', 5);

        // check max page bracketing (to deal with filter changing)
        $I->amOnPage('/admin/investment?page=1000');
        // Sends you to last page
        $I->seeElement(['css' => '.pagination li:last-child.disabled']);
    }

    /**
     * @group listview
     * @dataProvider filterProvider
     */
    public function checkListViewFilters(
        FunctionalTester $I,
        \Codeception\Example $example,
    ): void {
        if (empty($example['dbquery'])) {
            $example['dbquery'] = $example['filters'];
        }
        $count = $I->grabNumRecords('investments', $example['dbquery']);
        $I->amOnPage(
            '/admin/investment?'
            . http_build_query($example['filters'])
            . '&perPage=50',
        );
        $I->seeNumberOfElements('#investmentlist tbody tr', min(50, $count)); // at most 50 results
        $I->see($count, '#list-meta-results');
    }

    /**
     * @group listview
     */
    public function checkListViewFiltersStatus(FunctionalTester $I): void
    {
        // status filter separate as it uses an array rather than single values
        $query = [
            'lifecycleStatus' => [
                InvestmentLifecycle::STATE_OPEN,
                InvestmentLifecycle::STATE_APPROVED,
                InvestmentLifecycle::STATE_WITHDRAWN,
            ],
        ];
        $I->amOnPage('/admin/investment?' . http_build_query($query));
        foreach ($query['lifecycleStatus'] as $status) {
            $I->see($status, '#investmentlist tbody tr .badge');
        }
        $omittedStatuses = [
            InvestmentLifecycle::STATE_REJECTED,
            InvestmentLifecycle::STATE_SETTLED,
        ];
        foreach ($omittedStatuses as $status) {
            $I->dontSee($status, '#investmentlist tbody tr .badge');
        }
    }

    /**
     * @group listview
     */
    public function checkInvestmentPresetFilters(FunctionalTester $I)
    {
        $I->amOnPage('/admin/investment');
        $I->seeLink('Any Prefunding', '/admin/investment?type%5B0%5D=prefunding');
        $I->seeLink('Any by Corporate', '/admin/investment?corporateInvestor=1');
        $I->seeLink(
            'Approved Pending Settlement',
            '/admin/investment?lifecycleStatus%5B0%5D=approved',
        );
        $I->seeLink('Settled', '/admin/investment?lifecycleStatus%5B0%5D=settled');
        $I->seeLink(
            'Settled pending share certificate',
            '/admin/investment?lifecycleStatus%5B0%5D=settled&hasDocuments=0',
        );
    }

    protected function filterProvider(): array
    {
        return [
            [
                'filters' => ['id' => '1'],
            ],
            [
                'filters' => [
                    'userId' => 1,
                    'type' => ['prefunding'],
                ],
                'dbquery' => [
                    'user_id' => 1,
                    'type' => 'prefunding',
                ],
            ],
            [
                'filters' => [
                    'offeringId' => 2,
                ],
                'dbquery' => [
                    'off_id' => 2,
                ],
            ],
            [
                'filters' => [
                    'createdAt_gte' => date_format(new \DateTime('-4 days'), 'Y-m-d'),
                    'createdAt_lt' => date_format(new \DateTime('-1 days'), 'Y-m-d'),
                ],
                'dbquery' => [
                    'createdAt >=' => date_format(
                        new \DateTime('-4 days')->setTime(0, 0),
                        \DateTime::ATOM,
                    ),
                    'createdAt <' => date_format(
                        new \DateTime('-1 days')->setTime(0, 0),
                        \DateTime::ATOM,
                    ),
                ],
            ],
        ];
    }
}
