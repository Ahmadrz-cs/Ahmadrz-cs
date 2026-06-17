<?php

namespace App\Tests\Functional\Cms\Users;

use App\Tests\Support\FunctionalTester;

class UserManagerListCest
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
        $I->amOnPage('/admin/users/managers');

        $elements = [
            'Id',
            'Name',
            'Email',
            'Join Date',
            'Users Managed',
            'Actions',
        ];

        $locators = 'thead th';

        //check table headers
        $I->loopCheckElements($elements, $locators);
    }
}
