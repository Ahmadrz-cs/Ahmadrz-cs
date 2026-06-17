<?php

namespace App\Tests\Functional\Ops\MonthEnd\Settlement;

use App\Entity\Enum\TradeStatus;
use App\Entity\Lifecycle\InvestmentLifecycle;
use App\Service\MonthEndService;
use App\Tests\Support\FunctionalTester;

class SettlementOrderManageCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    /**
     * @group dashboard
     */
    public function checkSections(FunctionalTester $I): void
    {
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);
        $newOrderId = $I->createSettlementOrder('Royal Eversea Glades - Cambridge');
        $I->amOnPage("/admin/monthend/settlements/{$newOrderId}");

        // Should see a prompt to configure transfers
        $I->seeLink(
            'Configure Transfers',
            "/admin/monthend/settlements/{$newOrderId}#transfers-list",
        );

        // Check sections and titles present
        $sections = [
            'about-transfer' => 'About Transfer Order',
            'order-status' => 'Order Status',
            'about-asset' => 'About Asset',
            'settlement-summary' => 'Settlement Summary',
            'transfers' => 'Transfers',
        ];
        // check section headers
        foreach ($sections as $sectionId => $sectionTitle) {
            $I->see($sectionTitle, "section#{$sectionId} h3");
        }

        // Check transfer order about section
        $I->seeElement('section#about-transfer [data-field-name="scheduled-monthend"]');
        $I->seeElement(
            'section#about-transfer [data-field-name="transferring-out-of-wallet"]',
        );
        $I->seeElement('section#about-transfer [data-field-name="description"]');

        // Check order status section
        $I->seeElement('section#order-status [data-field-name="status"]');
        $I->seeElement('section#order-status [data-field-name="approved-by"]');

        // Check about asset section
        $I->seeElement('section#about-asset [data-field-name="spv-company-number"]');
        $I->seeElement('section#about-asset [data-field-name="asset-valuation"]');
        $I->see('View Asset Product', 'section#about-asset a');
        $I->see('View Wallet Balances', 'section#about-asset a');
        $I->seeLink('View Asset Product', "/admin/products/{$assetId}");
        $I->seeLink('View Wallet Balances', "/admin/asset/{$assetId}/manage-wallets");

        // Check summary section
        $I->seeElement(
            'section#settlement-summary [data-field-name="share-trades-being-settled"]',
        );
        $I->seeElement(
            'section#settlement-summary [data-field-name="shares-being-settled"]',
        );
        $I->seeElement(
            'section#settlement-summary [data-field-name="settlement-transfers"]',
        );
        $I->seeElement(
            'section#settlement-summary [data-field-name="value-of-settlement"]',
        );
        $I->seeElement(
            'section#settlement-summary [data-field-name="stamp-duty-transfers"]',
        );
        $I->seeElement(
            'section#settlement-summary [data-field-name="total-stamp-duty-to-transfer"]',
        );

        // Check transfers section
        $headers = [
            'Actions',
            'Trade Id',
            'Mode',
            'Amount',
            'Proportion',
            'Status',
            'Transfer From',
            'Transfer To',
            'Sell Order',
            'Buy Order',
            'Seller',
            'Buyer',
            'Description',
            'Updated',
            'Transaction Reference',
        ];
        foreach ($headers as $header) {
            $I->see($header, 'section#transfers table thead');
        }
        $I->seeLink(
            'Export Transfers List',
            "/admin/transfer-orders/{$newOrderId}/export",
        );
    }

    public function testNonSettlementRedirect(FunctionalTester $I): void
    {
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);
        $I->amOnPage('/admin/transfer-orders/create');
        $I->fillField('#transfer_order_description', 'Non settlement order test');
        $I->click('Create Transfer Order');
        $newOrderId = $I->grabTextFrom('[data-field-name="order-id"]');
        $I->seeCurrentUrlEquals("/admin/transfer-orders/{$newOrderId}/manage");

        // Most of the settlement routes will send you back to the regular transfer order editor
        $protectedPaths = [
            "/admin/monthend/settlements/{$newOrderId}",
            // "/admin/monthend/settlements/{$newOrderId}/add-transfer",
            // "/admin/monthend/settlements/edit-transfer/{$newTransferRequestId}",
        ];
        foreach ($protectedPaths as $path) {
            $I->amOnPage($path);
            $I->see('not a settlement order');
            $I->seeCurrentUrlEquals("/admin/transfer-orders/{$newOrderId}/manage");
        }
    }

    public function checkWarnings(FunctionalTester $I): void
    {
        $newOrderId = $I->createSettlementOrder('Kolness by the Moor - Okehampton');
        $I->amOnPage("/admin/monthend/settlements/{$newOrderId}");

        // No warnings on fresh settlement order
        $I->dontSeeElement('section#wallet-balance-warning');

        // Generate on Kolness (should have a handful with stamp duty)
        $I->amOnPage("/admin/monthend/settlements/{$newOrderId}/generate");
        $nextMonth = new \DateTime('+1 month');
        $I->fillField(
            '#settlement_search_generate_createdAt_lt',
            $nextMonth->format('Y-m-01'),
        );
        $I->click('Generate All Settlement Transfers');

        // If the total to transfer is too large - we'll us £10bn+ as an example
        // Can only edit by going to regular transfer orders
        $I->amOnPage("/admin/monthend/settlements/{$newOrderId}");
        $firstTransferId = $I->grabAttributeFrom(
            '#transfers-list tbody tr:first-child',
            'data-object-id',
        );
        $I->amOnPage("/admin/transfer-requests/{$firstTransferId}/edit");
        $I->fillField('#transfer_request_amount', '10111000111');
        $I->click('Save Changes');
        $I->amOnPage("/admin/monthend/settlements/{$newOrderId}");
        $I->seeElement('section#wallet-balance-warning');
        $I->seeElement(
            'section#wallet-balance-warning [data-field-name="total-remaining"]',
        );
        $I->seeElement(
            'section#wallet-balance-warning [data-field-name="debit-wallet-balance"]',
        );
        $I->seeElement(
            'section#wallet-balance-warning [data-field-name="additional-funds-required"]',
        );
    }

    public function testEditMonthend(FunctionalTester $I): void
    {
        $newOrderId = $I->createSettlementOrder('Royal Eversea Glades - Cambridge');
        $I->amOnPage("/admin/monthend/settlements/{$newOrderId}");
        $I->see('Edit Order', '#about-transfer a');
        $I->click('Edit Order');

        $I->seeCurrentUrlEquals("/admin/monthend/settlements/{$newOrderId}/edit");
        $I->seeLink('Back', "/admin/monthend/settlements/{$newOrderId}");
        $I->seeLink('Discard Changes', "/admin/monthend/settlements/{$newOrderId}");

        $newDate = new \DateTime('2020-04-08');
        $newDescription = 'Edit monthend settlement order description test';
        $I->fillField('#monthend_order_edit_scheduledFor', $newDate->format('Y-m-d'));
        $I->fillField('#monthend_order_edit_description', $newDescription);
        $I->click('Save Changes');

        $I->seeCurrentUrlEquals("/admin/monthend/settlements/{$newOrderId}");
        $I->assertEquals(
            $newDate->format('Y-m'),
            $I->grabTextFrom('[data-field-name="scheduled-monthend"]'),
        );
        $I->assertEquals(
            $newDescription,
            $I->grabTextFrom('[data-field-name="description"]'),
        );
    }

    public function testTransitionRedirect(FunctionalTester $I): void
    {
        $newOrderId = $I->createSettlementOrder('Kolness by the Moor - Okehampton');
        // Need at least 2 transfers to be able to abandon later
        // $I->amOnPage("/admin/monthend/settlements/{$newOrderId}/edit");
        // $nextMonth = new \DateTime('+1 month');
        // $I->fillField('#monthend_order_edit_scheduledFor', $nextMonth->format('Y-m-01'));
        // $I->click('Save Changes');
        $I->amOnPage("/admin/monthend/settlements/{$newOrderId}/generate");
        $nextMonth = new \DateTime('+1 month');
        $I->fillField(
            '#settlement_search_generate_createdAt_lt',
            $nextMonth->format('Y-m-01'),
        );
        $I->click('Search Share Trades');
        $shareTradeId = $I->grabTextFrom(
            "#share-trades-list tbody tr:first-child [data-field='id'] a",
        );
        $I->click('Generate All Settlement Transfers');
        // Lower the value of the settlements to reduce need to rebalance wallets
        $I->amOnPage("/admin/monthend/settlements/{$newOrderId}");
        $transfersCount = count($I->grabMultiple('#transfers-list tbody tr'));
        $I->click('Edit', '#transfers-list tbody tr:nth-child(1)');
        $I->fillField('#asset_transfer_request_amount', '0.01');
        $description = $I->grabValueFrom('#asset_transfer_request_description');
        $I->assertStringContainsString(
            MonthEndService::DESCRIPTION_PRESETS['settlement'],
            $description,
        );
        $I->click('Save Changes');

        $I->amOnPage("/admin/monthend/settlements/{$newOrderId}");
        $I->assertEquals('Draft', $I->grabTextFrom('[data-field-name="status"]'));

        // Reject
        $I->click('Reject Transfer Order');
        $I->seeCurrentUrlEquals(
            "/admin/transfer-orders/{$newOrderId}/reject?redirectRoute=admin_monthend_settlement_manage",
        );
        $I->seeLink('Discard Changes', "/admin/monthend/settlements/{$newOrderId}");
        $I->click('Reject Transfer Order');
        $I->seeCurrentUrlEquals("/admin/monthend/settlements/{$newOrderId}");
        $I->assertEquals('Closed', $I->grabTextFrom('[data-field-name="status"]'));

        // Reopen
        $I->click('Reopen Transfer Order');
        $I->seeCurrentUrlEquals("/admin/monthend/settlements/{$newOrderId}");
        $I->assertEquals('Draft', $I->grabTextFrom('[data-field-name="status"]'));

        // Approve
        $I->click('Approve Transfer Order');
        $I->seeCurrentUrlEquals("/admin/monthend/settlements/{$newOrderId}");
        $I->assertEquals('Approved', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals(
            $I::USER_SUPER_ADMIN,
            $I->grabTextFrom('[data-field-name="approved-by"]'),
        );

        // Unapprove
        $I->click('Unapprove Transfer Order');
        $I->seeCurrentUrlEquals("/admin/monthend/settlements/{$newOrderId}");
        $I->assertEquals('Draft', $I->grabTextFrom('[data-field-name="status"]'));

        // Abandon
        if ($transfersCount > 1) {
            $I->click('Approve Transfer Order');
            $I->click('Transfer Single');
            $I->seeCurrentUrlEquals("/admin/monthend/settlements/{$newOrderId}");
            $I->assertEquals(
                'In-progress',
                $I->grabTextFrom('[data-field-name="status"]'),
            );
            $I->click('Abandon Transfer Order');
            $I->seeCurrentUrlEquals(
                "/admin/transfer-orders/{$newOrderId}/abandon?redirectRoute=admin_monthend_settlement_manage",
            );
            $I->seeLink('Discard Changes', "/admin/monthend/settlements/{$newOrderId}");
            $I->click('Abandon Transfer Order');
            $I->seeCurrentUrlEquals("/admin/monthend/settlements/{$newOrderId}");
            $I->assertEquals(
                'Abandoned',
                $I->grabTextFrom('[data-field-name="status"]'),
            );

            // Change the share-trade back to approved to allow reruns
            // Note that this will set ALL status logs for that share trade back to unsettled
            $I->updateInDatabase(
                'share_trade_status_log',
                ['status' => TradeStatus::Unsettled->value],
                ['shareTrade_id' => $shareTradeId],
            );
        }
    }

    public function testEditTransfers(FunctionalTester $I): void
    {
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Kolness by the Moor - Okehampton',
        ]);
        $newOrderId = $I->createSettlementOrder('Kolness by the Moor - Okehampton');
        // Generate on Kolness (should have a handful with stamp duty)
        // Change the monthend so it can match fixtures
        $I->amOnPage("/admin/monthend/settlements/{$newOrderId}/generate");
        $nextMonth = new \DateTime('+1 month');
        $I->fillField(
            '#settlement_search_generate_createdAt_lt',
            $nextMonth->format('Y-m-01'),
        );
        $I->click('Search Share Trades');
        $I->click('Generate for Trade', '#share-trades-list tbody tr:first-child');
        $transferRequestId = $I->grabAttributeFrom(
            '#transfers-list tbody tr:first-child',
            'data-object-id',
        );

        // Check action links
        $I->seeLink(
            'Edit',
            "/admin/monthend/settlements/edit-transfer/{$transferRequestId}",
        );
        $I->seeLink(
            'Delete',
            "/admin/transfer-requests/{$transferRequestId}/delete?redirectRoute=admin_monthend_settlement_manage",
        );
        $I->click('Edit', '#transfers-list tbody tr:first-child');
        $I->seeCurrentUrlEquals(
            "/admin/monthend/settlements/edit-transfer/{$transferRequestId}",
        );
        $I->seeLink(
            'View Asset Wallet Balances (opens in new tab)',
            "/admin/asset/{$assetId}/manage-wallets",
        );
        $I->seeLink('Discard Changes', "/admin/monthend/settlements/{$newOrderId}");
        $I->seeLink('Back', "/admin/monthend/settlements/{$newOrderId}");

        // Check the datalist for description exists
        $I->seeElement('datalist#description-presets');
        $I->seeNumberOfElements('datalist#description-presets option', 2);
        $I->seeElement('#asset_transfer_request_description[list=description-presets]');

        // Modify the generated transfer amount
        $I->fillField('#asset_transfer_request_amount', '0.04');
        $I->fillField(
            '#asset_transfer_request_description',
            'Settlement transfer request test',
        );
        $I->click('Save Changes');

        // Check transfers summary
        $startTableRows = count($I->grabMultiple('#transfers-list tbody tr'));
        // $I->assertEquals('0.00/0.04', $I->grabTextFrom('[data-field-name="total-transferred-(£)"]'));
        $I->assertEquals(
            "0/{$startTableRows}",
            $I->grabTextFrom('[data-field-name="transfers-completed"]'),
        );

        // Check transfers table
        // $I->seeNumberOfElements('#transfers-list tbody tr', 1);
        $transferRequestId = $I->grabAttributeFrom(
            '#transfers-list tbody tr:first-child',
            'data-object-id',
        );
        $I->assertEquals(
            '0.04',
            $I->grabTextFrom(
                '#transfers-list tbody tr:first-child [data-field="amount"]',
            ),
        );
        $I->assertEquals(
            'Settlement transfer request test',
            $I->grabTextFrom(
                '#transfers-list tbody tr:first-child [data-field="description"]',
            ),
        );

        // Attempt to edit one of the transfers after approval
        $I->click('Approve Transfer Order');
        $I->click('Edit', '#transfers-list tbody tr:first-child');
        $I->see('Editing is restricted outside draft mode');
        $I->seeElement('#asset_transfer_request_debitWalletId', [
            'disabled' => 'disabled',
        ]);
        $I->seeElement('#asset_transfer_request_creditWalletId', [
            'disabled' => 'disabled',
        ]);
        $I->seeElement('#asset_transfer_request_description');
        $I->dontSeeElement('#asset_transfer_request_description', [
            'disabled' => 'disabled',
        ]);
        $I->seeElement('#asset_transfer_request_amount', ['disabled' => 'disabled']);
        $randomString = bin2hex(random_bytes(8));
        $I->fillField('#asset_transfer_request_description', $randomString);
        $I->click('Save Changes');
        $I->see(
            $randomString,
            '#transfers-list tbody tr:first-child td[data-field="description"]',
        );
        // Unapprove to allow deleting
        $I->click('Unapprove Transfer Order');

        // Delete the transfer
        $I->click('Delete', '#transfers-list tbody tr:first-child');
        $endTableRows = count($I->grabMultiple('#transfers-list tbody tr'));
        $I->seeCurrentUrlEquals("/admin/monthend/settlements/{$newOrderId}");
        $I->seeNumberOfElements('#transfers-list tbody tr', $endTableRows);
        $I->assertEquals(
            "0/{$endTableRows}",
            $I->grabTextFrom('[data-field-name="transfers-completed"]'),
        );

        // Clear all remaining transfers
        $I->click('Clear All Transfers');
        $I->seeCurrentUrlEquals(
            "/admin/transfer-orders/{$newOrderId}/clear-transfers?redirectRoute=admin_monthend_settlement_manage",
        );
        $I->see('Confirm Deletion');
        $I->seeLink('Discard Changes', "/admin/monthend/settlements/{$newOrderId}");
        $I->click('Clear All Transfers');
        $I->seeCurrentUrlEquals("/admin/monthend/settlements/{$newOrderId}");
        $I->seeNumberOfElements('#transfers-list tbody tr', 0);
    }

    public function testSettlementGeneration(FunctionalTester $I): void
    {
        $newOrderId = $I->createSettlementOrder('Kolness by the Moor - Okehampton');
        // Generate on Kolness (should have a handful with stamp duty)
        $I->amOnPage("/admin/monthend/settlements/{$newOrderId}");
        $I->click('Setup Settlement Transfers');
        $I->seeCurrentUrlEquals("/admin/monthend/settlements/{$newOrderId}/generate");

        // Check search section - prefilled default search range based on the scheduledFor date
        $defaultMonthStart = new \DateTimeImmutable($I->grabTextFrom(
            '[data-field-name="default-search-month"]',
        ));
        $I->seeInField(
            '#settlement_search_generate_createdAt_gte',
            $defaultMonthStart->format('Y-m-01'),
        );
        $I->seeInField(
            '#settlement_search_generate_createdAt_lt',
            $defaultMonthStart->modify('+1 month')->format('Y-m-01'),
        );
        // Expand search range for fixtures
        $nextMonth = new \DateTime('+1 month');
        $I->fillField(
            '#settlement_search_generate_createdAt_lt',
            $nextMonth->format('Y-m-01'),
        );
        $I->click('Search Share Trades');

        // Check matched share-trades list
        $I->seeElement(
            'section#share-trade-search [data-field-name="existing-transfers"]',
        );
        $I->seeElement(
            'section#share-trade-search [data-field-name="share-trade-count"]',
        );
        $I->seeElement('section#share-trade-search [data-field-name="total-invested"]');
        $I->seeElement(
            'section#share-trade-search [data-field-name="shares-invested"]',
        );
        $headers = [
            'Id',
            'Buy Order',
            'Sell Order',
            'Buyer',
            'Seller',
            'Quantity',
            'Price',
            'Value',
            'Fees',
            'Taxes',
            'Status',
            'Created',
        ];
        foreach ($headers as $header) {
            $I->see($header, 'section#share-trade-search table thead');
        }

        $I->click('Generate for Trade', '#share-trades-list tbody tr:first-child');
        $I->seeCurrentUrlEquals("/admin/monthend/settlements/{$newOrderId}");
        $I->seeNumberOfElements('#transfers-list tbody tr', 1);

        $I->amOnPage("/admin/monthend/settlements/{$newOrderId}/generate");
        $I->fillField(
            '#settlement_search_generate_createdAt_lt',
            $nextMonth->format('Y-m-01'),
        );
        $I->click('Search Share Trades');
        $remainingTrades = count($I->grabMultiple('#share-trades-list tbody tr'));
        $I->click('Generate All Settlement Transfers');
        $I->seeCurrentUrlEquals(
            "/admin/monthend/settlements/{$newOrderId}/generate-stamp-duty",
        );
        // Remaining plus the 1 that was generated individually before
        $I->amOnPage("/admin/monthend/settlements/{$newOrderId}");
        $I->seeNumberOfElements('#transfers-list tbody tr', 1 + $remainingTrades);

        // Now generate the stamp duty based on generated settlements
        $I->click('Setup Stamp Duty Transfers');
        $I->seeCurrentUrlEquals(
            "/admin/monthend/settlements/{$newOrderId}/generate-stamp-duty",
        );

        $I->seeElement(
            'section#investor-stamp-duty [data-field-name="current-stamp-duty-transfers"]',
        );
        $I->seeElement(
            'section#investor-stamp-duty [data-field-name="expected-stamp-duty-transfers"]',
        );
        $I->seeElement(
            'section#investor-stamp-duty [data-field-name="current-stamp-duty-total"]',
        );
        $I->seeElement(
            'section#investor-stamp-duty [data-field-name="expected-stamp-duty-total"]',
        );
        $headers = [
            'User',
            'Share Trades being Settled',
            'Total being Settled',
            'Stamp Duty Due',
            'Actions',
        ];
        foreach ($headers as $header) {
            $I->see($header, 'section#investor-stamp-duty table thead');
        }

        // Generate one first
        $userId = $I->grabAttributeFrom(
            '#investor-stamp-duty-list tbody tr:first-child td:first-child',
            'data-user-id',
        );
        $I->click('Generate for User');
        $I->seeCurrentUrlEquals(
            "/admin/monthend/settlements/{$newOrderId}/generate-stamp-duty",
        );
        $I->click('Finish and Review');
        $I->seeNumberOfElements('#transfers-list tbody tr', 2 + $remainingTrades);
        $I->assertStringContainsString(
            MonthEndService::DESCRIPTION_PRESETS['stamp duty'] . " User#{$userId}",
            $I->grabTextFrom(
                '#transfers-list tbody tr:last-child [data-field="description"]',
            ),
        );

        // Then delete it so we don't get duplicates
        // The stamp duty generator has no duplicate protection unlike the settlement generator
        $I->click('Delete', '#transfers-list tbody tr:last-child');

        // Try the generate all option
        $I->amOnPage("/admin/monthend/settlements/{$newOrderId}/generate-stamp-duty");
        $stampDutyTransfers = count($I->grabMultiple(
            '#investor-stamp-duty-list tbody tr',
        ));
        $I->click('Generate All Transfers');
        $I->seeCurrentUrlEquals("/admin/monthend/settlements/{$newOrderId}");
        $I->seeNumberOfElements(
            '#transfers-list tbody tr',
            1 + $remainingTrades + $stampDutyTransfers,
        );
    }
}
