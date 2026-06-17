<?php


class AuthRedirectCest
{
    public function _before(AcceptanceTester $I) {}

    public function _after(\Step\Acceptance\StaticPages $I) {}


    public function checkProfilePagesRedirect(\Step\Acceptance\StaticPages $I)
    {
        $I->amOnPage('/my-profile/dashboard');
        $I->seeElement('input', ['name' => '_username']);
        $I->amOnPage('/my-profile/profile');
        $I->seeElement('input', ['name' => '_username']);
        $I->amOnPage('/my-profile/password-security');
        $I->seeElement('input', ['name' => '_username']);
        $I->amOnPage('/my-profile/apply-top-yielder');
        $I->seeElement('input', ['name' => '_username']);
        $I->amOnPage('/my-profile/transactions');
        $I->seeElement('input', ['name' => '_username']);
        $I->amOnPage('/my-profile/categorisation');
        $I->seeElement('input', ['name' => '_username']);
    }

    public function checkPortfolioPagesPageRedirect(\Step\Acceptance\StaticPages $I)
    {
        $I->amOnPage('/my-portfolio');
        $I->seeElement('input', ['name' => '_username']);
        // $I->amOnPage('/my-portfolio/analytics');
        // $I->seeElement('input', ['name' => '_username']);
        // $I->amOnPage('/my-portfolio/analytics?property=1');
        // $I->seeElement('input', ['name' => '_username']);
        // $I->amOnPage('/my-investments');
        // $I->seeElement('input', ['name' => '_username']);
    }

    public function checkOpportunityPageRedirect(\Step\Acceptance\StaticPages $I)
    {
        $I->amOnPage('/current-properties');
        $I->seeElement('input', ['name' => '_username']);
    }

    // public function checkSecondMarketPageRedirect(\Step\Acceptance\StaticPages $I)
    // {
    //     $I->amOnPage('/second-market');
    //     $I->seeElement('input', ['name' => '_username']);
    // }

    // public function checkAssetOverviewPageRedirect(\Step\Acceptance\StaticPages $I)
    // {
    //     $I->amOnPage('/secondary-asset/1/overview');
    //     $I->seeElement('input', ['name' => '_username']);
    // }

    public function checkfundPagesRedirect(\Step\Acceptance\StaticPages $I)
    {
        $I->amOnPage('/add-funds-new');
        $I->seeElement('input', ['name' => '_username']);
        $I->amOnPage('/withdraw-funds');
        $I->seeElement('input', ['name' => '_username']);
    }
}
