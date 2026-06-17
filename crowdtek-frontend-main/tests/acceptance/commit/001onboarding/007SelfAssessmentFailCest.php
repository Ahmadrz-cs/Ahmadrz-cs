<?php

/**
 * This is a standalone test, does not require other tests to complete unlike 005ComplianceCest
 */
class SelfAssessmentFailCest
{
    /**
     * @group mobile
     */
    public function _before(AcceptanceTester $I)
    {
        if ($I->indietest) {
            $I->loginWithName($I->user_bryson);
        } else {
            $I->createUserAtStage('3', '0'); //Restricted Investor
        }
    }

    public function _after(AcceptanceTester $I) {}

    /**
     * @group mobile
     */
    public function FailQuestionnaireTwice(AcceptanceTester $I)
    {
        $I->amOnPage('/onboarding');
        $I->seeCurrentUrlEquals('/onboarding/assessment');

        $I->click('Start Test');
        $I->seeCurrentUrlEquals('/onboarding/assessment/quiz');
        $I->selectOption('input[name="user_assessment[0][choice]"]', '0');
        $I->selectOption('input[name="user_assessment[1][choice]"]', '0');
        $I->selectOption('input[name="user_assessment[2][choice]"]', '0');
        $I->click('Submit Answers');
        $I->wait(1);
        $I->seeCurrentUrlEquals('/onboarding/assessment/fail');
        $I->click('Try Again');

        $I->click('Start Test');
        $I->selectOption('input[name="user_assessment[0][choice]"]', '0');
        $I->selectOption('input[name="user_assessment[1][choice]"]', '0');
        $I->selectOption('input[name="user_assessment[2][choice]"]', '0');
        $I->click('Submit Answers');
        $I->wait(1);
        $I->seeCurrentUrlEquals('/onboarding/assessment/fail');
        $I->dontSee('Try Again', 'a');
    }

    /**
     * @group mobile
     */
    public function checkQuestionnaireAttempts(AcceptanceTester $I)
    {
        if ($I->cmsCheck) {
            if ($I->indietest) {
                $user_email = $I->user_bryson["email"];
            } else {
                $user_email = sqs('3') . $I->new_user_yorran["email"];
            }
            $token = $I->getUserToken($user_email, $I->new_user_yorran["password"]);
            if ($token) {
                $user = $I->getUserInfoByAPI($token);
                $user_info = $I->convertUserInfoToDict($user['info']);

                // Cleanup salesforce first
                try {
                    $salesforce_id = $user_info['salesforce_id'];
                    $I->salesforceAction('DELETE', $I->salesforce_params["user_object"], $salesforce_id);
                } catch (\Throwable $th) {
                    echo "Salesforce not setup";
                }

                $I->assertEquals(3, $user['ob_step']);
                $I->assertFalse($user['onboarding_profile']['assessmentPassed']);
                $I->assertEquals(2, $user['onboarding_profile']['assessmentAttempts']);
            } else {
                $I->assertTrue($token, "Unable to login");
            }
        }
    }
}
