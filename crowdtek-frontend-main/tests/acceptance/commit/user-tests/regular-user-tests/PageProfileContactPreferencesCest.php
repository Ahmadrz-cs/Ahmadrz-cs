<?php


class PageProfileContactPreferencesCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->loginWithCredentials($I->approved_investor_2, $I->admin_user_password);
        $I->amOnPage('/my-profile/contact-preferences');
    }

    public function _after(AcceptanceTester $I)
    {
    }

    /**
     * @group profile
     */
    public function checkProfileNav(\Step\Acceptance\StaticPages $I)
    {
        $I->amOnPage('/my-profile/contact-preferences');
        $I->see("Contact Preferences", ".navClick.active");
    }

    /**
     * @group profile
     */
    public function checkContactPreferencesForm(\Step\Acceptance\StaticPages $I)
    {
        $I->amOnPage('/my-profile/contact-preferences');
        $I->see('Contact Preferences', 'h4.mb-2');
        $I->seeElement('div#contactPreferences');
        $I->seeElement('form[name="gdpr_accepted"]');
        $I->seeElement('form a[href="/privacy-policy"]');
    }


    /**
     * @group profile
     */
    public function checkContactPreferencesEdit(\Step\Acceptance\StaticPages $I)
    {
        // check default is correctly displayed
        $I->amOnPage('/my-profile/contact-preferences');
        $I->seeCheckboxIsChecked('input#gdpr_accepted_gdpr_accepted');
        // change to opt out
        $I->uncheckOption('input#gdpr_accepted_gdpr_accepted');
        $I->clickWithLeftButton('button#gdpr_accepted_Submit');
        $I->waitForText('Your contact preferences have been successfully updated.');
        $I->clickWithLeftButton('div#modal-alertInfo button[data-dismiss="modal"]');
        // check page updates and opt out selected as new default
        // $I->amOnPage('/my-profile/feedback');
        $I->amOnPage('/my-profile/contact-preferences');
        $I->dontSeeCheckboxIsChecked('input#gdpr_accepted_gdpr_accepted');
        // change back to opt in
        $I->checkOption('input#gdpr_accepted_gdpr_accepted');
        $I->clickWithLeftButton('button#gdpr_accepted_Submit');
        $I->waitForText('Your contact preferences have been successfully updated.');
        $I->clickWithLeftButton('div#modal-alertInfo button[data-dismiss="modal"]');
        // check page updates and opt in selected as new default
        // $I->amOnPage('/my-profile/feedback');
        $I->amOnPage('/my-profile/contact-preferences');
        $I->seeCheckboxIsChecked('input#gdpr_accepted_gdpr_accepted');
    }
}
