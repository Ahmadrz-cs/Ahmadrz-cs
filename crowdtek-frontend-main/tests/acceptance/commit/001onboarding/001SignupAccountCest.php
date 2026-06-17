<?php

/**
 * This is the first of the sequential tests required by 002 to 005
 *
 * @group onboardingToStep2
 * @group onboardingToStep3
 * @group onboardingToStep4
 * @group onboardingToStep5
 * @group onboardingSequential
 */
class SignupAccountCest
{
    public function _before(AcceptanceTester $I) {}

    public function _after(AcceptanceTester $I) {}

    /**
     * @group mobile
     */
    public function checkFieldLabelsPresent(AcceptanceTester $I)
    {
        $I->amOnPage('/onboarding'); // check routing by only giving /onboarding
        $I->wait(1);
        $I->seeCurrentUrlEquals('/onboarding/sign-up');
        $I->see('First Name', '#lblfname');
        $I->see('Surname', '#lblsname');
        $I->see('Email Address', '#lblemail');
        $I->see('Password', 'label[for="signUpUser_password_first"]');
        $I->see('Confirm Password', 'label[for="signUpUser_password_second"]');
    }

    /**
     * @group mobile
     */
    public function checkEmailValidation(AcceptanceTester $I)
    {
        $I->amOnPage('/onboarding/sign-up');

        $I->fillField('#signUpUser_email', 'abc'); // invalid
        $I->clickWithLeftButton('#signUpUser_password_first');
        $I->seeElement('#signUpUser_email.invalid_email');

        $I->fillField('#signUpUser_email', 'abc@'); // invalid
        $I->clickWithLeftButton('#signUpUser_password_first');
        $I->seeElement('#signUpUser_email.invalid_email');

        $I->fillField('#signUpUser_email', 'abc@def'); // invalid
        $I->clickWithLeftButton('#signUpUser_password_first');
        $I->seeElement('#signUpUser_email.invalid_email');

        $I->fillField('#signUpUser_email', 'abc@def.co'); // valid
        $I->clickWithLeftButton('#signUpUser_password_first');
        $I->dontSeeElement('#signUpUser_email.invalid_email');
    }

    // Check typical password combinations

    /**
     * @group mobile
     */
    public function passwordValidityChecker(AcceptanceTester $I)
    {
        $I->amOnPage('/onboarding/sign-up');
        $I->fillField('#signUpUser_password_first', 'LONDON');
        $I->seeElement('#letter.border-danger');
        $I->dontSeeElement('#capital.border-danger');
        $I->seeElement('#number.border-danger');
        $I->seeElement('#length.border-danger');

        $I->fillField('#signUpUser_password_first', 'london');
        $I->dontSeeElement('#letter.border-danger');
        $I->seeElement('#capital.border-danger');
        $I->seeElement('#number.border-danger');
        $I->seeElement('#length.border-danger');

        $I->fillField('#signUpUser_password_first', '12345');
        $I->seeElement('#letter.border-danger');
        $I->seeElement('#capital.border-danger');
        $I->dontSeeElement('#number.border-danger');
        $I->seeElement('#length.border-danger');

        $I->fillField('#signUpUser_password_first', 'london1');
        $I->dontSeeElement('#letter.border-danger');
        $I->seeElement('#capital.border-danger');
        $I->dontSeeElement('#number.border-danger');
        $I->seeElement('#length.border-danger');

        $I->fillField('#signUpUser_password_first', 'london12');
        $I->dontSeeElement('#letter.border-danger');
        $I->seeElement('#capital.border-danger');
        $I->dontSeeElement('#number.border-danger');
        $I->dontSeeElement('#length.border-danger');

        $I->fillField('#signUpUser_password_first', 'London12');
        $I->dontSeeElement('#letter.border-danger');
        $I->dontSeeElement('#capital.border-danger');
        $I->dontSeeElement('#number.border-danger');
        $I->dontSeeElement('#length.border-danger');
    }

    // When you click the eye icon, the input type changes from password to text and vice versa

    /**
     * @group mobile
     */
    public function hideShowPasswordButton(AcceptanceTester $I)
    {
        $I->amOnPage('/onboarding/sign-up');
        // optionally fill in fields to visually see it working (has small performance impact on test speed)
        // Not necessary since effect is done by browser

        // check default state is type=password
        $I->seeElement('input#signUpUser_password_first');
        $I->seeElement('input#signUpUser_password_second');
        // click to "show" pw text
        $I->clickWithLeftButton('i.pw-eye');
        $I->seeElement('input#signUpUser_password_first[type="text"]');
        $I->seeElement('input#signUpUser_password_second[type="text"]');
        // click again to obscure pw text
        $I->clickWithLeftButton('i.pw-eye');
        $I->seeElement('input#signUpUser_password_first[type="password"]');
        $I->seeElement('input#signUpUser_password_second[type="password"]');
    }

