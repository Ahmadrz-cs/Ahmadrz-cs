<?php

namespace App\Tests\Functional\Cms\Transaction;

use App\Tests\Support\FunctionalTester;

class TransactionListCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    /**
     * @group listview
     */
    public function checkElements(FunctionalTester $I)
    {
        $I->amOnPage('/admin/transactions');

        $elements = [
            'Id',
            'Type',
            'Status',
            'Reference',
            'Currency',
            'Amount',
            'Credit Wallet',
            'Debit Wallet',
            'Description',
            'Credit User',
            'Debit User',
            'Investment Id',
            'Created By',
        ];

        $locator = 'thead th';

        //check table headers
        $I->loopCheckElements($elements, $locator);
    }

    /**
     * @group listview
     */
    public function checkFilterPresets(FunctionalTester $I)
    {
        $I->amOnPage('/admin/transactions');
        $I->seeLink('Succeeded Only', '/admin/transactions?payment_status=SUCCEEDED');
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
        $expected = $I->grabNumRecords('transactions', $example['dbquery']);
        $I->amOnPage('/admin/transactions?' . http_build_query($example['filters']));
        $resultsCountText = $I->grabTextFrom('#list-meta-results');
        $actual = (int) explode(' ', $resultsCountText)[0];
        $I->assertEquals($expected, $actual);
    }

    protected function filterProvider(): array
    {
        return [
            ['filters' => ['id' => '12']],
            ['filters' => ['credited_wallet_id' => 'wlt_m_01HW3DKQ6D3Y5Z2AM717NBZD6V']],
            [
                'filters' => [
                    'credited_wallet_id' => 'wlt_m_01HW3DKQ6D3Y5Z2AM717NBZD6V',
                    'payment_status' => 'SUCCEEDED',
                ],
            ],
            [
                'filters' => ['comments' => 'demo'],
                'dbquery' => ['comments like' => '%demo%'],
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
