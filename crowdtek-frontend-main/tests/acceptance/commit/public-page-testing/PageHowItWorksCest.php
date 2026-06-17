<?php


class PageHowItWorksCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->amOnPage('/process/how-it-works');
        $I->waitForText('How it works');
    }

    public function _after(AcceptanceTester $I)
    {
    }

    /**
     * @group how_it_works
     */
    public function checkHowItWorksSections(\Step\Acceptance\StaticPages $I)
    {
        $I->waitForText("How it works");
        $I->seeElement(".white-block");
        $I->scrollTo(".white-block");
        $I->seeElement(".bg-teal");
    }
}
