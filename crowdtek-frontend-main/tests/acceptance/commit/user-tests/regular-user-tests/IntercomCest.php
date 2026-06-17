<?php


class IntercomCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    public function _after(AcceptanceTester $I)
    {
    }

    /**
     * @group intercom
     */
    public function checkIntercomLoads(AcceptanceTester $I)
    {
        $I->wantTo("Check if the intercom shows up");

        $I->amOnPage('/');
        $I->seeCurrentUrlEquals('/');
        $I->WaitForElement(['name' => 'intercom-launcher-frame']);
    }
}
