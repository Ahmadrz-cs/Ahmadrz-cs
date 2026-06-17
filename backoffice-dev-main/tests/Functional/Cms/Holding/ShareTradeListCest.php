<?php

namespace App\Tests\Functional\Cms\Holding;

use App\Tests\Support\FunctionalTester;

class ShareTradeListCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    /**
     * @group listview
     */
    public function checkElementsDefault(FunctionalTester $I)
    {
        $I->amOnPage('/admin/holding/trades');

        $elements = [
            'Asset',
            'Buyer',
            'Seller',
            'Shares traded',
            'Investment',
            'Invested',
            'Settled',
        ];
        $locator = 'thead tr th';
        $I->loopCheckElements($elements, $locator);

        // check filters available
        $fields = [
            'assetId',
            'buyerId',
            'sellerId',
            'settledFrom',
            'settledTo',
            'aggregate',
        ];
        foreach ($fields as $fieldId) {
            $I->seeElement('input#' . $fieldId);
        }
    }

    /**
     * @group listview
     */
    public function checkElementsAggregate(FunctionalTester $I)
    {
        $I->amOnPage('/admin/holding/trades?aggregate=1');

        $elements = [
            'Asset',
            'Seller',
            'Shares traded',
            'Trade Count',
            'Settled',
        ];

        $locator = 'thead tr th';

        //check table headers
        $I->loopCheckElements($elements, $locator);
    }

    /**
     * @group listview
     */
    public function checkFilterFormQueryParameters(FunctionalTester $I)
    {
        $query = [
            'assetId' => 12,
            'buyerId' => 36,
            'sellerId' => 72,
            'settledFrom' => '2018-03-15',
            'settledTo' => '2020-12-31',
            'aggregate' => 1,
        ];
        $I->amOnPage('/admin/holding/trades?' . http_build_query($query));

        foreach ($query as $fieldId => $fieldValue) {
            $I->assertEquals($fieldValue, $I->grabAttributeFrom(
                'input#' . $fieldId,
                'value',
            ));
        }
    }
}
