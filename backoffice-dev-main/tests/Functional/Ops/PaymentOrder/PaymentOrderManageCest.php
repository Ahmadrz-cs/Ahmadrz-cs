<?php

namespace App\Tests\Functional\Ops\PaymentOrder;

use App\Entity\Enum\PaymentType;
use App\Entity\PaymentOrder;
use App\Service\PaymentService;
use App\Tests\Support\FunctionalTester;

class PaymentOrderManageCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function editPaymentOrderInfo(FunctionalTester $I): void
    {
        /**
         * Go to manage order via index link
         * Click on button "Edit Payment Order"
         * [TODO] Check that asset is disabled when there is at least 1 payment
         * Edit and discard -> no changes
         * Edit and save -> no changes
         * Approve
         * See restricted editing
         */
        $I->amOnPage('/admin/payment-order');
        $I->click('Manage', '#payment-order-list tbody tr:first-child');
        $paymentOrderId = $I->grabTextFrom('[data-field-name="order-id"]');
        $I->seeCurrentUrlEquals('/admin/payment-order/' . $paymentOrderId . '/manage');

        // Create a new order to ensure we're in a known state
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);
        $I->amOnPage('/admin/payment-order/create');
        $I->selectOption('#payment_order_asset', ['value' => (string) $assetId]);
        $I->selectOption('#payment_order_debitWallet', ['value' => 'distribution']);
        $I->click('Create Payment Order');
        $paymentOrderId = $I->grabTextFrom('[data-field-name="order-id"]');
        $I->seeCurrentUrlEquals('/admin/payment-order/' . $paymentOrderId . '/manage');
        $I->seeLink('View Wallet Balances', "/admin/asset/{$assetId}/manage-wallets");

        // If distribution wallet set, no warning should appear
        $I->dontSee('Distribution Wallet Missing', '#missing-wallet-warning');
        $I->dontSee('Configure Asset Wallet', '#missing-wallet-warning a');
        $I->dontSeeLink(
            'Configure Asset Wallets',
            "/admin/asset/{$assetId}/manage-wallets",
        );

        $infoFields = ['payment-type', 'asset', 'scheduled-for', 'description'];
        $expectedInfo = [
            'payment-type' => 'Dividend',
            'scheduled-for' => '2022-10-10',
            'description' => 'Automated test changed description',
        ];
        $currentInfo = [];
        foreach ($infoFields as $fieldName) {
            $currentInfo[$fieldName] = $I->grabTextFrom('[data-field-name="'
            . $fieldName
            . '"]');
        }

        $I->click('Edit Payment Order');
        $I->selectOption(
            'form input[name="payment_order[paymentType]"]',
            $expectedInfo['payment-type'],
        );
        $I->fillField('#payment_order_scheduledFor', $expectedInfo['scheduled-for']);
        $I->fillField('#payment_order_description', $expectedInfo['description']);
        $I->click('Discard Changes');
        foreach ($infoFields as $fieldName) {
            $actual = $I->grabTextFrom('[data-field-name="' . $fieldName . '"]');
            $I->assertEquals($currentInfo[$fieldName], $actual);
        }

        $I->click('Edit Payment Order');
        $I->selectOption(
            'form input[name="payment_order[paymentType]"]',
            $expectedInfo['payment-type'],
        );
        $I->fillField('#payment_order_scheduledFor', $expectedInfo['scheduled-for']);
        $I->fillField('#payment_order_description', $expectedInfo['description']);
        $I->click('Save Changes');
        foreach ($expectedInfo as $fieldName => $expected) {
            $actual = $I->grabTextFrom('[data-field-name="' . $fieldName . '"]');
            $I->assertEquals($expected, $actual);
        }

        // If the asset is missing the distribution wallet, check warning is visible
        $originalWallet = $I->grabFromDatabase('assets', 'distributionWalletId', [
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);
        // Remove the distribution wallet
        $I->updateInDatabase(
            'assets',
            ['distributionWalletId' => null],
            ['name' => 'Royal Eversea Glades - Cambridge'],
        );
        // Check warning appears
        $I->amOnPage('/admin/payment-order/' . $paymentOrderId . '/manage');
        $I->see('Distribution Wallet Missing', '#missing-wallet-warning');
        $I->see('Configure Asset Wallet', '#missing-wallet-warning a');
        $I->seeLink(
            'Configure Asset Wallets',
            "/admin/asset/{$assetId}/manage-wallets",
        );
        // Restore the distribution wallet
        $I->updateInDatabase(
            'assets',
            ['distributionWalletId' => $originalWallet],
            ['name' => 'Royal Eversea Glades - Cambridge'],
        );

        // Check empty payment warning is not visible on an empty order
        $I->dontSeeElement('#empty-payments-warning');
        // And still doesn't appear if you add a non-zero payment
        $I->addPaymentToOrder(1);
        $I->dontSeeElement('#empty-payments-warning');
        // But does appear if you add an empty payment
        $I->addPaymentToOrder('0');
        $I->seeElement('#empty-payments-warning');
    }

    public function testApprovedEditingRestrictions(FunctionalTester $I): void
    {
        $restrictedStates = [
            PaymentOrder::STATE_APPROVED,
            PaymentOrder::STATE_CLOSED,
            PaymentOrder::STATE_IN_PROGRESS,
            PaymentOrder::STATE_COMPLETED,
            PaymentOrder::STATE_ABANDONED,
        ];
        foreach ($restrictedStates as $state) {
            $sampleOrderId = $I->grabFromDatabase('payment_order', 'id', [
                'status' => $state,
            ]);
            $I->amOnPage('/admin/payment-order/' . $sampleOrderId . '/edit');

            $I->see('Editing is restricted');
            $I->seeElement('form input[name="payment_order[paymentType]"]', [
                'disabled' => 'disabled',
            ]);
            $I->seeElement('#payment_order_scheduledFor', ['disabled' => 'disabled']);
            $I->seeElement('#payment_order_asset', ['disabled' => 'disabled']);
            $I->seeElement('#payment_order_description');
            $I->dontSeeElement('#payment_order_description', [
                'disabled' => 'disabled',
            ]);

            // Check payment request can no longer be changed or added
            $I->amOnPage('/admin/payment-order/' . $sampleOrderId . '/manage');
            $paymentId = trim($I->grabTextFrom(
                '#payments-list tbody tr:first-child [data-field="id"]',
            ));

            $I->amOnPage('/admin/payment-request/' . $paymentId . '/edit');
            $I->see('Payments can only be edited when the order is in draft mode');
            $I->seeCurrentUrlEquals('/admin/payment-order/'
            . $sampleOrderId
            . '/manage');

            $I->amOnPage('/admin/payment-order/' . $sampleOrderId . '/add-payment');
            $I->seeCurrentUrlEquals('/admin/payment-order/'
            . $sampleOrderId
            . '/manage');
            $I->see('Payments can only be added when the order is in draft mode');
            $I->amOnPage('/admin/payment-request/' . $paymentId . '/delete');
            $I->seeCurrentUrlEquals('/admin/payment-order/'
            . $sampleOrderId
            . '/manage');
            $I->see('Payments can only be removed when the order is in draft mode');

            $I->amOnPage('/admin/payment-order/' . $sampleOrderId . '/clear-payments');
            $I->seeCurrentUrlEquals('/admin/payment-order/'
            . $sampleOrderId
            . '/manage');
            $I->see('Payments can only be cleared when the order is in draft mode');
        }
    }

    public function testOrderApproveAndRevoke(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/payment-order');
        $I->click('Create Payment Order');
        $I->fillField(
            '#payment_order_description',
            bin2hex(random_bytes(8)) . 'apprev',
        );
        $I->click('Create Payment Order');

        $orderIds = $I->grabColumnFromDatabase('payment_order', 'id');
        rsort($orderIds);
        $I->amOnPage("/admin/payment-order/{$orderIds[0]}/manage");

        // Only orders with at least 1 payment can be approved
        $I->addPaymentToOrder();
        $I->click('Approve Payment Order');
        $I->assertEquals('Approved', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals(
            $I::USER_SUPER_ADMIN,
            $I->grabTextFrom('[data-field-name="approved-by"]'),
        );

        $I->click('Unapprove Payment Order');
        $I->assertEquals('Draft', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals('-', $I->grabTextFrom('[data-field-name="approved-by"]'));
    }

    public function testOrderCloseAndReopen(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/payment-order');
        $I->click('Create Payment Order');
        $I->click('Create Payment Order');
        $paymentOrderId = $I->grabTextFrom('[data-field-name="order-id"]');
        $I->assertEquals('Draft', $I->grabTextFrom('[data-field-name="status"]'));

        $I->click('Close Payment Order');
        $I->seeCurrentUrlEquals('/admin/payment-order/' . $paymentOrderId . '/close');
        $I->see('Payment Order #' . $paymentOrderId);
        $I->click('Close Payment Order');
        $I->assertEquals('Closed', $I->grabTextFrom('[data-field-name="status"]'));

        $I->click('Reopen Payment Order');
        $I->assertEquals('Draft', $I->grabTextFrom('[data-field-name="status"]'));

        // try to close approved orders - these reopen to draft
        $I->addPaymentToOrder();
        $I->click('Approve Payment Order');
        $I->assertEquals('Approved', $I->grabTextFrom('[data-field-name="status"]'));
        $I->click('Close Payment Order');
        $I->seeCurrentUrlEquals('/admin/payment-order/' . $paymentOrderId . '/close');
        $I->click('Close Payment Order');
        $I->assertEquals('Closed', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals('-', $I->grabTextFrom('[data-field-name="approved-by"]'));
        $I->click('Reopen Payment Order');
        $I->assertEquals('Draft', $I->grabTextFrom('[data-field-name="status"]'));
    }

    public function testAddAndDeletePayments(FunctionalTester $I): void
    {
        /**
         * Create a fresh payment order
         * Add Payment -> changes to summary
         */
        $newPaymentAmount = 1.82;
        $newPaymentShares = 34;

        $I->amOnPage('/admin/payment-order');
        $I->click('Create Payment Order');
        $I->selectOption(
            'form input[name="payment_order[paymentType]"]',
            PaymentType::Divestment->value,
        );
        $I->click('Create Payment Order');
        $paymentOrderId = $I->grabTextFrom('[data-field-name="order-id"]');
        $I->seeCurrentUrlEquals('/admin/payment-order/' . $paymentOrderId . '/manage');

        $infoFields = ['total-due-(£)', 'total-shareholding', 'total-payments'];
        $currentInfo = [];
        foreach ($infoFields as $fieldName) {
            $currentInfo[$fieldName] = $I->grabTextFrom('[data-field-name="'
            . $fieldName
            . '"]');
        }

        // Add first payment and make a note of payee options
        $I->click('Add Payment');
        $I->seeCurrentUrlEquals('/admin/payment-order/'
        . $paymentOrderId
        . '/add-payment');
        $totalPayees =
            count($I->grabMultiple('#payment_request_payee option', 'value')) - 1;
        $firstOption = $I->grabAttributeFrom(
            '#payment_request_payee option:nth-child(2)',
            'value',
        );
        $I->selectOption('#payment_request_payee', ['value' => $firstOption]);
        $I->fillField('#payment_request_amount', $newPaymentAmount);
        $I->fillField('#payment_request_shareholding', $newPaymentShares);
        $I->click('Add Payment');

        // Check that the first payee is no longer selectable for a new payment
        $I->amOnPage('/admin/payment-order/' . $paymentOrderId . '/add-payment');
        $remainingPayees = $I->grabMultiple('#payment_request_payee option', 'value');
        $I->assertNotContains($firstOption, $remainingPayees);

        // check the asset can no longer be changed when there is at least 1 payment
        $I->amOnPage('/admin/payment-order/' . $paymentOrderId . '/edit');
        $I->dontSeeElement('form input[name="payment_order[paymentType]"]', [
            'disabled' => 'disabled',
        ]);
        $I->dontSeeElement('#payment_order_scheduledFor', ['disabled' => 'disabled']);
        $I->seeElement('#payment_order_asset', ['disabled' => 'disabled']);
        $I->dontSeeElement('#payment_order_description', ['disabled' => 'disabled']);
        $I->amOnPage('/admin/payment-order/' . $paymentOrderId . '/manage');

        // check the payment summaries and table have updated
        $expectedInfo = [
            'total-due-(£)' => $newPaymentAmount,
            'total-shareholding' => $newPaymentShares,
            'total-payments' => 1,
        ];
        foreach ($expectedInfo as $fieldName => $expected) {
            $actual = $I->grabTextFrom('[data-field-name="' . $fieldName . '"]');
            $I->assertEquals($expected, $actual);
        }
        $I->assertCount(
            $expectedInfo['total-payments'],
            $I->grabMultiple('#payments-list tbody tr'),
        );
        $I->see($newPaymentAmount, '#payments-list tbody tr:first-child');
        $I->see($newPaymentShares, '#payments-list tbody tr:first-child');

        // check deletion of payment updates the order properly
        $expectedInfo = [
            'total-due-(£)' => 0.00,
            'total-shareholding' => 0,
            'total-payments' => 0,
        ];
        $I->click('Delete', '#payments-list tbody tr:first-child');
        foreach ($expectedInfo as $fieldName => $expected) {
            $actual = $I->grabTextFrom('[data-field-name="' . $fieldName . '"]');
            $I->assertEquals($expected, $actual);
        }
        $I->assertCount(
            $expectedInfo['total-payments'],
            $I->grabMultiple('#payments-list tbody tr'),
        );
        $I->dontSee($newPaymentAmount, '#payments-list tbody tr:first-child');

        // check the asset can now be changed when there are no payments remaining
        $I->amOnPage('/admin/payment-order/' . $paymentOrderId . '/edit');
        $I->dontSeeElement('form input[name="payment_order[paymentType]"]', [
            'disabled' => 'disabled',
        ]);
        $I->dontSeeElement('#payment_order_scheduledFor', ['disabled' => 'disabled']);
        $I->dontSeeElement('#payment_order_asset', ['disabled' => 'disabled']);
        $I->dontSeeElement('#payment_order_description', ['disabled' => 'disabled']);
        $I->amOnPage('/admin/payment-order/' . $paymentOrderId . '/manage');

        // check auto-capping of shareholding
        $highShares = 123456789;
        $I->amOnPage('/admin/payment-order/' . $paymentOrderId . '/add-payment');
        $firstOption = $I->grabAttributeFrom(
            '#payment_request_payee option:nth-child(2)',
            'value',
        );
        $I->selectOption('#payment_request_payee', ['value' => $firstOption]);
        $I->fillField('#payment_request_amount', 1);
        $I->fillField('#payment_request_shareholding', $highShares);
        $I->click('Add Payment');
        $I->click('Edit', '#payments-list tbody tr:first-child');
        $actualShares = $I->grabValueFrom('#payment_request_shareholding');
        $I->assertLessThan($highShares, $actualShares);
        $I->amOnPage('/admin/payment-order/' . $paymentOrderId . '/manage');
        $I->click('Delete', '#payments-list tbody tr:first-child');

        // check you get a warning message when there are no unique payees remaining
        for ($i = 0; $i < $totalPayees; $i++) {
            $I->addPaymentToOrder(1);
        }
        $I->amOnPage('/admin/payment-order/' . $paymentOrderId . '/add-payment');
        $I->see('There are no unique payees remaining');

        // Check the summary stats are non-zero
        // The shareholding should also be auto-filled by the shareholdings of each investor
        $I->amOnPage('/admin/payment-order/' . $paymentOrderId . '/manage');
        foreach ($expectedInfo as $fieldName => $expected) {
            $actual = $I->grabTextFrom('[data-field-name="' . $fieldName . '"]');
            $I->assertNotEquals(0, $actual);
        }
    }

    public function testAddPaymentsPayeesForType(FunctionalTester $I): void
    {
        // Check whether only prefunders or shareholders are available to choose as payees
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name like' => 'Lodge de Lac%',
        ]);
        $offeringId = $I->grabFromDatabase('offerings', 'id', [
            'name like' => 'Lodge de Lac%',
            'inv_id' => null,
        ]);
        // Need to reset keys due to https://github.com/sebastianbergmann/comparator/issues/112
        $shareholders = array_values(array_unique($I->grabColumnFromDatabase(
            'investments',
            'user_id',
            [
                'off_id' => $offeringId,
            ],
        )));
        $prefunders = array_values(array_unique($I->grabColumnFromDatabase(
            'investments',
            'user_id',
            [
                'off_id' => $offeringId,
                'type' => 'prefunding',
            ],
        )));
        foreach ([
            PaymentService::TYPE_DIVIDEND,
            PaymentService::TYPE_REPAYMENT,
            PaymentService::TYPE_DIVESTMENT,
            PaymentService::TYPE_INVESTMENT_EXIT,
        ] as $paymentType) {
            $sampleOrderId = $I->grabFromDatabase('payment_order', 'id', [
                'status' => PaymentOrder::STATE_DRAFT,
                'paymentType' => $paymentType,
                'asset_id' => $assetId,
            ]);
            $I->amOnPage('/admin/payment-order/' . $sampleOrderId . '/clear-payments');
            $I->click('Clear All Payments');
            $I->amOnPage('/admin/payment-order/' . $sampleOrderId . '/add-payment');
            // Reliably remove the placeholder by filtering out empty values
            // Also need to reset keys due to https://github.com/sebastianbergmann/comparator/issues/112
            $payeeOptions = array_values(array_filter(
                $I->grabMultiple('#payment_request_payee option', 'value'),
                fn(string $item) => !empty($item),
            ));
            if (PaymentService::TYPE_REPAYMENT == $paymentType) {
                $I->assertEqualsCanonicalizing($prefunders, $payeeOptions);
            } else {
                $I->assertEqualsCanonicalizing($shareholders, $payeeOptions);
            }
        }
    }

    public function testEditPayments(FunctionalTester $I): void
    {
        /**
         * Go to specific order management
         * Edit and discard -> no changes
         * Edit and save -> changes
         */
        $paymentModifier = 1.82;
        $shareModifier = 9;
        $highShares = 123456789;

        // Change to divestment type to allow editing of shareholding as well
        $I->amOnPage('/admin/payment-order/1/edit');
        $I->selectOption(
            'form input[name="payment_order[paymentType]"]',
            PaymentType::Divestment->value,
        );
        $I->click('Save Changes');
        $infoFields = ['total-due-(£)', 'total-shareholding', 'total-payments'];
        $currentInfo = [];
        foreach ($infoFields as $fieldName) {
            $currentInfo[$fieldName] = $I->grabTextFrom('[data-field-name="'
            . $fieldName
            . '"]');
        }

        $I->click('Edit', '#payments-list tbody tr:first-child');
        $originalAmount = $I->grabValueFrom('#payment_request_amount');
        $originalShares = $I->grabValueFrom('#payment_request_shareholding');
        $I->fillField('#payment_request_amount', $originalAmount + $paymentModifier);
        $I->fillField(
            '#payment_request_shareholding',
            $originalShares - $shareModifier,
        );
        $I->click('Discard Changes');
        $I->see($originalAmount, '#payments-list tbody tr:first-child');
        foreach ($infoFields as $fieldName) {
            $I->see($currentInfo[$fieldName], '[data-field-name="' . $fieldName . '"]');
        }

        $I->click('Edit', '#payments-list tbody tr:first-child');
        $originalAmount = $I->grabValueFrom('#payment_request_amount');
        $I->fillField('#payment_request_amount', $originalAmount + $paymentModifier);
        $I->fillField(
            '#payment_request_shareholding',
            $originalShares - $shareModifier,
        );
        $I->click('Save Changes');
        $I->see(
            $originalAmount + $paymentModifier,
            '#payments-list tbody tr:first-child',
        );
        $I->see(
            $currentInfo['total-due-(£)'] + $paymentModifier,
            '[data-field-name="total-due-(£)"]',
        );
        $totalShareholding =
            str_replace(',', '', $currentInfo['total-shareholding']) - $shareModifier;
        $I->see(
            number_format($totalShareholding),
            '[data-field-name="total-shareholding"]',
        );

        $I->click('Edit', '#payments-list tbody tr:first-child');
        $newAmount = $I->grabValueFrom('#payment_request_amount');
        $I->assertEquals(round($originalAmount + $paymentModifier, 2), $newAmount);
        // $I->seeInField('#payment_request_amount', round($originalAmount + $paymentModifier, 2));
        $I->seeInField(
            '#payment_request_shareholding',
            (int) ($originalShares - $shareModifier),
        );

        // check auto-capping of shareholding
        $I->fillField('#payment_request_shareholding', $highShares);
        $I->click('Save Changes');
        $I->click('Edit', '#payments-list tbody tr:first-child');
        $actualShares = $I->grabValueFrom('#payment_request_shareholding');
        $I->assertLessThan($highShares, $actualShares);

        // Revert payment type back to dividend
        $I->amOnPage('/admin/payment-order/1/edit');
        $I->selectOption(
            'form input[name="payment_order[paymentType]"]',
            PaymentType::Dividend->value,
        );
        $I->click('Save Changes');
    }

    public function testClearPayments(FunctionalTester $I): void
    {
        // Create a new payment order
        $I->amOnPage('/admin/payment-order/create');
        $I->click('Create Payment Order');
        $paymentOrderId = $I->grabTextFrom('[data-field-name="order-id"]');
        $I->seeCurrentUrlEquals('/admin/payment-order/' . $paymentOrderId . '/manage');
        $numberOfShareholders = $I->grabTextFrom(
            '[data-field-name="asset-current-shareholders"]',
        );
        // Fill as many payments as possible
        foreach (range(1, $numberOfShareholders) as $value) {
            $I->addPaymentToOrder('0.0' . $value);
        }
        $numberOfPayments = $I->grabTextFrom('[data-field-name="total-payments"]');
        $I->assertEquals($numberOfShareholders, $numberOfPayments);

        // Clear the payments
        $I->click('Clear All Payments');
        $I->seeCurrentUrlEquals('/admin/payment-order/'
        . $paymentOrderId
        . '/clear-payments');
        $I->see(
            'You are about to delete all payments from a payment order. This cannot be undone!',
        );
        $I->click('Clear All Payments');

        // Check the payments are all gone
        $I->seeCurrentUrlEquals('/admin/payment-order/' . $paymentOrderId . '/manage');
        $numberOfPayments = $I->grabTextFrom('[data-field-name="total-payments"]');
        $I->assertEquals(0, $numberOfPayments);
    }

    public function testAbandonOrder(FunctionalTester $I): void
    {
        $paymentOrderId = $I->grabFromDatabase('payment_order', 'id', [
            'status' => PaymentOrder::STATE_IN_PROGRESS,
        ]);
        $I->amOnPage('/admin/payment-order/' . $paymentOrderId . '/manage');
        $I->click('Abandon Payment Order');
        $I->seeCurrentUrlEquals('/admin/payment-order/' . $paymentOrderId . '/abandon');
        $I->see('This cannot be undone');
        $I->click('Abandon Payment Order');
        $I->assertEquals('Abandoned', $I->grabTextFrom('[data-field-name="status"]'));

        // undo changes to the affected payment order
        $I->updateInDatabase(
            'payment_order',
            ['status' => PaymentOrder::STATE_IN_PROGRESS],
            ['id' => $paymentOrderId],
        );
    }
}