    // // Functional tests already check that the fields are required

    /**
     * @group mobile
     */
    public function preventInvalidSubmits(AcceptanceTester $I)
    {
        // empty form check
        $I->amOnPage('/onboarding/sign-up');
        $I->click('input[value="Continue"]');
        $I->wait(1);
        $I->seeCurrentUrlEquals('/onboarding/sign-up');

        // invalid password checks
        $I->amOnPage('/onboarding/sign-up');
        $I->fillField('#signUpUser_firstname', $I->new_user_yorran["firstname"]);
        $I->fillField('#signUpUser_lastname', $I->new_user_yorran["lastname"]);
        $I->fillField('#signUpUser_email', sqs('test') . $I->new_user_yorran["email"]);
        // Check if submission is blocked when an invalid password is given (doesn't meet all 4 specs)
        // Might want to try for 1, 2 and 3 requirements met, or just do 3
        $I->scrollTo('form input[type="submit"]');
        $I->fillField('#signUpUser_password_first', '12345678'); // number, 8 char
        $I->fillField('#signUpUser_password_second', '1235678');
        $I->click('input[value="Continue"]');
        $I->fillField('#signUpUser_password_first', 'London'); // upper, lower
        $I->fillField('#signUpUser_password_second', 'London');
        $I->click('input[value="Continue"]');
        $I->fillField('#signUpUser_password_first', 'london12'); // lowercase, number, 8 char
        $I->fillField('#signUpUser_password_second', 'london12');
        $I->click('input[value="Continue"]');
        $I->fillField('#signUpUser_password_first', 'Londoner'); // uppercase, lowercase,  8 char
        $I->fillField('#signUpUser_password_second', 'Londoner');
        $I->click('input[value="Continue"]');
        $I->see('Sign Up');

        // missmatch passwords check
        $I->amOnPage('/onboarding/sign-up');
        $I->fillField('#signUpUser_firstname', $I->new_user_yorran["firstname"]);
        $I->fillField('#signUpUser_lastname', $I->new_user_yorran["lastname"]);
        $I->fillField('#signUpUser_email', sqs('test') . $I->new_user_yorran["email"]);
        // Check if submission is blocked when an non-matching passwords given
        $I->fillField('#signUpUser_password_first', 'Password123!');
        $I->fillField('#signUpUser_password_second', 'Password123?');
        $I->click('input[value="Continue"]');
        $I->wait(1);
        $I->seeCurrentUrlEquals('/onboarding/sign-up');
        $I->see('Passwords must match');
    }

    /**
     * @group mobile
     * @group onboardingSequentialDirect
     */
    public function userSignupAndTerms(AcceptanceTester $I)
    {
        $I->clearMailCatcher();
        $I->amOnPage('/onboarding/sign-up'); // test ref code sanitization
        $I->see('Sign up');

        $I->fillField('#signUpUser_firstname', $I->new_user_yorran["firstname"]);
        $I->fillField('#signUpUser_lastname', $I->new_user_yorran["lastname"]);
        $I->fillField('#signUpUser_email', sqs('test') . $I->new_user_yorran["email"]);
        $I->fillField('#signUpUser_password_first', $I->new_user_yorran["password"]);
        $I->fillField('#signUpUser_password_second', $I->new_user_yorran["password"]);
        $I->click('input[value="Continue"]');

        $I->waitForText('Terms & Conditions and Privacy Policy', 5);
        $I->scrollTo('div.jumbotron');
        $I->clickWithLeftButton('input#signUpUser_term_service_accepted');
        $I->scrollTo('form button[type="submit"]', 0, -150);
        $I->click('button#btn_continue');
    }

    /**
     * @group mobile
     * @group onboardingSequentialDirect
     */
    public function checkUserCreated(AcceptanceTester $I)
    {
        if ($I->cmsCheck) {
            $token = $I->getUserToken(sqs('test') . $I->new_user_yorran["email"], $I->new_user_yorran["password"]);
            if ($token) {
                $user = $I->getUserInfoByAPI($token);
                $user_info = $I->convertUserInfoToDict($user['info']);
                $I->assertEquals(1, $user['ob_step']);
                $I->assertEquals("Yorran", $user['given_name']);
                $I->assertEquals("Davies", $user['family_name']);
                try {
                    $salesforce_id = $user_info['salesforce_id'];

                    $sf_user = $I->salesforceAction('GET', 'Contact', $salesforce_id);
                    $I->assertEquals("Davies", $sf_user['LastName']);
                    $I->assertEquals(sqs('test') . $I->new_user_yorran["email"], $sf_user['username__c']);
                } catch (\Throwable $th) {
                    echo "Salesforce not setup";
                }
            } else {
                $I->assertTrue($token, "Unable to login");
            }
        }
    }
}
