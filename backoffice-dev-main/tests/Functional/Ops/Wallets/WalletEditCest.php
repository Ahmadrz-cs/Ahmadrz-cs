<?php

namespace App\Tests\Functional\Ops\Wallets;

use App\Tests\Support\FunctionalTester;

class WalletEditCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function checkWalletEditor(FunctionalTester $I): void
    {
        $sampleWalletId = $I->grabFromDatabase('users', 'mangoPayWalletId', [
            'username' => $I::USER_REG1,
        ]);
        $I->amOnPage("/admin/wallets/{$sampleWalletId}");
        $I->seeLink('Back', '/admin/asset/wallets');
        $I->seeLink('Discard Changes', '/admin/asset/wallets');

        // Store the original values so we can change back
        $originalDescription = $I->grabTextFrom(
            '[data-field-name="wallet-description"]',
        );
        $modifiedDescription = $originalDescription . ' edit test';

        $I->fillField('#form_Description', $modifiedDescription);
        $I->click('Save Changes');
        $I->seeCurrentUrlEquals('/admin/asset/wallets');
        $I->see("Wallet {$sampleWalletId} description successfully updated");

        $I->amOnPage("/admin/wallets/{$sampleWalletId}");
        $I->see($modifiedDescription, '[data-field-name="wallet-description"]');

        // Revert changes
        $I->fillField('#form_Description', $originalDescription);
        $I->click('Save Changes');
    }
}
