<?php

/**
 * This is a sequential test and requires the 001 to 003 steps to be completed
 *
 * @group onboardingToStep4
 * @group onboardingToStep5
 * @group onboardingSequential
 */
class SelfAssessmentCest
{
    /**
     * @group mobile
     */
    public function _before(AcceptanceTester $I)
    {
        if ($I->indietest) {
            $I->loginWithName($I->user_henley);
        } else {
            $I->loginWithCredentials(sqs('test') . $I->new_user_yorran["email"], $I->new_user_yorran["password"]);
        }
    }

    public function _after(AcceptanceTester $I) {}

    /**
     * @group mobile
     */
    public function checkOnboardingRedirect(AcceptanceTester $I)
    {
        $I->amOnPage('/onboarding/sign-up');
        $I->seeCurrentUrlEquals('/onboarding/assessment');

        $I->amOnPage('/onboarding/email-verification');
        $I->seeCurrentUrlEquals('/onboarding/assessment');

        $I->amOnPage('/onboarding/regulation-preference');
        $I->seeCurrentUrlEquals('/onboarding/assessment');
    }

    /**
     * @group mobile
     * @group onboardingSequentialDirect
     */
    public function completeQuestionnaireWithRetry(AcceptanceTester $I)
    {
        $I->amOnPage('/onboarding');
        $I->wait(1);
        $I->seeCurrentUrlEquals('/onboarding/assessment');

        // Failed test can retry
        // The auto test here is a super truncated version, not the real version
        $I->click('Start Test');
        $I->wait(1);
        $I->seeCurrentUrlEquals('/onboarding/assessment/quiz');
        $I->selectOption('input[name="user_assessment[0][choice]"]', '0');
        $I->selectOption('input[name="user_assessment[1][choice]"]', '0');
        $I->selectOption('input[name="user_assessment[2][choice]"]', '0');
        $I->click('Submit Answers');
        $I->wait(1);
        $I->seeCurrentUrlEquals('/onboarding/assessment/fail');
        $I->click('Try Again');

        $I->wait(1);
        $I->click('Start Test');
        $I->selectOption('input[name="user_assessment[0][choice]"]', '0');
        $I->selectOption('input[name="user_assessment[1][choice]"]', '0');
        $I->selectOption('input[name="user_assessment[2][choice]"]', '1');
        $I->click('Submit Answers');
        $I->wait(1);
        $I->seeCurrentUrlEquals('/onboarding/compliance');
    }

    /**
     * @group mobile
     * @group onboardingSequentialDirect
     */
    public function checkQuestionnairePassed(AcceptanceTester $I)
    {
        if ($I->cmsCheck) {
            if ($I->indietest) {
                $user_email = $I->verified_user_yorran["email"];
            } else {
                $user_email = sqs('test') . $I->new_user_yorran["email"];
            }
            $token = $I->getUserToken($user_email, $I->new_user_yorran["password"]);
            if ($token) {
                $user = $I->getUserInfoByAPI($token);
                $I->assertEquals(4, $user['ob_step']);
                $I->assertTrue($user['onboarding_profile']['assessmentPassed']);
                $I->assertEquals(2, $user['onboarding_profile']['assessmentAttempts']);
            } else {
                $I->assertTrue($token, "Unable to login");
            }
        }
    }
}
