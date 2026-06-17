<?php

namespace App\Tests\Functional\Ops\MonthEnd\IncomeDisaggregation;

use App\Entity\Enum\TransferType;
use App\Tests\Support\FunctionalTester;

class IncomeDisaggregationManageCest
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
        $newOrderId = $I->createIncomeDisaggregationOrder();
        $I->amOnPage("/admin/monthend/income-disaggregations/{$newOrderId}");

        // Should see a prompt to configure transfers
        $I->seeLink(
            'Configure Transfers',
            "/admin/monthend/income-disaggregations/{$newOrderId}#transfers-list",
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
        // $I->seeElement('section#about-transfer [data-field-name^="fee-collection-wallet"]');
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
        // Check "existing-order-warning" where 1 or more Income Disaggregations have the same monthend period
        $newOrderId = $I->createIncomeDisaggregationOrder();
        $secondOrderId = $I->createIncomeDisaggregationOrder();
        $I->seeElement('section#existing-order-warning');
        $I->seeElement(
            "section#existing-order-warning tr[data-transfer-order-id='{$newOrderId}']",
        );
        $I->seeLink('View', "/admin/monthend/income-disaggregations/{$newOrderId}");

        $I->amOnPage("/admin/monthend/income-disaggregations/{$secondOrderId}/edit");
        $I->fillField('#monthend_order_edit_scheduledFor', '2016-04-08');
        $I->click('Save Changes');
        $I->dontSeeElement('section#existing-order-warning');

        // Change the monthend back to current month
        $I->amOnPage("/admin/monthend/income-disaggregations/{$secondOrderId}/edit");
        $I->fillField('#monthend_order_edit_scheduledFor', date('Y-m-d'));
        $I->click('Save Changes');
        $I->seeElement('section#existing-order-warning');
    }

    public function testEditMonthendOrder(FunctionalTester $I): void
    {
        $newOrderId = $I->createIncomeDisaggregationOrder();
        $I->amOnPage("/admin/monthend/income-disaggregations/{$newOrderId}");
        $I->see('Edit Order', '#about-transfer a');
        $I->click('Edit Order');

        $I->seeCurrentUrlEquals(
            "/admin/monthend/income-disaggregations/{$newOrderId}/edit",
        );
        $I->seeLink('Back', "/admin/monthend/income-disaggregations/{$newOrderId}");
        $I->seeLink(
            'Discard Changes',
            "/admin/monthend/income-disaggregations/{$newOrderId}",
        );

        $newDate = new \DateTime('2020-04-08');
        $newDescription = 'Edit monthend income disaggregation order description test';
        $I->fillField('#monthend_order_edit_scheduledFor', $newDate->format('Y-m-d'));
        $I->fillField('#monthend_order_edit_description', $newDescription);
        $I->click('Save Changes');

        $I->seeCurrentUrlEquals("/admin/monthend/income-disaggregations/{$newOrderId}");
        $I->assertEquals(
            $newDate->format('Y-m'),
            $I->grabTextFrom('[data-field-name="scheduled-monthend"]'),
        );
        $I->assertEquals(
            $newDescription,
            $I->grabTextFrom('[data-field-name="description"]'),
        );
    }

    public function testNonIncomeDisaggregationWarning(FunctionalTester $I): void
    {
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);
        $I->amOnPage('/admin/transfer-orders/create');
        $I->fillField('#transfer_order_description', 'Non Income Disaggregation test');
        $I->selectOption('#transfer_order_transferType', [
            'value' => TransferType::IncomeDisaggregation->value,
        ]);
        $I->selectOption('#transfer_order_asset', ['value' => (string) $assetId]);
        $I->click('Create Transfer Order');
        $newOrderId = $I->grabTextFrom('[data-field-name="order-id"]');
        $I->seeCurrentUrlEquals("/admin/transfer-orders/{$newOrderId}/manage");

        // Manage page shows a warning
        $I->amOnPage("/admin/monthend/income-disaggregations/{$newOrderId}");
        $I->seeCurrentUrlEquals("/admin/monthend/income-disaggregations/{$newOrderId}");
        $I->seeElement('section#validation-issues-warning');

        // Most other Income Disaggregation routes will send you back to the manage page
        $protectedPaths = [
            // "/admin/monthend/income-disaggregations/{$newOrderId}/generate"
        ];
        foreach ($protectedPaths as $path) {
            $I->amOnPage($path);
            $I->see('not a Income Disaggregation order');
            $I->seeCurrentUrlEquals(
                "/admin/monthend/income-disaggregations/{$newOrderId}",
            );
        }
    }

    public function testTransitionRedirect(FunctionalTester $I): void
    {
        $newOrderId = $I->createIncomeDisaggregationOrder();
        $I->amOnPage("/admin/monthend/income-disaggregations/{$newOrderId}");
        // Need at least 2 transfers to be able to abandon later
        // $I->amOnPage("/admin/monthend/income-disaggregations/{$newOrderId}/generate");
        $I->addTransferToIncomeDisaggregationOrder();
        $I->addTransferToIncomeDisaggregationOrder();
        $I->assertEquals('Draft', $I->grabTextFrom('[data-field-name="status"]'));

        // Reject
        $I->click('Reject Transfer Order');
        $I->seeCurrentUrlEquals(
            "/admin/transfer-orders/{$newOrderId}/reject?redirectRoute=admin_monthend_income_disaggregation_manage",
        );
        $I->seeLink(
            'Discard Changes',
            "/admin/monthend/income-disaggregations/{$newOrderId}",
        );
        $I->click('Reject Transfer Order');
        $I->seeCurrentUrlEquals("/admin/monthend/income-disaggregations/{$newOrderId}");
        $I->assertEquals('Closed', $I->grabTextFrom('[data-field-name="status"]'));

        // Reopen
        $I->click('Reopen Transfer Order');
        $I->seeCurrentUrlEquals("/admin/monthend/income-disaggregations/{$newOrderId}");
        $I->assertEquals('Draft', $I->grabTextFrom('[data-field-name="status"]'));

        // Approve
        $I->click('Approve Transfer Order');
        $I->seeCurrentUrlEquals("/admin/monthend/income-disaggregations/{$newOrderId}");
        $I->assertEquals('Approved', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals(
            $I::USER_SUPER_ADMIN,
            $I->grabTextFrom('[data-field-name="approved-by"]'),
        );

        // Unapprove
        $I->click('Unapprove Transfer Order');
        $I->seeCurrentUrlEquals("/admin/monthend/income-disaggregations/{$newOrderId}");
        $I->assertEquals('Draft', $I->grabTextFrom('[data-field-name="status"]'));

        // // Abandon
        $I->click('Approve Transfer Order');
        $I->click('Transfer Single');
        $I->seeCurrentUrlEquals("/admin/monthend/income-disaggregations/{$newOrderId}");
        $I->assertEquals('In-progress', $I->grabTextFrom('[data-field-name="status"]'));
        $I->click('Abandon Transfer Order');
        $I->seeCurrentUrlEquals(
            "/admin/transfer-orders/{$newOrderId}/abandon?redirectRoute=admin_monthend_income_disaggregation_manage",
        );
        $I->seeLink(
            'Discard Changes',
            "/admin/monthend/income-disaggregations/{$newOrderId}",
        );
        $I->click('Abandon Transfer Order');
        $I->seeCurrentUrlEquals("/admin/monthend/income-disaggregations/{$newOrderId}");
        $I->assertEquals('Abandoned', $I->grabTextFrom('[data-field-name="status"]'));
    }

    public function testAddRemoveTransfers(FunctionalTester $I): void
    {
        $newOrderId = $I->createIncomeDisaggregationOrder();
        $I->amOnPage("/admin/monthend/income-disaggregations/{$newOrderId}");
        $I->click('Add Transfer');
        $I->seeCurrentUrlEquals(
            "/admin/monthend/income-disaggregations/{$newOrderId}/add-transfer",
        );
        $assetId = $I->grabAttributeFrom(
            '#assets-list tbody tr:first-child',
            'data-object-id',
        );
        $I->click('Add Transfer', '#assets-list tbody tr:first-child');
        $I->seeCurrentUrlEquals(
            "/admin/monthend/income-disaggregations/{$newOrderId}/add-transfer/{$assetId}",
        );
        $I->seeLink('Back', "/admin/monthend/income-disaggregations/{$newOrderId}");
        $I->seeLink(
            'Cancel',
            "/admin/monthend/income-disaggregations/{$newOrderId}/add-transfer",
        );
        $I->seeOptionIsSelected(
            '#asset_transfer_request_creditWalletId',
            'Settlement/actual',
        );
        $I->fillField('#asset_transfer_request_amount', '10.62');
        $I->click('Add Transfer');
        $I->seeCurrentUrlEquals("/admin/monthend/income-disaggregations/{$newOrderId}");

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
            ->modify('first day of -1 month')
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
            "Deposit rental income;{$assetSpv} {$assetName};For month {$previousMonth}",
            $I->grabTextFrom('tr [data-field="description"]'),
        );

        // Edit the transfer amount and description
        $I->click('Edit', '#transfers-list tbody tr:first-child');
        $I->seeCurrentUrlEquals(
            "/admin/transfer-requests/$transferRequestId/edit?restricted=1",
        );
        $I->seeLink('Cancel', "/admin/monthend/income-disaggregations/{$newOrderId}");
        $I->seeElement('#transfer_request_debitWalletId[disabled]');
        $I->seeElement('#transfer_request_creditWalletId[disabled]');
        $I->fillField(
            '#transfer_request_description',
            'Updated test transfer description',
        );
        $I->fillField('#transfer_request_amount', '9.92');
        $I->click('Save Changes');
        $I->seeCurrentUrlEquals("/admin/monthend/income-disaggregations/{$newOrderId}");
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
        $I->addTransferToIncomeDisaggregationOrder();
        $I->assertEquals(
            '0/2',
            $I->grabTextFrom('[data-field-name="transfers-completed"]'),
        );
        $I->click('Delete', '#transfers-list tbody tr:first-child');
        $I->seeCurrentUrlEquals("/admin/monthend/income-disaggregations/{$newOrderId}");
        $I->assertEquals(
            '0/1',
            $I->grabTextFrom('[data-field-name="transfers-completed"]'),
        );

        // Clear all remaining transfers
        $I->click('Clear All Transfers');
        $I->seeCurrentUrlEquals(
            "/admin/transfer-orders/{$newOrderId}/clear-transfers?redirectRoute=admin_monthend_income_disaggregation_manage",
        );
        $I->see('Confirm Deletion');
        $I->seeLink(
            'Discard Changes',
            "/admin/monthend/income-disaggregations/{$newOrderId}",
        );
        $I->click('Clear All Transfers');
        $I->seeCurrentUrlEquals("/admin/monthend/income-disaggregations/{$newOrderId}");
        $I->seeNumberOfElements('#transfers-list tbody tr', 0);
    }

    public function testCopyTransfers(FunctionalTester $I): void
    {
        $newOrderId = $I->createIncomeDisaggregationOrder();
        $I->amOnPage("/admin/monthend/income-disaggregations/{$newOrderId}");
        $I->click('Copy Transfers');
        $I->seeCurrentUrlEquals(
            "/admin/monthend/income-disaggregations/{$newOrderId}/copy-existing",
        );
        $existingOrderId = $I->grabAttributeFrom(
            '#suitable-orders-list tbody tr:first-child',
            'data-object-id',
        );
        $I->click('Preview Transfers', '#suitable-orders-list tbody tr:first-child');
        $I->seeCurrentUrlEquals(
            "/admin/monthend/income-disaggregations/{$newOrderId}/copy-existing/{$existingOrderId}",
        );
        $I->seeLink('Cancel', "/admin/monthend/income-disaggregations/{$newOrderId}");

        $expectedMonthendPeriod = new \DateTime()
            ->modify('-1 month')
            ->format('Y-m');
        $transfersExpected = $I->grabTextFrom(
            '[data-field-name="number-of-transfers"]',
        );
        $originalTotal = $I->grabTextFrom(
            '[data-field-name="total-value-to-transfer"]',
        );
        $amounts = $I->grabMultiple(
            '#transfers-preview-list tbody tr td[data-field="amount"]',
        );
        $assetLinks = $I->grabMultiple(
            '#transfers-preview-list tbody tr td[data-field="asset"] a',
            'href',
        );
        $fromWallets = $I->grabMultiple(
            '#transfers-preview-list tbody tr td[data-field="debitWalletId"]',
        );
        $toWallets = $I->grabMultiple(
            '#transfers-preview-list tbody tr td[data-field="creditWalletId"]',
        );

        $I->click('Generate Transfers');
        $I->seeCurrentUrlEquals("/admin/monthend/income-disaggregations/{$newOrderId}");
        $I->seeNumberOfElements('#transfers-list tbody tr', (int) $transfersExpected);
        $I->see(
            str_replace('£', '', $originalTotal),
            '[data-field-name="total-transferred-(£)"]',
        );
        // Transfers should be generated in the same order, so just need to check they match
        foreach (range(1, 4) as $row) {
            $assetlink = $assetLinks[$row - 1];
            $assetLinkParts = explode('/', $assetlink);
            $assetId = array_pop($assetLinkParts);
            $assetName = $I->grabFromDatabase('assets', 'name', ['id' => $assetId]);
            $assetSpv = $I->grabFromDatabase('assets', 'companyNumber', [
                'id' => $assetId,
            ]);
            $expectedDescription = "Deposit rental income;{$assetSpv} {$assetName};For month {$expectedMonthendPeriod}";

            $I->assertEquals($assetlink, $I->grabAttributeFrom(
                "#transfers-list tbody tr:nth-child({$row}) td[data-field='asset'] a",
                'href',
            ));
            $I->assertEquals(
                $fromWallets[$row - 1],
                $I->grabTextFrom(
                    "#transfers-list tbody tr:nth-child({$row}) td[data-field='debitWalletId']",
                ),
            );
            $I->assertEquals(
                $toWallets[$row - 1],
                $I->grabTextFrom(
                    "#transfers-list tbody tr:nth-child({$row}) td[data-field='creditWalletId']",
                ),
            );
            $I->assertEquals(
                $expectedDescription,
                $I->grabTextFrom(
                    "#transfers-list tbody tr:nth-child({$row}) td[data-field='description']",
                ),
            );
            $I->assertEquals(
                $amounts[$row - 1],
                $I->grabTextFrom(
                    "#transfers-list tbody tr:nth-child({$row}) td[data-field='amount']",
                ),
            );
        }
    }
}
