<?php

namespace App\Tests\Functional\Ops\MonthEnd\FeeCollection;

use App\Entity\AbstractOrder;
use App\Entity\Enum\TransferType;
use App\Service\MonthEndService;
use App\Tests\Support\FunctionalTester;

class FeeCollectionManageCest
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
        $newOrderId = $I->createFeeCollectionOrder();
        $I->amOnPage("/admin/monthend/fee-collections/{$newOrderId}");

        // Should see a prompt to configure transfers
        $I->seeLink(
            'Configure Transfers',
            "/admin/monthend/fee-collections/{$newOrderId}#transfers-list",
        );

        // Check sections and titles present
        $sections = [
            'about-transfer' => 'About Transfer Order',
            'order-status' => 'Order Status',
            'transfers' => 'Transfers',
        ];
        // check section headers
        foreach ($sections as $sectionId => $sectionTitle) {
            $I->see($sectionTitle, "section#{$sectionId} h3");
        }

        // Check transfer order about section
        $I->seeElement('section#about-transfer [data-field-name="scheduled-monthend"]');
        $I->seeElement('section#about-transfer table#available-fee-wallets');
        $I->seeElement('section#about-transfer [data-field-name="description"]');

        // Check order status section
        $I->seeElement('section#order-status [data-field-name="status"]');
        $I->seeElement('section#order-status [data-field-name="approved-by"]');

        // Check transfers section
        $sections = [
            'Asset',
            'Transfer From',
            'Transfer To',
            'Description',
            'Amount',
            'Proportion',
            'Updated',
            'Status',
            'Transaction Reference',
            'Actions',
        ];
        foreach ($sections as $sectionId => $sectionTitle) {
            $I->see($sectionTitle, 'section#transfers table thead');
        }
        $I->seeLink(
            'Export Transfers List',
            "/admin/transfer-orders/{$newOrderId}/export",
        );
    }

    public function checkWarnings(FunctionalTester $I): void
    {
        // Check "existing-order-warning" where 1 or more fee collections have the same monthend period
        $newOrderId = $I->createFeeCollectionOrder();
        $secondOrderId = $I->createFeeCollectionOrder();
        $I->amOnPage("/admin/monthend/fee-collections/{$secondOrderId}");
        $I->seeElement('section#existing-order-warning');
        $I->seeElement(
            "section#existing-order-warning tr[data-transfer-order-id='{$newOrderId}']",
        );
        $I->seeLink('View', "/admin/monthend/fee-collections/{$newOrderId}");

        $I->amOnPage("/admin/monthend/fee-collections/{$secondOrderId}/edit");
        $I->fillField('#monthend_order_edit_scheduledFor', '2016-04-08');
        $I->click('Save Changes');
        $I->dontSeeElement('section#existing-order-warning');

        // Change the monthend back to current month
        $I->amOnPage("/admin/monthend/fee-collections/{$secondOrderId}/edit");
        $I->fillField('#monthend_order_edit_scheduledFor', date('Y-m-d'));
        $I->click('Save Changes');
        $I->seeElement('section#existing-order-warning');

        // Warning to check wallet balances if any transfers are in the order
        $I->amOnPage("/admin/monthend/fee-collections/{$newOrderId}");
        $I->dontSeeElement('#wallet-balance-prompt');
        $I->addTransferToFeeCollectionOrder();
        $I->click('Finish and Review');
        $I->seeElement('#wallet-balance-prompt');
        // Goes away once approved
        $I->click('Approve Transfer Order');
        $I->dontSeeElement('#wallet-balance-prompt');
        // Returns if back to draft
        $I->click('Unapprove Transfer Order');
        $I->seeElement('#wallet-balance-prompt');
        // Goes away if no transfers in order
        $I->click('Clear All Transfers');
        $I->click('Clear All Transfers');
        $I->amOnPage("/admin/monthend/fee-collections/{$newOrderId}");
        $I->dontSeeElement('#wallet-balance-prompt');
    }

    public function testEditMonthendOrder(FunctionalTester $I): void
    {
        $newOrderId = $I->createFeeCollectionOrder();
        $I->amOnPage("/admin/monthend/fee-collections/{$newOrderId}");
        $I->see('Edit Order', '#about-transfer a');
        $I->click('Edit Order');

        $I->seeCurrentUrlEquals("/admin/monthend/fee-collections/{$newOrderId}/edit");
        $I->seeLink('Back', "/admin/monthend/fee-collections/{$newOrderId}");
        $I->seeLink('Discard Changes', "/admin/monthend/fee-collections/{$newOrderId}");

        $newDate = new \DateTime('2020-04-08');
        $newDescription = 'Edit monthend fee collection order description test';
        $I->fillField('#monthend_order_edit_scheduledFor', $newDate->format('Y-m-d'));
        $I->fillField('#monthend_order_edit_description', $newDescription);
        $I->click('Save Changes');

        $I->seeCurrentUrlEquals("/admin/monthend/fee-collections/{$newOrderId}");
        $I->assertEquals(
            $newDate->format('Y-m'),
            $I->grabTextFrom('[data-field-name="scheduled-monthend"]'),
        );
        $I->assertEquals(
            $newDescription,
            $I->grabTextFrom('[data-field-name="description"]'),
        );
    }

    public function testNonFeeCollectionWarning(FunctionalTester $I): void
    {
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);
        $I->amOnPage('/admin/transfer-orders/create');
        $I->fillField('#transfer_order_description', 'Non fee collection test');
        $I->selectOption('#transfer_order_transferType', [
            'value' => TransferType::FeeCollection->value,
        ]);
        $I->selectOption('#transfer_order_asset', ['value' => (string) $assetId]);
        $I->click('Create Transfer Order');
        $newOrderId = $I->grabTextFrom('[data-field-name="order-id"]');
        $I->seeCurrentUrlEquals("/admin/transfer-orders/{$newOrderId}/manage");

        // Manage page shows a warning
        $I->amOnPage("/admin/monthend/fee-collections/{$newOrderId}");
        $I->seeCurrentUrlEquals("/admin/monthend/fee-collections/{$newOrderId}");
        $I->seeElement('section#validation-issues-warning');

        // Most other fee collection routes will send you back to the manage page
        $protectedPaths = [
            "/admin/monthend/fee-collections/{$newOrderId}/generate",
        ];
        foreach ($protectedPaths as $path) {
            $I->amOnPage($path);
            $I->see('not a fee collection order');
            $I->seeCurrentUrlEquals("/admin/monthend/fee-collections/{$newOrderId}");
        }
    }

    public function testTransitionRedirect(FunctionalTester $I): void
    {
        $newOrderId = $I->createFeeCollectionOrder();
        // Need at least 2 transfers to be able to abandon later
        // We'll just generate via fixtures
        $I->amOnPage("/admin/monthend/fee-collections/{$newOrderId}/generate");
        $I->click('Generate All Transfers');
        $I->assertEquals('Draft', $I->grabTextFrom('[data-field-name="status"]'));

        // Reject
        $I->click('Reject Transfer Order');
        $I->seeCurrentUrlEquals(
            "/admin/transfer-orders/{$newOrderId}/reject?redirectRoute=admin_monthend_fee_collection_manage",
        );
        $I->seeLink('Discard Changes', "/admin/monthend/fee-collections/{$newOrderId}");
        $I->click('Reject Transfer Order');
        $I->seeCurrentUrlEquals("/admin/monthend/fee-collections/{$newOrderId}");
        $I->assertEquals('Closed', $I->grabTextFrom('[data-field-name="status"]'));

        // Reopen
        $I->click('Reopen Transfer Order');
        $I->seeCurrentUrlEquals("/admin/monthend/fee-collections/{$newOrderId}");
        $I->assertEquals('Draft', $I->grabTextFrom('[data-field-name="status"]'));

        // Approve
        $I->click('Approve Transfer Order');
        $I->seeCurrentUrlEquals("/admin/monthend/fee-collections/{$newOrderId}");
        $I->assertEquals('Approved', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals(
            $I::USER_SUPER_ADMIN,
            $I->grabTextFrom('[data-field-name="approved-by"]'),
        );

        // Unapprove
        $I->click('Unapprove Transfer Order');
        $I->seeCurrentUrlEquals("/admin/monthend/fee-collections/{$newOrderId}");
        $I->assertEquals('Draft', $I->grabTextFrom('[data-field-name="status"]'));

        // Abandon
        $I->click('Approve Transfer Order');
        $I->click('Transfer Single');
        $I->seeCurrentUrlEquals("/admin/monthend/fee-collections/{$newOrderId}");
        $I->assertEquals('In-progress', $I->grabTextFrom('[data-field-name="status"]'));
        $I->click('Abandon Transfer Order');
        $I->seeCurrentUrlEquals(
            "/admin/transfer-orders/{$newOrderId}/abandon?redirectRoute=admin_monthend_fee_collection_manage",
        );
        $I->seeLink('Discard Changes', "/admin/monthend/fee-collections/{$newOrderId}");
        $I->click('Abandon Transfer Order');
        $I->seeCurrentUrlEquals("/admin/monthend/fee-collections/{$newOrderId}");
        $I->assertEquals('Abandoned', $I->grabTextFrom('[data-field-name="status"]'));
    }

    public function testFeeCollectionGenerateManagement(FunctionalTester $I): void
    {
        $newOrderId = $I->createFeeCollectionOrder();
        $I->amOnPage("/admin/monthend/fee-collections/{$newOrderId}");
        // No description suggestion if empty
        $I->dontSeeElement('#description-suggestion');
        $I->click('Setup Transfers');
        $I->click('Setup for Multi Wallet');
        $I->seeCurrentUrlEquals(
            "/admin/monthend/fee-collections/{$newOrderId}/generate",
        );
        $today = new \DateTimeImmutable(date('Y-m-01'));
        $nextMonth = $today->modify('+1 month');
        $superadminWallet = $I->grabFromDatabase('users', 'mangoPayWalletId', [
            'username' => $I::USER_SUPER_ADMIN,
        ]);
        $I->seeInField(
            '#fee_search_generate_scheduledFor_gte',
            $today->format('Y-m-01'),
        );
        $I->seeInField(
            '#fee_search_generate_scheduledFor_lt',
            $nextMonth->format('Y-m-01'),
        );
        $I->seeInField('#fee_search_generate_description', 'Yielders management fees');
        $I->seeElement(
            "#fee_search_generate_feeWalletId option[value='{$superadminWallet}']",
        );
        $yieldersFeeWallet = $I::YIELDERS_FEE_WALLET;
        $I->seeElement(
            "#fee_search_generate_feeWalletId option[value='{$yieldersFeeWallet}'][selected]",
        );
        $feeWallet = $I->grabValueFrom('#fee_search_generate_feeWalletId');
        $I->click('Generate All Transfers');
        $I->seeCurrentUrlEquals("/admin/monthend/fee-collections/{$newOrderId}");
        $transferCreditIds = $I->grabMultiple(
            '#transfers-list td[data-field="creditWalletId"]',
        );
        $I->assertCount(1, array_unique($transferCreditIds));
        $I->assertEquals($feeWallet, array_unique($transferCreditIds)[0]);
        $I->dontSee('N/A', 'tr [data-field="asset"]');

        // Try the alternative fee collection generator
        // This requires an existing completed income disaggregation order - there's a data fixture for this
        $incomeOrderIds = $I->grabColumnFromDatabase('transfer_order', 'id', [
            'transferType' => TransferType::IncomeDisaggregation->value,
            'status' => AbstractOrder::STATE_COMPLETED,
        ]);
        // Clear transfers first
        $I->click('Clear All Transfers');
        $I->click('Clear All Transfers');
        $I->amOnPage("/admin/monthend/fee-collections/{$newOrderId}/setup");
        $I->click('Setup for Single Wallet');
        $I->seeCurrentUrlEquals(
            "/admin/monthend/fee-collections/{$newOrderId}/income-deposits",
        );
        $firstOrderId = $I->grabAttributeFrom(
            '#suitable-orders-list tbody tr',
            'data-object-id',
        );
        $I->assertContains((int) $firstOrderId, $incomeOrderIds);
        $I->click('Preview Fee Collections');

        $feeCut = '15';
        $feeDescription = 'Custom test fee';
        $I->fillField('#fee_derive_generate_percentageCut', $feeCut);
        $sampleAmount = str_replace(
            ',',
            '',
            $I->grabTextFrom(
                '#transfers-preview-list tbody tr:first-child td[data-field="amount"]',
            ),
        );
        $expectedFee = number_format(($sampleAmount * $feeCut) / 100, 2);
        $I->click('Update Preview');
        $I->see(
            $expectedFee,
            '#transfers-preview-list tbody tr:first-child td[data-field="fee"]',
        );
        // Customise the description before generating
        $I->fillField('#fee_derive_generate_feeDescription', $feeDescription);
        $I->click('Generate All Transfers');
        // The preview should match the generated
        $I->seeCurrentUrlEquals("/admin/monthend/fee-collections/{$newOrderId}");
        $I->see(
            $expectedFee,
            '#transfers-list tbody tr:first-child td[data-field="amount"]',
        );
        $I->see(
            $feeDescription,
            '#transfers-list tbody tr:first-child td[data-field="description"]',
        );

        // Redo with default generate options
        $I->amOnPage("/admin/monthend/fee-collections/{$newOrderId}/setup");
        $I->click('Setup for Single Wallet');
        $I->click('Preview Fee Collections');
        $I->click('Generate All Transfers');

        // Should be prompted to change order description
        $I->seeElement('#description-suggestion');
        $I->click('Apply Suggested Description');
        $I->seeCurrentUrlEquals("/admin/monthend/fee-collections/{$newOrderId}");
        $I->see('Collect Yielders management fees', '[data-field-name="description"]');
    }

    public function testFeeCollectionGenerateRelisting(FunctionalTester $I): void
    {
        $newOrderId = $I->createFeeCollectionOrder();
        $I->amOnPage("/admin/monthend/fee-collections/{$newOrderId}");
        // No description suggestion if empty
        $I->dontSeeElement('#description-suggestion');
        $I->click('Setup Transfers');
        $I->click('Setup Relisting Fees');
        $I->seeCurrentUrlEquals(
            "/admin/monthend/fee-collections/{$newOrderId}/generate/relisting",
        );
        $today = new \DateTimeImmutable(date('Y-m-01'));
        $lastMonth = $today->modify('-1 month');
        $nextMonth = $today->modify('+1 month');
        $superadminWallet = $I->grabFromDatabase('users', 'mangoPayWalletId', [
            'username' => $I::USER_SUPER_ADMIN,
        ]);
        $I->seeInField(
            '#fee_search_generate_scheduledFor_gte',
            $lastMonth->format('Y-m-01'),
        );
        $I->seeInField(
            '#fee_search_generate_scheduledFor_lt',
            $today->format('Y-m-01'),
        );
        $I->fillField(
            '#fee_search_generate_scheduledFor_lt',
            $nextMonth->format('Y-m-01'),
        );
        $I->dontSeeElement('#fee_search_generate_description');
        $I->seeElement(
            "#fee_search_generate_feeWalletId option[value='{$superadminWallet}']",
        );
        $yieldersFeeWallet = $I::YIELDERS_FEE_WALLET;
        $I->seeElement(
            "#fee_search_generate_feeWalletId option[value='{$yieldersFeeWallet}'][selected]",
        );
        $feeWallet = $I->grabValueFrom('#fee_search_generate_feeWalletId');
        $I->click('Generate All Transfers');
        $I->seeCurrentUrlEquals("/admin/monthend/fee-collections/{$newOrderId}");
        $transferCreditIds = $I->grabMultiple(
            '#transfers-list td[data-field="creditWalletId"]',
        );
        $I->assertCount(1, array_unique($transferCreditIds));
        $I->assertEquals($feeWallet, array_unique($transferCreditIds)[0]);
        // All generated transfers should have an asset relation
        $I->dontSee('N/A', 'tr [data-field="asset"]');

        // Can also configure a single transfer
        $I->amOnPage(
            "/admin/monthend/fee-collections/{$newOrderId}/generate/relisting",
        );
        $I->fillField(
            '#fee_search_generate_scheduledFor_lt',
            $nextMonth->format('Y-m-01'),
        );
        $I->click('Search', '[name="fee_search_generate"]');
        $amount = $I->grabTextFrom(
            '#asset-relisting-fee-list tbody tr:first-child [data-field="amount"]',
        );
        $I->click(
            'Configure Transfer',
            '#asset-relisting-fee-list tbody tr:first-child',
        );
        $I->seeOptionIsSelected('#asset_transfer_request_debitWalletId', 'Asset hold');
        $I->seeInField('#asset_transfer_request_amount', $amount);

        // Should be prompted to change order description
        $I->amOnPage("/admin/monthend/fee-collections/{$newOrderId}");
        $I->seeElement('#description-suggestion');
        $I->click('Apply Suggested Description');
        $I->seeCurrentUrlEquals("/admin/monthend/fee-collections/{$newOrderId}");
        $I->see('Collect relisting fees', '[data-field-name="description"]');
    }

    public function testAddRemoveTransfers(FunctionalTester $I): void
    {
        $newOrderId = $I->createFeeCollectionOrder();
        $I->amOnPage("/admin/monthend/fee-collections/{$newOrderId}");
        $I->click('Add Transfer');
        $I->seeCurrentUrlEquals(
            "/admin/monthend/fee-collections/{$newOrderId}/add-transfer",
        );
        $assetId = $I->grabAttributeFrom(
            '#assets-list tbody tr:first-child',
            'data-object-id',
        );
        $I->click('Management', '#assets-list tbody tr:first-child');
        $I->seeCurrentUrlEquals(
            "/admin/monthend/fee-collections/{$newOrderId}/add-transfer/{$assetId}?feeType=management",
        );
        $I->seeLink(
            'Back',
            "/admin/monthend/fee-collections/{$newOrderId}/add-transfer",
        );
        $I->seeLink(
            'Cancel',
            "/admin/monthend/fee-collections/{$newOrderId}/add-transfer",
        );
        $I->seeOptionIsSelected(
            '#asset_transfer_request_debitWalletId',
            'Asset settlement/actual',
        );
        $I->seeOptionIsSelected(
            '#asset_transfer_request_creditWalletId',
            'YieldersFeeWallet',
        );
        $I->fillField('#asset_transfer_request_amount', '10.62');
        $I->click('Add Transfer');
        $I->seeCurrentUrlEquals(
            "/admin/monthend/fee-collections/{$newOrderId}/add-transfer",
        );
        $I->click('Finish and Review');
        $I->seeCurrentUrlEquals("/admin/monthend/fee-collections/{$newOrderId}");

        // Check transfers summary
        $I->assertEquals(
            '0.00/10.62',
            $I->grabTextFrom('[data-field-name="total-transferred-(£)"]'),
        );
        $I->assertEquals(
            '0/1',
            $I->grabTextFrom('[data-field-name="transfers-completed"]'),
        );

        // Check transfers table
        $assetSpv = $I->grabFromDatabase('assets', 'companyNumber', ['id' => $assetId]);
        $assetName = $I->grabFromDatabase('assets', 'name', ['id' => $assetId]);
        $scheduledMonthend = $I->grabTextFrom('[data-field-name="scheduled-monthend"]');
        $previousMonth = new \DateTime($scheduledMonthend)
            ->modify('-1 month')
            ->format('Y-m');
        $I->seeNumberOfElements('#transfers-list tbody tr', 1);
        $transferRequestId = $I->grabAttributeFrom(
            '#transfers-list tbody tr:first-child',
            'data-object-id',
        );
        $I->assertEquals('10.62', $I->grabTextFrom('tr [data-field="amount"]'));
        $I->assertEquals(
            "#{$assetId} // {$assetName} ({$assetSpv})",
            $I->grabTextFrom('tr [data-field="asset"]'),
        );
        $I->assertEquals(
            "Yielders management fees;{$assetSpv} {$assetName};For month {$previousMonth}",
            $I->grabTextFrom('tr [data-field="description"]'),
        );

        // Edit the transfer amount and description
        $I->click('Edit', '#transfers-list tbody tr:first-child');
        $I->seeCurrentUrlEquals(
            "/admin/transfer-requests/$transferRequestId/edit?restricted=1",
        );
        $I->seeLink('Cancel', "/admin/monthend/fee-collections/{$newOrderId}");
        $I->seeElement('#transfer_request_debitWalletId[disabled]');
        $I->seeElement('#transfer_request_creditWalletId[disabled]');
        $I->fillField(
            '#transfer_request_description',
            'Updated test transfer description',
        );
        $I->fillField('#transfer_request_amount', '9.92');
        $I->click('Save Changes');
        $I->seeCurrentUrlEquals("/admin/monthend/fee-collections/{$newOrderId}");
        $I->assertEquals('9.92', $I->grabTextFrom('tr [data-field="amount"]'));
        $I->assertEquals(
            'Updated test transfer description',
            $I->grabTextFrom('tr [data-field="description"]'),
        );

        // Attempt to edit one of the transfers after approval
        $I->click('Approve Transfer Order');
        $I->click('Edit', '#transfers-list tbody tr:first-child');
        $I->see('Editing is restricted outside draft mode');
        $I->seeElement('#transfer_request_debitWalletId', ['disabled' => 'disabled']);
        $I->seeElement('#transfer_request_creditWalletId', ['disabled' => 'disabled']);
        $I->seeElement('#transfer_request_description');
        $I->dontSeeElement('#transfer_request_description', ['disabled' => 'disabled']);
        $I->seeElement('#transfer_request_amount', ['disabled' => 'disabled']);
        $randomString = bin2hex(random_bytes(8));
        $I->fillField('#transfer_request_description', $randomString);
        $I->click('Save Changes');
        $I->see(
            $randomString,
            '#transfers-list tbody tr:first-child td[data-field="description"]',
        );
        // Unapprove to allow deleting
        $I->click('Unapprove Transfer Order');

        // Check deletions
        $I->addTransferToFeeCollectionOrder();
        $I->click('Finish and Review');
        $I->assertEquals(
            '0/2',
            $I->grabTextFrom('[data-field-name="transfers-completed"]'),
        );
        $I->click('Delete', '#transfers-list tbody tr:first-child');
        $I->seeCurrentUrlEquals("/admin/monthend/fee-collections/{$newOrderId}");
        $I->assertEquals(
            '0/1',
            $I->grabTextFrom('[data-field-name="transfers-completed"]'),
        );

        // Clear all remaining transfers
        $I->click('Clear All Transfers');
        $I->seeCurrentUrlEquals(
            "/admin/transfer-orders/{$newOrderId}/clear-transfers?redirectRoute=admin_monthend_fee_collection_manage",
        );
        $I->see('Confirm Deletion');
        $I->seeLink('Discard Changes', "/admin/monthend/fee-collections/{$newOrderId}");
        $I->click('Clear All Transfers');
        $I->seeCurrentUrlEquals("/admin/monthend/fee-collections/{$newOrderId}");
        $I->seeNumberOfElements('#transfers-list tbody tr', 0);

        // Multi-wallet enabled wallets can use the expenses wallet as the debit wallet
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);
        $I->amOnPage(
            "/admin/monthend/fee-collections/{$newOrderId}/add-transfer/{$assetId}",
        );
        // Still defaults to the settlement/actual wallet for now
        $I->seeOptionIsSelected(
            '#asset_transfer_request_debitWalletId',
            'Asset settlement/actual',
        );
        // This action will fail if the expenses wallet is not available to pick
        $I->selectOption('#asset_transfer_request_debitWalletId', 'Asset expenses');

        // Relisting fee defaults to hold wallet
        $I->amOnPage("/admin/monthend/fee-collections/{$newOrderId}/add-transfer");
        $assetId = $I->grabAttributeFrom(
            '#assets-list tbody tr:first-child',
            'data-object-id',
        );
        $I->click('Relisting', '#assets-list tbody tr:first-child');
        $I->seeOptionIsSelected('#asset_transfer_request_debitWalletId', 'Asset hold');

        // YPML fee defaults to special ypml wallet if it exists
        // Ensure the ypml wallet is configure
        $I->amOnPage('/admin/settings');
        $I->fillField('#app_setting_form_ypmlFeeWallet', $I::YPML_FEE_WALLET);
        $I->click('Save Changes');
        $I->seeInField('#app_setting_form_ypmlFeeWallet', $I::YPML_FEE_WALLET);
        // Then try to add a YPML pre-filled fee
        $I->amOnPage("/admin/monthend/fee-collections/{$newOrderId}/add-transfer");
        $assetId = $I->grabAttributeFrom(
            '#assets-list tbody tr:first-child',
            'data-object-id',
        );
        $I->click('YPML', '#assets-list tbody tr:first-child');
        $I->seeOptionIsSelected(
            '#asset_transfer_request_creditWalletId',
            'YpmlFeeWallet',
        );
    }
}
