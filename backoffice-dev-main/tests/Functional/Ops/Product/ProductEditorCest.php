<?php

namespace App\Tests\Functional\Ops\Product;

use App\Entity\BaseEntity;
use App\Entity\Enum\AssetStatus;
use App\Entity\Enum\ProductMode;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Tests\Support\FunctionalTester;

class ProductEditorCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    /**
     * @group dashboard
     */
    public function checkWalletEditor(FunctionalTester $I): void
    {
        $sampleAssetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Sagittarius Eystar - Horizon',
        ]);

        $I->amOnPage("/admin/products/{$sampleAssetId}/editor/wallets");
        $I->seeLink('Back', "/admin/products/{$sampleAssetId}");
        $I->seeLink('Discard Changes', "/admin/products/{$sampleAssetId}");

        // Store the original values so we can change back
        // Avoiding grabbing from the hidden csrf field by using :not([type=hidden])
        $fields = $I->grabMultiple(
            'input[id^=product_wallet_]:not([type=hidden])',
            'id',
        );
        $originalValues = $I->grabMultiple(
            'input[id^=product_wallet_]:not([type=hidden])',
            'value',
        );
        $originalValues = array_combine($fields, $originalValues);

        // Make edits to each field
        $randomString = bin2hex(random_bytes(8));
        $newExpectedValues = [];
        foreach ($fields as $index => $id) {
            $newValue = "{$randomString}_{$index}";
            $newExpectedValues[] = $newValue;
            $I->fillField("#{$id}", $newValue);
        }
        $newExpectedValues = array_combine($fields, $newExpectedValues);
        $I->click('Save Changes');
        $I->see('successfully updated');

        // Go back to the edit page and check the new values are now in each field
        $I->amOnPage("/admin/products/{$sampleAssetId}/editor/wallets");
        $newActualValues = $I->grabMultiple(
            'input[id^=product_wallet_]:not([type=hidden])',
            'value',
        );
        $newActualValues = array_combine($fields, $newActualValues);
        // Do not use canonicalizing as we want the keys and ordering to be checked as well
        $I->assertEquals($newExpectedValues, $newActualValues);

        // Revert Changes
        foreach ($originalValues as $id => $value) {
            $I->fillField("#{$id}", $value);
        }
        $I->click('Save Changes');
    }

    /**
     * @group dashboard
     */
    public function checkLocationEditor(FunctionalTester $I): void
    {
        $sampleAssetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Sagittarius Eystar - Horizon',
        ]);

        $I->amOnPage("/admin/products/{$sampleAssetId}/editor/location");
        $I->seeLink('Back', "/admin/products/{$sampleAssetId}");
        $I->seeLink('Discard Changes', "/admin/products/{$sampleAssetId}");

        // Store the original values so we can change back
        $textfields = $I->grabMultiple(
            'input[id^=address_create_]:not([type=hidden])',
            'id',
        );
        $fields = [
            ...$textfields,
            $I->grabAttributeFrom('select[id^=address_create_country]', 'id'),
        ];
        $originalValues = $I->grabMultiple(
            'input[id^=address_create_]:not([type=hidden])',
            'value',
        );
        $originalValues[] = $I->grabValueFrom('select[id^=address_create_country]');
        $originalValues = array_combine($fields, $originalValues);

        // Make edits to each field
        $randomString = bin2hex(random_bytes(8));
        $newExpectedValues = [];
        foreach ($textfields as $index => $id) {
            $newValue = "{$randomString}_{$index}";
            $newExpectedValues[] = $newValue;
            $I->fillField("#{$id}", $newValue);
        }
        $I->selectOption('select[id^=address_create_country]', 'FR');
        $newExpectedValues[] = 'FR';
        $newExpectedValues = array_combine($fields, $newExpectedValues);
        $I->click('Save Changes');
        $I->see('successfully updated');

        // Go back to the edit page and check the new values are now in each field
        $I->amOnPage("/admin/products/{$sampleAssetId}/editor/location");
        $newActualValues = $I->grabMultiple(
            'input[id^=address_create_]:not([type=hidden])',
            'value',
        );
        $newActualValues[] = $I->grabValueFrom('select[id^=address_create_country]');
        $newActualValues = array_combine($fields, $newActualValues);
        // Do not use canonicalizing as we want the keys and ordering to be checked as well
        $I->assertEquals($newExpectedValues, $newActualValues);

        // Revert Changes
        foreach ($textfields as $id) {
            $I->fillField("#{$id}", $originalValues[$id]);
        }
        $I->selectOption(
            'select[id^=address_create_country]',
            $originalValues['address_create_country'],
        );
        $I->click('Save Changes');
    }

    // /**
    //  * @group dashboard
    //  */
    // public function checkInvestmentRulesEditor(FunctionalTester $I): void
    // {
    //     $sampleAssetId = $I->grabFromDatabase('assets', 'id', [
    //         'name' => 'Sagittarius Eystar - Horizon',
    //     ]);
    //     $sampleSharePrice = $I->grabFromDatabase('assets', 'pricePerShare', [
    //         'id' => $sampleAssetId,
    //     ]);

    //     $I->amOnPage("/admin/products/{$sampleAssetId}/editor/rules");
    //     $I->seeLink('Back', "/admin/products/{$sampleAssetId}");
    //     $I->seeLink('Discard Changes', "/admin/products/{$sampleAssetId}");

    //     // Store the original values so we can change back
    //     $fields = $I->grabMultiple(
    //         'input[id^=product_rules_]:not([type=hidden])',
    //         'id',
    //     );
    //     $originalValues = $I->grabMultiple(
    //         'input[id^=product_rules_]:not([type=hidden])',
    //         'value',
    //     );
    //     $originalValues = array_combine($fields, $originalValues);

    //     // Make edits to each field
    //     $minNew = round(120 * $sampleSharePrice, 2);
    //     $maxNew = round(500 * $sampleSharePrice, 2);
    //     $newExpectedValues = [
    //         'product_rules_minCommitUser' => $minNew,
    //         'product_rules_maxCommitUser' => $maxNew,
    //     ];
    //     // Want to check the rounding behaviour of the min and max commits
    //     // Min should round up to represent "at least this amount but can be more"
    //     // Max should round down to represent "at most this amount but cannot be more"
    //     $inputValues = [
    //         'product_rules_minCommitUser' => round(
    //             $minNew - ($sampleSharePrice / 3),
    //             2,
    //         ),
    //         'product_rules_maxCommitUser' => round(
    //             $maxNew + ($sampleSharePrice / 3),
    //             2,
    //         ),
    //     ];
    //     foreach ($inputValues as $id => $newValue) {
    //         $I->fillField("#{$id}", $newValue);
    //     }
    //     $I->click('Save Changes');
    //     $I->see('successfully updated');

    //     // Go back to the edit page and check the new values are now in each field
    //     $I->amOnPage("/admin/products/{$sampleAssetId}/editor/rules");
    //     $newActualValues = $I->grabMultiple(
    //         'input[id^=product_rules_]:not([type=hidden])',
    //         'value',
    //     );
    //     $newActualValues = array_combine($fields, $newActualValues);
    //     // Do not use canonicalizing as we want the keys and ordering to be checked as well
    //     $I->assertEquals($newExpectedValues, $newActualValues);

    //     // Revert Changes
    //     foreach ($originalValues as $id => $value) {
    //         $I->fillField("#{$id}", $value);
    //     }
    //     $I->click('Save Changes');
    // }

    /**
     * @group dashboard
     */
    public function checkAboutEditor(FunctionalTester $I): void
    {
        $sampleAssetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Sagittarius Eystar - Horizon',
        ]);

        $I->amOnPage("/admin/products/{$sampleAssetId}/editor/about");
        $I->seeLink('Back', "/admin/products/{$sampleAssetId}");
        $I->seeLink('Discard Changes', "/admin/products/{$sampleAssetId}");

        // Store the original values so we can change back
        $textfields = $I->grabMultiple(
            'input[id^=product_about_]:not([type=hidden])',
            'id',
        );
        // Add text area at the end of textfields to be handled differently to input (has no value)
        $textfields = [
            ...$textfields,
            $I->grabAttributeFrom('textarea[id^=product_about_]', 'id'),
        ];
        $fields = [
            ...$textfields,
            $I->grabAttributeFrom('select[id^=product_about_assetType]', 'id'),
        ];
        $originalValues = $I->grabMultiple(
            'input[id^=product_about_]:not([type=hidden])',
            'value',
        );
        // Need to handle the text area after all other text fields are saved
        $originalValues[] = $I->grabTextFrom('textarea[id^=product_about_]');
        // Add the non-text fields last
        $originalValues[] = $I->grabValueFrom('select[id^=product_about_assetType]');
        $originalValues = array_combine($fields, $originalValues);

        // Make edits to each field
        $randomString = bin2hex(random_bytes(8));
        $newExpectedValues = [];
        foreach ($textfields as $index => $id) {
            $newValue = "{$randomString}_{$index}";
            $newExpectedValues[] = $newValue;

            $I->fillField("#{$id}", $newValue);
        }
        $I->selectOption('select[id^=product_about_assetType]', 'Commercial');
        $newExpectedValues[] = 'Commercial';
        $newExpectedValues = array_combine($fields, $newExpectedValues);
        $I->click('Save Changes');
        $I->see('successfully updated');

        // Go back to the edit page and check the new values are now in each field
        $I->amOnPage("/admin/products/{$sampleAssetId}/editor/about");
        $newActualValues = $I->grabMultiple(
            'input[id^=product_about_]:not([type=hidden])',
            'value',
        );
        // Get the textarea after all other input text fields stored
        $newActualValues[] = $I->grabTextFrom('textarea[id^=product_about_]');
        // Add the non-text fields last
        $newActualValues[] = $I->grabValueFrom('select[id^=product_about_assetType]');
        $newActualValues = array_combine($fields, $newActualValues);
        // Do not use canonicalizing as we want the keys and ordering to be checked as well
        $I->assertEquals($newExpectedValues, $newActualValues);
        // Check the descriptions and names have propagated to similar fields
        $I->assertEquals($newExpectedValues['product_about_name'], $I->grabFromDatabase(
            'assets',
            'displayName',
            [
                'id' => $sampleAssetId,
            ],
        ));
        $I->assertEquals($newExpectedValues['product_about_briefDescription'], $I->grabFromDatabase(
            'assets',
            'detailedDesc',
            ['id' => $sampleAssetId],
        ));

        // Revert Changes
        foreach ($textfields as $id) {
            $I->fillField("#{$id}", $originalValues[$id]);
        }
        $I->selectOption(
            'select[id^=product_about_assetType]',
            $originalValues['product_about_assetType'],
        );
        $I->click('Save Changes');
    }

    /**
     * @group dashboard
     */
    public function checkFinancialsEditor(FunctionalTester $I): void
    {
        $sampleAssetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Sagittarius Eystar - Horizon',
        ]);

        $I->amOnPage("/admin/products/{$sampleAssetId}/editor/financials");
        $I->seeLink('Back', "/admin/products/{$sampleAssetId}");
        $I->seeLink('Discard Changes', "/admin/products/{$sampleAssetId}");
        $I->seeLink(
            'Open Share Price Suggester in New Tab',
            '/admin/utilities/asset-share-price',
        );

        // Store the original values so we can change back
        $fields = $I->grabMultiple(
            'input[id^=asset_financial_]:not([type=hidden])',
            'id',
        );
        $originalValues = $I->grabMultiple(
            'input[id^=asset_financial_]:not([type=hidden])',
            'value',
        );
        $originalValues = array_combine($fields, $originalValues);

        // Make edits to each field
        // Due to the net project yield being auto calculated, must edit that one manually
        $newExpectedValues = [];
        foreach ($originalValues as $id => $originalValue) {
            if (empty($originalValue)) {
                $originalValue = 0;
            }
            $newValue = $originalValue * 2;
            if ($id == 'asset_financial_netProjectedIncome') {
                // Double it again, as both share price and share amount have doubled
                // Resulting in a 4x increase in share value
                // So you need 4x the net income to have the same yield
                $newValue *= 2;
            }
            if (in_array($id, [
                'asset_financial_financialYearStart',
                'asset_financial_termStart',
            ])) {
                $newValue = date('Y-m-d');
            }
            // The rental yield should stay the same as it's proportional
            // if ($id == 'asset_financial_netRentProjected') {
            //     // Note that this field is ignored if netProjectedIncome is empty
            //     $newExpectedValues[$id] = $originalValue;
            // } else {
            //     $newExpectedValues[$id] = $newValue;
            // }
            $newExpectedValues[$id] = $newValue;
            $I->fillField("#{$id}", $newValue);
        }
        $I->click('Save Changes');
        $I->see('successfully updated');

        // Go back to the edit page and check the new values are now in each field
        $I->amOnPage("/admin/products/{$sampleAssetId}/editor/financials");
        $newActualValues = $I->grabMultiple(
            'input[id^=asset_financial_]:not([type=hidden])',
            'value',
        );
        $newActualValues = array_combine($fields, $newActualValues);
        // Do not use canonicalizing as we want the keys and ordering to be checked as well
        $I->assertEquals($newExpectedValues, $newActualValues);
        $newFundingGoal =
            4
            * $originalValues['asset_financial_pricePerShare']
            * $originalValues['asset_financial_amountOfShares'];
        // Make sure term, share price, amount and valuation have propagated to similar fields
        $I->assertEquals($newFundingGoal, $I->grabFromDatabase(
            'assets',
            'fundingGoal',
            ['id' => $sampleAssetId],
        ));

        $I->assertEquals($newExpectedValues['asset_financial_financialYearStart'], $I->grabFromDatabase(
            'assets',
            'financialYearStart',
            ['id' => $sampleAssetId],
        ));
        $I->assertEquals($newExpectedValues['asset_financial_termStart'], $I->grabFromDatabase(
            'assets',
            'termStart',
            [
                'id' => $sampleAssetId,
            ],
        ));
        $I->assertEquals($newExpectedValues['asset_financial_minimumInvestment'], $I->grabFromDatabase(
            'assets',
            'minimumInvestment',
            [
                'id' => $sampleAssetId,
            ],
        ));

        // Revert Changes
        foreach ($originalValues as $id => $value) {
            $I->fillField("#{$id}", $value);
        }
        $I->click('Save Changes');
    }

    /**
     * @group dashboard
     */
    public function checkLaunchCentre(FunctionalTester $I): void
    {
        /**
         * Note that the actual launching actions are tested in
         * tests/Controller/Admin/ProductEditorControllerTest.php
         * Since the database can reset in between Symfony tests but can't in codeception
         * Only test visual indicators in codeception
         */

        // Not launched and not ready
        $notReadyAssetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Sagittarius Eystar - Horizon',
        ]);

        $I->amOnPage("/admin/products/{$notReadyAssetId}/editor/launch");
        $I->seeLink('Back', "/admin/products/{$notReadyAssetId}");

        $I->seeElement('section#launch-readiness');
        $I->see('not ready for launch', 'section#launch-readiness');
        $I->see('Prefunding Mode', 'section#prefunding-mode');
        $I->see('Configure Launch to Prefunding', 'section#prefunding-mode a');
        $I->seeLink(
            'Configure Launch to Prefunding',
            "/admin/products/{$notReadyAssetId}/editor/launch-prefunding",
        );
        $I->see('Retail Mode', 'section#retail-mode');
        $I->see('Configure Launch to Retail', 'section#retail-mode a');
        $I->seeLink(
            'Configure Launch to Retail',
            "/admin/products/{$notReadyAssetId}/editor/launch-retail",
        );

        // Not launched but is ready
        // We'll be launching this to prefunding and subsequently retail
        $readyAssetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Silverhood Down - Brighton',
        ]);
        $I->amOnPage("/admin/products/{$readyAssetId}/editor/launch");
        $I->dontSeeElement('section#launch-readiness');
        $I->dontSee('not ready for launch', 'section#launch-readiness');
        $I->see('Prefunding Mode', 'section#prefunding-mode');
        $I->see('Configure Launch to Prefunding', 'section#prefunding-mode a');
        $I->seeLink(
            'Configure Launch to Prefunding',
            "/admin/products/{$readyAssetId}/editor/launch-prefunding",
        );
        $I->see('Retail Mode', 'section#retail-mode');
        $I->see('Configure Launch to Retail', 'section#retail-mode a');
        $I->seeLink(
            'Configure Launch to Retail',
            "/admin/products/{$readyAssetId}/editor/launch-retail",
        );

        $I->click('Configure Launch to Prefunding');
        $I->seeCurrentUrlEquals(
            "/admin/products/{$readyAssetId}/editor/launch-prefunding",
        );
        $expectedSharePrice = $I->grabFromDatabase('assets', 'pricePerShare', [
            'id' => $readyAssetId,
        ]);
        $expectedShares = $I->grabFromDatabase('assets', 'amountOfShares', [
            'id' => $readyAssetId,
        ]);
        $expectedMax = $I->grabFromDatabase('assets', 'fundingGoal', [
            'id' => $readyAssetId,
        ]);
        $expectedMin = '25000';
        $expectedMinShares = ceil($expectedMin / $expectedSharePrice);
        $assetMinimumInvestment = $I->grabFromDatabase('assets', 'minimumInvestment', [
            'id' => $readyAssetId,
        ]);
        $expectedMinSharesRetail = ceil($assetMinimumInvestment / $expectedSharePrice);
        $I->seeInField('#product_launch_pricePerShare', number_format(
            $expectedSharePrice,
            2,
            '.',
            '',
        ));
        $I->seeInField('#product_launch_numberOfShares', $expectedShares);
        $I->seeInField('#product_launch_minimumInvestment', number_format(
            $expectedMin,
            2,
            '.',
            '',
        ));
        $I->seeInField('#product_launch_maximumInvestment', number_format(
            $expectedMax,
            2,
            '.',
            '',
        ));

        // Launch to prefunding
        $I->click('Launch to Prefunding');
        $prefundingSellOrderId = $I->grabTextFrom(
            '#trade-orders-list tbody tr:first-child [data-field="id"] a',
        );
        $I->assertStringContainsString(
            number_format($expectedSharePrice, 2),
            $I->grabTextFrom(
                '#trade-orders-list tbody tr:first-child [data-field="price"]',
            ),
        );
        $I->assertStringContainsString(
            number_format($expectedShares, 0),
            $I->grabTextFrom(
                '#trade-orders-list tbody tr:first-child [data-field="quantity-listed"]',
            ),
        );
        $I->assertStringContainsString(
            number_format($expectedMinShares, 0),
            $I->grabTextFrom(
                '#trade-orders-list tbody tr:first-child [data-field="min-shares"]',
            ),
        );
        $I->assertStringContainsString(
            number_format($expectedShares, 0),
            $I->grabTextFrom(
                '#trade-orders-list tbody tr:first-child [data-field="max-shares"]',
            ),
        );
        $I->assertEquals(
            ucfirst(TradeOrderStatus::Active->value),
            $I->grabTextFrom(
                '#trade-orders-list tbody tr:first-child [data-field="status"]',
            ),
        );
        $I->assertEquals(
            ucfirst(TradeOrderType::Initial->value),
            $I->grabTextFrom(
                '#trade-orders-list tbody tr:first-child [data-field="type"]',
            ),
        );
        $I->assertEquals(
            TradeDirection::Sell->name,
            $I->grabTextFrom(
                '#trade-orders-list tbody tr:first-child [data-field="direction"]',
            ),
        );

        // Then try to launch to retail
        $I->amOnPage("/admin/products/{$readyAssetId}/editor/launch");
        $I->click('Configure Launch to Retail');
        $I->seeInField('#product_launch_pricePerShare', number_format(
            $expectedSharePrice,
            2,
            '.',
            '',
        ));
        $I->seeInField('#product_launch_numberOfShares', $expectedShares);

        $I->seeInField('#product_launch_minimumInvestment', number_format(
            $assetMinimumInvestment,
            2,
            '.',
            '',
        ));
        $I->seeInField('#product_launch_maximumInvestment', number_format(
            $expectedMax,
            2,
            '.',
            '',
        ));
        $I->click('Launch to Retail');
        $retailSellOrderId = $I->grabTextFrom(
            '#trade-orders-list tbody tr:first-child [data-field="id"] a',
        );
        $I->assertStringContainsString(
            number_format($expectedSharePrice, 2),
            $I->grabTextFrom(
                '#trade-orders-list tbody tr:first-child [data-field="price"]',
            ),
        );
        $I->assertStringContainsString(
            number_format($expectedShares, 0),
            $I->grabTextFrom(
                '#trade-orders-list tbody tr:first-child [data-field="quantity-listed"]',
            ),
        );
        $I->assertStringContainsString(
            number_format($expectedMinSharesRetail, 0),
            $I->grabTextFrom(
                '#trade-orders-list tbody tr:first-child [data-field="min-shares"]',
            ),
        );
        $I->assertStringContainsString(
            number_format($expectedShares, 0),
            $I->grabTextFrom(
                '#trade-orders-list tbody tr:first-child [data-field="max-shares"]',
            ),
        );
        $I->assertEquals(
            ucfirst(TradeOrderStatus::Active->value),
            $I->grabTextFrom(
                '#trade-orders-list tbody tr:first-child [data-field="status"]',
            ),
        );
        $I->assertEquals(
            ucfirst(TradeOrderType::Initial->value),
            $I->grabTextFrom(
                '#trade-orders-list tbody tr:first-child [data-field="type"]',
            ),
        );
        $I->assertEquals(
            TradeDirection::Sell->name,
            $I->grabTextFrom(
                '#trade-orders-list tbody tr:first-child [data-field="direction"]',
            ),
        );

        // Try to reset the product to prelaunch state to allow re-runs
        $I->updateInDatabase(
            'asset_status_log',
            ['status' => AssetStatus::Draft->value],
            ['asset_id' => $readyAssetId],
        );
        $I->updateInDatabase(
            'trade_order_status_log',
            ['status' => TradeOrderStatus::Cancelled->value],
            ['tradeOrder_id' => $prefundingSellOrderId],
        );
        $I->updateInDatabase(
            'trade_order_status_log',
            ['status' => TradeOrderStatus::Cancelled->value],
            ['tradeOrder_id' => $retailSellOrderId],
        );

        // // Already launched
        // $launchedAssetId = $I->grabFromDatabase('assets', 'id', [
        //     'name' => 'Clarence Hold A - Camden',
        // ]);
        // $sharePrice = $I->grabFromDatabase('assets', 'pricePerShare', [
        //     'id' => $launchedAssetId,
        // ]);
        // $expectedRetailMinCommit = round(ceil(100 / $sharePrice) * $sharePrice, 2);
        // Change the minCommit to something much higher than normal
        // $I->updateInDatabase(
        //     'offerings',
        //     ['minCommitUser' => '928.25'],
        //     ['id' => $launchedOfferingId],
        // );
        // Hide the product by changing both asset and offering to admin only visibility
        // $I->updateInDatabase(
        //     'assets',
        //     ['visibility' => BaseEntity::VISIBILITY_ADMIN],
        //     ['id' => $launchedAssetId],
        // );
        // // Make the product prefunding mode
        // $I->amOnPage("/admin/products/{$launchedAssetId}");
        // $I->see('928.25', '[data-field-name="minimum-single-commitment"]');
        // $I->see('Admin Only', '[data-field-name="visible-to"]');
        // $I->see('Prefunding', '[data-field-name="mode"]');
        // $I->amOnPage("/admin/products/{$launchedAssetId}/editor/launch");
        // $I->dontSeeElement('section#launch-readiness');
        // $I->dontSee('not ready for launch', 'section#launch-readiness');
        // $I->dontSee('Prefunding Mode', 'section#prefunding-mode');
        // $I->dontSee('Configure Launch to Prefunding', 'section#prefunding-mode a');
        // $I->dontSeeLink(
        //     'Configure Configure Launch to Prefunding',
        //     "/admin/products/{$launchedAssetId}/editor/launch-prefunding",
        // );
        // $I->dontSee('Retail Mode', 'section#retail-mode');
        // $I->dontSee('Configure Launch to Retail', 'section#retail-mode a');
        // $I->dontSeeLink(
        //     'Configure Launch to Retail',
        //     "/admin/products/{$launchedAssetId}/editor/launch-retail",
        // );
    }

    /**
     * @group dashboard
     */
    public function checkStatusEditor(FunctionalTester $I): void
    {
        // Not launched
        $notLaunchedAssetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Sagittarius Eystar - Horizon',
        ]);

        $I->amOnPage("/admin/products/{$notLaunchedAssetId}/editor/status");
        $I->seeLink('Back', "/admin/products/{$notLaunchedAssetId}");

        // Should see the launch warning
        $I->seeElement('section#restricted-editing');
        $I->see('Product Not Launched Yet', 'section#restricted-editing h3');
        $I->see('Go to Launch Centre', 'section#restricted-editing a');
        $I->seeLink(
            'Go to Launch Centre',
            "/admin/products/{$notLaunchedAssetId}/editor/launch",
        );

        // Already launched
        $launchedAssetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Clarence Hold A - Camden',
        ]);
        $I->amOnPage("/admin/products/{$launchedAssetId}/editor/status");
        // Should not see the launch warning
        $I->dontSeeElement('section#restricted-editing');
        $I->dontSee('Product Not Launched Yet', 'section#restricted-editing h3');
        $I->dontSee('Go to Launch Centre', 'section#restricted-editing a');
        $I->dontSeeLink(
            'Go to Launch Centre',
            "/admin/products/{$launchedAssetId}/editor/launch",
        );

        // Check buy market toggle
        $I->see('Buying Market', 'section#buying-controls');
        foreach (range(1, 2) as $iteration) {
            $current = $I->grabTextFrom('#buying-controls [data-field-name=currently]');
            if ('Closed' == $current) {
                $I->click('Open Buying');
                $current = $I->grabTextFrom(
                    '#buying-controls [data-field-name=currently]',
                );
                $I->assertEquals('Open', $current);
            } else {
                $I->click('Close Buying');
                $current = $I->grabTextFrom(
                    '#buying-controls [data-field-name=currently]',
                );
                $I->assertEquals('Closed', $current);
            }
        }

        // Check secondary market toggle
        $I->see('Selling Market', 'section#selling-controls');
        foreach (range(1, 2) as $iteration) {
            $current = $I->grabTextFrom(
                '#selling-controls [data-field-name=currently]',
            );
            if ('Closed' == $current) {
                $I->click('Open Selling');
                $current = $I->grabTextFrom(
                    '#selling-controls [data-field-name=currently]',
                );
                $I->assertEquals('Open', $current);
            } else {
                $I->click('Close Selling');
                $current = $I->grabTextFrom(
                    '#selling-controls [data-field-name=currently]',
                );
                $I->assertEquals('Closed', $current);
            }
        }

        // Check feature toggle
        $I->see('Feature Product', 'section#feature-product');
        $featuredActions = ["Don't Feature", 'Feature'];
        $current = $I->grabTextFrom('#feature-product [data-field-name=currently]');
        if ('Not Featured' == $current) {
            // If currently, not featured, start toggling to "Featured" first
            $featuredActions = array_reverse($featuredActions);
        }
        foreach ($featuredActions as $action) {
            if ('Featured' == $current) {
                $I->click("{$action} Product");
                $current = $I->grabTextFrom(
                    '#feature-product [data-field-name=currently]',
                );
                $I->assertEquals('Not Featured', $current);
            } else {
                $I->click("{$action} Product");
                $current = $I->grabTextFrom(
                    '#feature-product [data-field-name=currently]',
                );
                $I->assertEquals('Featured', $current);
            }
        }

        // Check visibility toggle
        $I->see('Visibility', 'section#visibility');
        $visibilityActions = ['Hide', 'Show'];
        foreach ($visibilityActions as $action) {
            $current = $I->grabTextFrom(
                '#visibility [data-field-name=currently-visibile-to]',
            );
            if ('Everyone' == $current) {
                $I->click("{$action} Product");
                $current = $I->grabTextFrom(
                    '#visibility [data-field-name=currently-visibile-to]',
                );
                $I->assertEquals('Admin Only', $current);
            } else {
                $I->click("{$action} Product");
                $current = $I->grabTextFrom(
                    '#visibility [data-field-name=currently-visibile-to]',
                );
                $I->assertEquals('Everyone', $current);
            }
        }

        // Check the form
        $I->amOnPage("/admin/products/{$notLaunchedAssetId}/editor/status");
        $I->checkOption('#asset_trading_control_buyRestricted');
        $I->checkOption('#asset_trading_control_sellRestricted');
        $I->fillField('#asset_trading_control_featured', '25');
        $I->selectOption('#asset_trading_control_visibility', '1');
        $I->click('Save Changes');
        $I->seeCurrentUrlEquals("/admin/products/{$notLaunchedAssetId}");
        $I->see('Closed', '#trading-status-info [data-field-name="buying"]');
        $I->see('Closed', '#trading-status-info [data-field-name="selling"]');
        $I->see('Admin Only', '#trading-status-info [data-field-name="visible-to"]');
        $I->see(
            'Yes (25)',
            '#trading-status-info [data-field-name="featured-(weighting)"]',
        );
    }

    /**
     * @group dashboard
     */
    public function checkDocumentsEditor(FunctionalTester $I): void
    {
        $sampleAssetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);

        $I->amOnPage("/admin/products/{$sampleAssetId}/editor/documents");
        $I->seeLink('Back', "/admin/products/{$sampleAssetId}");

        // Outside of setup mode, no additional button to return to dashboard
        $I->dontSeeLink('Finish Setup');

        // Check the logo section
        $I->see('Logo', 'section#logo h3');
        $I->seeLink(
            'Add Logo',
            "/admin/products/{$sampleAssetId}/editor/documents/create?type=logo",
        );

        // Check the aoa section
        $I->see('Articles of Association', 'section#articles-of-association h3');
        $I->seeLink(
            'Add Articles',
            "/admin/products/{$sampleAssetId}/editor/documents/create?type=articles_of_association",
        );

        // Check the im section
        $I->see('Information Memo', 'section#information-memorandum h3');
        $I->seeLink(
            'Add Memo',
            "/admin/products/{$sampleAssetId}/editor/documents/create?type=information_memorandum",
        );

        // Check the fs section
        $I->see('Financial Summary', 'section#financial-summary h3');
        $I->seeLink(
            'Add Financial Summary',
            "/admin/products/{$sampleAssetId}/editor/documents/create?type=financial_summary",
        );

        // Check the property photos section
        $I->see('Property Photos', 'section#property-photos h3');
        $I->seeLink(
            'Add Property Photo',
            "/admin/products/{$sampleAssetId}/editor/documents/create?type=property_photos",
        );

        // Check redirect if attempting to go to create document editor without a permitted type
        $I->amOnPage("/admin/products/{$sampleAssetId}/editor/documents/create");
        $I->seeCurrentUrlEquals("/admin/products/{$sampleAssetId}/editor/documents");
        $I->see('Unknown document type', '.alert');

        // Check the create document editor - check each of the 5 preset the right doc
        $I->amOnPage(
            "/admin/products/{$sampleAssetId}/editor/documents/create?type=logo",
        );
        $I->seeOptionIsSelected('#product_document_create_type', 'Logo');
        $I->amOnPage(
            "/admin/products/{$sampleAssetId}/editor/documents/create?type=articles_of_association",
        );
        $I->seeOptionIsSelected(
            '#product_document_create_type',
            'Articles Of Association',
        );
        $I->amOnPage(
            "/admin/products/{$sampleAssetId}/editor/documents/create?type=information_memorandum",
        );
        $I->seeOptionIsSelected(
            '#product_document_create_type',
            'Information Memorandum',
        );
        $I->amOnPage(
            "/admin/products/{$sampleAssetId}/editor/documents/create?type=financial_summary",
        );
        $I->seeOptionIsSelected('#product_document_create_type', 'Financial Summary');
        $I->amOnPage(
            "/admin/products/{$sampleAssetId}/editor/documents/create?type=property_photos",
        );
        $I->seeOptionIsSelected('#product_document_create_type', 'Property Photos');
        $I->see('Add Document');
        $I->seeLink('Cancel', "/admin/products/{$sampleAssetId}/editor/documents");
        // Check the back button will take you back to the docs editor, not the dashboard
        $I->seeLink('Back', "/admin/products/{$sampleAssetId}/editor/documents");

        // If in setup mode, this will retain the setup mode toggle
        $I->amOnPage(
            "/admin/products/{$sampleAssetId}/editor/documents/create?type=property_photos&setup=1",
        );
        $I->seeLink(
            'Back',
            "/admin/products/{$sampleAssetId}/editor/documents?setup=1",
        );

        // Try to upload a file and ensure it is saved to the database
        // Sanity check that the tags are configured, but that is largely being testing in unit tests
        $currentTime = new \DateTime();
        $beforePhotoCount = $I->grabNumRecords('documents', [
            'tag' => 'property_photos',
        ]);
        // $I->attachFile('#product_document_create_document_file', 'uploads/public/fixtures/Test_PDF.pdf');
        $I->attachFile(
            '#product_document_create_document_file',
            'uploads/public/fixtures/smallbedroomstock.jpg',
        );
        $I->click('Add Document');
        $I->see('document successfully created', '.alert');
        // setup mode will return to editor in setup mode
        $I->seeCurrentUrlEquals(
            "/admin/products/{$sampleAssetId}/editor/documents?setup=1",
        );

        // Whereas normal editor won't
        $I->amOnPage(
            "/admin/products/{$sampleAssetId}/editor/documents/create?type=property_photos",
        );
        $I->attachFile(
            '#product_document_create_document_file',
            'uploads/public/fixtures/smallbedroomstock.jpg',
        );
        $I->click('Add Document');
        $I->seeCurrentUrlEquals("/admin/products/{$sampleAssetId}/editor/documents");

        // New database entry created
        $afterPhotoCount = $I->grabNumRecords('documents', [
            'tag' => 'property_photos',
        ]);
        $I->assertSame(2, $afterPhotoCount - $beforePhotoCount);

        // Try to locate the new doc (either one) in the database to get the id
        $newDocumentId = $I->grabFromDatabase('documents', 'id', [
            'tag' => 'property_photos',
            'createdAt >=' => $currentTime->format(\DateTimeInterface::ATOM),
        ]);

        // TEST DEBUG - deliberately corrupt the document url to result in a 404 error to check fail case
        // $I->updateInDatabase(
        //     'documents',
        //     ['documentUrl' => "asset/{$sampleAssetId}/differentprefix_smallbedroomstock.jpg"],
        //     ['id' => $newDocumentId]
        // );

        // Check that the file is downloadable without issue therefore successfully uploaded and saved
        // If it didn't upload correctly, you'll get an error response (e.g. 404 or 500) rather than OK
        $I->amOnPage("/admin/document/{$newDocumentId}/download?type=public");
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);

        // Check that the delete link works
        $I->amOnPage("/admin/products/{$sampleAssetId}/documents");
        $newAssetDocumentId = $I->grabFromDatabase('asset_docs', 'id', [
            'asset_id' => $sampleAssetId,
            'document_id' => $newDocumentId,
        ]);
        $I->seeLink(
            'Delete',
            "/admin/assetdocument/{$newAssetDocumentId}/delete?redirectRoute=admin_product_documents",
        );
        // Collect the before state - document id in list and number of docs
        $I->see($newAssetDocumentId, '#asset-documents table tbody tr td:nth-child(2)');
        $assetDocsCountBefore = count($I->grabMultiple(
            '#asset-documents table tbody tr',
        ));
        $I->amOnPage(
            "/admin/assetdocument/{$newAssetDocumentId}/delete?redirectRoute=admin_product_documents",
        );
        $I->seeCurrentUrlEquals("/admin/products/{$sampleAssetId}/documents");
        $I->dontSee(
            $newAssetDocumentId,
            '#asset-documents table tbody tr td:nth-child(2)',
        );
        $I->assertEquals(
            $assetDocsCountBefore - 1,
            count($I->grabMultiple('#asset-documents table tbody tr')),
        );
    }
}
