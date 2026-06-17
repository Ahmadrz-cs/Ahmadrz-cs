<?php

namespace App\Tests\Functional\Ops\Administration;

use App\Tests\Support\FunctionalTester;

class AssetWalletsCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function checkAssetWallets(FunctionalTester $I)
    {
        $I->amOnPage('/admin/asset/wallets');
        $I->see('Asset Wallets');
        $elements = [
            'Id',
            'Name',
            'SPV Id',
            'Holding',
            'Settlement',
            'Deposit',
            'Expenses',
            'Tax',
            'Distribution',
            'Treasury',
            'Actions',
        ];
        // check table headers
        $I->loopCheckElements($elements, 'thead th');
        $I->see('Manage');
    }

    public function checkAssetWallet(FunctionalTester $I)
    {
        $I->amOnPage('/admin/asset/11/manage-wallets');
        $I->see('Type');
        $I->see('Wallet Id');
        $I->see('Wallet Owner');
        $I->see('Description');
        $I->see('Balance (£)');
        $I->see('Actions');

        $I->see('Edit Asset');
        $I->see('Edit Description');
    }

    public function createAssetWallet(FunctionalTester $I)
    {
        // Check wallet created and saved to database
        $assetId = $I->grabFromDatabase('assets', 'id', ['expensesWalletId' => null]);
        $I->amOnPage('/admin/asset/' . $assetId . '/manage-wallets');
        $I->see('Missing', "tr[data-wallet-type='expenses'] span.badge");
        $I->see('Create Wallet', "tr[data-wallet-type='expenses'] a");
        $I->seeLink('Create Wallet', "/admin/asset/{$assetId}/wallets/create/expenses");
        $I->click('Create Wallet', "tr[data-wallet-type='expenses'] a");
        $I->dontSeeLink(
            'Create Wallet',
            "/admin/asset/{$assetId}/wallets/create/expenses",
        );

        $expensesWalletId = $I->grabFromDatabase('assets', 'expensesWalletId', [
            'id' => $assetId,
        ]);
        $I->assertNotNull($expensesWalletId);
        $I->see('Expenses wallet has been successfully created.');
    }

    public function createAllAssetWallets(FunctionalTester $I)
    {
        // Check wallets created and saved to database

        $assetId = $I->grabFromDatabase('assets', 'id', [
            'mangoPayWalletId' => null,
            'additional_wallet' => null,
            'depositWalletId' => null,
            'expensesWalletId' => null,
            'taxWalletId' => null,
            'distributionWalletId' => null,
            'treasuryWalletId' => null,
        ]);
        $I->amOnPage('/admin/asset/' . $assetId . '/manage-wallets');
        $I->see('Missing', "tr[data-wallet-type='hold'] span.badge");
        $I->see('Missing', "tr[data-wallet-type='settlement'] span.badge");
        $I->see('Missing', "tr[data-wallet-type='deposit'] span.badge");
        $I->see('Missing', "tr[data-wallet-type='tax'] span.badge");
        $I->see('Missing', "tr[data-wallet-type='treasury'] span.badge");
        $I->see('Missing', "tr[data-wallet-type='distribution'] span.badge");
        $I->see('Missing', "tr[data-wallet-type='expenses'] span.badge");
        $I->see('Create Missing Wallets');
        $I->click('Create Missing Wallets');
        $I->see('wallets successfully created');

        $holdWalletId = $I->grabFromDatabase('assets', 'mangoPayWalletId', [
            'id' => $assetId,
        ]);
        $settlementWalletId = $I->grabFromDatabase('assets', 'additional_wallet', [
            'id' => $assetId,
        ]);
        $depositWalletId = $I->grabFromDatabase('assets', 'depositWalletId', [
            'id' => $assetId,
        ]);
        $expensesWalletId = $I->grabFromDatabase('assets', 'expensesWalletId', [
            'id' => $assetId,
        ]);
        $taxWalletId = $I->grabFromDatabase('assets', 'taxWalletId', [
            'id' => $assetId,
        ]);
        $distributionWalletId = $I->grabFromDatabase('assets', 'distributionWalletId', [
            'id' => $assetId,
        ]);
        $treasuryWalletId = $I->grabFromDatabase('assets', 'treasuryWalletId', [
            'id' => $assetId,
        ]);

        $I->assertNotNull($holdWalletId);
        $I->assertNotNull($settlementWalletId);
        $I->assertNotNull($depositWalletId);
        $I->assertNotNull($expensesWalletId);
        $I->assertNotNull($taxWalletId);
        $I->assertNotNull($distributionWalletId);
        $I->assertNotNull($treasuryWalletId);

        // Check only redirects only work with permitted routes
        // Valid redirect tested in ProductCreateSetupCest.php
        // This test expects redirect to the default page
        $I->amOnPage(
            "/admin/asset/{$assetId}/wallets/create-all?"
                . http_build_query([
                    'redirectRoute' => 'not_permitted_route',
                    'redirectId' => 1,
                ]),
        );
        $I->seeCurrentUrlEquals("/admin/asset/{$assetId}/manage-wallets");

        // Reset this asset's wallets for test reruns
        $I->amOnPage("/admin/products/{$assetId}/editor/wallets");
        $I->fillField('#product_wallet_holdWalletId', '');
        $I->fillField('#product_wallet_settlementWalletId', '');
        $I->fillField('#product_wallet_depositWalletId', '');
        $I->fillField('#product_wallet_expensesWalletId', '');
        $I->fillField('#product_wallet_taxWalletId', '');
        $I->fillField('#product_wallet_distributionWalletId', '');
        $I->fillField('#product_wallet_treasuryWalletId', '');
        $I->click('Save Changes');
    }
}
