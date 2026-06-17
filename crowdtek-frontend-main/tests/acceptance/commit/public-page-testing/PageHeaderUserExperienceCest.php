<?php


class PageHeaderUserExperienceCest
{
    public function _before(\Step\Acceptance\StaticPages $I)
    {
        $I->amOnPage('/');
    }

    public function _after(AcceptanceTester $I) {}

    /**
     * @group header
     */
    public function checkHeaderElements(\Step\Acceptance\StaticPages $I)
    {
        /**
         * Top navbar (the one with blog and contact)
         * The main navbar (with logo, properties, login, sign-up etc)
         */


        $I->wantTo('Top navbar and The main navbar');
        $I->amOnPage('/');
        $I->wait(1);
        // $I->see("Blog", "//header[@id='myHeader']/section/div/ul/li[1]/a");
        // $I->see("Contact", "//header[@id='myHeader']/section/div/ul/li[2]/a");
        $I->seeElement("//a[@class='navbar-brand']//img");
        $I->see("Properties", "//div[@id='navbarCollapse']/ul/li[1]/div/a");
        $I->see("How It Works", "//div[@id='navbarCollapse']/ul/li[2]");
        $I->see("About Us", "//div[@id='navbarCollapse']/ul/li[3]/div/a");
        $I->see("Knowledge Hub", "//div[@id='navbarCollapse']/ul/li[4]/div/a");
        $I->see("Login", "//div[@class='user-control']/a[1]");
        $I->see("Sign up", "//div[@class='user-control']/a[2]");

        $I->loginWithCredentials($I->reg_user_name, $I->admin_user_password, skipScaCheck: false);
        $I->seeElement("div.logged-in a[href='/my-portfolio']");
        $I->seeElement("div.logged-in a[href='/my-profile/dashboard']");
        // $I->see("Refer a Friend", "//header[@id='myHeader']/section/div/ul/li/a");
        // $I->see("Blog", "//header[@id='myHeader']/section/div/ul/li/a");
        $I->see("Contact", "//header[@id='myHeader']/section/div/ul/li/a");
        $I->see("Logout", "//header[@id='myHeader']/section/div/ul/li/a");
        $I->seeElement("a[href='/my-profile/transactions']");
    }


    /**
     * @group header
     */
    public function checkDropdownMenuItems(\Step\Acceptance\StaticPages $I)
    {
        /**
         * Properties dropdown contains the correct menu items
         * About us dropdown contains the correct menu items
         */


        $I->wantTo('Properties dropdown contains the correct menu items and  About us dropdown contains the correct menu items');
        $I->amOnPage('/');
        $I->click("//div[@id='navbarCollapse']/ul/li[1]/div/a");
        $I->waitForElementVisible("a[href='/current-properties']");
        $I->see("Current Properties", "a[href='/current-properties']");
        $I->see("Archived Properties", "a[href='/archived-properties']");
        // $I->dontSee("Top Yielders Properties", "a");
        // $I->see("Relisted Properties", "a[href='/second-market']");
        // $I->see("Become a Top Yielder", "a[href='/become-top-yielder']");
        $I->click("//div[@id='navbarCollapse']/ul/li[3]/div/a");
        $I->waitForElementVisible("a[href='/about-us']");
        $I->see("About Us", "a[href='/about-us']");
        $I->see("Contact Us", "a[href='/contact-us']");
        $I->click("//div[@id='navbarCollapse']/ul/li[4]/div/a");
        $I->waitForElementVisible("div.dropdown-menu a[href='http://help.yielders.co.uk/en']");
        $I->see("Help Centre", "div.dropdown-menu a[href='http://help.yielders.co.uk/en']");
        // $I->see("Blog", "div.dropdown-menu a[href='http://blog.yielders.co.uk/']");
    }
}
