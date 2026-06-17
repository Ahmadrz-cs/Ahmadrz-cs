<?php

namespace App\Tests\Functional\Cms\Dashboard;

use App\Tests\Support\FunctionalTester;

class ActivityTimelineCest
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
        $I->amOnPage('/admin/activity');

        $elements = [
            'Actions',
            'Id',
            'Date',
            'Action',
            'By User',
            'On Object',
            'With Id',
        ];

        $locator = 'table th';

        $I->loopCheckElements($elements, $locator);

        // $I->amOnPage('/admin/activity/1');
        // $I->seeElement('#activity-log');
    }
}
