<?php

namespace App\Tests\Functional\Cms\Profile;

use App\Tests\Support\FunctionalTester;

class MfaConfigurationCest
{
    public function checkMfaManagementWhenMfaOff(FunctionalTester $I)
    {
        // 1. 2FA status - Not-Enabled
        $I->loginAsUser($I::USER_SUPER_ADMIN, $I::TEST_PASSWORD);
        $I->amOnPage('/admin/profile/mfa');
        $I->see('Not-Enabled', '#mfa-totp-state');
        // $I->see('Not-Enabled', '#mfa-email-state');

        // 2. Setup 2fa link
        $I->seeLink('Setup App-code 2FA', '/admin/profile/mfa/setup/totp');
        // $I->seeLink('Enable Email 2FA', '/admin/profile/mfa/setup/email');

        // 3. Redirect of various routes
        $I->amOnPage('/admin/profile/mfa/disable/totp');
        $I->seeCurrentUrlEquals('/admin/profile/mfa');
        $I->see('Two-Factor-Authentication successfully disabled');

        // $I->amOnPage('/admin/profile/mfa/disable/email');
        // $I->seeCurrentUrlEquals('/admin/profile/mfa');
        // $I->see('Two-Factor-Authentication successfully disabled');

        $I->amOnPage('/admin/profile/mfa/check');
        $I->seeCurrentUrlEquals('/admin/profile/mfa');
        $I->see('2FA not enabled. Nothing to check');

        // 4. Check preference setting
        $I->see('Default', '#mfa-email-state .badge');
        $I->see('Set As Preferred', '#mfa-prefer-email');
        $I->see('Set As Preferred', '#mfa-prefer-totp');
        $I->seeLink('Set As Preferred', '/admin/profile/mfa/preference/email');
        $I->seeLink('Set As Preferred', '/admin/profile/mfa/preference/totp');

        $I->click('#mfa-prefer-email');
        $I->see('Preferred', '#mfa-email-state .badge');
        $I->dontSee('Default', '#mfa-email-state .badge');
        $I->dontSee('Preferred', '#mfa-totp-state .badge');
        $I->dontSee('Set As Preferred', '#mfa-prefer-email');
        $I->see('Set As Preferred', '#mfa-prefer-totp');

        $I->click('#mfa-prefer-totp');
        $I->dontSee('Preferred', '#mfa-email-state .badge');
        $I->dontSee('Default', '#mfa-email-state .badge');
        $I->see('Preferred', '#mfa-totp-state .badge');
        $I->see('Set As Preferred', '#mfa-prefer-email');
        $I->dontSee('Set As Preferred', '#mfa-prefer-totp');
    }
}
