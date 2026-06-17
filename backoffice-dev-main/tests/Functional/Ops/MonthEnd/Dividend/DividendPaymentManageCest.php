<?php

namespace App\Tests\Functional\Ops\MonthEnd\Dividend;

use App\Entity\Enum\PaymentType;
use App\Tests\Support\FunctionalTester;

class DividendPaymentManageCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function checkSections(FunctionalTester $I): void
    {
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);
        $paymentOrder = $I->grabFromDatabase('payment_order', 'id', [
            'asset_id' => $assetId,
            'paymentType' => PaymentType::Dividend->value,
        ]);
        $I->amOnPage("/admin/monthend/dividends/{$paymentOrder}");

        // Check sections and titles present
        $sections = [
            'about-payment' => 'About Payment Order',
            'order-status' => 'Order Status',
            'about-asset' => 'About Asset',
            'payment-target' => 'Payment Target',
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
        $I->seeElement('section#about-asset [data-field-name="current-shareholders"]');
        $I->seeElement(
            'section#about-asset [data-field-name="active-shares-in-circulation"]',
        );
        $I->see('View Asset Product', 'section#about-asset a');
        $I->see('View Wallet Balances', 'section#about-asset a');
        $I->seeLink('View Asset Product', "/admin/products/{$assetId}");
        $I->seeLink('View Wallet Balances', "/admin/asset/{$assetId}/manage-wallets");

        // Check guidelines section
        $I->see(
            'What do the shareholders/shareholdings stats mean',
            'section#payment-target details summary',
        );
        $I->seeElement(
            'section#payment-target [data-field-name="shareholders-being-paid"]',
        );
        $I->seeElement(
            'section#payment-target [data-field-name="shareholding-with-payments"]',
        );
        $I->seeElement('section#payment-target [data-field-name^="actual-"]');
        $I->seeElement('section#payment-target [data-field-name^="target-"]');

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
        $newOrderId = $I->createPaymentOrder('Royal Eversea Glades - Cambridge');
        $I->amOnPage("/admin/monthend/dividends/{$newOrderId}");

        // No warnings on fresh dividend payment orders
        $I->dontSeeElement('section#low-dividend-warning');
        $I->dontSeeElement('section#wallet-balance-warning');
        $I->dontSeeElement('#empty-payments-warning');

        // If at least 1 transfer exists, and the total to pay is too low
        $I->click('Add Payment');
        $firstOption = $I->grabAttributeFrom(
            '#payment_request_payee option:nth-child(2)',
            'value',
        );
        $I->selectOption('#payment_request_payee', ['value' => $firstOption]);
        $I->fillField('#payment_request_amount', 0.01);
        $I->click('Add Payment');
        // Low dividend warning will now appear with some guiding figures
        $I->seeElement('section#low-dividend-warning');
        $I->seeElement(
            'section#low-dividend-warning [data-field-name="dividend-expected"]',
        );
        $I->seeElement(
            'section#low-dividend-warning [data-field-name="dividend-being-paid"]',
        );
        $I->seeElement('section#low-dividend-warning [data-field-name="performance"]');
        $I->see('Add Description', 'section#low-dividend-warning a');
        $I->seeLink(
            'Add Description',
            "/admin/payment-orders/{$newOrderId}/description?redirectRoute=admin_monthend_dividend_manage",
        );
        // Empty payment still doesn't appear if you add a non-zero payment
        $I->dontSeeElement('#empty-payments-warning');
        // But does appear if you add an empty payment
        $I->addPaymentToOrder('0');
        $I->seeElement('#empty-payments-warning');

        // Check generator instance warning on accrual
        $I->amOnPage("/admin/monthend/dividends/{$newOrderId}/generate");
        $I->fillField('#payment_order_generate_amount', '121.71');
        $I->click('Generate Payments');
        $totalToPay = explode(
            '/',
            $I->grabTextFrom('[data-field-name="total-paid-(£)"]'),
        )[1];
        $difference = round(121.71 - (float) $totalToPay, 2);
        $I->see("{$difference} will be accrued", '.alert');

        // If the total to pay is too large - we'll us £10bn+ as an example
        $I->click('Clear All Payments');
        $I->click('Clear All Payments');
        $I->addPaymentToOrder('0.12');
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
        $I->assertEquals('0.12', round($currentTotalRemaining - $newTotalRemaining, 2));
        $I->assertEquals($currentAmountRequired, $newAmountRequired);
    }

    public function testNonDividendPaymentRedirect(FunctionalTester $I): void
    {
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);
        $I->amOnPage('/admin/payment-order/create');
        $I->fillField('#payment_order_description', 'Non dividend payment test');
        $I->selectOption('#payment_order_asset', ['value' => (string) $assetId]);
        $I->selectOption(
            'form input[name="payment_order[paymentType]"]',
            PaymentType::Divestment->value,
        );
        $I->click('Create Payment Order');
        $newOrderId = $I->grabTextFrom('[data-field-name="order-id"]');
        $I->seeCurrentUrlEquals("/admin/payment-order/{$newOrderId}/manage");

        // Most of the dividend payment routes will send you back to the regular payment order page
        $protectedPaths = [
            "/admin/monthend/dividends/{$newOrderId}",
            "/admin/monthend/dividends/{$newOrderId}/generate",
        ];
        foreach ($protectedPaths as $path) {
            $I->amOnPage($path);
            $I->see('not for dividends');
            $I->seeCurrentUrlEquals("/admin/payment-order/{$newOrderId}/manage");
        }
    }

    public function testEditPaymentOrder(FunctionalTester $I): void
    {
        $newOrderId = $I->createPaymentOrder('Royal Eversea Glades - Cambridge');
        $I->amOnPage("/admin/monthend/dividends/{$newOrderId}");
        $I->see('Edit Order', '#about-payment a');
        $I->see('Edit Description', '#about-payment a');
        // Default debit wallet
        $I->see('Main', '[data-field-name="paying-from-wallet"]');

        // Change the monthend date
        $I->click('Edit Order');
        $I->seeCurrentUrlEquals(
            "/admin/payment-orders/{$newOrderId}/date?redirectRoute=admin_monthend_dividend_manage",
        );
        $I->seeLink('Back', "/admin/monthend/dividends/{$newOrderId}");
        $I->seeLink('Discard Changes', "/admin/monthend/dividends/{$newOrderId}");

        $I->fillField('#payment_order_date_scheduledFor', '2020-04-08');
        $I->selectOption('#payment_order_date_debitWallet', 'Distribution');
        $I->click('Save Changes');
        $I->see('Payment order successfully updated', '.alert');
        $I->seeCurrentUrlEquals("/admin/monthend/dividends/{$newOrderId}");
        $I->assertEquals(
            '2020-04-08',
            $I->grabTextFrom('[data-field-name="scheduled-monthend"]'),
        );
        $I->see('Distribution', '[data-field-name="paying-from-wallet"]');

        // CHange the description
        $I->click('Edit Description');
        $I->seeCurrentUrlEquals(
            "/admin/payment-orders/{$newOrderId}/description?redirectRoute=admin_monthend_dividend_manage",
        );
        $I->seeLink('Back', "/admin/monthend/dividends/{$newOrderId}");
        $I->seeLink('Discard Changes', "/admin/monthend/dividends/{$newOrderId}");

        $I->fillField(
            '#payment_order_description_description',
            'Special dividend payment',
        );
        $I->click('Save Changes');
        $I->see('Payment order successfully updated', '.alert');
        $I->seeCurrentUrlEquals("/admin/monthend/dividends/{$newOrderId}");
        $I->assertEquals(
            'Special dividend payment',
            $I->grabTextFrom('[data-field-name="description"]'),
        );
    }

    public function testTransitionRedirect(FunctionalTester $I): void
    {
        $newOrderId = $I->createPaymentOrder();

        // Generate dividends so at least 1 payment can be run to abandon after
        $I->amOnPage("/admin/monthend/dividends/{$newOrderId}/generate");
        $I->fillField('#payment_order_generate_amount', '0.08');
        $I->click('Generate Payments');
        $I->amOnPage("/admin/monthend/dividends/{$newOrderId}");
        $I->assertEquals('Draft', $I->grabTextFrom('[data-field-name="status"]'));

        // Close
        $I->click('Close Payment Order');
        $I->seeCurrentUrlEquals(
            "/admin/payment-order/{$newOrderId}/close?redirectRoute=admin_monthend_dividend_manage",
        );
        $I->seeLink('Discard Changes', "/admin/monthend/dividends/{$newOrderId}");
        $I->click('Close Payment Order');
        $I->seeCurrentUrlEquals("/admin/monthend/dividends/{$newOrderId}");
        $I->assertEquals('Closed', $I->grabTextFrom('[data-field-name="status"]'));

        // Reopen
        $I->click('Reopen Payment Order');
        $I->seeCurrentUrlEquals("/admin/monthend/dividends/{$newOrderId}");
        $I->assertEquals('Draft', $I->grabTextFrom('[data-field-name="status"]'));

        // Approve
        $I->click('Approve Payment Order');
        $I->seeCurrentUrlEquals("/admin/monthend/dividends/{$newOrderId}");
        $I->assertEquals('Approved', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals(
            $I::USER_SUPER_ADMIN,
            $I->grabTextFrom('[data-field-name="approved-by"]'),
        );

        // Unapprove
        $I->click('Unapprove Payment Order');
        $I->seeCurrentUrlEquals("/admin/monthend/dividends/{$newOrderId}");
        $I->assertEquals('Draft', $I->grabTextFrom('[data-field-name="status"]'));

        // Abandon
        $I->click('Approve Payment Order');
        $I->click('Pay Single');
        $I->seeCurrentUrlEquals("/admin/monthend/dividends/{$newOrderId}");
        $I->assertEquals('In-progress', $I->grabTextFrom('[data-field-name="status"]'));
        $I->click('Abandon Payment Order');
        $I->seeCurrentUrlEquals(
            "/admin/payment-order/{$newOrderId}/abandon?redirectRoute=admin_monthend_dividend_manage",
        );
        $I->seeLink('Discard Changes', "/admin/monthend/dividends/{$newOrderId}");
        $I->click('Abandon Payment Order');
        $I->seeCurrentUrlEquals("/admin/monthend/dividends/{$newOrderId}");
        $I->assertEquals('Abandoned', $I->grabTextFrom('[data-field-name="status"]'));
    }

    public function testAddRemovePayments(FunctionalTester $I): void
    {
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);
        $newOrderId = $I->createPaymentOrder('Royal Eversea Glades - Cambridge');
        $I->amOnPage("/admin/monthend/dividends/{$newOrderId}");

        $I->click('Add Payment');
        $I->seeLink('Cancel', "/admin/monthend/dividends/{$newOrderId}");
        $firstOption = $I->grabAttributeFrom(
            '#payment_request_payee option:nth-child(2)',
            'value',
        );
        $I->selectOption('#payment_request_payee', ['value' => $firstOption]);
        $I->fillField('#payment_request_amount', 0.04);
        $I->dontSeeElement('#payment_request_shareholding');
        $I->click('Add Payment');
        $I->seeCurrentUrlEquals("/admin/monthend/dividends/{$newOrderId}");

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
            "/admin/payment-request/{$paymentRequestId}/edit?redirectRoute=admin_monthend_dividend_manage",
        );
        $I->seeLink(
            'Delete',
            "/admin/payment-request/{$paymentRequestId}/delete?redirectRoute=admin_monthend_dividend_manage",
        );

        // Edit the payment
        $I->click('Edit', '#payments-list tbody tr:first-child');
        $I->seeLink('Discard Changes', "/admin/monthend/dividends/{$newOrderId}");
        $I->fillField('#payment_request_amount', 0.08);
        $I->dontSeeElement('#payment_request_shareholding');
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

        // Test dividend generation
        $I->click('Generate Payments');
        $I->seeCurrentUrlEquals("/admin/monthend/dividends/{$newOrderId}/generate");
        // See some assistive information
        $I->seeElement('section#guidelines [data-field-name="asset-valuation"]');
        $I->seeElement('section#guidelines [data-field-name="shareholders-in-asset"]');
        $I->seeElement(
            'section#guidelines [data-field-name="expected-dividend-yield"]',
        );
        $I->seeElement(
            'section#guidelines [data-field-name="expected-dividend-to-pay"]',
        );
        $I->seeElement('section#guidelines [data-field-name="debit-wallet-balance"]');
        // Generate the dividends
        $I->fillField('#payment_order_generate_amount', '0.16');
        $I->selectOption(
            'form input[name="payment_order_generate[method]"]',
            'distribute',
        );
        $I->click('Generate Payments');
        $I->seeCurrentUrlEquals("/admin/monthend/dividends/{$newOrderId}");
        // Check there's a warning about overwriting payments if generating again
        $I->amOnPage("/admin/monthend/dividends/{$newOrderId}/generate");
        $I->seeElement('section#overwrite-warning');
        $I->amOnPage("/admin/monthend/dividends/{$newOrderId}");

        // Check auto-fill behaviour
        $I->assertEquals(
            date('Y-m-01'),
            $I->grabTextFrom('[data-field-name="scheduled-monthend"]'),
        );
        $I->assertEquals(PaymentType::Dividend->value, $I->grabFromDatabase(
            'payment_order',
            'paymentType',
            [
                'id' => $newOrderId,
            ],
        ));

        // Check generation worked
        $shareholders = $I->grabTextFrom('[data-field-name="current-shareholders"]');
        $I->seeNumberOfElements('#payments-list tbody tr', (int) $shareholders);
        $I->assertEquals(
            0.16,
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
        $I->seeCurrentUrlEquals("/admin/monthend/dividends/{$newOrderId}");
        $I->seeNumberOfElements('#payments-list tbody tr', $shareholders - 1);
        $I->assertEquals(
            round(0.16 - $firstPaymentAmount, 2),
            explode('/', $I->grabTextFrom('[data-field-name="total-paid-(£)"]'))[1],
        );
        $I->assertEquals(
            $shareholders - 1,
            explode('/', $I->grabTextFrom('[data-field-name="payments-completed"]'))[1],
        );

        // Clear all remaining payments
        $I->click('Clear All Payments');
        $I->seeCurrentUrlEquals(
            "/admin/payment-order/{$newOrderId}/clear-payments?redirectRoute=admin_monthend_dividend_manage",
        );
        $I->see('Confirm Deletion');
        $I->seeLink('Discard Changes', "/admin/monthend/dividends/{$newOrderId}");
        $I->click('Clear All Payments');
        $I->seeCurrentUrlEquals("/admin/monthend/dividends/{$newOrderId}");
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
            "/admin/monthend/dividends/{$newOrderId}#payments-list",
        );
    }
}
