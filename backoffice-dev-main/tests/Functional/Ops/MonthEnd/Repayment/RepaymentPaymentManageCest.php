<?php

namespace App\Tests\Functional\Ops\MonthEnd\Repayment;

use App\Entity\Enum\PaymentType;
use App\Tests\Support\FunctionalTester;

class RepaymentPaymentManageCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function checkSections(FunctionalTester $I): void
    {
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Lodge de Lac - Cumbria',
        ]);
        $paymentOrder = $I->grabFromDatabase('payment_order', 'id', [
            'asset_id' => $assetId,
            'paymentType' => PaymentType::Repayment->value,
        ]);
        $I->amOnPage("/admin/monthend/repayments/{$paymentOrder}");

        // Check sections and titles present
        $sections = [
            'about-payment' => 'About Payment Order',
            'order-status' => 'Order Status',
            'about-asset' => 'About Asset',
            'payment-guidelines' => 'Payment Guidelines',
            'payments' => 'Payments',
        ];
        // check section headers
        foreach ($sections as $sectionId => $sectionTitle) {
            $I->see($sectionTitle, "section#{$sectionId} h3");
        }

        // Check payment order about section
        $I->seeElement('section#about-payment [data-field-name="scheduled-monthend"]');
        $I->seeElement('section#about-payment [data-field-name="paying-from-wallet"]');
        $I->seeElement('section#about-payment [data-field-name="description"]');

        // Check order status section
        $I->seeElement('section#order-status [data-field-name="status"]');
        $I->seeElement('section#order-status [data-field-name="approved-by"]');

        // Check about asset section
        $I->seeElement('section#about-asset [data-field-name="spv-company-number"]');
        $I->seeElement('section#about-asset [data-field-name="asset-valuation"]');
        $I->seeElement('section#about-asset [data-field-name="share-price"]');
        $I->seeElement('section#about-asset [data-field-name="shares-issued"]');
        $I->seeElement('section#about-asset [data-field-name="prefunders"]');
        $I->seeElement(
            'section#about-asset [data-field-name="active-shares-in-circulation"]',
        );
        $I->see('View Asset Product', 'section#about-asset a');
        $I->see('View Wallet Balances', 'section#about-asset a');
        $I->seeLink('View Asset Product', "/admin/products/{$assetId}");
        $I->seeLink('View Wallet Balances', "/admin/asset/{$assetId}/manage-wallets");

        // Check guidelines section
        $I->see(
            'How many shares need to be repaid',
            'section#payment-guidelines details summary',
        );
        $I->seeElement(
            'section#payment-guidelines [data-field-name="surplus-shares-sold"]',
        );
        $I->seeElement(
            'section#payment-guidelines [data-field-name="shares-recently-settled"]',
        );
        $I->seeElement(
            'section#payment-guidelines [data-field-name="prefunders-being-paid"]',
        );
        $I->seeElement(
            'section#payment-guidelines [data-field-name="shares-in-current-order-paid"]',
        );

        // Asset repayment sub-section
        $I->seeElement(
            'section#repayment-progress [data-field-name="original-shares-to-repay"]',
        );
        $I->seeElement(
            'section#repayment-progress [data-field-name="shares-already-repaid"]',
        );
        $I->seeElement(
            'section#repayment-progress [data-field-name="shares-remaining-in-current-order"]',
        );
        $I->seeElement(
            'section#repayment-progress [data-field-name="shares-still-to-repay"]',
        );

        // Check payments section
        $sections = [
            'Payee',
            'Payee Wallet',
            'Amount',
            'Proportion',
            'Shares Still to Repay',
            'Shares in Current Repayment',
            'Updated',
            'Status',
            'Transaction Reference',
            'Actions',
        ];
        foreach ($sections as $sectionId => $sectionTitle) {
            $I->see($sectionTitle, 'section#payments table thead');
        }
        $I->seeLink(
            'Export Payments List',
            "/admin/payment-order/{$paymentOrder}/export",
        );
    }

    public function checkWarnings(FunctionalTester $I): void
    {
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Lodge de Lac - Cumbria',
        ]);
        $newOrderId = $I->createPaymentOrder(
            'Lodge de Lac - Cumbria',
            PaymentType::Repayment,
        );
        $I->amOnPage("/admin/monthend/repayments/{$newOrderId}");

        // No warnings on fresh repayment payment orders
        $I->dontSeeElement('section#wallet-balance-warning');
        $I->dontSeeElement('#empty-payments-warning');
        // Empty payment still doesn't appear if you add a non-zero payment
        $I->addPaymentToOrder('0.23');
        $I->dontSeeElement('#empty-payments-warning');
        // But does appear if you add an empty payment
        $I->addPaymentToOrder('0');
        $I->seeElement('#empty-payments-warning');
        // Delete this empty payment
        $I->click('Delete', '#payments-list tbody tr:nth-child(2)');

        // If the total to pay is too large - we'll us £10bn+ as an example
        $I->addPaymentToOrder('10111000111');
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

        // Additional prompt if paying from distribution wallet
        $I->click('Edit Order');
        $I->selectOption('#payment_order_date_debitWallet', 'Distribution');
        $I->click('Save Changes');
        $I->seeLink(
            'Transfer Repayment Funds',
            "/admin/monthend/repayments/transfer/{$assetId}/create",
        );

        // Note that you can't run repayment orders with invalid payments (missing suitable sell order)
        // Or has too big a share price (can't be over 6 figures)
        // Check warning balance required updates
        // $currentTotalRemaining = $I->convertMonetaryToNumber($I->grabTextFrom(
        //     'section#wallet-balance-warning [data-field-name="total-remaining"]',
        // ));
        // $currentAmountRequired = $I->convertMonetaryToNumber($I->grabTextFrom(
        //     'section#wallet-balance-warning [data-field-name="additional-funds-required"]',
        // ));
        // $I->click('Approve Payment Order');
        // $I->click('Pay Single', '#payments-list tbody tr:first-child');
        // $newTotalRemaining = $I->convertMonetaryToNumber($I->grabTextFrom(
        //     'section#wallet-balance-warning [data-field-name="total-remaining"]',
        // ));
        // $newAmountRequired = $I->convertMonetaryToNumber($I->grabTextFrom(
        //     'section#wallet-balance-warning [data-field-name="additional-funds-required"]',
        // ));
        // $I->assertEquals('0.23', round($currentTotalRemaining - $newTotalRemaining, 2));
        // $I->assertEquals($currentAmountRequired, $newAmountRequired);
    }

    public function testNonRepaymentPaymentRedirect(FunctionalTester $I): void
    {
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Lodge de Lac - Cumbria',
        ]);
        $I->amOnPage('/admin/payment-order/create');
        $I->fillField('#payment_order_description', 'Non repayment payment test');
        $I->selectOption('#payment_order_asset', ['value' => (string) $assetId]);
        $I->selectOption(
            'form input[name="payment_order[paymentType]"]',
            PaymentType::Divestment->value,
        );
        $I->click('Create Payment Order');
        $newOrderId = $I->grabTextFrom('[data-field-name="order-id"]');
        $I->seeCurrentUrlEquals("/admin/payment-order/{$newOrderId}/manage");

        // Most of the repayment payment routes will send you back to the regular payment order page
        $protectedPaths = [
            "/admin/monthend/repayments/{$newOrderId}",
            "/admin/monthend/repayments/{$newOrderId}/generate",
        ];
        foreach ($protectedPaths as $path) {
            $I->amOnPage($path);
            $I->see('not for repayments');
            $I->seeCurrentUrlEquals("/admin/payment-order/{$newOrderId}/manage");
        }
    }

    public function testEditPaymentOrder(FunctionalTester $I): void
    {
        $newOrderId = $I->createPaymentOrder(
            'Lodge de Lac - Cumbria',
            PaymentType::Repayment,
        );
        $I->amOnPage("/admin/monthend/repayments/{$newOrderId}");
        $I->see('Edit Order', '#about-payment a');
        $I->see('Edit Description', '#about-payment a');
        // Default debit wallet
        $I->see('Main', '[data-field-name="paying-from-wallet"]');

        // Change the monthend date
        $I->click('Edit Order');
        $I->seeCurrentUrlEquals(
            "/admin/payment-orders/{$newOrderId}/date?redirectRoute=admin_monthend_repayment_manage",
        );
        $I->seeLink('Back', "/admin/monthend/repayments/{$newOrderId}");
        $I->seeLink('Discard Changes', "/admin/monthend/repayments/{$newOrderId}");

        $I->fillField('#payment_order_date_scheduledFor', '2020-04-08');
        $I->selectOption('#payment_order_date_debitWallet', 'Distribution');
        $I->click('Save Changes');
        $I->see('Payment order successfully updated', '.alert');
        $I->seeCurrentUrlEquals("/admin/monthend/repayments/{$newOrderId}");
        $I->assertEquals(
            '2020-04-08',
            $I->grabTextFrom('[data-field-name="scheduled-monthend"]'),
        );
        $I->see('Distribution', '[data-field-name="paying-from-wallet"]');

        // CHange the description
        $I->click('Edit Description');
        $I->seeCurrentUrlEquals(
            "/admin/payment-orders/{$newOrderId}/description?redirectRoute=admin_monthend_repayment_manage",
        );
        $I->seeLink('Back', "/admin/monthend/repayments/{$newOrderId}");
        $I->seeLink('Discard Changes', "/admin/monthend/repayments/{$newOrderId}");

        $I->fillField(
            '#payment_order_description_description',
            'Special repayment payment',
        );
        $I->click('Save Changes');
        $I->see('Payment order successfully updated', '.alert');
        $I->seeCurrentUrlEquals("/admin/monthend/repayments/{$newOrderId}");
        $I->assertEquals(
            'Special repayment payment',
            $I->grabTextFrom('[data-field-name="description"]'),
        );
    }

    public function testTransitionRedirect(FunctionalTester $I): void
    {
        $newOrderId = $I->createPaymentOrder(
            'Lodge de Lac - Cumbria',
            PaymentType::Repayment,
        );

        // Generate repayments so at least 1 payment can be run to abandon after
        $I->amOnPage("/admin/monthend/repayments/{$newOrderId}/generate");
        $I->fillField('#payment_order_generate_shares', '3');
        $I->click('Generate Payments');
        $I->amOnPage("/admin/monthend/repayments/{$newOrderId}");
        $I->assertEquals('Draft', $I->grabTextFrom('[data-field-name="status"]'));

        // Close
        $I->click('Close Payment Order');
        $I->seeCurrentUrlEquals(
            "/admin/payment-order/{$newOrderId}/close?redirectRoute=admin_monthend_repayment_manage",
        );
        $I->seeLink('Discard Changes', "/admin/monthend/repayments/{$newOrderId}");
        $I->click('Close Payment Order');
        $I->seeCurrentUrlEquals("/admin/monthend/repayments/{$newOrderId}");
        $I->assertEquals('Closed', $I->grabTextFrom('[data-field-name="status"]'));

        // Reopen
        $I->click('Reopen Payment Order');
        $I->seeCurrentUrlEquals("/admin/monthend/repayments/{$newOrderId}");
        $I->assertEquals('Draft', $I->grabTextFrom('[data-field-name="status"]'));

        // Approve
        $I->click('Approve Payment Order');
        $I->seeCurrentUrlEquals("/admin/monthend/repayments/{$newOrderId}");
        $I->assertEquals('Approved', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals(
            $I::USER_SUPER_ADMIN,
            $I->grabTextFrom('[data-field-name="approved-by"]'),
        );

        // Unapprove
        $I->click('Unapprove Payment Order');
        $I->seeCurrentUrlEquals("/admin/monthend/repayments/{$newOrderId}");
        $I->assertEquals('Draft', $I->grabTextFrom('[data-field-name="status"]'));

        // Abandon
        $I->click('Approve Payment Order');
        $I->click('Pay Single');
        $I->seeCurrentUrlEquals("/admin/monthend/repayments/{$newOrderId}");
        $I->assertEquals('In-progress', $I->grabTextFrom('[data-field-name="status"]'));
        $I->click('Abandon Payment Order');
        $I->seeCurrentUrlEquals(
            "/admin/payment-order/{$newOrderId}/abandon?redirectRoute=admin_monthend_repayment_manage",
        );
        $I->seeLink('Discard Changes', "/admin/monthend/repayments/{$newOrderId}");
        $I->click('Abandon Payment Order');
        $I->seeCurrentUrlEquals("/admin/monthend/repayments/{$newOrderId}");
        $I->assertEquals('Abandoned', $I->grabTextFrom('[data-field-name="status"]'));
    }

    public function testAddRemovePayments(FunctionalTester $I): void
    {
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Lodge de Lac - Cumbria',
        ]);
        $newOrderId = $I->createPaymentOrder(
            'Lodge de Lac - Cumbria',
            PaymentType::Repayment,
        );
        $I->amOnPage("/admin/monthend/repayments/{$newOrderId}");

        $I->click('Add Payment');
        $I->seeLink('Cancel', "/admin/monthend/repayments/{$newOrderId}");
        $firstOption = $I->grabAttributeFrom(
            '#payment_request_payee option:nth-child(2)',
            'value',
        );
        $I->selectOption('#payment_request_payee', ['value' => $firstOption]);
        $I->fillField('#payment_request_amount', 0.04);
        $I->fillField('#payment_request_shareholding', '1');
        $I->click('Add Payment');
        $I->seeCurrentUrlEquals("/admin/monthend/repayments/{$newOrderId}");

        // Check payments summary
        $I->assertEquals(
            '0.00/0.04',
            $I->grabTextFrom('[data-field-name="total-paid-(£)"]'),
        );
        $I->assertEquals(
            '0/1',
            $I->grabTextFrom('[data-field-name="payments-completed"]'),
        );

        // Check payments table
        $I->seeNumberOfElements('#payments-list tbody tr', 1);
        $I->assertEquals('0.04', $I->grabTextFrom('tr [data-field="amount"]'));

        // Check action links
        $paymentRequestId = $I->grabAttributeFrom(
            '#payments-list tbody tr:first-child',
            'data-object-id',
        );
        $I->seeLink(
            'Edit',
            "/admin/payment-request/{$paymentRequestId}/edit?redirectRoute=admin_monthend_repayment_manage",
        );
        $I->seeLink(
            'Delete',
            "/admin/payment-request/{$paymentRequestId}/delete?redirectRoute=admin_monthend_repayment_manage",
        );

        // Edit the payment
        $I->click('Edit', '#payments-list tbody tr:first-child');
        $I->seeLink('Discard Changes', "/admin/monthend/repayments/{$newOrderId}");
        $I->fillField('#payment_request_amount', 0.08);
        $I->fillField('#payment_request_shareholding', '1');
        $I->click('Save Changes');

        // Check fields updated (or not)
        $I->assertEquals(
            '0.00/0.08',
            $I->grabTextFrom('[data-field-name="total-paid-(£)"]'),
        );
        $I->assertEquals(
            '0/1',
            $I->grabTextFrom('[data-field-name="payments-completed"]'),
        );

        // Test repayment generation
        $I->click('Generate Payments');
        $I->seeCurrentUrlEquals("/admin/monthend/repayments/{$newOrderId}/generate");
        // See some assistive information
        $I->seeElement('section#guidelines [data-field-name="shares-issued"]');
        $I->seeElement(
            'section#guidelines [data-field-name="active-shares-in-circulation"]',
        );
        $I->seeElement('section#guidelines [data-field-name="total-shares-to-repay"]');
        $I->seeElement('section#guidelines [data-field-name="prefunders"]');
        $I->seeElement('section#guidelines [data-field-name="surplus-shares-sold"]');
        $I->seeElement(
            'section#guidelines [data-field-name="shares-recently-settled"]',
        );
        // Generate the repayments
        $I->fillField('#payment_order_generate_shares', '3');
        $I->click('Generate Payments');
        $I->seeCurrentUrlEquals("/admin/monthend/repayments/{$newOrderId}");
        // Check there's a warning about overwriting payments if generating again
        $I->amOnPage("/admin/monthend/repayments/{$newOrderId}/generate");
        $I->seeElement('section#overwrite-warning');
        $I->amOnPage("/admin/monthend/repayments/{$newOrderId}");

        // Check auto-fill behaviour
        $I->assertEquals(
            date('Y-m-01'),
            $I->grabTextFrom('[data-field-name="scheduled-monthend"]'),
        );
        $I->assertEquals(PaymentType::Repayment->value, $I->grabFromDatabase(
            'payment_order',
            'paymentType',
            [
                'id' => $newOrderId,
            ],
        ));

        // Check generation worked
        $prefunders = $I->grabTextFrom('[data-field-name="prefunders"]');
        $sharePrice = explode(
            '£',
            $I->grabTextFrom('[data-field-name="share-price"]'),
        )[1];
        $totalToPay = round($sharePrice * 3, 2);
        $I->seeNumberOfElements('#payments-list tbody tr', (int) $prefunders);
        $I->assertEquals(
            $totalToPay,
            explode('/', $I->grabTextFrom('[data-field-name="total-paid-(£)"]'))[1],
        );
        $I->assertEquals(
            $prefunders,
            explode('/', $I->grabTextFrom('[data-field-name="payments-completed"]'))[1],
        );

        // Delete a payment
        $firstPaymentAmount = $I->grabTextFrom(
            '#payments-list tbody tr:first-child [data-field="amount"]',
        );
        $I->click('Delete', '#payments-list tbody tr:first-child');
        $I->seeCurrentUrlEquals("/admin/monthend/repayments/{$newOrderId}");
        $I->seeNumberOfElements('#payments-list tbody tr', $prefunders - 1);
        $I->assertEquals(
            round($totalToPay - $firstPaymentAmount, 2),
            explode('/', $I->grabTextFrom('[data-field-name="total-paid-(£)"]'))[1],
        );
        $I->assertEquals(
            $prefunders - 1,
            explode('/', $I->grabTextFrom('[data-field-name="payments-completed"]'))[1],
        );

        // Clear all remaining payments
        $I->click('Clear All Payments');
        $I->seeCurrentUrlEquals(
            "/admin/payment-order/{$newOrderId}/clear-payments?redirectRoute=admin_monthend_repayment_manage",
        );
        $I->see('Confirm Deletion');
        $I->seeLink('Discard Changes', "/admin/monthend/repayments/{$newOrderId}");
        $I->click('Clear All Payments');
        $I->seeCurrentUrlEquals("/admin/monthend/repayments/{$newOrderId}");
        $I->seeNumberOfElements('#payments-list tbody tr', 0);
        $I->assertEquals(
            0.00,
            explode('/', $I->grabTextFrom('[data-field-name="total-paid-(£)"]'))[1],
        );
        $I->assertEquals(
            0,
            explode('/', $I->grabTextFrom('[data-field-name="payments-completed"]'))[1],
        );

        // Should see a prompt to configure payments
        $I->seeLink(
            'Configure Payments',
            "/admin/monthend/repayments/{$newOrderId}#payments-list",
        );
    }
}
