<?php


class PageProfileSecurityCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->loginWithCredentials("Email-verified@crowdtek.co.uk", $I->admin_user_password, false);
    }

    public function _after(AcceptanceTester $I) {}

    /**
     * @group profile
     */
    public function checkProfileNav(\Step\Acceptance\StaticPages $I)
    {
        $I->amOnPage('/my-profile/password-security');
        $I->see("Security", ".navClick.active");
    }

    /**
     * @group profile
     */
    public function checkPasswordForm(\Step\Acceptance\StaticPages $I)
    {
        $I->amOnPage('/my-profile/password-security');
        $I->waitForElement('#change_password_type_current_password');
        $I->see("Current Password");
        $I->seeElement("input#change_password_type_password_new_password");
        $I->see("Conﬁrm New Password");
    }

    /**
     * @group profile
     */
    public function checkPasswordValidation(\Step\Acceptance\StaticPages $I)
    {
        $I->amOnPage('/my-profile/password-security');
        $I->fillField("//input[@id='change_password_type_current_password']", "a");
        $I->fillField("//input[@id='change_password_type_password_new_password']", "a");
        $I->fillField("//input[@id='change_password_type_password_new_password_confirm']", "a");
        $I->click("//button[@id='change_password_type_submit']");
        $I->waitForText("Password must be 6 characters at least.", 5, "//p[@id='error_password']");

        $I->fillField("//input[@id='change_password_type_current_password']", "Password123!");
        $I->fillField("//input[@id='change_password_type_password_new_password']", "Password123!");
        $I->fillField("//input[@id='change_password_type_password_new_password_confirm']", "Password123!");
        $I->click("//button[@id='change_password_type_submit']");
        $I->waitForText("New password is matching with current password.Please enter another password", 5, "//p[@id='error_password']");

        $I->fillField("//input[@id='change_password_type_current_password']", "Password1234!");
        $I->fillField("//input[@id='change_password_type_password_new_password']", "Password12345!");
        $I->fillField("//input[@id='change_password_type_password_new_password_confirm']", "Password12345!");
        $I->click("//button[@id='change_password_type_submit']");
        $I->waitForText("Current password is not valid", 5, "//p[@id='error_password']");

        $I->fillField("//input[@id='change_password_type_current_password']", "Password123!");
        $I->fillField("//input[@id='change_password_type_password_new_password']", "Password123456!");
        $I->fillField("//input[@id='change_password_type_password_new_password_confirm']", "Password12345!");
        $I->click("//button[@id='change_password_type_submit']");
        $I->waitForText("The password fields must match.", 5, "//p[@id='error_password']");

        $I->fillField("//input[@id='change_password_type_current_password']", "Password123!");
        $I->fillField("//input[@id='change_password_type_password_new_password']", "Password12345!");
        $I->fillField("//input[@id='change_password_type_password_new_password_confirm']", "Password123456!");
        $I->click("//button[@id='change_password_type_submit']");
        $I->waitForText("The password fields must match.", 5, "//p[@id='error_password']");
    }

    /**
     * @group profile
     * @group resetPassword
     */
    public function checkPasswordFormEdit(\Step\Acceptance\StaticPages $I)
    {
        /**
         * Check the form submission works (and saves changes)
         * - Update password
         * - WaitforText: "Your password has been successfully changed" message
         * - Logout by going to url amOnPage /logout
         * - Log back in with
         *   - $I->loginWithCredentials("Email-verified@crowdtek.co.uk", <whatever the new pw is>);
         * - If you've successfully logged in, you should see "my portfolio" as part of the nav bar
         */
        $I->amOnPage('/my-profile/password-security');
        $I->waitForElement('#change_password_type_current_password');
        $I->fillField("//input[@id='change_password_type_current_password']", "Password123!");
        $I->fillField("//input[@id='change_password_type_password_new_password']", "Password1234!");
        $I->fillField("//input[@id='change_password_type_password_new_password_confirm']", "Password1234!");
        $I->click("//button[@id='change_password_type_submit']");
        $I->waitForText("Your password has been successfully changed");
        $I->amOnPage("/logout");
        $I->loginWithCredentials("Email-verified@crowdtek.co.uk", "Password1234!", false);
        $I->seeElement(".user-control.logged-in");
    }

    /**
     * @depends checkPasswordFormEdit
     * @group resetPassword
     */
    public function resetPassword(AcceptanceTester $I)
    {
        /**
         * This test is working, no need to modify
         * Check password reset works
         */
        $userName = "Email-verified@crowdtek.co.uk";

        $I->amOnPage('/logout');
        $I->clearMailCatcher();

        $I->amOnPage('/forgot-password');
        $I->fillField('#form_email', $userName);
        $I->click('#form_submit');

        $I->amOnUrl($I->getMailcatcherUrl() . '/messages/1.html');
        $I->waitForText("Reset Password", 5);
        $I->click('Reset Password');

        $I->switchToNextTab();
        $I->fillField('#form_password', $I->admin_user_password);
        $I->fillField('#form_password_confirm', $I->admin_user_password);
        $I->click('#form_submit');

        $I->resetBaseHost();
        $I->loginWithCredentials($userName, $I->admin_user_password, false);
        $I->waitForText('My Portfolio');
    }
}
