<?php

namespace App\Tests\Functional\Cms\Holding;

use App\Tests\Support\FunctionalTester;

class ShareholdingListCest
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
        $I->amOnPage('/admin/holding');

        $elements = [
            'Asset',
            'User',
            'Current Shareholding',
            'Original Shareholding',
            'Divested Shareholding',
            'Divestment Trades',
            'Repaid Shareholding',
        ];

        $locator = 'thead tr th';

        //check table headers
        $I->loopCheckElements($elements, $locator);
    }

    /**
     * @group listview
     */
    public function checkFilterForm(FunctionalTester $I)
    {
        $I->amOnPage('/admin/holding');
        $I->seeLink('Clear Filters', '/admin/holding');

        $fields = [
            'assetId',
            'userId',
            'currentHolding',
            'capitalRepayments',
        ];

        foreach ($fields as $fieldId) {
            $I->seeElement('form #' . $fieldId);
        }

        /**
         * Check form submissions
         */
        $I->click('Apply Filters');
        $query = [
            'assetId' => '',
            'userId' => '',
            'currentHolding' => 1,
        ];
        $I->seeCurrentUrlEquals('/admin/holding?' . http_build_query($query));

        /**
         * Check form prefills from query parameters
         */
        $query = [
            'assetId' => 123,
            'userId' => 987,
            'currentHolding' => 1,
            'capitalRepayments' => 1,
        ];
        $I->amOnPage('/admin/holding?' . http_build_query($query));
        $I->assertEquals($query['assetId'], $I->grabAttributeFrom('#assetId', 'value'));
        $I->assertEquals($query['userId'], $I->grabAttributeFrom('#userId', 'value'));
        $I->seeOptionIsSelected('#currentHolding', 'Current/Active');
        $I->seeCheckboxIsChecked('#capitalRepayments');

        /**
         * Check notice appears if you try to
         * view capital repayments with only current shareholders
         */
        $I->see('Don\'t see expected divested shareholders?');
    }
}
