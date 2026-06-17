<?php

// IncompleteOnboardingCest.php

class IncompleteOnboardingCest
{
    public function _before(AcceptanceTester $I)
    {
    }
    
    public function _after(AcceptanceTester $I)
    {
    }

    public function checkLoginRedirect(AcceptanceTester $I)
    {
        $I->amOnPage('/');
        $I->loginWithCredentials($I->unapproved_investor, "Password123!", false);
        $I->seeInCurrentUrl('/onboarding/email-verification');
        $I->amOnPage('/onboarding/compliance');
        $I->seeInCurrentUrl('/onboarding/email-verification');
    }

    public function checkHomepageAlert(AcceptanceTester $I)
    {
        $I->amOnPage('/');
        $I->loginWithCredentials($I->unapproved_investor, "Password123!", false);
        $I->waitForElement('a[href="/"]');
        $I->clickWithLeftButton('a[href="/"]');
        $I->seeInCurrentUrl('/');
        $I->seeElement('div.alert');
        $I->clickWithLeftButton('div.alert');
        $I->seeInCurrentUrl('/onboarding/email-verification');
    }
}
