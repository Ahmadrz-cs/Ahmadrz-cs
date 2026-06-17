<?php

/**
 * This is a standalone test, does not require other tests to complete unlike 005ComplianceCest
 */
class ComplianceBusinessCest
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
            $this->username = $I->createUserAtStage('4', '1', 'bud'); // get sophisticated bud user
        }
    }

    public function _after(AcceptanceTester $I) {}

    /**
     * Compliance - things to look out for
     * - Check when adding docs
     *   - image of file is there (just check that a new <img> element has appeared)
     *   - check remove also removes that element - display property changes
     *   - after removing files, should not be able to submit - check form validation is kicking in as well
     *     - class when red: fileupload_start, class when green: fileupload_Valid
     * - Check flag phone numbers
     */

    /**
     * @group mobile
     */
    public function checkFormValidation(AcceptanceTester $I)
    {
        $I->amOnPage('/onboarding/compliance');
        $I->click('Next');

        // stage 1
        // check prefilled names are correct
        if ($I->indietest) {
            $I->seeInField(['id' => 'userInformation_firstname'], $I->assessed_user_patton["firstname"]);
            $I->seeInField(['id' => 'userInformation_lastname'], $I->assessed_user_patton["lastname"]);
        } else {
            $I->seeInField(['id' => 'userInformation_firstname'], $I->new_user_yorran["firstname"]);
            $I->seeInField(['id' => 'userInformation_lastname'], $I->new_user_yorran["lastname"]);
        }
        // stage validation
        $I->click('//div[@id="accordion1"]//a[text()="NEXT"]');
        $I->seeElement('//select[@id="userInformation_honorific_prefix"][contains(@class, "invalid_email")]');
        $I->seeElement('//select[@id="userInformation_gender"][contains(@class, "invalid_email")]');
        $I->seeElement('//select[@id="userInformation_birthDate_day"][contains(@class, "invalid_email")]');
        $I->seeElement('//select[@id="userInformation_birthDate_month"][contains(@class, "invalid_email")]');
        $I->seeElement('//select[@id="userInformation_birthDate_year"][contains(@class, "invalid_email")]');
        // check dynamic validation of dob
        $I->selectOption(['id' => 'userInformation_honorific_prefix'], 'Ms');
        $I->selectOption(['id' => 'userInformation_gender'], 'FEMALE');
        $I->selectOption(['id' => 'userInformation_birthDate_day'], '08');
        $I->selectOption(['id' => 'userInformation_birthDate_month'], '12');
        $I->selectOption(['id' => 'userInformation_birthDate_year'], '1980');
        $I->seeElement('//select[@id="userInformation_honorific_prefix"][contains(@class, "valid_email")]');
        $I->seeElement('//select[@id="userInformation_gender"][contains(@class, "valid_email")]');
        $I->seeElement('//select[@id="userInformation_birthDate_day"][contains(@class, "valid_email")]');
        $I->seeElement('//select[@id="userInformation_birthDate_month"][contains(@class, "valid_email")]');
        $I->seeElement('//select[@id="userInformation_birthDate_year"][contains(@class, "valid_email")]');
        $I->selectOption(['id' => 'userInformation_honorific_prefix'], 'Select an option');
        $I->selectOption(['id' => 'userInformation_gender'], 'Select an option');
        $I->selectOption(['id' => 'userInformation_birthDate_day'], 'dd');
        $I->selectOption(['id' => 'userInformation_birthDate_month'], 'mm');
        $I->selectOption(['id' => 'userInformation_birthDate_year'], 'yyyy');
        $I->seeElement('//select[@id="userInformation_honorific_prefix"][contains(@class, "invalid_email")]');
        $I->seeElement('//select[@id="userInformation_gender"][contains(@class, "invalid_email")]');
        $I->seeElement('//select[@id="userInformation_birthDate_day"][contains(@class, "invalid_email")]');
        $I->seeElement('//select[@id="userInformation_birthDate_month"][contains(@class, "invalid_email")]');
        $I->seeElement('//select[@id="userInformation_birthDate_year"][contains(@class, "invalid_email")]');
        // move onto next stage
        $I->selectOption(['id' => 'userInformation_honorific_prefix'], 'Ms');
        $I->selectOption(['id' => 'userInformation_gender'], 'FEMALE');
        $I->selectOption(['id' => 'userInformation_birthDate_day'], '08');
        $I->selectOption(['id' => 'userInformation_birthDate_month'], '12');
        $I->selectOption(['id' => 'userInformation_birthDate_year'], '1980');
        $I->clickWithLeftButton('//div[@id="accordion1"]//label[contains(text(), "Investing through a limited company")]');
        $I->click('//div[@id="accordion1"]//a[text()="NEXT"]');

        // stage 2
        $I->scrollTo('//div[@id="accordion2"]//a[text()="NEXT"]');
        $I->click('//div[@id="accordion2"]//a[text()="NEXT"]'); // stage validation
        $I->seeElement('//select[@id="userInformation_address_country"][contains(@class, "invalid_email")]');
        $I->seeElement('//input[@id="userInformation_address_address1"][contains(@class, "invalid_business")]');
        $I->seeElement('//input[@id="userInformation_address_city"][contains(@class, "invalid_business")]');
        $I->seeElement('//input[@id="userInformation_address_postal_code"][contains(@class, "invalid_business")]');
        // dynamic validation
        $I->selectOption(['id' => 'userInformation_address_country'], 'United Kingdom');
        $I->fillField(['id' => 'userInformation_address_address1'], '4 Kings Road');
        $I->fillField(['id' => 'userInformation_address_city'], 'London');
        $I->fillField(['id' => 'userInformation_address_postal_code'], 'E1 2PT');
        $I->clickWithLeftButton(['id' => 'userInformation_address_address1']);
        $I->seeElement('//input[@id="userInformation_address_address1"][contains(@class, "used")]');
        $I->seeElement('//input[@id="userInformation_address_city"][contains(@class, "used")]');
        $I->seeElement('//input[@id="userInformation_address_postal_code"][contains(@class, "used")]');
        $I->fillField(['id' => 'userInformation_address_address1'], '');
        $I->fillField(['id' => 'userInformation_address_city'], '');
        $I->fillField(['id' => 'userInformation_address_postal_code'], '');
        $I->clickWithLeftButton(['id' => 'userInformation_address_address1']);
        $I->seeElement('//input[@id="userInformation_address_address1"][contains(@class, "invalid_business")]');
        $I->seeElement('//input[@id="userInformation_address_city"][contains(@class, "invalid_business")]');
        $I->seeElement('//input[@id="userInformation_address_postal_code"][contains(@class, "invalid_business")]');
        // move onto next stage
        $I->selectOption(['id' => 'userInformation_address_country'], 'United Kingdom');
        $I->fillField(['id' => 'userInformation_address_address1'], '4 Kings Road');
        $I->fillField(['id' => 'userInformation_address_city'], 'London');
        $I->fillField(['id' => 'userInformation_address_postal_code'], 'E1 2PT');
        $I->scrollTo('//div[@id="accordion2"]//a[text()="NEXT"]');
        $I->click('//div[@id="accordion2"]//a[text()="NEXT"]');

        // stage 3
        $I->scrollTo('//div[@id="accordion3"]//a[text()="NEXT"]');
        $I->click('//div[@id="accordion3"]//a[text()="NEXT"]'); // stage validation
        $I->seeElement('//input[@id="userInformation_info_company_name"][contains(@class, "invalid_business")]');
        $I->seeElement('//input[@id="userInformation_info_company_registered_number"][contains(@class, "invalid_business")]');
        $I->seeElement('//input[@id="userInformation_info_company_nature_of_business"][contains(@class, "invalid_business")]');
        $I->seeElement('//input[@id="userInformation_info_company_telephone"][contains(@class, "invalid_business")]');
        $I->seeElement('//select[@id="userInformation_info_company_registration_country"][contains(@class, "invalid_email")]');
        $I->seeElement('//input[@id="userInformation_info_company_registered_address_1"][contains(@class, "invalid_business")]');
        $I->seeElement('//input[@id="userInformation_info_company_postcode"][contains(@class, "invalid_business")]');
        // dynamic validation
        $I->fillField(['id' => 'userInformation_info_company_name'], 'Patton and Sonnet Investments');
        $I->fillField(['id' => 'userInformation_info_company_registered_number'], '1123581321');
        $I->fillField(['id' => 'userInformation_info_company_nature_of_business'], 'Investments management');
        $I->fillField(['id' => 'userInformation_info_company_telephone'], '09871234567');
        $I->fillField(['id' => 'userInformation_info_company_registered_address_1'], '4 Kings Road');
        $I->fillField(['id' => 'userInformation_info_company_postcode'], 'E1 2PT');
        $I->clickWithLeftButton(['id' => 'userInformation_info_company_registered_address_1']); // click a nearby space
        $I->seeElement('//input[@id="userInformation_info_company_name"][contains(@class, "used")]');
        $I->seeElement('//input[@id="userInformation_info_company_registered_number"][contains(@class, "used")]');
        $I->seeElement('//input[@id="userInformation_info_company_nature_of_business"][contains(@class, "used")]');
        $I->seeElement('//input[@id="userInformation_info_company_telephone"][contains(@class, "used")]');
        $I->seeElement('//input[@id="userInformation_info_company_registered_address_1"][contains(@class, "used")]');
        $I->seeElement('//input[@id="userInformation_info_company_postcode"][contains(@class, "used")]');
        $I->fillField(['id' => 'userInformation_info_company_name'], '');
        $I->fillField(['id' => 'userInformation_info_company_registered_number'], '');
        $I->fillField(['id' => 'userInformation_info_company_nature_of_business'], '');
        $I->fillField(['id' => 'userInformation_info_company_telephone'], '');
        $I->fillField(['id' => 'userInformation_info_company_registered_address_1'], '');
        $I->fillField(['id' => 'userInformation_info_company_postcode'], '');
        // $I->clickWithLeftButton(['id' => 'userInformation_info_operating_address'], 20, 50); // click a nearby space
        $I->seeElement('//input[@id="userInformation_info_company_name"][contains(@class, "invalid_business")]');
        $I->seeElement('//input[@id="userInformation_info_company_registered_number"][contains(@class, "invalid_business")]');
        $I->seeElement('//input[@id="userInformation_info_company_nature_of_business"][contains(@class, "invalid_business")]');
        $I->seeElement('//input[@id="userInformation_info_company_telephone"][contains(@class, "invalid_business")]');
        $I->seeElement('//input[@id="userInformation_info_company_registered_address_1"][contains(@class, "invalid_business")]');
        $I->seeElement('//input[@id="userInformation_info_company_postcode"][contains(@class, "invalid_business")]');
        // move onto next stage
        $I->fillField(['id' => 'userInformation_info_company_name'], 'Patton and Sonnet Investments');
        $I->fillField(['id' => 'userInformation_info_company_registered_number'], '1123581321');
        $I->fillField(['id' => 'userInformation_info_company_nature_of_business'], 'Investments management');
        $I->fillField(['id' => 'userInformation_info_company_telephone'], '09871234567');
        $I->selectOption(['id' => 'userInformation_info_company_registration_country'], 'United Kingdom');
        $I->fillField(['id' => 'userInformation_info_company_registered_address_1'], '4 Kings Road');
        $I->fillField(['id' => 'userInformation_info_company_postcode'], 'E1 2PT');
        $I->scrollTo('//div[@id="accordion3"]//a[text()="NEXT"]');
        $I->click('//div[@id="accordion3"]//a[text()="NEXT"]');

        // stage 4
        $I->click('//div[@id="accordion4"]//a[text()="NEXT"]'); // stage validation
        $I->seeElement('//input[@id="userInformation_phone1"][contains(@class, "invalid_business")]');
        // $I->seeElement('//input[@id="userInformation_phone2"][contains(@class, "invalid_business")]');
        // dynamic validation
        $I->fillField(['id' => 'userInformation_phone1'], '02072054650');
        // $I->fillField(['id' => 'userInformation_phone2'], '07911123456');
        $I->clickWithLeftButton('#user-phone-number span');
        $I->seeElement('//input[@id="userInformation_phone1"][contains(@class, "used")]');
        // $I->seeElement('//input[@id="userInformation_phone2"][contains(@class, "used")]');
        $I->fillField(['id' => 'userInformation_phone1'], '');
        // $I->fillField(['id' => 'userInformation_phone2'], '');
        $I->clickWithLeftButton('#user-phone-number span');
        $I->seeElement('//input[@id="userInformation_phone1"][contains(@class, "invalid_business")]');
        // $I->seeElement('//input[@id="userInformation_phone2"][contains(@class, "invalid_business")]');
        // continue to next stage
        $I->fillField(['id' => 'userInformation_phone1'], '02072054650');
        // $I->fillField(['id' => 'userInformation_phone2'], '07911123456');
        $I->click('//div[@id="accordion4"]//a[text()="NEXT"]');

        // stage 5
        $I->scrollTo('#submit-compliance', 0, -150);
        $I->click('//form[contains(@name, "userInformation")]//button[text()="Submit"]');
        $I->seeCurrentUrlEquals('/onboarding/compliance'); // check block submit
        $I->seeElement('//span[@id="file_1"][contains(@class, "fileupload_start")]');
        $I->seeElement('//span[@id="file_2"][contains(@class, "fileupload_start")]');
        $I->attachFile(['id' => 'userInformation_document_proof_of_id'], 'specimen_passport.jpg');
        $I->attachFile(['id' => 'userInformation_document_proof_of_address'], 'org_img.jpg');
        $I->attachFile(['id' => 'userInformation_document_proof_of_business'], 'org_img.JPEG');
        $I->seeElement('//span[@id="file_1"][contains(@class, "fileupload_Valid")]');
        $I->seeElement('//span[@id="file_2"][contains(@class, "fileupload_Valid")]');

        // remove files - should prevent submit
        $I->clickWithLeftButton('//div[@id="imgcontent1"]//button');
        $I->seeElement('//span[@id="file_1"][contains(@class, "fileupload_start")]');
        $I->seeElement('#submit-compliance[disabled]');
        $I->click('//form[contains(@name, "userInformation")]//button[text()="Submit"]');
        $I->seeCurrentUrlEquals('/onboarding/compliance'); // check block submit after removing file

        // try adding invalid files (too big or wrong type) - should prevent submit
        $I->attachFile(['id' => 'userInformation_document_proof_of_id'], 'test_dump.sql');
        $I->see('The file you chose was too large.', ['id' => 'sizeWarning1']);
        $I->see('The file you chose was not in a supported format.', ['id' => 'typeWarning1']);
        $I->see('Please try a different one.', ['id' => 'tryother1']);
        $I->seeElement('#submit-compliance[disabled]');
        $I->scrollTo(['id' => 'userInformation_document_proof_of_id']);
        $I->click('//form[contains(@name, "userInformation")]//button[@type="submit"]');
        $I->seeCurrentUrlEquals('/onboarding/compliance');

        // check messages update  - should prevent submit
        $I->attachFile(['id' => 'userInformation_document_proof_of_id'], 'dump.sql');
        $I->dontSee('The file you chose was too large.', ['id' => 'sizeWarning1']);
        $I->see('The file you chose was not in a supported format.', ['id' => 'typeWarning1']);
        $I->see('Please try a different one.', ['id' => 'tryother1']);
        $I->seeElement('#submit-compliance[disabled]');
        $I->click('//form[contains(@name, "userInformation")]//button[@type="submit"]');
        $I->seeCurrentUrlEquals('/onboarding/compliance');

        // clearout existing images
        $I->clickWithLeftButton('//div[@id="imgcontent2"]//button');

        // Per input warning message
        $I->attachFile(['id' => 'userInformation_document_proof_of_address'], 'painting7mb.png');
        $I->see('The file you chose was too large.', ['id' => 'sizeWarning2']);

        $I->attachFile(['id' => 'userInformation_document_proof_of_business'], 'painting7mb.png');
        $I->see('The file you chose was too large.', ['id' => 'sizeWarning3']);

        $I->attachFile(['id' => 'userInformation_document_proof_of_address'], 'Caerte_van_Oostlant_4MB.jpg');
        $I->dontSee('The file you chose was too large.', ['id' => 'sizeWarning2']);
        $I->dontSee('Please try a different one.', ['id' => 'tryother2']);

        // reopen tabs one by one bottom up, don't need to try all combos
        $I->click(['id' => 'step_4']);
        $I->seeElement(['id' => "userInformation_phone1"]);
        $I->click(['id' => 'step_3']);
        $I->seeElement(['id' => 'userInformation_info_company_name']);
        $I->click(['id' => 'step_2']);
        $I->seeElement(['id' => "userInformation_address_address1"]);
        $I->click(['id' => 'step_1']);
        $I->seeElement(['id' => "userInformation_honorific_prefix"]);
    }

    /**
     * hybrid test:
     * - does business option show extra fields
     * @group mobile
     */
    public function complianceBudBusiness(AcceptanceTester $I)
    {
        $I->amOnPage('/onboarding');
        $I->click('Next');
        $I->wait(1);
        $I->seeCurrentUrlEquals('/onboarding/compliance');

        // NOTE, cannot use submit form due to pre-filled inputs e.g. date of birth and nationality
        $I->selectOption(['id' => 'userInformation_honorific_prefix'], 'Ms');
        $I->selectOption(['id' => 'userInformation_gender'], 'FEMALE');
        $I->selectOption(['id' => 'userInformation_birthDate_day'], '08');
        $I->selectOption(['id' => 'userInformation_birthDate_month'], '12');
        $I->selectOption(['id' => 'userInformation_birthDate_year'], '1980');
        $I->selectOption(['id' => 'userInformation_nationality'], 'United Kingdom');
        $I->clickWithLeftButton('//div[@id="accordion1"]//label[contains(text(), "Investing through a limited company")]');
        // implicit isChecked test since if not will fail to fill accordion3 (business address) fields
        $I->click('//div[@id="accordion1"]//a[text()="NEXT"]');

        $I->wait(1);
        $I->selectOption(['id' => 'userInformation_address_country'], 'United Kingdom');
        $I->fillField(['id' => 'userInformation_address_postal_code'], 'E1 2PT');
        $I->fillField(['id' => 'userInformation_address_address1'], '4 Kings Road');
        $I->fillField(['id' => 'userInformation_address_city'], 'London');
        $I->scrollTo('//div[@id="accordion2"]//a[text()="NEXT"]');
        $I->click('//div[@id="accordion2"]//a[text()="NEXT"]');

        $I->wait(1);
        $I->fillField(['id' => 'userInformation_info_company_name'], 'Patton and Sonnet Investments');
        $I->fillField(['id' => 'userInformation_info_company_registered_number'], '1123581321');
        $I->fillField(['id' => 'userInformation_info_company_nature_of_business'], 'Investments management');
        $I->fillField(['id' => 'userInformation_info_company_telephone'], '09871234567');
        $I->scrollTo(['link' => 'ADD DIRECTORS']);
        $I->click(['link' => 'ADD DIRECTORS']);
        $I->fillField(['id' => 'userInformation_info_company_directors_0_firstname'], 'Jeremy');
        $I->fillField(['id' => 'userInformation_info_company_directors_0_lastname'], 'AccTest-Sonnet');
        $I->scrollTo(['link' => 'ADD BENEFICIAL OWNERS']);
        $I->click(['link' => 'ADD BENEFICIAL OWNERS']);
        $I->fillField(['id' => 'userInformation_info_company_beneficial_owners_0_firstname'], 'Harriet');
        $I->fillField(['id' => 'userInformation_info_company_beneficial_owners_0_lastname'], 'AccTest-Patton');
        $I->selectOption(['id' => 'userInformation_info_company_registration_country'], 'United Kingdom');
        $I->fillField(['id' => 'userInformation_info_company_registered_address_1'], '4 Kings Road');
        $I->fillField(['id' => 'userInformation_info_company_postcode'], 'E1 2PT');
        $I->scrollTo('//div[@id="accordion3"]//a[text()="NEXT"]');
        $I->click('//div[@id="accordion3"]//a[text()="NEXT"]');

        // Select the French phone number prefix for SCA test phone number
        $I->wait(1);
        $I->clickWithLeftButton('#user-phone-number div.selected-flag');
        $I->clickWithLeftButton('#user-phone-number li[data-country-code="fr"]');
        $I->fillField(['id' => 'userInformation_phone1'], $I::SCA_TEST_PHONE_NUMBER);
        // $I->fillField(['id' => 'userInformation_phone1'], '02072054650');
        // $I->fillField(['id' => 'userInformation_phone2'], '07911123456');
        $I->click('//div[@id="accordion4"]//a[text()="NEXT"]');

        $I->wait(1);
        $I->attachFile(['id' => 'userInformation_document_proof_of_id'], 'specimen_passport.jpg');
        $I->attachFile(['id' => 'userInformation_document_proof_of_address'], 'org_img.jpg');
        $I->attachFile(['id' => 'userInformation_document_proof_of_business'], 'org_img.jpg');

        $I->scrollTo('#submit-compliance', 0, -150);
        $I->click('//form[contains(@name, "userInformation")]//button[@type="submit"]');

        $I->wait(1);
        $I->waitForText("setup Strong Customer Authentication");
        $I->click("Start SCA Setup");
        $I->completeScaEnrollment($this->username);
        $I->waitForText("Congratulations", 15);
        $I->seeCurrentUrlEquals('/onboarding/complete');
    }

    /**
     * @group mobile
     */
    public function checkComplianceInfo(AcceptanceTester $I)
    {
        if ($I->cmsCheck) {
            if ($I->indietest) {
                $user_email = $I->assessed_user_patton["email"];
            } else {
                $user_email = sqs('bud') . $I->new_user_yorran["email"];
            }
            // check company personnel
            $token = $I->getUserToken($user_email, $I->new_user_yorran["password"]);
            if ($token) {
                $user = $I->getUserInfoByAPI($token);
                $user_info = $I->convertUserInfoToDict($user['info']);
                $I->assertEquals(5, $user['ob_step']);
                $I->assertEquals("0", $user['gdpr_accepted']);
                $I->assertStringContainsString("Harriet", $user_info['company_beneficial_owners']);
                $I->assertStringContainsString("AccTest-Patton", $user_info['company_beneficial_owners']);
                $I->assertStringContainsString("Jeremy", $user_info['company_directors']);
                $I->assertStringContainsString("AccTest-Sonnet", $user_info['company_directors']);

                try {
                    $salesforce_id = $user_info['salesforce_id'];
                    $sf_user = $I->salesforceAction('GET', 'Contact', $salesforce_id);

                    $I->assertEquals($user_email, $sf_user['Email']);
                    $I->assertEquals("FEMALE", $sf_user['gender__c']);
                    $I->assertEquals("Ms", $sf_user['honoricPrefix__c']);
                    $I->assertEquals("4 Kings Road", $sf_user['address1__c']);
                    $I->assertEquals("London", $sf_user['city__c']);
                    $I->assertEquals("E1 2PT", $sf_user['postCode__c']);
                    $I->assertEquals("GB", $sf_user['country__c']);
                    $I->assertEquals("GB", $sf_user['nationality__c']);

                    $I->assertStringContainsString($I::SCA_TEST_PHONE_NUMBER, (string)$sf_user['Phone']);
                    $I->assertStringContainsString("1980-12-08", $sf_user['birthDate__c']);
                    // $I->assertNotEmpty($sf_user['mangoPayUserId__c']);
                    // $I->assertNotEmpty($sf_user['mangoPayWalletId__c']);

                    $I->assertEquals(0, $sf_user['gdpr_accepted__c']);
                    $I->assertEquals(1, $sf_user['IsEmailValidated__c']);
                    $I->assertEquals(0, $sf_user['cxbWorthInvestor__c']);
                    $I->assertEquals(1, $sf_user['cxbSophisticatedInvestor__c']);
                    $I->assertEquals(0, $sf_user['cxbRestrictedUser__c']);
                    $I->assertEquals(1, $sf_user['corporateInvestor__c']);
                    $I->assertEquals(0, $sf_user['wordsOfOwn__c']);
                    $I->assertEquals(0, $sf_user['IsApproved__c']);
                    $I->assertEquals(0, $sf_user['isRegCompleted__c']);
                    $I->assertEquals(0, $sf_user['isBlocked__c']);

                    $I->salesforceAction('DELETE', $I->salesforce_params["user_object"], $salesforce_id);
                } catch (\Throwable $th) {
                    echo "Salesforce not setup";
                }
            } else {
                $I->assertTrue($token, "Unable to login");
            }
        }
    }
}
