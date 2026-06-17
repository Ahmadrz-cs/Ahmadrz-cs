<?php

namespace App\Tests\Functional\Cms\Payout;

use App\Tests\Support\FunctionalTester;

class PayoutDetailsCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    /**
     * @group detailview
     */
    public function checkPayoutDetailsElements(FunctionalTester $I)
    {
        $I->amOnPage('/admin/payout/1/edit');

        $elements = [
            'Cancel',
            'Submit',
            'Investment',
            'Asset',
            'Credited user',
            'Payout type',
            'Transaction Id',
            'Shareholding',
            'Due date',
            'Currency',
            'Payout Amount',
            'Last updated:',
            'Created:',
        ];

        //Check form fields
        $I->loopCheckElements($elements);
    }

    /**
     * @group detailview
     */
    public function checkEditPayout(FunctionalTester $I)
    {
        $I->amOnPage('/admin/payout');

        //Take existing payout
        $id = $I->grabTextFrom('tbody tr:nth-child(1) td:nth-child(1) a');
        $I->amOnPage('/admin/payout/' . $id . '/edit');

        $I->selectOption('input#payout_payoutType_0', '0');
        $I->fillField('input#payout_dueDate', date('Y') . '-12-01');
        $I->fillField('input#payout_payoutAmount', 12.8);
        $I->fillField('input#payout_transactionId', 88821408);
        $I->click('button#payout_submit');
    }
}
