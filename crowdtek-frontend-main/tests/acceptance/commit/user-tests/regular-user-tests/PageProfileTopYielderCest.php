<?php


class PageProfileTopYielderCest
{
    public function _before(\Step\Acceptance\StaticPages $I)
    {
        $I->loginWithCredentials($I->reg_user_name, $I->admin_user_password);
        $I->amOnPage('/my-profile/apply-top-yielder');
    }

    public function _after(AcceptanceTester $I)
    {
    }

    /**
     * @group profile
     */
    public function checkProfileNav(\Step\Acceptance\StaticPages $I)
    {
        $I->see("Top Yielders", ".navClick.active");
    }

    /**
     * @group profile
     */
    public function checkTopYielderForm(\Step\Acceptance\StaticPages $I)
    {
        $I->see("Apply to become a Top Yielder");

        $htmlOne = $I->grabAttributeFrom("input[value^='43']", 'readonly');
        $I->assertEquals('true', $htmlOne);

        $htmltwo = $I->grabAttributeFrom("input[value^='Ben']", 'readonly');
        $I->assertEquals('true', $htmltwo);

        $htmlthreee = $I->grabAttributeFrom("input[value^='Charlton']", 'readonly');
        $I->assertEquals('true', $htmlthreee);

        $I->scrollTo("textarea");

        $I->seeElement("div .custom-file");

        $I->seeElementInDOM("input#user_form_type_POIFile1");
        $I->seeElementInDOM("input#user_form_type_POIFile2");
        $I->seeElementInDOM("input#user_form_type_POIFile2");
    }

    /**
     * @group profile
     */
    public function checkTopYielderFormEdit(\Step\Acceptance\StaticPages $I)
    {
        /**
         * Check the form submission works (and saves changes)
         * WaitforText: "Your user information has been saved successfully" message
         */

        //Scroll the page
        $I->scrollTo("form#apply-top-yielder");

        //Inserting data into text box
        $I->click("textarea#user_form_type_info_words_of_your_own");
        $I->fillField("textarea#user_form_type_info_words_of_your_own", "test content");

        //uploading the TestData
        $I->attachFile("input#user_form_type_POIFile1", "TestData.xlsx");
        $I->attachFile("input#user_form_type_POIFile2", "TestData.xlsx");
        $I->attachFile("input#user_form_type_POIFile3", "TestData.xlsx");

        //clicking submit button
        $I->click("button[type='submit']");

        $I->waitForText("Thank you for your application, we will be in touch with you soon with the results.");

        $I->reloadPage();
    }

    /**
     * @group profile
     */
    public function checkTopYielderFormCharacters(\Step\Acceptance\StaticPages $I)
    {
        /**
         * 1) Check character counter updates on input
         * 2) Check max character behaviour
         *    a) check submits on max
         *    b) check does not allow above
         */

        // Scroll to form
        $I->scrollTo("form#apply-top-yielder");

        /**
         * 1)
         */

        // Get value before input
        $before = $I->grabTextFrom('small#limitNotice');

        // Fill field
        $I->fillField('textarea#user_form_type_info_words_of_your_own', '0123456789');

        // Get value after input
        $after = $I->grabTextFrom('small#limitNotice');

        // Assertions
        $I->assertNotEquals($before, $after);
        $I->assertEquals(10, $after);

        /**
         * 2a
         */

        $maxCharacters = $I->grabAttributeFrom('textarea#user_form_type_info_words_of_your_own', 'maxlength');
        $maxedString = $I->generateRandomString($maxCharacters);

        // Fill field to max
        $I->fillField('textarea#user_form_type_info_words_of_your_own', $maxedString);

        // Attatch file
        $I->attachFile("input#user_form_type_POIFile1", "TestData.xlsx");
        $I->attachFile("input#user_form_type_POIFile2", "TestData.xlsx");
        $I->attachFile("input#user_form_type_POIFile3", "TestData.xlsx");

        // Submit
        $I->click("button[type='submit']");

        $I->waitForText("Thank you for your application, we will be in touch with you soon with the results.");

        $I->reloadPage();

        /**
         * 2b
         */

        $maxCharacters = $I->grabAttributeFrom('textarea#user_form_type_info_words_of_your_own', 'maxlength');
        $overString = $I->generateRandomString($maxCharacters + 1);

        // Attempt to fill field above max
        $I->fillField('textarea#user_form_type_info_words_of_your_own', $overString);

        // See character count still only on max
        $charCount = $I->grabTextFrom('small#limitNotice');
        $I->assertEquals($charCount, $maxCharacters);
    }

    /**
     * @group profile
     */
    public function checkTopYielderFormEmptyBehaviour(\Step\Acceptance\StaticPages $I)
    {
        // Scroll to form
        $I->scrollTo("form#apply-top-yielder");

        // Attempt Submit
        $I->click("button[type='submit']");

        // Check Still on page
        $I->seeInCurrentUrl('/my-profile/apply-top-yielder');
    }
}
