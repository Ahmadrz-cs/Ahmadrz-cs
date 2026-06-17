<?php

namespace App\Tests\Functional\Ops\PaymentOrder;

use App\Tests\Support\FunctionalTester;

class PaymentOrderPayCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function testPaySingle(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/payment-order');
        $I->click('Create Payment Order');
        // Need an asset with at least 2 shareholders
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name like' => 'Royal Eversea%',
        ]);
        $I->selectOption('#payment_order_asset', ['value' => (string) $assetId]);
        $I->click('Create Payment Order');

        // Only orders with at least 1 payment can be approved
        $I->addPaymentToOrder(0.03);
        $I->addPaymentToOrder(0.0);
        $I->click('Approve Payment Order');
        $I->assertEquals('Approved', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals(
            $I::USER_SUPER_ADMIN,
            $I->grabTextFrom('[data-field-name="approved-by"]'),
        );
        $I->assertEquals('2', $I->grabTextFrom('[data-field-name="payments-pending"]'));
        $I->assertEquals('0', $I->grabTextFrom('[data-field-name="payments-paid"]'));

        $I->click('Pay Single', '#payments-list tbody tr:first-child');
        $I->see('Payment successfully made');
        $I->assertEquals('In-progress', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals('1', $I->grabTextFrom('[data-field-name="payments-pending"]'));
        $I->assertEquals('1', $I->grabTextFrom('[data-field-name="payments-paid"]'));
        $I->see('View Payout', '#payments-list tbody tr:first-child');
        $I->seeLink('View Payout');

        $I->click('Pay Single', '#payments-list tbody tr:nth-child(2)');
        $I->assertEquals('Completed', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals('0', $I->grabTextFrom('[data-field-name="payments-pending"]'));
        $I->assertEquals('2', $I->grabTextFrom('[data-field-name="payments-paid"]'));
        $I->dontSee('View Payout', '#payments-list tbody tr:nth-child(2)');
    }

    public function testRunOrder(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/payment-order');
        $I->click('Create Payment Order');
        // Need an asset with at least 3 shareholders
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name like' => 'Royal Eversea%',
        ]);
        $I->selectOption('#payment_order_asset', ['value' => (string) $assetId]);
        $I->click('Create Payment Order');

        // Only orders with at least 1 payment can be approved
        $I->addPaymentToOrder(0.03);
        $I->addPaymentToOrder(0.0);
        $I->addPaymentToOrder(0.02);

        // Check proportions are correct when total is below £1
        // 3p is 60% of the 5p total
        // 2p is 40% of the 5p total
        // Proportions are set to 3 decimal places
        $I->see('60.000', '#payments-list tbody tr:nth-child(1)');
        $I->see('40.000', '#payments-list tbody tr:nth-child(3)');

        $I->click('Approve Payment Order');
        $I->assertEquals('Approved', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals(
            $I::USER_SUPER_ADMIN,
            $I->grabTextFrom('[data-field-name="approved-by"]'),
        );
        $I->assertEquals('3', $I->grabTextFrom('[data-field-name="payments-pending"]'));
        $I->assertEquals('0', $I->grabTextFrom('[data-field-name="payments-paid"]'));

        $I->click('Pay Single', '#payments-list tbody tr:first-child');
        $I->assertEquals('In-progress', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals('2', $I->grabTextFrom('[data-field-name="payments-pending"]'));
        $I->assertEquals('1', $I->grabTextFrom('[data-field-name="payments-paid"]'));
        $I->see('View Payout', '#payments-list tbody tr:first-child');

        $I->click('Run Payment Order');
        $I->see('Payment order successfully run');
        $I->assertEquals('Completed', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals('0', $I->grabTextFrom('[data-field-name="payments-pending"]'));
        $I->assertEquals('3', $I->grabTextFrom('[data-field-name="payments-paid"]'));
        $I->dontSee('View Payout', '#payments-list tbody tr:nth-child(2)');
        $I->see('View Payout', '#payments-list tbody tr:nth-child(3)');
    }
}
