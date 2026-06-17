<?php

namespace App\Tests\Functional\Ops\MonthEnd\Repayment;

use App\Tests\Support\FunctionalTester;

class RepaymentOverviewCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->loginAdmin();
    }

    public function seeTableAndStatHeadings(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/monthend/repayments');

        $elements = [
            'Asset',
            'Shares Issued',
            'Shares in Circulation',
            'Shares Still to Repay',
            'Shares Available to Repay',
            'Actions',
        ];
        $I->loopCheckElements($elements, '#prefunder-repayment-list thead tr th');
        $stats = [
            '[data-field-name="assets-with-prefunders-to-repay"]',
            '[data-field-name="assets-with-shares-available-to-repay"]',
        ];
        foreach ($stats as $stat) {
            $I->seeElement($stat);
        }

        $I->seeNumberOfElements('tbody tr', [1, 100]);
        $I->see('Review Asset Repayments', '#prefunder-repayment-list tbody tr a');
    }
}
