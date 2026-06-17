<?php


class PageProfileUserInfoCest
{
    public function _before(\Step\Acceptance\StaticPages $I)
    {
        $I->loginWithCredentials($I->reg_user_name, $I->admin_user_password);
        $I->amOnPage('/my-profile/profile');
    }

    public function _after(AcceptanceTester $I)
    {
    }

    /**
     * @group profile
     */
    public function checkProfileNav(\Step\Acceptance\StaticPages $I)
    {
        /**
         * Check correct nav item is highlighted (i.e. has the class "active")
         */
        $I->see("General Information", ".navClick.active");
    }

    /**
     * @group profile
     */
    public function checkUserInfoForm(\Step\Acceptance\StaticPages $I)
    {
        /**
         * See page section title is present: "General Information"
         * Check that the correct labels and fields are present, plus that specific ones are disabled
         * - User Id - disabled
         * - First Name - disabled
         * - Last Name - disabled
         * - Communications Email (Required)
         * - (Mobile) Phone (Required)
         * - Company Name (Optional)
         * - Company Postcode (Optional)
         * - Company Address (Optional)
         */

        $I->seeElement("h4.mb-3");

        //Checkind Disable value
        $I->seeElement("input[disabled][name='userId']");
        $I->seeElement("input[disabled][name='given_name']");
        $I->seeElement("input[disabled][name='family_name']");

        //Checkind Required value
        $I->scrollTo('div.active h4');
        $I->clearField("input#email");
        $I->clearField("input#phone");
        $I->clickWithLeftButton("//input[@class='btn btn-primary btn-popup rounded-pill px-2' or @value='Save']");
        $I->waitForText("Please, enter an email");
        $I->waitForText("This field is required.");
        $I->fillField("input#email", "ben.autotest@crowdtek.co.uk");
        $I->clickWithLeftButton("div.iti__selected-flag");
        $I->clickWithLeftButton("li#iti-item-gb");
        $I->fillField("input#phone", "020 7946 0614");
        $I->clickWithLeftButton("//input[@class='btn btn-primary btn-popup rounded-pill px-2' or @value='Save']");

        //Checking Optional value
        $I->clickWithLeftButton(null, 10, 10);
        $I->scrollTo("#editProfile");
        $I->seeElement("input#company_name");
        $I->seeElement("input#company_postcode");
        $I->seeElement("input#company_registered_address_1");
    }

    /**
     * @group profile
     */
    public function checkUserInfoFormEdit(\Step\Acceptance\StaticPages $I)
    {
        // Filling Fields
        $I->fillField("input#email", "ben.autotest@crowdtek.co.uk");
        $I->clickWithLeftButton("div.iti__selected-flag");
        $I->clickWithLeftButton("li#iti-item-gb");
        $I->fillField("input#phone", "02079460614");
        $I->fillField("input#company_name", "ABC Company");
        $I->fillField("input#company_postcode", "DA4 9DJ");
        $I->fillField("input#company_registered_address_1", "20 Rashleigh Way Horton Kirby DARTFORD");
        $I->scrollTo("input#company_name");
        $I->clickWithLeftButton("//input[@class='btn btn-primary btn-popup rounded-pill px-2' or @value='Save']");
        $I->reloadPage();
        $I->waitForElementVisible('input#phone');

        // Checking Changes Have Saved
        $newEmail = $I->grabValueFrom('input#email');
        $I->assertEquals("ben.autotest@crowdtek.co.uk", $newEmail);
        $newPhone = $I->grabValueFrom('input#phone_1');
        $I->assertEquals("+442079460614", $newPhone);
        $newCompanyName = $I->grabValueFrom('input#company_name');
        $I->assertEquals("ABC Company", $newCompanyName);
        $newPostcode = $I->grabValueFrom('input#company_postcode');
        $I->assertEquals("DA4 9DJ", $newPostcode);
        $newRegAddress = $I->grabValueFrom('input#company_registered_address_1');
        $I->assertEquals("20 Rashleigh Way Horton Kirby DARTFORD", $newRegAddress);
    }
}
