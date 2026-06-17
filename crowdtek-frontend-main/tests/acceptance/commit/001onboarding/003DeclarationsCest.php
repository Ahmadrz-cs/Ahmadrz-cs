<?php

/**
 * This is a sequential test and requires the 001 to 002 steps to be completed
 *
 * @group onboardingToStep3
 * @group onboardingToStep4
 * @group onboardingToStep5
 * @group onboardingSequential
 */
class DeclarationsCest
{
    /**
     * @group mobile
     */
    public function _before(AcceptanceTester $I)
    {
        if ($I->indietest) {
            $I->loginWithName($I->verified_user_yorran);
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
        $I->wait(1);
        $I->seeCurrentUrlEquals('/onboarding/regulation-preference');

        $I->amOnPage('/onboarding/email-verification');
        $I->wait(1);
        $I->seeCurrentUrlEquals('/onboarding/regulation-preference');
    }

    /**
     * @group mobile
     * @group onboardingSequentialDirect
     */
    public function preferencesDeclarationsCategorisation(AcceptanceTester $I)
    {
        $I->amOnPage('/onboarding'); // actual page: /regulation-preference
        $I->wait(1);
        $I->seeCurrentUrlEquals('/onboarding/regulation-preference');
        $I->see('Marketing preferences');
        // check default state checkboxes are not checked
        $I->dontSeeCheckboxIsChecked(['id' => 'userPreference_contact_via_email']);
        $I->dontSeeCheckboxIsChecked(['id' => 'userPreference_contact_via_tele']);
        $I->dontSeeCheckboxIsChecked(['id' => 'userPreference_contact_via_sms']);

        $I->scrollTo('form[name="userPreference"]');
        $I->clickWithLeftButton('//form[@name="userPreference"]/label[contains(text(), "Email")]');
        $I->scrollTo('button[type="submit"]');
        $I->click('//button[text()="Next"]');
        // $I->see('Investor Declaration');
        // $I->scrollTo('form[name="userPreference"]');
        // $I->clickWithLeftButton('//input[@id="userPreference_investor_type_2"]/parent::*');
        $I->clickWithLeftButton('#fatca label');
        $I->seeCheckboxIsChecked('input#userPreference_fatca');
        $I->scrollTo('//form[@name="userPreference"]//button[@type="submit"]');
        $I->click('//button[text()="Next"]');
        $I->wait(1);
        $I->seeCurrentUrlEquals('/onboarding/categorisation');

        // Should now be redirected here by default in the onboarding
        $I->amOnPage('/onboarding');
        $I->seeCurrentUrlEquals('/onboarding/categorisation');

        // Click through each options
        $I->selectOption('input[name="user_categorisation[category]"]', 'Restricted');
        $I->waitForText('Restricted Investor', 10, '#category-summary');
        $I->selectOption('input[name="user_categorisation[category]"]', 'Sophisticated');
        $I->waitForText('Sophisticated Investor', 10, '#category-summary');
        $I->selectOption('input[name="user_categorisation[category]"]', 'High net worth');
        $I->waitForText('High Net Worth Investor', 10, '#category-summary');

        // Continue with restricted
        $I->selectOption('input[name="user_categorisation[category]"]', 'Restricted');
        $I->scrollTo('form button[type="submit"]', 0, -150);
        $I->click('Continue');
        // Link to go back to choose again
        $I->seeLink("Choose a Different Type", '/onboarding/categorisation');
        $I->seeCurrentUrlEquals('/onboarding/categorisation/restricted');
        $I->fillField('#category_restricted_last12M', 4);
        $I->fillField('#category_restricted_next12M', 6);
        $I->scrollTo('form button[type="submit"]', 0, -150);
        $I->click('Confirm Investor Type');
        $I->wait(1);
        $I->seeCurrentUrlEquals('/onboarding/assessment');
    }

    /**
     * @group mobile
     * @group onboardingSequentialDirect
     */
    public function checkUserPreferences(AcceptanceTester $I)
    {
        if ($I->cmsCheck) {
            $token = $I->getUserToken(sqs('test') . $I->new_user_yorran["email"], $I->new_user_yorran["password"]);
            if ($token) {
                $user = $I->getUserInfoByAPI($token);
                $user_info = $I->convertUserInfoToDict($user['info']);
                $I->assertEquals(true, $user_info['cxb_restricted_investor']);
                $I->assertEquals(false, $user_info['cxb_sophisticated_investor']);
                $I->assertEquals(false, $user_info['cxb_worth_investor']);
                $I->assertEquals('restricted', $user['onboarding_profile']['category']);
                $I->assertEquals("1", $user_info['fatca']);
                $I->assertEquals("1", $user_info['contact_via_email']);
                $I->assertEquals("0", $user_info['contact_via_tele']);
                $I->assertEquals("0", $user_info['contact_via_sms']);
                $I->assertEquals("1", $user['gdpr_accepted']);
                // check salesforce gdpr_accepted updated
                try {
                    $salesforce_id = $user_info['salesforce_id'];
                    $sf_user = $I->salesforceAction('GET', 'Contact', $salesforce_id);
                    $I->assertEquals("1", $sf_user['gdpr_accepted__c']);
                } catch (\Throwable $th) {
                    echo "Salesforce not setup";
                }
            } else {
                $I->assertTrue($token, "Unable to login");
            }
        }
    }
}
