<?php


class PageFooterUserExperienceCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->amOnPage('/');
    }

    public function _after(AcceptanceTester $I) {}



    /**
     * @group footer
     */
    public function checkFooterSections(\Step\Acceptance\StaticPages $I)
    {
        /**
         * The risk warning is present
         * The navigation links are present and correct
         * Social media, copyright, terms & privacy at bottom are present and correct
         */
        $I->scrollTo("//div[@class='container pt-3 pb-2 risk-warning']/p");
        $I->click("//a[@class='more btn-underline font-medium text-sm']"); // click on the read more button
        $I->see("sufficiently sophisticated to understand the risks.", ".risk-warning");

        $I->seeLink("Properties", "/current-properties");
        $I->seeLink("About Us", "/about-us");
        $I->seeLink("How It Works", "/process/how-it-works");
        $I->seeLink("Knowledge Center", "http://help.yielders.co.uk/en");

        $I->seeElement("//div[@class='footer-bottom bg-greydark']/div/div/div[1]/a[1]");
        $I->seeElement("//div[@class='social d-flex mb-1 mb-md-0']/a[2]");
        $I->seeElement("//div[@class='social d-flex mb-1 mb-md-0']/a[3]");
    }
}
