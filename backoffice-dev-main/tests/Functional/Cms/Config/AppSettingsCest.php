<?php

namespace App\Tests\Functional\Cms\Config;

use App\Entity\AbstractOrder;
use App\Tests\Support\FunctionalTester;

class AppSettingsCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->loginAdmin();
    }

    public function checkAppSettingsConfig(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/settings');

        $I->seeLink(
            'Manage Superadmin Mangopay SCA',
            '/admin/settings/superadmin/mangopay-sca',
        );
        $I->seeLink('View Mangopay Client', '/admin/settings/mangopay-client');

        $elements = [
            'Id',
            'Name',
            'Value',
            'Section',
            'Created At',
            'Created By',
            'Updated At',
            'Updated By',
        ];
        $locator = '#application-settings-overview thead tr th';
        $I->loopCheckElements($elements, $locator);

        // Saving (posting the form) should trigger the setup
        // Which will creating any missing app settings
        $I->click('Save Changes');
        $I->seeNumberOfElements('#application-settings-overview table tbody tr', 3);

        // YPML fee wallet should be nullable
        $I->fillField('#app_setting_form_ypmlFeeWallet', '');
        $I->click('Save Changes');
        $I->seeInField('#app_setting_form_ypmlFeeWallet', '');

        // Set the ypml wallet back to the dev default
        $I->fillField('#app_setting_form_ypmlFeeWallet', $I::YPML_FEE_WALLET);
        $I->click('Save Changes');
        $I->seeInField('#app_setting_form_ypmlFeeWallet', $I::YPML_FEE_WALLET);

        // Set the order issue limit to the dev default
        $I->fillField('#app_setting_form_orderIssueLimit', AbstractOrder::ISSUE_LIMIT);
        $I->click('Save Changes');
        $I->seeInField('#app_setting_form_orderIssueLimit', AbstractOrder::ISSUE_LIMIT);
    }
}
