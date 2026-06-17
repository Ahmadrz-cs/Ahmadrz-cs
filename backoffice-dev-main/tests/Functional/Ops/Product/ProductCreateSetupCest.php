<?php

namespace App\Tests\Functional\Ops\Product;

use App\Tests\Support\FunctionalTester;

class ProductCreateSetupCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    /**
     * @group dashboard
     */
    public function checkProductCreation(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/products/create');

        $I->seeLink('Back', '/admin/products');
        $I->seeLink('Abandon', '/admin/products');
        $uniqueString = bin2hex(random_bytes(4));
        $randomNumber = round(rand(200, 800) / 100, 2);
        $I->fillField('#product_create_name', "The Trials Hub {$uniqueString}");
        $I->click('Create New Product');
        $I->see('New asset product created', '.alert');

        $newAssetId = $I->grabFromDatabase('assets', 'id', [
            'name' => "The Trials Hub {$uniqueString}",
        ]);
        // $newOfferingId = $I->grabFromDatabase('offerings', 'id', [
        //     'name' => "The Trials Hub {$uniqueString}",
        // ]);

        // Check the Skip for now link is present
        // Just fill in a single field to ensure things are working
        // We'll check the filled fields at the end

        $I->see('About Asset', 'h3');
        $I->seeCurrentUrlEquals("/admin/products/{$newAssetId}/editor/about?setup=1");
        $I->seeLink(
            'Skip for now',
            "/admin/products/{$newAssetId}/editor/location?setup=1",
        );
        $I->fillField('#product_about_companyNumber', "SPVT{$uniqueString}");
        $I->click('Save and Continue');

        $I->see('Property Location', 'h3');
        $I->seeCurrentUrlEquals(
            "/admin/products/{$newAssetId}/editor/location?setup=1",
        );
        $I->seeLink(
            'Skip for now',
            "/admin/products/{$newAssetId}/editor/financials?setup=1",
        );
        $I->fillField('#address_create_address1', "{$uniqueString} Avenue");
        $I->click('Save and Continue');

        $I->see('Financial Information', 'h3');
        $I->seeCurrentUrlEquals(
            "/admin/products/{$newAssetId}/editor/financials?setup=1",
        );
        // Check the asset default minimumInvestment is configured
        $I->seeInField('#asset_financial_minimumInvestment', '100.00');
        $I->seeLink(
            'Skip for now',
            "/admin/products/{$newAssetId}/editor/rules?setup=1",
        );
        $I->fillField('#asset_financial_pricePerShare', "$randomNumber");
        $I->click('Save and Continue');

        // Trading Rules are currently skipped
        // $I->see('Trading Rules', 'h3');
        // $I->seeCurrentUrlEquals(
        //     "/admin/products/{$newAssetId}/editor/rules?setup=1",
        // );
        // $I->seeLink(
        //     'Skip for now',
        //     "/admin/products/{$newAssetId}/editor/wallets?setup=1",
        // );
        // $I->fillField('#product_rules_minCommitUser', round($randomNumber * 10, 2));
        // $I->click('Save and Continue');

        $I->see('Wallets', 'h3');
        $I->seeCurrentUrlEquals("/admin/products/{$newAssetId}/editor/wallets?setup=1");
        $I->seeLink(
            'Skip for now',
            "/admin/products/{$newAssetId}/editor/documents?setup=1",
        );
        // We'll fill in all but 1 to reduce the number of real wallets to generate

        // $I->fillField('#product_wallet_holdWalletId', "WalletHold{$uniqueString}");
        $I->fillField(
            '#product_wallet_settlementWalletId',
            "WalletSettlement{$uniqueString}",
        );
        $I->fillField(
            '#product_wallet_depositWalletId',
            "WalletDeposit{$uniqueString}",
        );
        $I->fillField(
            '#product_wallet_expensesWalletId',
            "WalletExpense{$uniqueString}",
        );
        $I->fillField('#product_wallet_taxWalletId', "WalletTax{$uniqueString}");
        $I->fillField(
            '#product_wallet_distributionWalletId',
            "WalletDistribution{$uniqueString}",
        );
        // $I->fillField('#product_wallet_treasuryWalletId', "WalletTreasury{$uniqueString}");
        $I->click('Save and Continue');

        $I->see('Documents', 'h3');
        $I->seeCurrentUrlEquals(
            "/admin/products/{$newAssetId}/editor/documents?setup=1",
        );
        $I->seeLink('Finish Setup', "/admin/products/{$newAssetId}");
        $I->click('Finish Setup');

        $I->seeCurrentUrlEquals("/admin/products/{$newAssetId}");
        $I->see('Not Yet Launched', '#status');
        $I->see("SPVT{$uniqueString}", '[data-field-name="company-number"]');
        $I->see("{$uniqueString} Avenue", '[data-field-name="address"]');
        $I->see($randomNumber, '[data-field-name="share-price"]');
        // $I->see(
        //     round($randomNumber * 10, 2),
        //     '[data-field-name="minimum-single-commitment"]',
        // );
        // $I->see("WalletHold{$uniqueString}", '#wallets table tbody');
        $I->see("WalletSettlement{$uniqueString}", '#wallets table tbody');
        $I->see("WalletDeposit{$uniqueString}", '#wallets table tbody');
        $I->see("WalletExpense{$uniqueString}", '#wallets table tbody');
        $I->see("WalletTax{$uniqueString}", '#wallets table tbody');
        $I->see("WalletDistribution{$uniqueString}", '#wallets table tbody');
        // $I->see("WalletTreasury{$uniqueString}", '#wallets table tbody');
        // First and last wallets still missing
        $I->see('Missing', '#wallets table tbody tr:first-child');
        $I->see('Missing', '#wallets table tbody tr:last-child');
        $I->click('Go to Launch Centre');
        $I->see('Not Ready For Launch', '#launch-readiness');

        // Try wallet generation
        $I->amOnPage("/admin/products/{$newAssetId}/editor/wallets?setup=1");
        $I->click('Auto-generate Missing Hold and Main Wallets');
        $I->seeCurrentUrlEquals(
            "/admin/products/{$newAssetId}/editor/documents?setup=1",
        );
        $I->see('["hold","settlement"] wallets successfully created');

        $I->amOnPage("/admin/products/{$newAssetId}");
        // Only the hold and settlement/main wallets should be created
        // The last wallet (treasury) should still not be created
        $I->dontSee('Missing', '#wallets table tbody tr:first-child');
        $I->see('Missing', '#wallets table tbody tr:last-child');

        // Also available outside of setup
        $I->amOnPage("/admin/products/{$newAssetId}/editor/wallets");
        $I->click('Auto-generate All Missing Wallets');
        $I->seeCurrentUrlEquals("/admin/products/{$newAssetId}");
        $I->see(
            '["hold","settlement","deposit","expenses","tax","distribution","treasury"] wallets successfully created',
        );
        // All missing wallets should be created so none "missing"
        $I->dontSee('Missing', '#wallets table tbody');
    }
}
