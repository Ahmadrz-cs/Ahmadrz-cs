<?php

class CheckupCest
{
    public function _before(AcceptanceTester $I)
    {
        // Legacy onboarded user, no onboarding profile yet
        $I->loginWithName($I->user_hamlin);
    }

    public function _after(AcceptanceTester $I) {}

    public function checkCheckupFlow(AcceptanceTester $I)
    {
        // Immediate redirect on login
        $I->seeCurrentUrlEquals('/checkup');

        // First stage redirect is categorisation
        $I->click('Update Now');
        $I->waitForText("Investor Type");
        $I->wait(1);
        $I->seeCurrentUrlEquals('/checkup/categorisation');

        // Check that this is also accessible from profile
        $I->amOnPage('/my-profile/dashboard');
        $I->click('Update Now', '#checkup-prompt');
        $I->wait(1);
        $I->seeCurrentUrlEquals('/checkup/categorisation');

        $I->selectOption('input[name="user_categorisation[category]"]', 'Restricted');
        $I->scrollTo('form button[type="submit"]', 0, -150);
        $I->seeLink("Update Later", '');
        $I->click('Continue');
        // Link to go back to choose again
        $I->waitForText("Investor Type Confirmation");
        $I->seeCurrentUrlEquals('/checkup/categorisation/restricted');
        $I->fillField('#category_restricted_last12M', 4);
        $I->fillField('#category_restricted_next12M', 6);
        $I->scrollTo('form button[type="submit"]', 0, -150);
        $I->seeLink("Choose a Different Type", '/checkup/categorisation');
        $I->click('Confirm Investor Type');
        $I->waitForText("Appropriateness Test");
        $I->seeCurrentUrlEquals('/checkup/assessment');

        // If you quit now and then resume, you'll be taken to the assessment stage
        $I->seeLink("I'll do this later", '');
        $I->amOnPage('/checkup');
        $I->click('Update Now');
        $I->waitForText("Appropriateness Test");
        $I->seeCurrentUrlEquals('/checkup/assessment');

        // Failed test can retry
        // The auto test here is a super truncated version, not the real version
        $I->click('Start Test');
        $I->seeCurrentUrlEquals('/checkup/assessment/quiz');
        $I->selectOption('input[name="user_assessment[0][choice]"]', '0');
        $I->selectOption('input[name="user_assessment[1][choice]"]', '0');
        $I->selectOption('input[name="user_assessment[2][choice]"]', '0');
        $I->click('Submit Answers');
        $I->wait(1);
        $I->seeCurrentUrlEquals('/checkup/assessment/fail');
        $I->click('Try Again');

        $I->click('Start Test');
        $I->selectOption('input[name="user_assessment[0][choice]"]', '0');
        $I->selectOption('input[name="user_assessment[1][choice]"]', '0');
        $I->selectOption('input[name="user_assessment[2][choice]"]', '1');
        $I->click('Submit Answers');

        // Next step is the risk and cooldown acceptance
        $I->wait(1);
        $I->waitForText("Understanding of risk");
        $I->seeCurrentUrlEquals('/checkup/risk');
        // If you quit now and then resume, you'll be taken to the risk acknowledgement stage
        $I->amOnPage('/checkup');
        $I->click('Update Now');
        $I->waitForText("Understanding of risk");
        $I->seeCurrentUrlEquals('/checkup/risk');
        // If you leave, you'll be taken to the homepage
        $I->click('Leave');
        $I->wait(1);
        $I->seeCurrentUrlEquals('/');

        // See what happens if the 24hr cooloff has not elapsed yet
        $obpId = $I->grabFromDatabase('users', 'onboardingProfile_id', ['username' => $I->user_hamlin['email']]);
        $futureDt = new \DateTime('+6 hours');
        $I->updateInDatabase('onboarding_profile', ['cooloffEnd' => $futureDt->format('Y-m-d H:i:s')], ['id' => $obpId]);
        // On resumption, you'll be taken back to the risk step
        // Note that /checkup will reload the userInfo on clicking Update Now (form submission)
        $I->amOnPage('/checkup');
        $I->click('Update Now');
        $I->wait(1);
        $I->seeCurrentUrlEquals('/checkup/risk');
        $I->see('Cooling off period');
        $I->dontSee('Understanding of risk');
        $I->seeLink("View Opportunities", '/current-properties');
        $I->dontSeeElement('form button[type="submit"]');
        // If you try to invest, you'll also see the cooldown
        $I->amOnPage("/properties/33");
        $I->see('Cooling off period', '#checkup-prompt');
        $I->dontSee('Please update your investor profile', '#checkup-prompt');

        // Set the cooloff to be expired again
        $oldDt = new \DateTime('-2 days');
        $I->updateInDatabase('onboarding_profile', ['cooloffEnd' => $oldDt->format('Y-m-d H:i:s')], ['id' => $obpId]);
        // Refresh userInfo via /checkup
        $I->amOnPage('/checkup');
        $I->click('Update Now');
        $I->wait(1);
        // Check full prompt to update investor profile and then resume the checkup
        $I->amOnPage("/properties/33");
        $I->wait(1);
        $I->waitForText('Please update your investor profile', 10);
        $I->dontSee('Cooling off period', '#checkup-prompt');
        $I->see('Please update your investor profile', '#checkup-prompt');
        $I->click('Go to your Profile');
        $I->wait(1);
        $I->seeCurrentUrlEquals('/my-profile/dashboard');
        $I->see('Please update your investor profile', '#checkup-prompt');
        $I->click('Update Now');

        $I->wait(1);
        $I->seeCurrentUrlEquals('/checkup/risk');
        $I->dontSee('Cooling off period');
        $I->see('Understanding of risk');
        $I->click('Proceed');

        $I->wait(1);
        $I->seeCurrentUrlEquals('/checkup/completion');
        $I->seeLink("View Opportunities", '/current-properties');

        // If you try to go back to the checkup, you'll be sent straight to the end
        $I->amOnPage('/checkup');
        $I->click('Update Now');
        $I->wait(1);
        $I->seeCurrentUrlEquals('/checkup/completion');

        // No longer see a prompt for checkup in profile dashboard
        $I->amOnPage('/my-profile/dashboard');
        $I->dontSeeElement('#checkup-prompt');

        // If you re-login, you won't be redirected to the checkup anymore
        $I->amOnPage('/logout');
        $I->loginWithName($I->user_hamlin);
        $I->seeCurrentUrlEquals('/');
    }
}
