<?php

/**
 * This is a sequential test and requires the 001 to 005 steps to be completed
 *
 * @group onboardingSequential
 */
class CompletionRedirectCest
{
    /**
     * @group mobile
     */
    public function _before(AcceptanceTester $I)
    {
        if ($I->indietest) {
            $I->createUserAtStage('5');
        } else {
            $I->loginWithCredentials(sqs('test') . $I->new_user_yorran["email"], $I->new_user_yorran["password"]);
        }
    }

    public function _after(AcceptanceTester $I)
    {
    }

    /**
     * @group mobile
     */
    public function checkOnboardingRedirect(AcceptanceTester $I)
    {
        $I->amOnPage('/onboarding/sign-up');
        $I->seeCurrentUrlEquals('/onboarding/complete');

        $I->amOnPage('/onboarding/email-verification');
        $I->seeCurrentUrlEquals('/onboarding/complete');

        $I->amOnPage('/onboarding/regulation-preference');
        $I->seeCurrentUrlEquals('/onboarding/complete');

        $I->amOnPage('/onboarding/regulation-knowledge');
        $I->seeCurrentUrlEquals('/onboarding/complete');

        $I->amOnPage('/onboarding/compliance');
        $I->seeCurrentUrlEquals('/onboarding/complete');
    }
}
