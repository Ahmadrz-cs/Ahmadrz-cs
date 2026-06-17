<?php

namespace App\Tests\Functional\Ops\TransferOrder;

use App\Entity\AbstractOrder;
use App\Entity\Enum\TransferType;
use App\Service\Manager\AssetManagerV2;
use App\Tests\Support\FunctionalTester;

class TransferOrderManageCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function editTransferOrderInfo(FunctionalTester $I): void
    {
        /**
         * Go to manage order via index link
         * Click on button "Edit Transfer Order"
         * [TODO] Check that asset is disabled when there is at least 1 transfer
         * Edit and discard -> no changes
         * Edit and save -> no changes
         * Approve
         * See restricted editing
         */
        $I->amOnPage('/admin/transfer-orders');
        $I->click('Create Transfer Order');
        $I->fillField('#transfer_order_description', 'Edit transfer order test');
        $I->click('Create Transfer Order');
        $I->amOnPage('/admin/transfer-orders');
        $I->click('Manage', '#transfer-order-list tbody tr:first-child');
        $infoFields = ['asset', 'scheduled-for', 'description'];
        $expectedInfo = [
            'transfer-type' => ucfirst(TransferType::AssetIncomeProcessing->value),
            'scheduled-for' => '2022-10-10',
            'description' => 'Automated test changed description',
        ];
        $currentInfo = [];
        foreach ($infoFields as $fieldName) {
            $currentInfo[$fieldName] = $I->grabTextFrom('[data-field-name="'
            . $fieldName
            . '"]');
        }

        $I->click('Edit Transfer Order');
        $I->selectOption('#transfer_order_transferType', [
            'value' => TransferType::AssetIncomeProcessing->value,
        ]);
        $I->fillField('#transfer_order_scheduledFor', $expectedInfo['scheduled-for']);
        $I->fillField('#transfer_order_description', $expectedInfo['description']);
        $I->click('Discard Changes');
        foreach ($infoFields as $fieldName) {
            $actual = $I->grabTextFrom('[data-field-name="' . $fieldName . '"]');
            $I->assertEquals($currentInfo[$fieldName], $actual);
        }

        $I->click('Edit Transfer Order');
        $I->selectOption('#transfer_order_transferType', [
            'value' => TransferType::AssetIncomeProcessing->value,
        ]);
        $I->fillField('#transfer_order_scheduledFor', $expectedInfo['scheduled-for']);
        $I->fillField('#transfer_order_description', $expectedInfo['description']);
        $I->click('Save Changes');
        foreach ($expectedInfo as $fieldName => $expected) {
            $actual = $I->grabTextFrom('[data-field-name="' . $fieldName . '"]');
            $I->assertEquals($expected, $actual);
        }
    }

    public function testAddAndDeleteTransfers(FunctionalTester $I): void
    {
        /**
         * Create a fresh transfer order
         * Add Transfer -> changes to summary
         */
        $debitWallet = 'wlt_m_01HW3DETE9YHAGN7GEGAH1PF65';
        $creditWallet = 'wlt_m_01HW3DD8S6MFPYGVC0FPBHXAF2';
        $transferDescription = 'Add and delete transfers test';
        $newTransferAmount = 1.82;

        $I->amOnPage('/admin/transfer-orders');
        $I->click('Create Transfer Order');
        $I->fillField('#transfer_order_description', 'Add and delete transfer test');
        $I->click('Create Transfer Order');
        $transferOrderId = $I->grabTextFrom('[data-field-name="order-id"]');
        $I->seeCurrentUrlEquals('/admin/transfer-orders/'
        . $transferOrderId
        . '/manage');

        $I->dontSeeLink('Go to Monthend Dashboard');
        $infoFields = ['total-to-transfer-(£)', 'total-transfers'];
        $currentInfo = [];
        foreach ($infoFields as $fieldName) {
            $currentInfo[$fieldName] = $I->grabTextFrom('[data-field-name="'
            . $fieldName
            . '"]');
        }

        // Add first transfer and make a note of payee options
        $I->click('Add Transfer');
        $I->seeCurrentUrlEquals('/admin/transfer-orders/'
        . $transferOrderId
        . '/add-transfer');
        $I->fillField('#transfer_request_debitWalletId', $debitWallet);
        $I->fillField('#transfer_request_creditWalletId', $creditWallet);
        $I->fillField('#transfer_request_description', $transferDescription);
        $I->fillField('#transfer_request_amount', $newTransferAmount);
        $I->click('Add Transfer');

        // check the transfer summaries and table have updated
        $expectedInfo = [
            'total-to-transfer-(£)' => $newTransferAmount,
            'total-transfers' => 1,
        ];
        foreach ($expectedInfo as $fieldName => $expected) {
            $actual = $I->grabTextFrom('[data-field-name="' . $fieldName . '"]');
            $I->assertEquals($expected, $actual);
        }
        $I->assertCount(
            $expectedInfo['total-transfers'],
            $I->grabMultiple('#transfers-list tbody tr'),
        );
        $I->see($newTransferAmount, '#transfers-list tbody tr:first-child');

        // check the transfer table row contains the correct info
        $I->see("$debitWallet", '#transfers-list tbody tr td');
        $I->see("$creditWallet", '#transfers-list tbody tr td');
        $I->see("$transferDescription", '#transfers-list tbody tr td');
        $I->see("$newTransferAmount", '#transfers-list tbody tr td');

        // check deletion of transfer updates the order properly
        $expectedInfo = [
            'total-to-transfer-(£)' => 0.00,
            'total-transfers' => 0,
        ];
        $I->click('Delete', '#transfers-list tbody tr:first-child');
        foreach ($expectedInfo as $fieldName => $expected) {
            $actual = $I->grabTextFrom('[data-field-name="' . $fieldName . '"]');
            $I->assertEquals($expected, $actual);
        }
        $I->assertCount(
            $expectedInfo['total-transfers'],
            $I->grabMultiple('#transfers-list tbody tr'),
        );
        $I->dontSee($newTransferAmount, '#transfers-list tbody tr:first-child');
    }

    public function testAddAssetTransfers(FunctionalTester $I): void
    {
        $activeAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Clarence Hold A - Camden',
        ]);
        $transferDescription = 'Move from treasury to distribution to pay shareholders';
        $newTransferAmount = 0.17;

        // Create new transfer order without asset
        $I->amOnPage('/admin/transfer-orders');
        $I->click('Create Transfer Order');
        $I->fillField('#transfer_order_description', 'Add asset transfer test');
        $I->click('Create Transfer Order');
        $transferOrderId = $I->grabTextFrom('[data-field-name="order-id"]');
        $I->seeCurrentUrlEquals('/admin/transfer-orders/'
        . $transferOrderId
        . '/manage');

        // Without a linked asset, no button to add asset transfer
        $I->dontSeeLink('Add Asset Transfer');
        // Trying to go to the page manually results in a warning
        $I->amOnPage("/admin/transfer-orders/{$transferOrderId}/add-asset-transfer");
        $I->see(
            'Asset transfers can only be created if the order is linked to an asset with a wallet',
        );

        // Link an asset to the transfer
        $I->click('Edit Transfer Order');
        $I->selectOption('#transfer_order_asset', (string) $activeAsset);
        $I->click('Save Changes');
        // Check the asset is now linked to the transfer order
        $I->see('Clarence Hold A - Camden', '[data-field-name=asset]');
        $I->seeLink(
            'View Wallet Balances',
            "/admin/asset/{$activeAsset}/manage-wallets",
        );

        // Attempt to add an asset transfer
        $I->click('Add Asset Transfer');
        $I->seeCurrentUrlEquals(
            "/admin/transfer-orders/{$transferOrderId}/add-asset-transfer",
        );
        // See all asset wallets are available as choices
        foreach (AssetManagerV2::SUPPORTED_WALLETS as $walletType) {
            $I->see($walletType, 'select#asset_transfer_request_debitWalletId option');
            $I->see($walletType, 'select#asset_transfer_request_creditWalletId option');
        }
        // Superadmin should also be available for debit wallet
        $superadminWallet = $I->grabFromDatabase('users', 'mangoPayWalletId', [
            'username' => $I::USER_SUPER_ADMIN,
        ]);
        $I->see(
            'Superadmin',
            "#asset_transfer_request_debitWalletId option[value='{$superadminWallet}']",
        );
        $I->selectOption('#asset_transfer_request_debitWalletId', 'Treasury');
        $I->selectOption('#asset_transfer_request_creditWalletId', 'Distribution');
        $I->fillField('#asset_transfer_request_amount', $newTransferAmount);
        $I->fillField('#asset_transfer_request_description', $transferDescription);
        $I->click('Add Transfer');

        // check the transfer summaries and table have updated like adding transfers normally would
        $expectedInfo = [
            'total-to-transfer-(£)' => $newTransferAmount,
            'total-transfers' => 1,
        ];
        foreach ($expectedInfo as $fieldName => $expected) {
            $actual = $I->grabTextFrom('[data-field-name="' . $fieldName . '"]');
            $I->assertEquals($expected, $actual);
        }
        $I->assertCount(
            $expectedInfo['total-transfers'],
            $I->grabMultiple('#transfers-list tbody tr'),
        );
        $I->see($newTransferAmount, '#transfers-list tbody tr:first-child');

        // check the transfer table row contains the correct info - in particular the superadmin wallet
        $I->see(
            $I::FIXTURE_WALLETS['treasury'],
            "#transfers-list tbody tr td[data-field='debitWalletId']",
        );
        $I->see(
            $I::FIXTURE_WALLETS['distribution'],
            "#transfers-list tbody tr td[data-field='creditWalletId']",
        );
        $I->see(
            "$transferDescription",
            "#transfers-list tbody tr td[data-field='description']",
        );
        $I->see(
            "$newTransferAmount",
            "#transfers-list tbody tr td[data-field='amount']",
        );
    }

    public function testEditTransfers(FunctionalTester $I): void
    {
        /**
         * Go to specific order management
         * Edit and discard -> no changes
         * Edit and save -> changes
         */
        $I->amOnPage('/admin/transfer-orders/1/manage');
        $infoFields = ['total-to-transfer-(£)', 'total-transfers'];
        $currentInfo = [];
        foreach ($infoFields as $fieldName) {
            $currentInfo[$fieldName] = $I->grabTextFrom('[data-field-name="'
            . $fieldName
            . '"]');
        }

        $walletModifier = 'EDT';
        $descriptionModifier = ' Edited';
        $amountModifier = '14.53';

        $I->click('Edit', '#transfers-list tbody tr:first-child');
        $originalDebitWallet = $I->grabValueFrom('#transfer_request_debitWalletId');
        $originalCreditWallet = $I->grabValueFrom('#transfer_request_creditWalletId');
        $originalDescription = $I->grabValueFrom('#transfer_request_description');
        $originalAmount = $I->grabValueFrom('#transfer_request_amount');
        $I->fillField(
            '#transfer_request_debitWalletId',
            $originalDebitWallet . $walletModifier,
        );
        $I->fillField(
            '#transfer_request_creditWalletId',
            $originalCreditWallet . $walletModifier,
        );
        $I->fillField(
            '#transfer_request_description',
            $originalDescription . $descriptionModifier,
        );
        $I->fillField(
            '#transfer_request_amount',
            (float) $originalAmount + (float) $amountModifier,
        );
        $I->click('Discard Changes');
        $I->see($originalAmount, '#transfers-list tbody tr:first-child');
        foreach ($infoFields as $fieldName) {
            $I->see($currentInfo[$fieldName], '[data-field-name="' . $fieldName . '"]');
        }

        $I->click('Edit', '#transfers-list tbody tr:first-child');
        $I->assertEquals(
            $originalDebitWallet,
            $I->grabValueFrom('#transfer_request_debitWalletId'),
        );
        $I->assertEquals(
            $originalCreditWallet,
            $I->grabValueFrom('#transfer_request_creditWalletId'),
        );
        $I->assertEquals(
            $originalDescription,
            $I->grabValueFrom('#transfer_request_description'),
        );
        $I->assertEquals(
            $originalAmount,
            $I->grabValueFrom('#transfer_request_amount'),
        );
        $I->fillField(
            '#transfer_request_debitWalletId',
            $originalDebitWallet . $walletModifier,
        );
        $I->fillField(
            '#transfer_request_creditWalletId',
            $originalCreditWallet . $walletModifier,
        );
        $I->fillField(
            '#transfer_request_description',
            $originalDescription . $descriptionModifier,
        );
        $I->fillField(
            '#transfer_request_amount',
            (float) $originalAmount + (float) $amountModifier,
        );
        $I->click('Save Changes');
        $I->see(
            $originalDebitWallet . $walletModifier,
            '#transfers-list tbody tr:first-child',
        );
        $I->see(
            $originalCreditWallet . $walletModifier,
            '#transfers-list tbody tr:first-child',
        );
        $I->see(
            $originalDescription . $descriptionModifier,
            '#transfers-list tbody tr:first-child',
        );
        $I->see(
            $originalAmount + (float) $amountModifier,
            '#transfers-list tbody tr:first-child',
        );
        $I->see(
            number_format(
                $currentInfo['total-to-transfer-(£)'] + (float) $amountModifier,
                2,
            ),
            '[data-field-name="total-to-transfer-(£)"]',
        );

        $I->click('Edit', '#transfers-list tbody tr:first-child');
        $I->assertEquals(
            $originalDebitWallet . $walletModifier,
            $I->grabValueFrom('#transfer_request_debitWalletId'),
        );
        $I->assertEquals(
            $originalCreditWallet . $walletModifier,
            $I->grabValueFrom('#transfer_request_creditWalletId'),
        );
        $I->assertEquals(
            $originalDescription . $descriptionModifier,
            $I->grabValueFrom('#transfer_request_description'),
        );
        $I->assertEquals(
            number_format($originalAmount + (float) $amountModifier, 2),
            $I->grabValueFrom('#transfer_request_amount'),
        );
    }

    public function testClearTransfers(FunctionalTester $I): void
    {
        // Create a new transfer order with add some transfers
        $I->amOnPage('/admin/transfer-orders/create');
        $I->fillField('#transfer_order_description', 'Clear transfers test');
        $I->click('Create Transfer Order');

        $orderIds = $I->grabColumnFromDatabase('transfer_order', 'id');
        rsort($orderIds);
        $transferOrderId = $orderIds[0];
        $I->amOnPage("/admin/transfer-orders/{$transferOrderId}/clear-transfers");
        $I->see(
            'You are about to delete all transfers from a transfer order. This cannot be undone!',
        );
        $I->amOnPage("/admin/transfer-orders/{$transferOrderId}/manage");

        $debitWallet = 'wlt_m_01HW3DETE9YHAGN7GEGAH1PF65';
        $creditWallet = 'wlt_m_01HW3DD8S6MFPYGVC0FPBHXAF2';
        $transferDescription = 'Clear transfers test';
        $newTransferAmount = 12.34;
        for ($i = 0; $i <= 2; $i++) {
            $I->click('Add Transfer');
            $I->fillField('#transfer_request_debitWalletId', $debitWallet);
            $I->fillField('#transfer_request_creditWalletId', $creditWallet);
            $I->fillField('#transfer_request_description', $transferDescription);
            $I->fillField('#transfer_request_amount', $newTransferAmount);
            $I->click('Add Transfer');
        }
        $I->amOnPage("/admin/transfer-orders/{$transferOrderId}/manage");
        $numberOfTransfers = $I->grabTextFrom('[data-field-name="total-transfers"]');
        $I->assertEquals(3, $numberOfTransfers);

        // Clear the transfers
        $I->seeLink(
            'Clear All Transfers',
            "/admin/transfer-orders/{$transferOrderId}/clear-transfers",
        );
        $I->amOnPage("/admin/transfer-orders/{$transferOrderId}/clear-transfers");
        $I->click('Clear All Transfers');

        // Check the transfers are all gone
        $I->seeCurrentUrlEquals('/admin/transfer-orders/'
        . $transferOrderId
        . '/manage');
        $numberOfTransfers = $I->grabTextFrom('[data-field-name="total-transfers"]');
        $I->assertEquals(0, $numberOfTransfers);
    }

    public function testApprovedEditingRestrictions(FunctionalTester $I): void
    {
        $restrictedStates = [
            AbstractOrder::STATE_APPROVED,
            AbstractOrder::STATE_CLOSED,
            AbstractOrder::STATE_IN_PROGRESS,
            AbstractOrder::STATE_COMPLETED,
            AbstractOrder::STATE_ABANDONED,
        ];
        foreach ($restrictedStates as $state) {
            $sampleOrderId = $I->grabFromDatabase('transfer_order', 'id', [
                'status' => $state,
            ]);
            $I->amOnPage('/admin/transfer-orders/' . $sampleOrderId . '/edit');

            $I->see('Editing is restricted');
            $I->seeElement('#transfer_order_scheduledFor', ['disabled' => 'disabled']);
            $I->seeElement('#transfer_order_asset', ['disabled' => 'disabled']);
            $I->seeElement('#transfer_order_description');
            $I->dontSeeElement('#transfer_order_description', [
                'disabled' => 'disabled',
            ]);

            // Check transfer request can no longer be changed or added
            $I->amOnPage('/admin/transfer-orders/' . $sampleOrderId . '/manage');
            $transferId = trim($I->grabTextFrom(
                '#transfers-list tbody tr:first-child [data-field="id"]',
            ));

            // Transfer requests are still editable but only the description field
            $I->amOnPage('/admin/transfer-requests/' . $transferId . '/edit');
            $I->seeCurrentUrlEquals('/admin/transfer-requests/'
            . $transferId
            . '/edit');
            $I->see('Editing is restricted outside draft mode');
            $I->seeElement('#transfer_request_debitWalletId', [
                'disabled' => 'disabled',
            ]);
            $I->seeElement('#transfer_request_creditWalletId', [
                'disabled' => 'disabled',
            ]);
            $I->seeElement('#transfer_request_description');
            $I->dontSeeElement('#transfer_request_description', [
                'disabled' => 'disabled',
            ]);
            $I->seeElement('#transfer_request_amount', ['disabled' => 'disabled']);

            $I->amOnPage('/admin/transfer-orders/' . $sampleOrderId . '/add-transfer');
            $I->seeCurrentUrlEquals('/admin/transfer-orders/'
            . $sampleOrderId
            . '/manage');
            $I->see('Transfers can only be added when the order is in draft mode');

            $I->amOnPage('/admin/transfer-requests/' . $transferId . '/delete');
            $I->seeCurrentUrlEquals('/admin/transfer-orders/'
            . $sampleOrderId
            . '/manage');
            $I->see('Transfers can only be removed when the order is in draft mode');

            $I->amOnPage('/admin/transfer-orders/'
            . $sampleOrderId
            . '/clear-transfers');
            $I->seeCurrentUrlEquals('/admin/transfer-orders/'
            . $sampleOrderId
            . '/manage');
            $I->see('Transfers can only be cleared when the order is in draft mode');
        }
    }

    public function testOrderApproveAndRevoke(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/transfer-orders');
        $I->click('Create Transfer Order');
        $I->fillField(
            '#transfer_order_description',
            bin2hex(random_bytes(8)) . 'apprev',
        );
        $I->click('Create Transfer Order');

        $orderIds = $I->grabColumnFromDatabase('transfer_order', 'id');
        rsort($orderIds);
        $I->amOnPage("/admin/transfer-orders/{$orderIds[0]}/manage");

        // Only orders with at least 1 transfer can be approved
        $I->addTransferToOrder();
        $I->click('Approve Transfer Order');
        $I->assertEquals('Approved', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals(
            $I::USER_SUPER_ADMIN,
            $I->grabTextFrom('[data-field-name="approved-by"]'),
        );

        $I->click('Unapprove Transfer Order');
        $I->assertEquals('Draft', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals('-', $I->grabTextFrom('[data-field-name="approved-by"]'));
    }

    public function testOrderCloseAndReopen(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/transfer-orders');
        $I->click('Create Transfer Order');
        $I->click('Create Transfer Order');
        $transferOrderId = $I->grabTextFrom('[data-field-name="order-id"]');
        $I->assertEquals('Draft', $I->grabTextFrom('[data-field-name="status"]'));

        $I->click('Reject Transfer Order');
        $I->seeCurrentUrlEquals('/admin/transfer-orders/'
        . $transferOrderId
        . '/reject');
        $I->see('Transfer Order #' . $transferOrderId);
        $I->click('Reject Transfer Order');
        $I->assertEquals('Closed', $I->grabTextFrom('[data-field-name="status"]'));

        $I->click('Reopen Transfer Order');
        $I->assertEquals('Draft', $I->grabTextFrom('[data-field-name="status"]'));

        // try to close approved orders - these reopen to draft
        $I->addTransferToOrder();
        $I->click('Approve Transfer Order');
        $I->assertEquals('Approved', $I->grabTextFrom('[data-field-name="status"]'));
        $I->click('Reject Transfer Order');
        $I->seeCurrentUrlEquals('/admin/transfer-orders/'
        . $transferOrderId
        . '/reject');
        $I->click('Reject Transfer Order');
        $I->assertEquals('Closed', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals('-', $I->grabTextFrom('[data-field-name="approved-by"]'));
        $I->click('Reopen Transfer Order');
        $I->assertEquals('Draft', $I->grabTextFrom('[data-field-name="status"]'));
    }

    public function testAbandonOrder(FunctionalTester $I): void
    {
        $transferOrderId = $I->grabFromDatabase('transfer_order', 'id', [
            'status' => AbstractOrder::STATE_IN_PROGRESS,
        ]);
        $I->amOnPage('/admin/transfer-orders/' . $transferOrderId . '/manage');
        $I->seeLink(
            'Force Complete Transfer Order',
            '/admin/transfer-orders/' . $transferOrderId . '/force-complete',
        );
        $I->click('Abandon Transfer Order');
        $I->seeCurrentUrlEquals('/admin/transfer-orders/'
        . $transferOrderId
        . '/abandon');
        $I->see('This cannot be undone');
        $I->click('Abandon Transfer Order');
        $I->assertEquals('Abandoned', $I->grabTextFrom('[data-field-name="status"]'));
        $I->click('Force Complete Transfer Order');
        $I->seeCurrentUrlEquals('/admin/transfer-orders/'
        . $transferOrderId
        . '/force-complete');

        // undo changes to the affected transfer order
        $I->updateInDatabase(
            'transfer_order',
            ['status' => AbstractOrder::STATE_IN_PROGRESS],
            ['id' => $transferOrderId],
        );
    }
}
