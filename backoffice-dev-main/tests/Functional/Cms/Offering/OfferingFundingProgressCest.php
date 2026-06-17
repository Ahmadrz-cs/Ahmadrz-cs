<?php

namespace App\Tests\Functional\Cms\Offering;

use App\Tests\Support\FunctionalTester;

class OfferingFundingProgressCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function checkPendingRelistingList(FunctionalTester $I)
    {
        $I->amOnPage('/admin/offering');
        $I->click('View by Raised Amount');
        $I->seeCurrentUrlEquals('/admin/offering/funding-progress');

        $tableHeaders = [
            'Offering Id',
            'Seller Type',
            'Asset',
            'Raised (£)',
            'Offered (£)',
            'Progress (%)',
            'Status',
            'Created',
            'Published',
        ];

        foreach ($tableHeaders as $tableHeader) {
            $I->see($tableHeader, '#offering-funding-progress-list thead th');
        }
    }
}
