<?php

namespace App\Tests\Functional\Cms\Profile;

use App\Tests\Support\FunctionalTester;

// class MfaTotpCest
// {
//     protected $secret;

//     protected function saveVar($var = "")
//     {
//         $this->secret = $var;
//     }

//     public function checkMfaSetupTotp(FunctionalTester $I)
//     {
//         /**
//          * 1. Two Factor Authentication
//          * 2. Check that 2FA manage page has updated
//          * 3. Use Check tool
//          * 4. Check redirects
//          * 5. Enable email 2fa
//          * 6. Set totp preference
//          *
//          */
//         $I->loginAsUser($I::USER_ADMIN_ENGINEERING, $I::TEST_PASSWORD);
//         $I->amOnPage('/admin/profile/mfa/setup/totp');
//         $secret = $I->grabTextFrom('#mfa-key');
//         $this->saveVar($secret);
//         $timestamp = time();
//         $I->fillField('#form_code1', $I->generateOTP($timestamp - 30, $secret));
//         $I->fillField('#form_code2', $I->generateOTP($timestamp, $secret));
//         $I->click('Enable Two-Factor-Authentication', '#form_submit');

//         $I->seeCurrentUrlEquals('/admin/profile/mfa');
//         $I->see('Two-Factor-Authentication is now enabled');
//         $I->see('Enabled', '#mfa-totp-state');
//         $I->seeLink('Check 2FA Codes', '/admin/profile/mfa/check');

//         $I->seeElement('#mfa-totp-disable');
//         $I->seeLink('Disable App-code 2FA', '/admin/profile/mfa/disable/totp');

//         $I->click('Check 2FA Codes');
//         $I->fillField('#form_code1', $I->generateOTP(time(), $secret));
//         $I->click('Submit', '#form_submit');
//         $I->see('Code valid');

//         $I->amOnPage('/admin/profile/mfa/setup/totp');
//         $I->seeCurrentUrlEquals('/admin/profile/mfa');

//         $I->amOnPage('/admin/profile/mfa/setup/email');
//         $I->amOnPage('/admin/profile/mfa/preference/totp');
//     }

//     /**
//      * @depends checkMfaSetupTotp
//      */
//     public function checkMfaTotpLoginPrompt(FunctionalTester $I)
//     {
//         /**
//          * Login with 2fa
//          * Disable 2FA
//          * Login should be back to normal
//          */
//         $I->amOnPage('/login');
//         $I->fillField('_username', $I::USER_ADMIN_ENGINEERING);
//         $I->fillField('_password', $I::TEST_PASSWORD);
//         $I->click('Login');
//         $I->seeCurrentUrlEquals('/2fa');

//         $I->fillField('_auth_code', $I->generateOTP(time(), $this->secret));
//         $I->checkOption('#_trusted');
//         $I->click('form input[type=submit]');
//         $I->see('Activity timeline');

//         // Note that since the button is in a modal that is hidden by JS, not clickable by phpbrowser
//         $I->amOnPage('/admin/profile/mfa/disable/totp');
//         $I->see('Two-Factor-Authentication successfully disabled');

//         $I->amOnPage('/logout');
//         $I->loginAsUser($I::USER_ADMIN_ENGINEERING, $I::TEST_PASSWORD);
//         $I->see('Activity timeline');
//     }
// }
