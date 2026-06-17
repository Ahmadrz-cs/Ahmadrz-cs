<?php

namespace App\Tests\Functional\Cms\Payout;

use App\Tests\Support\FunctionalTester;

class PayoutListCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    /**
     * @group listview
     */
    public function checkListViewElements(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/payout');

        $elements = [
            'Id',
            'Asset',
            'Due Date',
            'Investor',
            'Investment Id',
            'Currency',
            'Payout Amount',
            'Payout Type',
        ];
        // check table headers
        $I->loopCheckElements($elements, 'thead th');

        $filterLabels = [
            'User id',
            'Asset id',
            'Asset name',
            'Investment id',
            'Type',
            'CreatedAt Start',
            'CreatedAt End',
            'Items Per Page',
            'Order by',
            'Order Direction',
        ];
        // check table filters
        $I->loopCheckElements($filterLabels, 'form label');
        $I->see('Show/Hide Type', 'form button');

        $I->amOnPage('/admin/payout');
        $I->seeNumberOfElements('#payoutlist tbody tr', 10);
        $I->selectOption('form select[name=perPage]', '5');
        $I->click('Apply Filters');
        $I->seeNumberOfElements('#payoutlist tbody tr', 5);

        // check max page bracketing (to deal with filter changing)
        $I->amOnPage('/admin/payout?page=1000');
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
        $count = $I->grabNumRecords('payouts', $example['dbquery']);
        $I->amOnPage(
            '/admin/payout?' . http_build_query($example['filters']) . '&perPage=50',
        );
        $I->seeNumberOfElements('#payoutlist tbody tr', min(50, $count)); // at most 50 results
        $I->see($count, '#list-meta-results');
    }

    /**
     * @group listview
     */
    public function checkPayoutPresetFilters(FunctionalTester $I)
    {
        $I->amOnPage('/admin/payout');
        $I->seeLink('Dividends', '/admin/payout?payoutType%5B0%5D=0');
        $I->seeLink('Any non-dividend', '/admin/payout?payoutType%5B0%5D=1');
    }

    protected function filterProvider(): array
    {
        return [
            [
                'filters' => ['id' => '1'],
            ],
            [
                'filters' => [
                    'payoutType' => ['0'],
                ],
                'dbquery' => [
                    'payoutType' => '0',
                ],
            ],
            [
                'filters' => [
                    'investmentId' => 1,
                ],
                'dbquery' => [
                    'investment_id' => 1,
                ],
            ],
            [
                'filters' => [
                    'createdAt_gte' => date_format(new \DateTime('-3 months'), 'Y-m-d'),
                    'createdAt_lt' => date_format(new \DateTime('-1 months'), 'Y-m-d'),
                ],
                'dbquery' => [
                    'createdAt >=' => date_format(
                        new \DateTime('-3 months')->setTime(0, 0),
                        \DateTime::ATOM,
                    ),
                    'createdAt <' => date_format(
                        new \DateTime('-1 months')->setTime(0, 0),
                        \DateTime::ATOM,
                    ),
                ],
            ],
        ];
    }
}
