<?php

namespace App\Tests\Functional\Cms\Holding;

use App\Tests\Support\FunctionalTester;

class ShareholdingTrackingCest
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
        $I->amOnPage('/admin/holding/summary');

        $elements = [
            'Asset',
            'Total Shares for Asset',
            'Shares In Circulation',
            'External Commitments (£)',
            'External Commitments (Shares)',
        ];

        $locator = '#share-circulation-list thead tr th';

        //check table headers
        $I->loopCheckElements($elements, $locator);

        // And the new trade based one
        $elements = [
            'Asset Id',
            'Asset Name',
            'Asset Guide Price',
            'Trades',
            'Orders',
            'Sells',
            'Buys',
            'Asset Declared Shares',
            'Legacy Shares Circulating',
            'Trade Shares Circulating',
            'Buy Value',
            'Sell Value',
            'Net Value',
        ];

        $locator = '#trade-holdings thead tr th';

        //check table headers
        $I->loopCheckElements($elements, $locator);
    }
}
