<?php


class PageTermsConditionsCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->amOnPage('/terms-conditions');
        $I->waitForText('Terms & Conditions');
    }

    public function _after(AcceptanceTester $I)
    {
    }

    /**
     * @group terms
     */
    public function checkTermsAndConditionsPage(\Step\Acceptance\StaticPages $I)
    {
        $I->waitForText("Terms & Conditions");
        $I->seeNumberOfElementsInDOM(".accordion-content", 29);
        $I->see("1.DEFINITIONS");
        $I->clickWithLeftButton(".accordion-content:nth-child(1) h3");

        $I->scrollTo(".accordion-content:nth-child(23) h3");
        $I->see("29.GOVERNING");
        $I->waitForElementClickable(".accordion-content:nth-child(29) h3", 10);
        $I->click(".accordion-content:nth-child(29) h3");
    }
}
