<?php

namespace App\Tests\Functional\Ops\MonthEnd\Divestment;

use App\Entity\Enum\PaymentType;
use App\Tests\Support\FunctionalTester;

class DivestmentPaymentManageCest
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
            'paymentType' => PaymentType::Divestment->value,
        ]);
        $I->amOnPage("/admin/monthend/divestments/{$paymentOrder}");

        // Check sections and titles present
        $sections = [
            'about-payment' => 'About Payment Order',
            'order-status' => 'Order Status',
            'about-asset' => 'About Asset',
            'divestment-summary' => 'Divestment Summary',
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
        $I->seeElement('section#about-asset [data-field-name="current-shareholders"]');
        $I->seeElement(
            'section#about-asset [data-field-name="active-shares-in-circulation"]',
        );
        $I->see('View Asset Product', 'section#about-asset a');
        $I->see('View Wallet Balances', 'section#about-asset a');
        $I->seeLink('View Asset Product', "/admin/products/{$assetId}");
        $I->seeLink('View Wallet Balances', "/admin/asset/{$assetId}/manage-wallets");

        // Check summary section
        $I->seeElement(
            'section#divestment-summary [data-field-name="shareholders-being-paid"]',
        );
        $I->seeElement('section#divestment-summary [data-field-name="payment-type"]');
        $I->seeElement(
            'section#divestment-summary [data-field-name="shares-being-liquidated"]',
        );
        $I->seeElement(
            'section#divestment-summary [data-field-name="total-to-pay-shareholders"]',
        );
        $I->seeElement(
            'section#divestment-summary [data-field-name="divestment-share-price"]',
        );
        $I->seeElement(
            'section#divestment-summary [data-field-name="capital-gain-or-loss"]',
        );

        // Check payments section
        $sections = [
            'Payee',
            'Payee Wallet',
            'Amount',
            'Proportion',
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
        $newOrderId = $I->createPaymentOrder(
            'Royal Eversea Glades - Cambridge',
            PaymentType::Divestment,
        );
        $I->amOnPage("/admin/monthend/divestments/{$newOrderId}");
        $I->dontSeeElement('section#shares-number-warning');
        $I->dontSeeElement('section#wallet-balance-warning');
        // The share number warning should also be absent in the generate page
        $I->amOnPage("/admin/monthend/divestments/{$newOrderId}/generate");
        $I->dontSeeElement('section#shares-number-warning');

        $newOrderId = $I->createPaymentOrder(
            'Lodge de Lac - Cumbria',
            PaymentType::Divestment,
        );
        $I->amOnPage("/admin/monthend/divestments/{$newOrderId}");

        // Lodge de Lac has shareholder divestments outstanding so should show warning for share number mismatch
        $I->seeElement(
            'section#shares-number-warning [data-field-name="shares-issued"]',
        );
        $I->seeElement(
            'section#shares-number-warning [data-field-name="active-shares-in-circulation"]',
        );
        $I->seeElement('section#shares-number-warning [data-field-name="difference"]');

        // No warnings on fresh divestment payment orders
        $I->dontSeeElement('section#wallet-balance-warning');
        $I->dontSeeElement('#empty-payments-warning');

        // Check the share number warning also appears in the generate page
        $I->amOnPage("/admin/monthend/divestments/{$newOrderId}/generate");
        $I->seeElement(
            'section#shares-number-warning [data-field-name="shares-issued"]',
        );
        $I->seeElement(
            'section#shares-number-warning [data-field-name="active-shares-in-circulation"]',
        );
        $I->seeElement('section#shares-number-warning [data-field-name="difference"]');
        $I->amOnPage("/admin/monthend/divestments/{$newOrderId}");

        // If the total to pay is too large - we'll us £10bn+ as an example
        // Note that price per share has a limit of 6 digits (and 6dp)
        // so set number of shares appropriate to reduce share price to max under 1 million
        // Freya has over 15000 share in Royal Eversea
        $I->addPaymentToOrder('0.23');
        $I->addPaymentToOrder('10111000111', 15295);
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
        // Empty payment still doesn't appear if you add a non-zero payment
        $I->dontSeeElement('#empty-payments-warning');
        // But does appear if you add an empty payment
        $I->addPaymentToOrder('0');
        $I->seeElement('#empty-payments-warning');

        // Check warning balance required updates
        $currentTotalRemaining = $I->convertMonetaryToNumber($I->grabTextFrom(
            'section#wallet-balance-warning [data-field-name="total-remaining"]',
        ));
        $currentAmountRequired = $I->convertMonetaryToNumber($I->grabTextFrom(
            'section#wallet-balance-warning [data-field-name="additional-funds-required"]',
        ));

        $I->click('Approve Payment Order');
        $I->click('Pay Single', '#payments-list tbody tr:first-child');
        $newTotalRemaining = $I->convertMonetaryToNumber($I->grabTextFrom(
            'section#wallet-balance-warning [data-field-name="total-remaining"]',
        ));
        $newAmountRequired = $I->convertMonetaryToNumber($I->grabTextFrom(
            'section#wallet-balance-warning [data-field-name="additional-funds-required"]',
        ));
        $I->assertEquals('0.23', round($currentTotalRemaining - $newTotalRemaining, 2));
        $I->assertEquals($currentAmountRequired, $newAmountRequired);
    }

    public function testNonDivestmentPaymentRedirect(FunctionalTester $I): void
    {
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Lodge de Lac - Cumbria',
        ]);
        $I->amOnPage('/admin/payment-order/create');
        $I->fillField('#payment_order_description', 'Non divestment payment test');
        $I->selectOption('#payment_order_asset', ['value' => (string) $assetId]);
        $I->selectOption(
            'form input[name="payment_order[paymentType]"]',
            PaymentType::Dividend->value,
        );
        $I->click('Create Payment Order');
        $newOrderId = $I->grabTextFrom('[data-field-name="order-id"]');
        $I->seeCurrentUrlEquals("/admin/payment-order/{$newOrderId}/manage");

        // Most of the divestment payment routes will send you back to the regular payment order page
        $protectedPaths = [
            "/admin/monthend/divestments/{$newOrderId}",
            "/admin/monthend/divestments/{$newOrderId}/generate",
        ];
        foreach ($protectedPaths as $path) {
            $I->amOnPage($path);
            $I->see('not for divestments');
            $I->seeCurrentUrlEquals("/admin/payment-order/{$newOrderId}/manage");
        }
    }

    public function testEditPaymentOrder(FunctionalTester $I): void
    {
        $newOrderId = $I->createPaymentOrder(
            'Lodge de Lac - Cumbria',
            PaymentType::Divestment,
        );
        $I->amOnPage("/admin/monthend/divestments/{$newOrderId}");
        $I->see('Edit Order', '#about-payment a');
        $I->see('Edit Description', '#about-payment a');
        // Default debit wallet
        $I->see('Main', '[data-field-name="paying-from-wallet"]');

        // Change the monthend date
        $I->click('Edit Order');
        $I->seeCurrentUrlEquals(
            "/admin/payment-orders/{$newOrderId}/date?redirectRoute=admin_monthend_divestment_manage",
        );
        $I->seeLink('Back', "/admin/monthend/divestments/{$newOrderId}");
        $I->seeLink('Discard Changes', "/admin/monthend/divestments/{$newOrderId}");

        $I->fillField('#payment_order_date_scheduledFor', '2020-04-08');
        $I->selectOption('#payment_order_date_debitWallet', 'Distribution');
        $I->click('Save Changes');
        $I->see('Payment order successfully updated', '.alert');
        $I->seeCurrentUrlEquals("/admin/monthend/divestments/{$newOrderId}");
        $I->assertEquals(
            '2020-04-08',
            $I->grabTextFrom('[data-field-name="scheduled-monthend"]'),
        );
        $I->see('Distribution', '[data-field-name="paying-from-wallet"]');

        // CHange the description
        $I->click('Edit Description');
        $I->seeCurrentUrlEquals(
            "/admin/payment-orders/{$newOrderId}/description?redirectRoute=admin_monthend_divestment_manage",
        );
        $I->seeLink('Back', "/admin/monthend/divestments/{$newOrderId}");
        $I->seeLink('Discard Changes', "/admin/monthend/divestments/{$newOrderId}");

        $I->fillField(
            '#payment_order_description_description',
            'Special divestment payment',
        );
        $I->click('Save Changes');
        $I->see('Payment order successfully updated', '.alert');
        $I->seeCurrentUrlEquals("/admin/monthend/divestments/{$newOrderId}");
        $I->assertEquals(
            'Special divestment payment',
            $I->grabTextFrom('[data-field-name="description"]'),
        );
    }

    public function testTransitionRedirect(FunctionalTester $I): void
    {
        $newOrderId = $I->createPaymentOrder(
            'Lodge de Lac - Cumbria',
            PaymentType::Divestment,
        );

        // Generate divestments so at least 1 payment can be run to abandon after
        $I->amOnPage("/admin/monthend/divestments/{$newOrderId}/generate");
        $I->fillField('#payment_order_generate_shares', '3');
        $I->fillField('#payment_order_generate_amount', '0.96');
        $I->click('Generate Payments');
        $I->amOnPage("/admin/monthend/divestments/{$newOrderId}");
        $I->assertEquals('Draft', $I->grabTextFrom('[data-field-name="status"]'));

        // Close
        $I->click('Close Payment Order');
        $I->seeCurrentUrlEquals(
            "/admin/payment-order/{$newOrderId}/close?redirectRoute=admin_monthend_divestment_manage",
        );
        $I->seeLink('Discard Changes', "/admin/monthend/divestments/{$newOrderId}");
        $I->click('Close Payment Order');
        $I->seeCurrentUrlEquals("/admin/monthend/divestments/{$newOrderId}");
        $I->assertEquals('Closed', $I->grabTextFrom('[data-field-name="status"]'));

        // Reopen
        $I->click('Reopen Payment Order');
        $I->seeCurrentUrlEquals("/admin/monthend/divestments/{$newOrderId}");
        $I->assertEquals('Draft', $I->grabTextFrom('[data-field-name="status"]'));

        // Approve
        $I->click('Approve Payment Order');
        $I->seeCurrentUrlEquals("/admin/monthend/divestments/{$newOrderId}");
        $I->assertEquals('Approved', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals(
            $I::USER_SUPER_ADMIN,
            $I->grabTextFrom('[data-field-name="approved-by"]'),
        );

        // Unapprove
        $I->click('Unapprove Payment Order');
        $I->seeCurrentUrlEquals("/admin/monthend/divestments/{$newOrderId}");
        $I->assertEquals('Draft', $I->grabTextFrom('[data-field-name="status"]'));

        // Abandon
        $I->click('Approve Payment Order');
        $I->click('Pay Single');
        $I->seeCurrentUrlEquals("/admin/monthend/divestments/{$newOrderId}");
        $I->assertEquals('In-progress', $I->grabTextFrom('[data-field-name="status"]'));
        $I->click('Abandon Payment Order');
        $I->seeCurrentUrlEquals(
            "/admin/payment-order/{$newOrderId}/abandon?redirectRoute=admin_monthend_divestment_manage",
        );
        $I->seeLink('Discard Changes', "/admin/monthend/divestments/{$newOrderId}");
        $I->click('Abandon Payment Order');
        $I->seeCurrentUrlEquals("/admin/monthend/divestments/{$newOrderId}");
        $I->assertEquals('Abandoned', $I->grabTextFrom('[data-field-name="status"]'));
    }

    public function testAddRemovePayments(FunctionalTester $I): void
    {
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Lodge de Lac - Cumbria',
        ]);
        $newOrderId = $I->createPaymentOrder(
            'Lodge de Lac - Cumbria',
            PaymentType::Divestment,
        );
        $I->amOnPage("/admin/monthend/divestments/{$newOrderId}");

        $I->click('Add Payment');
        $I->seeLink('Cancel', "/admin/monthend/divestments/{$newOrderId}");
        $firstOption = $I->grabAttributeFrom(
            '#payment_request_payee option:nth-child(2)',
            'value',
        );
        $I->selectOption('#payment_request_payee', ['value' => $firstOption]);
        $I->fillField('#payment_request_amount', 0.04);
        $I->fillField('#payment_request_shareholding', '1');
        $I->click('Add Payment');
        $I->seeCurrentUrlEquals("/admin/monthend/divestments/{$newOrderId}");

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
            "/admin/payment-request/{$paymentRequestId}/edit?redirectRoute=admin_monthend_divestment_manage",
        );
        $I->seeLink(
            'Delete',
            "/admin/payment-request/{$paymentRequestId}/delete?redirectRoute=admin_monthend_divestment_manage",
        );

        // Edit the payment
        $I->click('Edit', '#payments-list tbody tr:first-child');
        $I->seeLink('Discard Changes', "/admin/monthend/divestments/{$newOrderId}");
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

        // Test divestment generation
        $totalToPay = '1.84';
        $I->click('Generate Payments');
        $I->seeCurrentUrlEquals("/admin/monthend/divestments/{$newOrderId}/generate");
        // See some assistive information
        $I->seeElement('section#guidelines [data-field-name="shares-issued"]');
        $I->seeElement('section#guidelines [data-field-name="share-price"]');
        $I->seeElement('section#guidelines [data-field-name="asset-valuation"]');
        // Generate the divestments
        $I->fillField('#payment_order_generate_shares', '3');
        $I->fillField('#payment_order_generate_amount', $totalToPay);
        $I->click('Generate Payments');
        $I->seeCurrentUrlEquals("/admin/monthend/divestments/{$newOrderId}");
        // Check there's a warning about overwriting payments if generating again
        $I->amOnPage("/admin/monthend/divestments/{$newOrderId}/generate");
        $I->seeElement('section#overwrite-warning');
        $I->amOnPage("/admin/monthend/divestments/{$newOrderId}");

        // Check auto-fill behaviour
        $I->assertEquals(
            date('Y-m-01'),
            $I->grabTextFrom('[data-field-name="scheduled-monthend"]'),
        );
        $I->assertEquals(PaymentType::Divestment->value, $I->grabFromDatabase(
            'payment_order',
            'paymentType',
            [
                'id' => $newOrderId,
            ],
        ));

        // Check generation worked
        $shareholders = $I->grabTextFrom('[data-field-name="current-shareholders"]');
        $sharePrice = explode(
            '£',
            $I->grabTextFrom('[data-field-name="share-price"]'),
        )[1];
        $I->seeNumberOfElements('#payments-list tbody tr', (int) $shareholders);
        $I->assertEquals(
            $totalToPay,
            explode('/', $I->grabTextFrom('[data-field-name="total-paid-(£)"]'))[1],
        );
        $I->assertEquals(
            $shareholders,
            explode('/', $I->grabTextFrom('[data-field-name="payments-completed"]'))[1],
        );

        // Delete a payment
        $firstPaymentAmount = $I->grabTextFrom(
            '#payments-list tbody tr:first-child [data-field="amount"]',
        );
        $I->click('Delete', '#payments-list tbody tr:first-child');
        $I->seeCurrentUrlEquals("/admin/monthend/divestments/{$newOrderId}");
        $I->seeNumberOfElements('#payments-list tbody tr', $shareholders - 1);
        $I->assertEquals(
            round($totalToPay - $firstPaymentAmount, 2),
            explode('/', $I->grabTextFrom('[data-field-name="total-paid-(£)"]'))[1],
        );
        $I->assertEquals(
            $shareholders - 1,
            explode('/', $I->grabTextFrom('[data-field-name="payments-completed"]'))[1],
        );

        // Clear all remaining payments
        $I->click('Clear All Payments');
        $I->seeCurrentUrlEquals(
            "/admin/payment-order/{$newOrderId}/clear-payments?redirectRoute=admin_monthend_divestment_manage",
        );
        $I->see('Confirm Deletion');
        $I->seeLink('Discard Changes', "/admin/monthend/divestments/{$newOrderId}");
        $I->click('Clear All Payments');
        $I->seeCurrentUrlEquals("/admin/monthend/divestments/{$newOrderId}");
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
            "/admin/monthend/divestments/{$newOrderId}#payments-list",
        );
    }
}
