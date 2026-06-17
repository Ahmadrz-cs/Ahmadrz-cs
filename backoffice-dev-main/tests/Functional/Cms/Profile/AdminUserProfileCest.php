<?php

namespace App\Tests\Functional\Cms\Profile;

use App\Tests\Support\FunctionalTester;

class AdminUserProfileCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function checkProfilePageContents(FunctionalTester $I)
    {
        /**
         * Check Profile page sections
         * 1. Two Factor Authentication
         */
        $I->amOnPage('/admin/profile');
        $I->see('Two Factor Authentication');
        $I->seeLink('Manage Two Factor Authentication', '/admin/profile/mfa');
    }
}
