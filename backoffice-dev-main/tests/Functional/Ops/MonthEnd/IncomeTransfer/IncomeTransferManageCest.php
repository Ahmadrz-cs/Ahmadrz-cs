<?php

namespace App\Tests\Functional\Ops\MonthEnd\IncomeTransfer;

use App\Entity\Enum\TransferType;
use App\Service\MonthEndService;
use App\Tests\Support\FunctionalTester;

class IncomeTransferManageCest
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
        $transferOrder = $I->grabFromDatabase('transfer_order', 'id', [
            'asset_id' => $assetId,
            'description' => 'Process asset income',
        ]);
        $I->amOnPage("/admin/monthend/income-transfers/{$transferOrder}");

        // Check sections and titles present
        $sections = [
            'about-transfer' => 'About Transfer Order',
            'order-status' => 'Order Status',
            'transfer-breakdown' => 'Transfer Breakdown',
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
        $I->seeElement('section#about-transfer [data-field-name="spv-company-number"]');
        $I->seeLink('View Asset Product', "/admin/products/{$assetId}");
        $I->seeLink('View Wallet Balances', "/admin/asset/{$assetId}/manage-wallets");

        // Check order status section
        $I->seeElement('section#order-status [data-field-name="status"]');
        $I->seeElement('section#order-status [data-field-name="approved-by"]');

        // Check breakdown graph section
        $I->seeElement('section#transfer-breakdown .progress');

        // Check transfers section
        $sections = [
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
            "/admin/transfer-orders/{$transferOrder}/export",
        );
    }

    public function testNonIncomeTransferRedirect(FunctionalTester $I): void
    {
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);
        $I->amOnPage('/admin/transfer-orders/create');
        $I->fillField('#transfer_order_description', 'Non income transfer test');
        $I->selectOption('#transfer_order_transferType', [
            'value' => TransferType::AssetIncomeProcessing->value,
        ]);
        $I->selectOption('#transfer_order_asset', ['value' => (string) $assetId]);
        $I->click('Create Transfer Order');
        $newOrderId = $I->grabTextFrom('[data-field-name="order-id"]');
        $I->seeCurrentUrlEquals("/admin/transfer-orders/{$newOrderId}/manage");

        // Fresh transfer order should have no issues
        $I->amOnPage("/admin/monthend/income-transfers/{$newOrderId}");
        $I->seeCurrentUrlEquals("/admin/monthend/income-transfers/{$newOrderId}");
        $I->dontSee('not an income transfer order');
        $I->amOnPage("/admin/transfer-orders/{$newOrderId}/manage");

        // Add a suspect transfer though...
        $I->click('Add Transfer');
        $I->fillField('#transfer_request_debitWalletId', 'this_doesnt_exist');
        $I->fillField('#transfer_request_creditWalletId', 'neither_does_this');
        $I->fillField('#transfer_request_description', 'what are those wallets');
        $I->fillField('#transfer_request_amount', '0.01');
        $I->click('Add Transfer');
        $newTransferRequestId = $I->grabTextFrom(
            '#transfers-list tbody tr:first-child [data-field="id"]',
        );

        // Manage page shows a warning
        $I->amOnPage("/admin/monthend/income-transfers/{$newOrderId}");
        $I->seeCurrentUrlEquals("/admin/transfer-orders/{$newOrderId}/manage");
        // $I->seeCurrentUrlEquals("/admin/monthend/income-transfers/{$newOrderId}");
        // $I->seeElement('section#validation-issues-warning');

        // Most other income transfer routes will send you back to the manage page
        $protectedPaths = [
            "/admin/monthend/income-transfers/{$newOrderId}/template",
            "/admin/monthend/income-transfers/{$newOrderId}/template-default",
            "/admin/monthend/income-transfers/{$newOrderId}/add-transfer",
            "/admin/monthend/income-transfers/edit-transfer/{$newTransferRequestId}",
        ];
        foreach ($protectedPaths as $path) {
            $I->amOnPage($path);
            $I->see('not an income transfer order');
            $I->seeCurrentUrlEquals("/admin/transfer-orders/{$newOrderId}/manage");

            // $I->seeCurrentUrlEquals("/admin/monthend/income-transfers/{$newOrderId}");
        }
    }

    public function checkWarnings(FunctionalTester $I): void
    {
        $newOrderId = $I->createIncomeTransferOrder('Royal Eversea Glades - Cambridge');
        $I->amOnPage("/admin/monthend/income-transfers/{$newOrderId}");

        // No warnings on fresh income transfer order
        $I->dontSeeElement('section#wallet-balance-warning');

        // If the total to transfer is too large - we'll us £10bn+ as an example
        $I->addAssetTransferToOrder('Expenses', '0.12');
        $I->addAssetTransferToOrder('Expenses', '10111000111');
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

        // Check warning balance required updates
        $currentTotalRemaining = $I->convertMonetaryToNumber($I->grabTextFrom(
            'section#wallet-balance-warning [data-field-name="total-remaining"]',
        ));
        $currentAmountRequired = $I->convertMonetaryToNumber($I->grabTextFrom(
            'section#wallet-balance-warning [data-field-name="additional-funds-required"]',
        ));

        $I->click('Approve Transfer Order');
        $I->click('Transfer Single', '#transfers-list tbody tr:first-child');
        $newTotalRemaining = $I->convertMonetaryToNumber($I->grabTextFrom(
            'section#wallet-balance-warning [data-field-name="total-remaining"]',
        ));
        $newAmountRequired = $I->convertMonetaryToNumber($I->grabTextFrom(
            'section#wallet-balance-warning [data-field-name="additional-funds-required"]',
        ));
        $I->assertEquals('0.12', round($currentTotalRemaining - $newTotalRemaining, 2));
        $I->assertEquals($currentAmountRequired, $newAmountRequired);
    }

    public function testEditMonthendOrder(FunctionalTester $I): void
    {
        $newOrderId = $I->createIncomeTransferOrder('Royal Eversea Glades - Cambridge');
        $I->amOnPage("/admin/monthend/income-transfers/{$newOrderId}");
        $I->see('Edit Order', '#about-transfer a');
        $I->click('Edit Order');

        $I->seeCurrentUrlEquals("/admin/monthend/income-transfers/{$newOrderId}/edit");
        $I->seeLink('Back', "/admin/monthend/income-transfers/{$newOrderId}");
        $I->seeLink(
            'Discard Changes',
            "/admin/monthend/income-transfers/{$newOrderId}",
        );

        $newDate = new \DateTime('2020-04-08');
        $newDescription = 'Edit monthend income processing order description test';
        $I->fillField('#monthend_order_edit_scheduledFor', $newDate->format('Y-m-d'));
        $I->fillField('#monthend_order_edit_description', $newDescription);
        $I->click('Save Changes');

        $I->seeCurrentUrlEquals("/admin/monthend/income-transfers/{$newOrderId}");
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
        $newOrderId = $I->createIncomeTransferOrder();
        // Need at least 2 transfers to be able to abandon later
        // We'll just generate the default set
        $I->amOnPage("/admin/monthend/income-transfers/{$newOrderId}/template-default");
        $I->amOnPage("/admin/monthend/income-transfers/{$newOrderId}");
        $I->assertEquals('Draft', $I->grabTextFrom('[data-field-name="status"]'));

        // Reject
        $I->click('Reject Transfer Order');
        $I->seeCurrentUrlEquals(
            "/admin/transfer-orders/{$newOrderId}/reject?redirectRoute=admin_monthend_income_transfer_manage",
        );
        $I->seeLink(
            'Discard Changes',
            "/admin/monthend/income-transfers/{$newOrderId}",
        );
        $I->click('Reject Transfer Order');
        $I->seeCurrentUrlEquals("/admin/monthend/income-transfers/{$newOrderId}");
        $I->assertEquals('Closed', $I->grabTextFrom('[data-field-name="status"]'));

        // Reopen
        $I->click('Reopen Transfer Order');
        $I->seeCurrentUrlEquals("/admin/monthend/income-transfers/{$newOrderId}");
        $I->assertEquals('Draft', $I->grabTextFrom('[data-field-name="status"]'));

        // Approve
        $I->click('Approve Transfer Order');
        $I->seeCurrentUrlEquals("/admin/monthend/income-transfers/{$newOrderId}");
        $I->assertEquals('Approved', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals(
            $I::USER_SUPER_ADMIN,
            $I->grabTextFrom('[data-field-name="approved-by"]'),
        );

        // Unapprove
        $I->click('Unapprove Transfer Order');
        $I->seeCurrentUrlEquals("/admin/monthend/income-transfers/{$newOrderId}");
        $I->assertEquals('Draft', $I->grabTextFrom('[data-field-name="status"]'));

        // Abandon
        $I->click('Approve Transfer Order');
        $I->click('Transfer Single');
        $I->seeCurrentUrlEquals("/admin/monthend/income-transfers/{$newOrderId}");
        $I->assertEquals('In-progress', $I->grabTextFrom('[data-field-name="status"]'));
        $I->click('Abandon Transfer Order');
        $I->seeCurrentUrlEquals(
            "/admin/transfer-orders/{$newOrderId}/abandon?redirectRoute=admin_monthend_income_transfer_manage",
        );
        $I->seeLink(
            'Discard Changes',
            "/admin/monthend/income-transfers/{$newOrderId}",
        );
        $I->click('Abandon Transfer Order');
        $I->seeCurrentUrlEquals("/admin/monthend/income-transfers/{$newOrderId}");
        $I->assertEquals('Abandoned', $I->grabTextFrom('[data-field-name="status"]'));
    }

    public function testAddRemoveTransfers(FunctionalTester $I): void
    {
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);

        $newOrderId = $I->createIncomeTransferOrder('Royal Eversea Glades - Cambridge');
        $I->amOnPage("/admin/monthend/income-transfers/{$newOrderId}");
        $I->click('Add Transfer');
        $I->seeCurrentUrlEquals(
            "/admin/monthend/income-transfers/{$newOrderId}/add-transfer",
        );
        $I->seeLink(
            'View Asset Wallet Balances (opens in new tab)',
            "/admin/asset/{$assetId}/manage-wallets",
        );
        $I->seeLink('Cancel', "/admin/monthend/income-transfers/{$newOrderId}");
        $I->seeLink('Back', "/admin/monthend/income-transfers/{$newOrderId}");

        // The debit wallet is always disabled and deposit not available to transfer to
        $I->seeElement('#asset_transfer_request_debitWalletId[disabled]');
        $I->dontSeeElement('#asset_transfer_request_creditWalletId[disabled]');
        $I->dontSeeElement('#asset_transfer_request_amount[disabled]');
        $I->dontSeeElement('#asset_transfer_request_description[disabled]');
        $creditWalletOptions = $I->grabMultiple(
            '#asset_transfer_request_creditWalletId option',
        );
        $I->assertNotContains('Deposit', $creditWalletOptions);
        $I->assertNotContains('Hold', $creditWalletOptions);
        $I->assertNotContains('Settlement', $creditWalletOptions);

        // Check the datalist for description exists
        $I->seeElement('datalist#description-presets');
        $I->seeNumberOfElements('datalist#description-presets option', 8);
        $I->seeElement('#asset_transfer_request_description[list=description-presets]');

        $I->selectOption('#asset_transfer_request_creditWalletId', 'Treasury');
        $I->fillField('#asset_transfer_request_amount', '0.04');
        $I->fillField(
            '#asset_transfer_request_description',
            'Delete transfer request test',
        );
        $I->click('Add Transfer');

        // Check transfers summary
        $I->assertEquals(
            '0.00/0.04',
            $I->grabTextFrom('[data-field-name="total-transferred-(£)"]'),
        );
        $I->assertEquals(
            '0/1',
            $I->grabTextFrom('[data-field-name="transfers-completed"]'),
        );

        // Check transfers table
        $I->seeNumberOfElements('#transfers-list tbody tr', 1);
        $transferRequestId = $I->grabAttributeFrom(
            '#transfers-list tbody tr:first-child',
            'data-object-id',
        );
        $I->assertStringContainsString(
            'Deposit',
            $I->grabTextFrom('tr [data-field="debitWalletId"]'),
        );
        $I->assertStringContainsString(
            'Treasury',
            $I->grabTextFrom('tr [data-field="creditWalletId"]'),
        );
        $I->assertEquals('0.04', $I->grabTextFrom('tr [data-field="amount"]'));
        $I->assertEquals(
            'Delete transfer request test',
            $I->grabTextFrom('tr [data-field="description"]'),
        );

        // Check action links
        $I->seeLink(
            'Edit',
            "/admin/monthend/income-transfers/edit-transfer/{$transferRequestId}",
        );
        $I->seeLink(
            'Delete',
            "/admin/transfer-requests/{$transferRequestId}/delete?redirectRoute=admin_monthend_income_transfer_manage",
        );

        // Edit the transfer
        $I->click('Edit', '#transfers-list tbody tr:first-child');
        $I->seeLink(
            'View Asset Wallet Balances (opens in new tab)',
            "/admin/asset/{$assetId}/manage-wallets",
        );
        $I->seeLink(
            'Discard Changes',
            "/admin/monthend/income-transfers/{$newOrderId}",
        );
        $I->seeLink('Back', "/admin/monthend/income-transfers/{$newOrderId}");

        // Just like add, debit wallet is locked and deposit not available to transfer to
        $I->seeElement('#asset_transfer_request_debitWalletId[disabled]');
        $I->dontSeeElement('#asset_transfer_request_creditWalletId[disabled]');
        $I->dontSeeElement('#asset_transfer_request_amount[disabled]');
        $I->dontSeeElement('#asset_transfer_request_description[disabled]');
        $creditWalletOptions = $I->grabMultiple(
            '#asset_transfer_request_creditWalletId option',
        );
        // Editing does support credit wallet as hold or settlement - mainly to allow corrections
        $I->assertNotContains('Deposit', $creditWalletOptions);
        $I->selectOption('#asset_transfer_request_creditWalletId', 'Tax');
        $I->fillField('#asset_transfer_request_amount', '0.02');
        $I->fillField(
            '#asset_transfer_request_description',
            'Delete tax transfer request test',
        );
        $I->click('Save Changes');

        // Check fields updated (or not)
        $I->assertStringContainsString(
            'Deposit',
            $I->grabTextFrom('tr [data-field="debitWalletId"]'),
        );
        $I->assertStringContainsString(
            'Tax',
            $I->grabTextFrom('tr [data-field="creditWalletId"]'),
        );
        $I->assertEquals('0.02', $I->grabTextFrom('tr [data-field="amount"]'));
        $I->assertEquals(
            'Delete tax transfer request test',
            $I->grabTextFrom('tr [data-field="description"]'),
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
        $I->seeCurrentUrlEquals("/admin/monthend/income-transfers/{$newOrderId}");
        $I->seeNumberOfElements('#transfers-list tbody tr', 0);

        // Clear all remaining transfers
        $I->click('Clear All Transfers');
        $I->seeCurrentUrlEquals(
            "/admin/transfer-orders/{$newOrderId}/clear-transfers?redirectRoute=admin_monthend_income_transfer_manage",
        );
        $I->see('Confirm Deletion');
        $I->seeLink(
            'Discard Changes',
            "/admin/monthend/income-transfers/{$newOrderId}",
        );
        $I->click('Clear All Transfers');
        $I->seeCurrentUrlEquals("/admin/monthend/income-transfers/{$newOrderId}");
        $I->seeNumberOfElements('#transfers-list tbody tr', 0);
    }

    public function testUseTemplate(FunctionalTester $I): void
    {
        $newOrderId = $I->createIncomeTransferOrder('Royal Eversea Glades - Cambridge');
        $I->amOnPage("/admin/monthend/income-transfers/{$newOrderId}");

        // Check transfer generation from templates is still an option (even after initial setup)
        $I->seeLink(
            'Use Template',
            "/admin/monthend/income-transfers/{$newOrderId}/template",
        );
        $I->click('Use Template');
        // Links to generate are still there
        $I->see('Default', '#default-template h3');
        $I->see('Build with Default Template', '#default-template a');
        $I->seeLink(
            'Build with Default Template',
            "/admin/monthend/income-transfers/{$newOrderId}/template-default",
        );
        // Go through the income transfer builder
        $I->click('Build with Default Template');
        $I->seeCurrentUrlEquals(
            "/admin/monthend/income-transfers/{$newOrderId}/builder/starting-balance",
        );
        $I->seeElement('#transfers-summary');
        $I->fillField('#form_amountToProcess', '125.80');
        $I->click('Save and Continue');

        $I->seeCurrentUrlEquals(
            "/admin/monthend/income-transfers/{$newOrderId}/builder/expenses",
        );
        $I->seeElement('#transfers-summary');

        // Add a new transfer with absolute amount
        $I->seeLink(
            'Create New Transfer',
            "/admin/monthend/income-transfers/{$newOrderId}/builder/add-transfer?creditWallet=expenses",
        );
        $I->click('Create New Transfer');
        $I->fillField('#income_transfer_description', 'Absolute expense');
        $expensesWallet = $I->grabFromDatabase('assets', 'expensesWalletId', [
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);
        $I->seeOptionIsSelected(
            '[name="income_transfer[creditWalletId]"]',
            $expensesWallet,
        );
        $I->seeOptionIsSelected('[name="income_transfer[amountType]"]', 'absolute');
        $I->fillField('#income_transfer_amount', '14.72');
        $I->click('Create Transfer');
        $I->seeCurrentUrlEquals(
            "/admin/monthend/income-transfers/{$newOrderId}/builder/expenses",
        );
        $I->see('14.72', '#expenses-list tbody tr:last-child [data-field="amount"]');

        // Edit existing transfer with percentage amount
        $I->click('Edit', '#expenses-list tbody tr:first-child');
        $I->selectOption('[name="income_transfer[amountType]"]', 'percentage');
        $I->fillField('#income_transfer_amount', '12.5');
        $I->click('Save Changes');
        $I->seeCurrentUrlEquals(
            "/admin/monthend/income-transfers/{$newOrderId}/builder/expenses",
        );
        $I->see('15.73', '#expenses-list tbody tr:first-child [data-field="amount"]');

        $I->click('Continue to Next Section');
        $I->seeCurrentUrlEquals(
            "/admin/monthend/income-transfers/{$newOrderId}/builder/tax",
        );
        $I->click('Edit', '#tax-list tbody tr:first-child');
        $taxWallet = $I->grabFromDatabase('assets', 'taxWalletId', [
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);
        $I->seeOptionIsSelected('[name="income_transfer[creditWalletId]"]', $taxWallet);
        $I->selectOption('[name="income_transfer[amountType]"]', 'percentage');
        $I->fillField('#income_transfer_amount', '19');
        $I->click('Save Changes');
        $I->seeCurrentUrlEquals(
            "/admin/monthend/income-transfers/{$newOrderId}/builder/tax",
        );
        $I->see('18.12', '#tax-list tbody tr:first-child [data-field="amount"]');

        $I->click('Continue to Next Section');
        $I->seeCurrentUrlEquals(
            "/admin/monthend/income-transfers/{$newOrderId}/builder/treasury",
        );
        $I->click('Edit', '#treasury-list tbody tr:first-child');
        $treasuryWallet = $I->grabFromDatabase('assets', 'treasuryWalletId', [
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);
        $I->seeOptionIsSelected(
            '[name="income_transfer[creditWalletId]"]',
            $treasuryWallet,
        );
        $I->selectOption('[name="income_transfer[amountType]"]', 'percentage');
        $I->fillField('#income_transfer_amount', '5.8');
        $I->click('Save Changes');
        $I->seeCurrentUrlEquals(
            "/admin/monthend/income-transfers/{$newOrderId}/builder/treasury",
        );
        $I->see('4.48', '#treasury-list tbody tr:first-child [data-field="amount"]');

        $I->click('Continue to Next Section');
        $I->seeCurrentUrlEquals(
            "/admin/monthend/income-transfers/{$newOrderId}/builder/distribution",
        );
        $I->click('Edit', '#distribution-list tbody tr:first-child');
        $distributionWallet = $I->grabFromDatabase('assets', 'distributionWalletId', [
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);
        $I->seeOptionIsSelected(
            '[name="income_transfer[creditWalletId]"]',
            $distributionWallet,
        );
        $I->selectOption('[name="income_transfer[amountType]"]', 'percentage');
        $I->fillField('#income_transfer_amount', '100');
        $I->click('Save Changes');
        $I->seeCurrentUrlEquals(
            "/admin/monthend/income-transfers/{$newOrderId}/builder/distribution",
        );
        $I->see(
            '72.75',
            '#distribution-list tbody tr:first-child [data-field="amount"]',
        );

        $I->click('Finish and Review');
        $I->seeCurrentUrlEquals("/admin/monthend/income-transfers/{$newOrderId}");
        $I->assertEquals(
            '0.00/125.80',
            $I->grabTextFrom('[data-field-name="total-transferred-(£)"]'),
        );
        $I->assertEquals(
            '0/7',
            $I->grabTextFrom('[data-field-name="transfers-completed"]'),
        );

        // Check the generation will overwrite existing transfers
        // In this case, using default template will create 6 transfers all with amount £0
        $I->amOnPage("/admin/monthend/income-transfers/{$newOrderId}/template-default");
        $I->amOnPage("/admin/monthend/income-transfers/{$newOrderId}");
        $I->seeNumberOfElements('#transfers-list tbody tr', 6);
        // Check transfers summary
        $I->assertEquals(
            '0.00/0.00',
            $I->grabTextFrom('[data-field-name="total-transferred-(£)"]'),
        );
        $I->assertEquals(
            '0/6',
            $I->grabTextFrom('[data-field-name="transfers-completed"]'),
        );

        // Create a monthend transfer for previous month
        $lastMonthOrderId = $I->createIncomeTransferOrder(
            'Royal Eversea Glades - Cambridge',
        );
        $I->amOnPage("/admin/monthend/income-transfers/{$lastMonthOrderId}");
        $I->addAssetTransferToOrder(
            'Expenses',
            '22.13',
            MonthEndService::DESCRIPTION_PRESETS['management'],
        );
        $I->addAssetTransferToOrder(
            'Distribution',
            '322.44',
            MonthEndService::DESCRIPTION_PRESETS['dividend'],
        );
        $I->addAssetTransferToOrder(
            'Treasury',
            '39.74',
            MonthEndService::DESCRIPTION_PRESETS['maintenance'],
        );
        $I->amOnPage("/admin/monthend/income-transfers/{$lastMonthOrderId}/edit");
        $I->fillField(
            '#monthend_order_edit_scheduledFor',
            new \DateTimeImmutable('first day of last month')->format('Y-m-d'),
        );
        $I->click('Save Changes');

        // Check transfer generation using another previous transfer order
        $I->amOnPage("/admin/monthend/income-transfers/{$newOrderId}/template");
        $I->seeElement('section#last-month-template');
        $I->seeLink(
            'Copy from Last Month',
            "/admin/monthend/income-transfers/{$newOrderId}/template-existing/{$lastMonthOrderId}",
        );
        $I->click('Copy from Last Month');
        $I->seeNumberOfElements('#transfers-list tbody tr', 3);
        $I->assertEquals(
            '0.00/384.31',
            $I->grabTextFrom('[data-field-name="total-transferred-(£)"]'),
        );
        $I->assertEquals(
            '0/3',
            $I->grabTextFrom('[data-field-name="transfers-completed"]'),
        );

        $I->see('Expenses', 'tbody tr:nth-child(1) [data-field="creditWalletId"]');
        $I->see(
            MonthEndService::DESCRIPTION_PRESETS['management'],
            'tbody tr:nth-child(1) [data-field="description"]',
        );
        $I->see('22.13', 'tbody tr:nth-child(1) [data-field="amount"]');

        $I->see('Distribution', 'tbody tr:nth-child(2) [data-field="creditWalletId"]');
        $I->see(
            MonthEndService::DESCRIPTION_PRESETS['dividend'],
            'tbody tr:nth-child(2) [data-field="description"]',
        );
        $I->see('322.44', 'tbody tr:nth-child(2) [data-field="amount"]');

        $I->see('Treasury', 'tbody tr:nth-child(3) [data-field="creditWalletId"]');
        $I->see(
            MonthEndService::DESCRIPTION_PRESETS['maintenance'],
            'tbody tr:nth-child(3) [data-field="description"]',
        );
        $I->see('39.74', 'tbody tr:nth-child(3) [data-field="amount"]');

        // If there are no income transfers from the previous month, won't see option to copy
        // Although default template will remain
        $I->amOnPage("/admin/monthend/income-transfers/{$lastMonthOrderId}/edit");
        $I->fillField(
            '#monthend_order_edit_scheduledFor',
            new \DateTimeImmutable('first day of -2 month')->format('Y-m-d'),
        );
        $I->click('Save Changes');

        $I->amOnPage("/admin/monthend/income-transfers/{$newOrderId}/template");
        $I->dontSeeElement('section#last-month-template');
        $I->dontSeeLink('Copy from Last Month');
        $I->see('Build with Default Template');
    }
}
