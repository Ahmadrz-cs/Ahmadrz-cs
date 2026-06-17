<?php

/**
 * This is a sequential test and requires the 001 to 004 steps to be completed
 *
 * @group onboardingToStep5
 * @group onboardingSequential
 */
class ComplianceCest
{
    private ?string $username = null;
    /**
     * @group mobile
     */
    public function _before(AcceptanceTester $I)
    {
        if ($I->indietest) {
            $I->loginWithName($I->assessed_user_patton);
            $this->username = $I->assessed_user_patton;
        } else {
            $I->loginWithCredentials(sqs('test') . $I->new_user_yorran["email"], $I->new_user_yorran["password"]);
            $this->username = sqs('test') . $I->new_user_yorran["email"];
        }
    }

    public function _after(AcceptanceTester $I) {}

    /**
     * @group mobile
     */
    public function checkOnboardingRedirect(AcceptanceTester $I)
    {
        $I->amOnPage('/onboarding/sign-up');
        $I->seeCurrentUrlEquals('/onboarding/compliance');

        $I->amOnPage('/onboarding/email-verification');
        $I->seeCurrentUrlEquals('/onboarding/compliance');

        $I->amOnPage('/onboarding/regulation-preference');
        $I->seeCurrentUrlEquals('/onboarding/compliance');

        $I->amOnPage('/onboarding/regulation-knowledge');
        $I->seeCurrentUrlEquals('/onboarding/compliance');
    }

    /**
     * @group mobile
     */
    public function complianceStageReopenAsUser(AcceptanceTester $I)
    {
        $I->amOnPage('/onboarding/compliance');
        $I->click('Next');

        $I->wait(1);
        $I->selectOption(['id' => 'userInformation_honorific_prefix'], 'Ms');
        $I->selectOption(['id' => 'userInformation_gender'], 'FEMALE');
        $I->fillField(['id' => 'userInformation_firstname'], $I->assessed_user_patton["firstname"]);
        $I->fillField(['id' => 'userInformation_lastname'], $I->assessed_user_patton["lastname"]);
        $I->selectOption(['id' => 'userInformation_birthDate_day'], '08');
        $I->selectOption(['id' => 'userInformation_birthDate_month'], '12');
        $I->selectOption(['id' => 'userInformation_birthDate_year'], '1980');
        $I->selectOption(['id' => 'userInformation_nationality'], 'United Kingdom');
        $I->click('//div[@id="accordion1"]//a[text()="NEXT"]');

        $I->wait(1);
        $I->selectOption(['id' => 'userInformation_address_country'], 'United Kingdom');
        $I->fillField(['id' => 'userInformation_address_postal_code'], 'E1 2PT');
        $I->fillField(['id' => 'userInformation_address_address1'], '4 Kings Road');
        $I->fillField(['id' => 'userInformation_address_city'], 'London');
        $I->scrollTo('//div[@id="accordion2"]//a[text()="NEXT"]');
        $I->click('//div[@id="accordion2"]//a[text()="NEXT"]');

        $I->wait(1);
        $I->fillField(['id' => 'userInformation_phone1'], '02072054650');
        $I->fillField(['id' => 'userInformation_phone2'], '07911123456');
        $I->seeElement(['id' => 'userInformation_info_referral']);
        $I->click('//div[@id="accordion4"]//a[text()="NEXT"]');

        // reopen tabs one by one bottom up, don't need to try all combos
        $I->click(['id' => 'step_4']);
        $I->seeElement(['id' => "userInformation_phone1"]);
        $I->click(['id' => 'step_2']);
        $I->seeElement(['id' => "userInformation_address_address1"]);
        $I->click(['id' => 'step_1']);
        $I->seeElement(['id' => "userInformation_honorific_prefix"]);
    }

    /**
     * @group mobile
     * @group onboardingSequentialDirect
     */
    public function complianceFormUser(AcceptanceTester $I)
    {
        $I->amOnPage('/onboarding');
        $I->click('Next');
        $I->wait(1);
        $I->seeCurrentUrlEquals('/onboarding/compliance');
        $this->fillComplianceForm($I);
        $I->click('//form[contains(@name, "userInformation")]//button[@type="submit"]');

        $I->waitForText("setup Strong Customer Authentication");

        // If you decide to preamturely exit SCA enrollment
        // You'll need to redo the compliance
        $I->amOnPage('/onboarding');
        $I->click('Next');
        $I->wait(1);
        $I->seeCurrentUrlEquals('/onboarding/compliance');
        $this->fillComplianceForm($I);
        $I->click('//form[contains(@name, "userInformation")]//button[@type="submit"]');

        $I->waitForText("setup Strong Customer Authentication");
        $I->click("Start SCA Setup");
        $I->completeScaEnrollment($this->username);
        $I->waitForText("Congratulations", 15);
        $I->seeCurrentUrlEquals('/onboarding/complete');
    }

