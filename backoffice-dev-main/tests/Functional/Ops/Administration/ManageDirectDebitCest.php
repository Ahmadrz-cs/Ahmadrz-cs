<?php

namespace App\Tests\Functional\Ops\Administration;

use App\Tests\Support\FunctionalTester;

class ManageDirectDebitCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function checkElements(FunctionalTester $I)
    {
        $I->amOnPage('/admin/administration/directdebit/all');

        $elements = [
            'Due Direct Debits',
            'Settled Direct Debits',
            'ID',
            'User',
            'Active',
            'MangoPay Status',
            'MangoPay Result Code',
            'Amount (£)',
            'Last Settlement Date',
        ];

        $I->loopCheckElements($elements);

        $I->amOnPage('/admin/administration/directdebit');
        $I->see('All Direct Debits');
    }
}
