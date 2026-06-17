<?php


class PageProfileDashboardCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->loginWithCredentials($I->reg_user_name, $I->admin_user_password);
        $I->amOnPage('/my-profile/dashboard');
    }

    public function _after(AcceptanceTester $I)
    {
    }

    /**
     * @group profile
     */
    public function checkProfileNav(\Step\Acceptance\StaticPages $I)
    {
        /**
         * Ensure that the 6 profile sections in tab nav are present and correct
         * - Dashboard - /my-profile/dashboard
         * - General Information - /my-profile/profile
         * - Security - /my-profile/password-security
         * - Top Yielders - /my-profile/apply-top-yielder
         * - Transaction History - /my-profile/transactions
         * - Investor Type - /my-profile/categorisation
         *
         * Check correct nav item is highlighted (i.e. has the class "active")
         */

        $I->click("//strong[contains(text(),'General Information')]");
        $I->seeCurrentUrlEquals('/my-profile/profile');
        $I->waitForText("General Information", 5, "//strong[contains(text(),'General Information')]");

        $I->click("//strong[contains(text(),'Security')]");
        $I->seeCurrentUrlEquals('/my-profile/password-security');
        $I->waitForText("Security", 5, "//strong[contains(text(),'Security')]");

        $I->click("//strong[contains(text(),'Top Yielders')]");
        $I->seeCurrentUrlEquals('/my-profile/apply-top-yielder');
        $I->waitForText("Top Yielders", 5, "//strong[contains(text(),'Top Yielders')]");

        $I->click("//strong[contains(text(),'Transaction History')]");
        $I->seeCurrentUrlEquals('/my-profile/transactions');
        $I->waitForText("Transaction History", 5, "//strong[contains(text(),'Transaction History')]");

        $I->click("//strong[contains(text(),'Investor Type')]");
        $I->seeCurrentUrlEquals('/my-profile/categorisation');
        $I->waitForText("Investor Type", 5, "//strong[contains(text(),'Investor Type')]");

        $I->click('Contact Preferences', '#profilenav');
        $I->seeCurrentUrlEquals('/my-profile/contact-preferences');
        $I->waitForText("Contact Preferences", 5, ".tab-content");

        $I->click('Linked Bank Accounts', '#profilenav');
        $I->seeCurrentUrlEquals('/my-profile/bank-accounts');
        $I->waitForText("Linked Bank Accounts", 5, ".tab-content");

        $I->click("//strong[contains(text(),'Dashboard')]");
        $I->seeCurrentUrlEquals('/my-profile/dashboard');
        $I->waitForText("Dashboard", 5, "//strong[contains(text(),'Dashboard')]");

        // $I->click("//strong[contains(text(),'Feedback')]");
        // $I->seeCurrentUrlEquals('/my-profile/feedback');
        // $I->waitForText("Feedback", 5, "//strong[contains(text(),'Feedback')]");
    }

    /**
     * @group profile
     */
    public function checkDashboardContent(\Step\Acceptance\StaticPages $I)
    {
        /**
         * Check the 3 blocks
         * - My Wallet
         *   - Contain a field with the wallet amount (do NOT check that actual value of the wallet)
         *   - Add Funds link
         *   - Withdraw funds link
         *   - View transactions link
         * - Refer your friends
         *   - Generate Link button is visible
         * - Apply to become a Top Yielder
         *   - Apply button visible
         */

        $I->waitForElementVisible("//div[@class='col-lg-12 mb-3']//div[@class='card p-3 border-0 bg-info rounded-0 shadow-sm']");
        $I->waitForElementVisible("//div[@class='col-lg-6 mb-3'][1]");
        // $I->waitForElementVisible("//div[@class='col-lg-6 mb-3'][2]");

        $amount = $I->grabTextFrom("//div[@class='tab-pane fade show active']/div/div[1]/div/p[2][@class='h3 text-center mb-3']");
        $I->comment("Total Amount is: $amount");
        $I->see($amount, "//div[@class='tab-pane fade show active']/div/div[1]/div/p[2][@class='h3 text-center mb-3']");

        $I->see("Add Funds", "//div[@class='d-flex justify-content-center mb-2']/a[1]");
        $I->see("Withdraw Funds", "a[href='/withdraw-funds']");


        $I->see("View transactions", "//div[@class='text-right']/a");

        // $I->seeElement("//div[@class='col-lg-6 mb-3'][1]/div");
        // $I->see("Generate Link", "//div[@class='col-lg-6 mb-3'][1]/div/div[2]/a");

        $I->seeElement("//div[@class='col-lg-6 mb-3']");
        $I->see("Apply", "//div[@class='col-lg-6 mb-3']/div/div/a");
    }
}
