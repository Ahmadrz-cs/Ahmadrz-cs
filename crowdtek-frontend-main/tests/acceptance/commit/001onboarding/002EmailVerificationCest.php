<?php

/**
 * This is a sequential test and requires the 001 step to be completed
 *
 * @group onboardingToStep2
 * @group onboardingToStep3
 * @group onboardingToStep4
 * @group onboardingToStep5
 * @group onboardingSequential
 */
class EmailVerificationCest
{
    /**
     * @group mobile
     */
    public function _before(AcceptanceTester $I)
    {
        if ($I->indietest) {
            $I->loginWithName($I->new_user_yalta);
        } else {
            $I->loginWithCredentials(sqs('test') . $I->new_user_yorran["email"], $I->new_user_yorran["password"]);
        }
    }

    public function _after(AcceptanceTester $I) {}

    /**
     * @group mobile
     */
    public function checkOnboardingLogoIsClickable(AcceptanceTester $I)
    {
        $I->amOnPage('/onboarding');
        $I->click('div.logo_box a');
        $I->wait(1);
        $I->waitForText('My Portfolio');
    }

    // should always be sent to /email-verification page if logged in at this step

    /**
     * @group mobile
     */
    public function checkOnboardingRedirect(AcceptanceTester $I)
    {
        $I->amOnPage('/onboarding/sign-up');
        $I->wait(1);
        $I->seeCurrentUrlEquals('/onboarding/email-verification');
    }

    // webdriver does not support http auth request headers (e.g. with amHttpAuthenticated)

    /**
     * @group mobile
     * @group onboardingSequentialDirect
     */
    public function emailResendAndRedirect(AcceptanceTester $I)
    {
        $I->clearMailCatcher();
        $I->amOnPage('/onboarding');
        $I->wait(1);
        $I->seeCurrentUrlEquals('/onboarding/email-verification');
        $I->see('Verify your email address');
        $I->click('RESEND LINK'); // click is case sensitive
        $I->amOnUrl($I->getMailcatcherUrl() . '/messages/1.html');
        $I->wait(1);
        $I->click('Verify Email');
        $I->wait(1);
        $I->seeCurrentUrlEquals('/onboarding/regulation-preference');
        $I->see('Thank you for verifying your email address, please complete your registration'); // flash message
    }
}
