<?php

namespace App\Tests\Functional\Cms\Profile;

use App\Tests\Support\FunctionalTester;

class MfaEmailCest
{
    // public function checkMfaSetupEmail(FunctionalTester $I)
    // {
    //     /**
    //      *
    //      */
    //     $I->loginAsUser($I::USER_ADMIN_ENGINEERING, $I::TEST_PASSWORD);
    //     $I->amOnPage('/admin/profile/mfa/setup/email');
    //     $I->seeCurrentUrlEquals('/admin/profile/mfa');
    //     $I->see('Two-Factor-Authentication is now enabled');
    //     $I->see('Enabled', '#mfa-email-state');
    // }

    public function checkMfaEmailLoginPrompt(FunctionalTester $I)
    {
        /**
         * Login with 2fa
         * Disable 2FA
         * Login should be back to normal
         */
        $mailcatcher = $I->getMailcatcherClient();
        $mailcatcher->delete('/messages');
        $I->amOnPage('/login');
        $I->fillField('_username', $I::USER_ADMIN_ENGINEERING);
        $I->fillField('_password', $I::TEST_PASSWORD);
        $I->click('Login');
        $I->seeCurrentUrlEquals('/2fa');

        $authCode = explode(':', $mailcatcher->get('/messages/1.plain')->getBody())[1];
        $I->fillField('_auth_code', trim($authCode));
        $I->click('form input[type=submit]');
        $I->seeCurrentUrlEquals('/admin');

        // Note that since the button is in a modal that is hidden by JS, not clickable by phpbrowser
        $I->amOnPage('/admin/profile/mfa/disable/email');
        $I->see('Two-Factor-Authentication successfully disabled');

        $I->amOnPage('/logout');
        $I->loginAsUser($I::USER_ADMIN_ENGINEERING, $I::TEST_PASSWORD);
        $I->seeCurrentUrlEquals('/admin');

        // reset mfa status for reruns
        $I->amOnPage('/admin/profile/mfa/setup/email');
    }
}