    /**
     * @group mobile
     * @group onboardingSequentialDirect
     */
    public function checkUserInfo(AcceptanceTester $I)
    {
        // resync the user by logging in again
        $I->loginWithCredentials(sqs('test') . $I->new_user_yorran["email"], $I->new_user_yorran["password"], false);

        if ($I->cmsCheck) {
            if ($I->indietest) {
                $user_email = $I->assessed_user_patton["email"];
            } else {
                $user_email = sqs('test') . $I->new_user_yorran["email"];
            }
            // check phone number is saved with country code prefix
            // check mangopay wallet created for user
            // check gender is all caps
            // check default referral code given
            $token = $I->getUserToken($user_email, $I->new_user_yorran["password"]);
            if ($token) {
                $user = $I->getUserInfoByAPI($token);
                $user_info = $I->convertUserInfoToDict($user['info']);

                try {
                    $salesforce_id = $user_info['salesforce_id'];
                    // $sf_user = $I->salesforceAction('GET', 'Contact', $salesforce_id);
                    // the 2 login synced fields should no longer be null after we have done a login and synced with SF
                    // $I->assertNotNull($sf_user['last_login__c']);
                    // $I->assertNotNull($sf_user['MPWalletBalance__c']);

                    // Note that the salesforce object isn't changed in 008, so can delete here
                    $I->salesforceAction('DELETE', $I->salesforce_params["user_object"], $salesforce_id);
                } catch (\Throwable $th) {
                    echo "Salesforce not setup";
                }

                $I->assertEquals(5, $user['ob_step']);
                $I->assertEquals("+33" . $I::SCA_TEST_PHONE_NUMBER, $user['phone_1']);
                $I->assertEquals("FEMALE", $user['gender']);
                // https://mangopay.com/docs/api-basics/data-formats
                // Mangopay IDs come in various formats, oldest format is at least 8 chars long
                $I->assertGreaterOrEquals(8, strlen($user['mangopay_user_id']));
                $I->assertNotEmpty($user['mangopay_wallet_id']);
            } else {
                $I->assertTrue($token, "Unable to login");
            }
        }
    }

    private function fillComplianceForm(AcceptanceTester $I): void
    {
        // NOTE, cannot use submit form due to pre-filled inputs e.g. date of birth and nationality
        $I->selectOption(['id' => 'userInformation_honorific_prefix'], 'Ms');
        $I->selectOption(['id' => 'userInformation_gender'], 'FEMALE');
        $I->fillField(['id' => 'userInformation_firstname'], $I->assessed_user_patton["firstname"]);
        $I->fillField(['id' => 'userInformation_lastname'], $I->assessed_user_patton["lastname"]);
        $I->selectOption(['id' => 'userInformation_birthDate_day'], '08');
        $I->selectOption(['id' => 'userInformation_birthDate_month'], '12');
        $I->selectOption(['id' => 'userInformation_birthDate_year'], '1980');
        $I->selectOption(['id' => 'userInformation_nationality'], 'United Kingdom');
        $I->click('//div[@id="accordion1"]//a[text()="NEXT"]');

        $I->selectOption(['id' => 'userInformation_address_country'], 'United Kingdom');
        $I->fillField(['id' => 'userInformation_address_postal_code'], 'E1 2PT');
        $I->fillField(['id' => 'userInformation_address_address1'], '4 Kings Road');
        $I->fillField(['id' => 'userInformation_address_city'], 'London');
        $I->scrollTo('//div[@id="accordion2"]//a[text()="NEXT"]');
        $I->click('//div[@id="accordion2"]//a[text()="NEXT"]');


        // Select the French phone number prefix for SCA test phone number
        $I->clickWithLeftButton('#user-phone-number div.selected-flag');
        $I->clickWithLeftButton('#user-phone-number li[data-country-code="fr"]');
        $I->fillField(['id' => 'userInformation_phone1'], $I::SCA_TEST_PHONE_NUMBER);
        // $I->fillField(['id' => 'userInformation_phone1'], '02072054650');
        $I->fillField(['id' => 'userInformation_phone2'], '07911123456');
        $I->seeElement(['id' => 'userInformation_info_referral']);
        $I->click('//div[@id="accordion4"]//a[text()="NEXT"]');

        $I->attachFile(['id' => 'userInformation_document_proof_of_id'], 'specimen_passport.jpg');
        $I->attachFile(['id' => 'userInformation_document_proof_of_address'], 'org_img.jpg');

        // check you can reattach the same file after removing
        $I->clickWithLeftButton('//div[@id="imgcontent1"]//button');
        $I->seeElement('//span[@id="file_1"][contains(@class, "fileupload_start")]');
        $I->attachFile(['id' => 'userInformation_document_proof_of_id'], 'specimen_passport.jpg');
        $I->scrollTo('#submit-compliance', 0, -150);
        // $I->click('//form[contains(@name, "userInformation")]//button[@type="submit"]');
    }
}
