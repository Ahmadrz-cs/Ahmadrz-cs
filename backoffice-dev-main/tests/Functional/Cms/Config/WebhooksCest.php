<?php

namespace App\Tests\Functional\Cms\Config;

use App\Tests\Support\FunctionalTester;

class WebhooksCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->loginAdmin();
    }

    public function checkRecentWebhookEventListElements(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/webhooks/recent-events');

        $elements = [
            'Id',
            'Fingerprint',
            'Event Type',
            'Resource Id',
            'Last Received',
        ];
        $locator = '#webhook-events-list thead tr th';
        $I->loopCheckElements($elements, $locator);
    }

    public function checkMangopayHookListElements(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/webhooks/mangopay');
        $I->seeLink('View Recent Events', '/admin/webhooks/recent-events');

        $elements = [
            'Hook Id',
            'Event Type',
            'Creation Date',
            'Endpoint Url',
            'Status',
            'Validity',
            'Tag/Description',
            'Actions',
        ];
        $locator = '#mangopay-hooks thead tr th';
        $I->loopCheckElements($elements, $locator);
    }

    public function checkMangopayHookEdit(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/webhooks/mangopay');
        $I->click('View/Edit');
        $I->seeLink('Back', '/admin/webhooks/mangopay');
        $I->seeLink('Discard Changes', '/admin/webhooks/mangopay');

        $currentHookId = $I->grabTextFrom(
            'section#edit-hook-form [data-field-name=hook-id]',
        );

        $stats = [
            'hook-id',
            'creation-date',
            'status',
            'validity',
        ];
        foreach ($stats as $fieldName) {
            $I->seeElement("section#edit-hook-form [data-field-name={$fieldName}]");
        }

        $formFields = [
            'mangopay_hook_EventType' => 'select',
            'mangopay_hook_Status' => 'select',
            'mangopay_hook_Url' => 'input',
            'mangopay_hook_Tag' => 'input',
        ];
        foreach ($formFields as $elementId => $inputType) {
            $I->seeElement("section#edit-hook-form {$inputType}#{$elementId}");
        }

        // Update the tag field
        $currentTag = $I->grabValueFrom('#mangopay_hook_Tag');
        if (empty($currentTag)) {
            $currentTag = 'Test tag';
        }
        $randomString = bin2hex(random_bytes(8));
        $I->fillField('#mangopay_hook_Tag', $randomString . $currentTag);
        $I->click('Save Changes');
        $I->seeCurrentUrlEquals("/admin/webhooks/mangopay/{$currentHookId}");
        $I->see('hook successfully updated');
        $I->seeInField('#mangopay_hook_Tag', $randomString . $currentTag);

        // Revert changes
        $I->fillField('#mangopay_hook_Tag', $currentTag);
        $I->click('Save Changes');
    }

    public function checkMangopayHookCreateCancel(FunctionalTester $I): void
    {
        // Note that we can't delete hooks, only update them
        // So we can't use automated tests for creating hooks and we'll eventually run out of event types

        $I->amOnPage('/admin/webhooks/mangopay');
        $I->click('Create New Hook');
        $I->seeCurrentUrlEquals('/admin/webhooks/mangopay/create');
        $I->seeLink('Back', '/admin/webhooks/mangopay');
        $I->seeLink('Abandon', '/admin/webhooks/mangopay');
        $I->see('Create Hook', '#create-hook-form form button[type=submit]');

        $formFields = [
            'mangopay_hook_EventType' => 'select',
            'mangopay_hook_Status' => 'select',
            'mangopay_hook_Url' => 'input',
            'mangopay_hook_Tag' => 'input',
        ];
        foreach ($formFields as $elementId => $inputType) {
            $I->seeElement("section#create-hook-form {$inputType}#{$elementId}");
        }
    }
}
