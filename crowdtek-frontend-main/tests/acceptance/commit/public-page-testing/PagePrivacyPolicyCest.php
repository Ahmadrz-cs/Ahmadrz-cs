<?php


class PagePrivacyPolicyCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->amOnPage('/privacy-policy');
        $I->waitForText('Privacy Policy');
    }

    public function _after(AcceptanceTester $I)
    {
    }

    /**
     * @group privacy
     */
    public function checkPrivacyPolicyPages(\Step\Acceptance\StaticPages $I)
    {
        $I->waitForText("Privacy Policy");
        $I->seeNumberOfElementsInDOM(".accordion-content", 4);
        $I->see("WEBSITE ACCEPTABLE USE POLICY");
        $I->clickWithLeftButton(".accordion-content:nth-child(1) h3");
        $I->scrollTo(".accordion-content:nth-child(2) h3");
        $I->see("TERMS OF WEBSITE USE POLICY");
        $I->clickWithLeftButton(".accordion-content:nth-child(2) h3");
        $I->scrollTo(".accordion-content:nth-child(3) h3");
        $I->see("COOKIE POLICY");
        $I->clickWithLeftButton(".accordion-content:nth-child(3) h3");
        $I->scrollTo(".accordion-content:nth-child(4) h3");
        $I->see("PRIVACY POLICY");
        $I->clickWithLeftButton(".accordion-content:nth-child(4) h3");
    }
}
