<?php

namespace App\Tests\Functional\Cms\EmailTemplates;

use App\Tests\Support\FunctionalTester;

class LegacyEmailTemplateCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->loginAdmin();
    }

    /**
     * @group listview
     */
    public function checkListTableHeaders(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/email-templates/legacy');

        $elements = [
            'Id',
            'Slug',
            'Name',
            'Subject',
            'Last Updated',
            'Actions',
        ];

        $locator = 'th';

        $I->loopCheckElements($elements, $locator);
    }

    /**
     * @group detailview
     * @group emails
     */
    public function checkCreateAndEdit(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/email-templates/legacy');
        $I->click('Create New Email Template');
        $I->seeCurrentUrlEquals('/admin/email-templates/legacy/create');
        $fieldContent = [
            'mail_slug' => 'test_email_slug' . bin2hex(random_bytes(4)),
            'mail_name' => 'Test email name-identifier',
            'mail_subject' => 'Test email subject line',
            'mail_body' => 'Test email body',
            'mail_params' => '[]',
        ];

        foreach ($fieldContent as $field => $value) {
            $I->fillField("#{$field}", $value);
        }
        $checkOptions = [
            'mail_sendAdmin',
            'mail_sendUser',
            'mail_confirmation',
        ];
        foreach ($checkOptions as $field) {
            $I->checkOption("#{$field}");
        }
        $I->click('Create Email Template');
        $I->seeCurrentUrlMatches('~^/admin/email-templates/legacy/(\d+)$~');
        foreach ($fieldContent as $field => $value) {
            $I->seeInField("#{$field}", $value);
        }
        $I->seeCheckboxIsChecked('#mail_sendAdmin');
        $I->seeCheckboxIsChecked('#mail_sendUser');
        $I->dontSeeCheckboxIsChecked('#mail_confirmation');

        // Update and save
        $updatedFieldContent = [
            'mail_slug' => 'test_email_slug' . bin2hex(random_bytes(4)),
            'mail_name' => 'Test email name-identifier modified',
            'mail_subject' => 'Test email subject line modified',
            'mail_body' => 'Test email body modified',
            'mail_params' => '[]',
        ];
        foreach ($updatedFieldContent as $field => $value) {
            $I->fillField("#{$field}", $value);
        }
        $I->uncheckOption('#mail_sendAdmin');
        $I->uncheckOption('#mail_sendUser');
        $I->checkOption('#mail_confirmation');
        $I->click('Save Changes', '#mail_submit');

        $I->seeCurrentUrlMatches('~^/admin/email-templates/legacy/(\d+)$~');
        $I->see('Successfully updated email template');
        foreach ($updatedFieldContent as $field => $value) {
            $I->seeInField("#{$field}", $value);
        }
        $I->dontSeeCheckboxIsChecked('#mail_sendAdmin');
        $I->dontSeeCheckboxIsChecked('#mail_sendUser');
        $I->dontSeeCheckboxIsChecked('#mail_confirmation');

        // Test sending this email
        $mailcatcher = $I->getMailcatcherClient();
        $mailcatcher->delete('/messages');
        $I->click('Test Email Template');
        $I->seeCurrentUrlMatches('~^/admin/email-templates/legacy/(\d+)/test$~');

        // Will show error if missing required parameters
        // Parameter enforcement has been removed
        // $I->click('Send Test Email');
        // $I->see('Failed to send email with template');

        // // Should work after filling in the params
        // $I->fillField('#mail_parameters_params', json_encode([
        //     'keyexample' => 'example',
        //     'keyexample2' => 'example',
        //     'skipObjectCheck' => true // disable the type check
        // ]));
        $I->click('Send Test Email');
        $I->see('Successfully sent email with template');
        $emailMetadata = json_decode(
            (string) $mailcatcher->get('/messages/1.json')->getBody(),
            true,
        );
        $I->assertEquals(
            $updatedFieldContent['mail_subject'],
            $emailMetadata['subject'],
        );
    }
}
