<?php

namespace App\Tests\Functional\Cms\Transaction;

use App\Tests\Support\FunctionalTester;

class TransactionViewCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    /**
     * @group detailview
     */
    public function checkElements(FunctionalTester $I)
    {
        $I->amOnPage('/admin/transactions/1');

        $elements = [
            'Id',
            'Type',
            'Status',
            'Reference Id',
            'Debit User Id',
            'Credit User Id',
            'Debit Wallet Id',
            'Credit Wallet Id',
            'Amount',
            'Fees',
            'Currency',
            'Share Amount',
            'Investment Id',
            'Offering Id',
            'Trade Order Id',
            'Comments',
            'Created At',
            'Created By',
            'Updated At',
            'Updated By',
        ];

        $locator = '#transaction-info';

        //check table headers
        $I->loopCheckElements($elements, $locator);
    }
}
