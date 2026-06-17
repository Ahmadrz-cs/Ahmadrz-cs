<?php

namespace App\Tests\Functional\Ops\MonthEnd\Settlement;

use App\Tests\Support\FunctionalTester;

class SettlementOverviewCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->loginAdmin();
    }

    public function seeTableAndStatHeadings(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/monthend/settlements');

        $elements = [
            'Asset',
            'Investments to settle',
            'Actions',
        ];
        $I->loopCheckElements($elements, '#asset-settlement-list thead tr th');

        $stats = [
            '[data-field-name="total-to-settle"]',
            '[data-field-name="prefunding"]',
            '[data-field-name="retail-first-party"]',
            '[data-field-name="retail-relisted"]',
        ];
        foreach ($stats as $stat) {
            $I->seeElement($stat);
        }

        // Check search range for share-trades
        $I->submitForm(['css' => 'form'], [
            'dateStart' => date('Y-m', strtotime('first day of last month')) . '-01',
            'dateEnd' => date('Y-m', strtotime('first day of next month')) . '-01',
        ]);
        $I->seeNumberOfElements('tbody tr', [1, 100]);
        $I->see('Go to Asset Monthend Dashboard', '#asset-settlement-list tbody tr a');
    }
}
