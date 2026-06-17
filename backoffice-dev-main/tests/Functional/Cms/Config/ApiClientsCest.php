<?php

namespace App\Tests\Functional\Cms\Config;

use App\Tests\Support\FunctionalTester;

class ApiClientsCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->loginAdmin();
    }

    public function checkClientListElements(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/administration/clients');

        $elements = [
            'Identifier',
            'Alias',
            'User',
            'Redirect',
            'Grants',
            'Active',
        ];

        $locator = 'thead tr th';

        $I->loopCheckElements($elements, $locator);
    }

    public function checkClientEdit(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/administration/clients/' . $I::OAUTH2_CLIENT_ID);

        $elements = [
            'User',
            'Alias',
            'Description',
            'Identifier',
            'Redirect',
            'Grants',
            'Scopes',
        ];
        $locator = 'form[name="user_client"]';
        $I->loopCheckElements($elements, $locator);
        $I->see('Active', 'label');

        $altAdminUserId = $I->grabFromDatabase('user_client', 'user_id', [
            'client_id' => $I::OAUTH2_CLIENT_ID,
        ]);

        $I->fillField('#user_client_user', $altAdminUserId);
        $I->fillField('#user_client_alias', 'yielderneoverse');
        $I->fillField('#user_client_description', 'Updated dev client description');
        $randomString = bin2hex(random_bytes(8));
        $newRedirectUrl = " https://test.com/{$randomString}cburl";
        $redirectUrls =
            $I->grabTextFrom('#user_client_client_redirectUris') . $newRedirectUrl;
        $I->fillField('#user_client_client_redirectUris', $redirectUrls);
        $I->uncheckOption('#user_client_client_active');
        $I->uncheckOption('#user_client_client_scopes_2');
        $I->uncheckOption('#user_client_client_grants_2');
        $I->click('#user_client_submit');

        $I->amOnPage('/admin/administration/clients/' . $I::OAUTH2_CLIENT_ID);
        $I->seeInField('#user_client_user', $altAdminUserId);
        $I->seeInField('#user_client_alias', 'yielderneoverse');
        $I->seeInField('#user_client_description', 'Updated dev client description');
        $I->see($newRedirectUrl);
        $I->dontSeeCheckboxIsChecked('#user_client_client_active');
        $I->dontSeeCheckboxIsChecked('#user_client_client_scopes_2');
        $I->dontSeeCheckboxIsChecked('#user_client_client_grants_2');

        $I->seeCheckboxIsChecked('#user_client_client_scopes_0');
        $I->seeCheckboxIsChecked('#user_client_client_scopes_1');
        $I->seeCheckboxIsChecked('#user_client_client_scopes_3');

        $I->seeCheckboxIsChecked('#user_client_client_grants_0');
        $I->seeCheckboxIsChecked('#user_client_client_grants_1');
        $I->seeCheckboxIsChecked('#user_client_client_grants_3');

        $I->checkOption('#user_client_client_active');
        $I->click('#user_client_submit');
    }

    public function checkClientCreateCancel(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/administration/clients');

        $I->click('Create New Client');
        $I->see('Client id');
        $I->see('Client secret');

        $I->click('Cancel and Delete');
        $I->see('Client successfully deleted');
    }

    public function checkClientCreateAndDelete(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/administration/clients');

        $I->click('Create New Client');
        $I->see('Client id');
        $I->see('Client secret');
        $clientId = $I->grabTextFrom('#client-id');

        $I->click('Configure Client');

        // Check default grants and scopes
        $grantOptions = $I->grabMultiple('#user_client_client_grants input', 'value');
        $grantDefaults = [
            'client_credentials',
        ];
        foreach ($grantOptions as $grant) {
            $locator = '#user_client_client_grants input[value="' . $grant . '"]';
            if (in_array($grant, $grantDefaults)) {
                $I->seeCheckboxIsChecked($locator);
            } else {
                $I->dontSeeCheckboxIsChecked($locator);
            }
        }

        $scopeOptions = $I->grabMultiple('#user_client_client_scopes input', 'value');
        $scopeDefaults = [
            'asset:read',
            'offering:read',
        ];
        foreach ($scopeOptions as $scope) {
            $locator = '#user_client_client_scopes input[value="' . $scope . '"]';
            if (in_array($scope, $scopeDefaults)) {
                $I->seeCheckboxIsChecked($locator);
            } else {
                $I->dontSeeCheckboxIsChecked($locator);
            }
        }

        // Should be able to submit empty redirect uris
        $I->click('#user_client_submit');

        $I->amOnPage('/admin/administration/clients/' . $clientId);
        $I->click('Delete Client');
        $I->see('is active and cannot be deleted');

        // Must deactivate before deleting
        $I->amOnPage('/admin/administration/clients/' . $clientId);
        $I->uncheckOption('#user_client_client_active');
        $I->click('#user_client_submit');
        $I->amOnPage('/admin/administration/clients/' . $clientId);
        $I->click('Delete Client');
        $I->see('Client successfully deleted');
    }

    public function checkClientCreateInsufficientGrantsAndScopes(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/administration/clients');

        $I->click('Create New Client');
        $I->see('Client id');
        $I->see('Client secret');
        $clientId = $I->grabTextFrom('#client-id');

        $I->click('Configure Client');

        // Uncheck active so the client is deletable for cleanup
        $I->uncheckOption('#user_client_client_active');
        $I->click('#user_client_submit');
        $I->amOnPage('/admin/administration/clients/' . $clientId);

        // uncheck default grants
        $checkedOptions = $I->grabMultiple(
            'input[type=checkbox][checked=checked]',
            'id',
        );
        foreach ($checkedOptions as $inputId) {
            $I->uncheckOption("#{$inputId}");
        }
        $I->click('#user_client_submit');
        $I->see('You must select at least 1 choice', '.invalid-feedback');

        $I->amOnPage('/admin/administration/clients/' . $clientId);
        $I->click('Delete Client');
        $I->see('Client successfully deleted');
    }
}
