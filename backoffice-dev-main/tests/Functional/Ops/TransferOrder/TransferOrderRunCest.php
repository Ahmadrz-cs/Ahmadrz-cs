<?php

namespace App\Tests\Functional\Ops\TransferOrder;

use App\Entity\AbstractOrder;
use App\Tests\Support\FunctionalTester;

class TransferOrderRunCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function testTransferSingle(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/transfer-orders');
        $I->click('Create Transfer Order');
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name like' => 'Royal Eversea%',
        ]);
        $I->selectOption('#transfer_order_asset', ['value' => (string) $assetId]);
        $I->fillField(
            '#transfer_order_description',
            'Functional test run requests individually',
        );
        $I->click('Create Transfer Order');

        // Only orders with at least 1 transfer can be approved
        $I->addTransferToOrder(0.03);
        $I->addTransferToOrder(0.0);
        $I->click('Approve Transfer Order');
        $I->assertEquals('Approved', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals(
            $I::USER_SUPER_ADMIN,
            $I->grabTextFrom('[data-field-name="approved-by"]'),
        );
        $I->assertEquals(
            '2',
            $I->grabTextFrom('[data-field-name="transfers-pending"]'),
        );
        $I->assertEquals(
            '0',
            $I->grabTextFrom('[data-field-name="transfers-completed"]'),
        );

        $I->click('Transfer Single', '#transfers-list tbody tr:first-child');
        $I->see('Transfer successfully made');
        $I->assertEquals('In-progress', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals(
            '1',
            $I->grabTextFrom('[data-field-name="transfers-pending"]'),
        );
        $I->assertEquals(
            '1',
            $I->grabTextFrom('[data-field-name="transfers-completed"]'),
        );
        $I->see('View Transaction', '#transfers-list tbody tr:first-child');
        $I->seeLink('View Transaction');

        $I->click('Transfer Single', '#transfers-list tbody tr:nth-child(2)');
        $I->assertEquals('Completed', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals(
            '0',
            $I->grabTextFrom('[data-field-name="transfers-pending"]'),
        );
        $I->assertEquals(
            '2',
            $I->grabTextFrom('[data-field-name="transfers-completed"]'),
        );
        $I->dontSee('View Transaction', '#transfers-list tbody tr:nth-child(2)');
    }

    public function testRunOrder(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/transfer-orders');
        $I->click('Create Transfer Order');
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name like' => 'Royal Eversea%',
        ]);
        $I->selectOption('#transfer_order_asset', ['value' => (string) $assetId]);
        $I->fillField('#transfer_order_description', 'Functional test run order');
        $I->click('Create Transfer Order');

        // Only orders with at least 1 transfer can be approved
        $I->addTransferToOrder(0.03);
        $I->addTransferToOrder(0.0);
        $I->addTransferToOrder(0.02);

        // Check proportions are correct when total is below £1
        // 3p is 60% of the 5p total
        // 2p is 40% of the 5p total
        // Proportions are set to 3 decimal places
        $I->see('60.000', '#transfers-list tbody tr:nth-child(1)');
        $I->see('40.000', '#transfers-list tbody tr:nth-child(3)');

        $I->click('Approve Transfer Order');
        $I->assertEquals('Approved', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals(
            $I::USER_SUPER_ADMIN,
            $I->grabTextFrom('[data-field-name="approved-by"]'),
        );
        $I->assertEquals(
            '3',
            $I->grabTextFrom('[data-field-name="transfers-pending"]'),
        );
        $I->assertEquals(
            '0',
            $I->grabTextFrom('[data-field-name="transfers-completed"]'),
        );

        $I->click('Transfer Single', '#transfers-list tbody tr:first-child');
        $I->assertEquals('In-progress', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals(
            '2',
            $I->grabTextFrom('[data-field-name="transfers-pending"]'),
        );
        $I->assertEquals(
            '1',
            $I->grabTextFrom('[data-field-name="transfers-completed"]'),
        );
        $I->see('View Transaction', '#transfers-list tbody tr:first-child');

        $I->click('Run Transfer Order');
        $I->see('Transfer order successfully run');
        $I->assertEquals('Completed', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals(
            '0',
            $I->grabTextFrom('[data-field-name="transfers-pending"]'),
        );
        $I->assertEquals(
            '3',
            $I->grabTextFrom('[data-field-name="transfers-completed"]'),
        );
        $I->dontSee('View Transaction', '#transfers-list tbody tr:nth-child(2)');
        $I->see('View Transaction', '#transfers-list tbody tr:nth-child(3)');
    }

    public function testRunOrderCrashProgressSaved(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/transfer-orders');
        $I->click('Create Transfer Order');
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name like' => 'Royal Eversea%',
        ]);
        $I->selectOption('#transfer_order_asset', ['value' => (string) $assetId]);
        $I->fillField(
            '#transfer_order_description',
            'Test progress saved on crash midway',
        );
        $I->click('Create Transfer Order');

        // We'll add a total of 5 transfers + addition AbstractOrder::ISSUE_LIMIT number of transfers

        // Add some valid transfers
        $I->addTransferToOrder(0.03);
        $I->addTransferToOrder(0.04);

        // Add an invalid transfer (3rd in the sequence)
        $I->addTransferToOrder(0.02, creditWallet: 'invalid wallet');

        // Invalid transfers will be skipped and order will continue
        $I->addTransferToOrder(0.05);

        // Add AbstractOrder::ISSUE_LIMIT number of invalid transfers
        foreach (range(1, AbstractOrder::ISSUE_LIMIT) as $i) {
            $I->addTransferToOrder(0.01, creditWallet: "invalid wallet {$i}");
        }

        // This transfer will not be run as issue limit will cause the order to end early
        $I->addTransferToOrder(0.04);

        $I->click('Approve Transfer Order');
        $I->click('Run Transfer Order');
        $I->see('Unable to run transfer order');

        foreach (range(1, 5 + AbstractOrder::ISSUE_LIMIT) as $row) {
            // 5th to the penultimate transfer will fail due to invalid wallet
            if (
                $row == 3
                || in_array($row, range(5, 5 + AbstractOrder::ISSUE_LIMIT - 1))
            ) {
                $I->see(
                    'Failed',
                    "#transfers-list tbody tr:nth-child({$row}) td[data-field='status']",
                );
                $I->see(
                    'Transfer Single',
                    "#transfers-list tbody tr:nth-child({$row}) a",
                );
            } elseif ($row == (5 + AbstractOrder::ISSUE_LIMIT)) {
                // Very last transfer will not have been attempted
                $I->see(
                    'Pending',
                    "#transfers-list tbody tr:nth-child({$row}) td[data-field='status']",
                );
                $I->see(
                    'Transfer Single',
                    "#transfers-list tbody tr:nth-child({$row}) a",
                );
            } else {
                $I->see(
                    'Complete',
                    "#transfers-list tbody tr:nth-child({$row}) td[data-field='status']",
                );
                $I->see(
                    'View Transaction',
                    "#transfers-list tbody tr:nth-child({$row}) a",
                );
            }
        }
    }

    public function testForceCompleteOrderZeroed(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/transfer-orders');
        $I->click('Create Transfer Order');
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name like' => 'Royal Eversea%',
        ]);
        $I->selectOption('#transfer_order_asset', ['value' => (string) $assetId]);
        $I->fillField('#transfer_order_description', 'Functional test run order');
        $I->click('Create Transfer Order');

        // Only orders with at least 1 transfer can be approved
        $I->addTransferToOrder(0.03);
        $I->addTransferToOrder(0.01);
        $I->addTransferToOrder(0.02);

        $I->click('Approve Transfer Order');
        $I->assertEquals('Approved', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals(
            $I::USER_SUPER_ADMIN,
            $I->grabTextFrom('[data-field-name="approved-by"]'),
        );
        $I->assertEquals(
            '3',
            $I->grabTextFrom('[data-field-name="transfers-pending"]'),
        );
        $I->assertEquals(
            '0',
            $I->grabTextFrom('[data-field-name="transfers-completed"]'),
        );

        $I->click('Transfer Single', '#transfers-list tbody tr:first-child');
        $I->assertEquals('In-progress', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals(
            '2',
            $I->grabTextFrom('[data-field-name="transfers-pending"]'),
        );
        $I->assertEquals(
            '1',
            $I->grabTextFrom('[data-field-name="transfers-completed"]'),
        );
        $I->see('View Transaction', '#transfers-list tbody tr:first-child');

        $I->click('Force Complete Transfer Order');
        $I->click('Force Complete Transfer Order');
        $I->assertEquals('Completed', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals(
            '0',
            $I->grabTextFrom('[data-field-name="transfers-pending"]'),
        );
        $I->assertEquals(
            '3',
            $I->grabTextFrom('[data-field-name="transfers-completed"]'),
        );

        // Second and third transfer has been zeroed
        $I->see('0.00', '#transfers-list tbody tr:nth-child(2) [data-field="amount"]');
        $I->see('0.00', '#transfers-list tbody tr:nth-child(3) [data-field="amount"]');
        $I->dontSee('View Transaction', '#transfers-list tbody tr:nth-child(2)');
        $I->dontSee('View Transaction', '#transfers-list tbody tr:nth-child(3)');
    }

    public function testForceCompleteOrderAbandonedTruncate(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/transfer-orders');
        $I->click('Create Transfer Order');
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name like' => 'Royal Eversea%',
        ]);
        $I->selectOption('#transfer_order_asset', ['value' => (string) $assetId]);
        $I->fillField('#transfer_order_description', 'Functional test run order');
        $I->click('Create Transfer Order');

        // Only orders with at least 1 transfer can be approved
        $I->addTransferToOrder(0.03);
        $I->addTransferToOrder(0.01);
        $I->addTransferToOrder(0.02);

        $I->click('Approve Transfer Order');
        $I->assertEquals('Approved', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals(
            $I::USER_SUPER_ADMIN,
            $I->grabTextFrom('[data-field-name="approved-by"]'),
        );
        $I->assertEquals(
            '3',
            $I->grabTextFrom('[data-field-name="transfers-pending"]'),
        );
        $I->assertEquals(
            '0',
            $I->grabTextFrom('[data-field-name="transfers-completed"]'),
        );

        $I->click('Transfer Single', '#transfers-list tbody tr:first-child');
        $I->assertEquals('In-progress', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals(
            '2',
            $I->grabTextFrom('[data-field-name="transfers-pending"]'),
        );
        $I->assertEquals(
            '1',
            $I->grabTextFrom('[data-field-name="transfers-completed"]'),
        );
        $I->see('View Transaction', '#transfers-list tbody tr:first-child');

        $I->click('Abandon Transfer Order');
        $I->click('Abandon Transfer Order');
        $I->assertEquals('Abandoned', $I->grabTextFrom('[data-field-name="status"]'));

        // Force complete is available on abandoned orders
        $I->click('Force Complete Transfer Order');
        $I->fillField('#action_confirmation_reason', 'Truncated Test');
        $I->checkOption('#action_confirmation_truncate');
        $I->click('Force Complete Transfer Order');
        $I->assertEquals('Completed', $I->grabTextFrom('[data-field-name="status"]'));
        $I->see('Truncated Test', '[data-field-name="description"]');
        // Truncate will delete the incomplete transfers, so only the completed one is left
        $I->assertEquals(
            '0',
            $I->grabTextFrom('[data-field-name="transfers-pending"]'),
        );
        $I->assertEquals(
            '1',
            $I->grabTextFrom('[data-field-name="transfers-completed"]'),
        );
    }
}
