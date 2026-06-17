<?php

namespace App\Tests\Functional\Ops\MonthEnd\Repayment;

use App\Entity\Enum\PaymentType;
use App\Tests\Support\FunctionalTester;

class RepaymentPaymentCreateCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function createRepaymentPayment(FunctionalTester $I): void
    {
        $newOrderId = $I->createPaymentOrder(
            'Lodge de Lac - Cumbria',
            PaymentType::Repayment,
        );
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Lodge de Lac - Cumbria',
        ]);

        // Should be on monthend date page
        $I->see('Payment order successfully created', '.alert');
        $I->seeCurrentUrlEquals(
            "/admin/payment-orders/{$newOrderId}/date?setup=1&redirectRoute=admin_monthend_repayment_manage",
        );
        $I->seeLink('Back', "/admin/monthend/repayments/{$newOrderId}");

        // Next is the payment description editor
        $I->click('Save Changes');
        $I->see('Payment order successfully updated', '.alert');
        $I->seeLink('Back', "/admin/monthend/repayments/{$newOrderId}");
        $I->seeCurrentUrlEquals(
            "/admin/payment-orders/{$newOrderId}/description?setup=1&redirectRoute=admin_monthend_repayment_manage",
        );

        // Then onto the payment generation
        $I->click('Save Changes');
        $I->see('Payment order successfully updated', '.alert');
        $I->seeLink('Back', "/admin/monthend/repayments/{$newOrderId}");
        $I->seeCurrentUrlEquals(
            "/admin/monthend/repayments/{$newOrderId}/generate?setup=1&redirectRoute=admin_monthend_repayment_manage",
        );

        // Finally reach the overview
        $I->fillField('#payment_order_generate_shares', '3');
        $I->click('Generate Payments');
        $I->seeCurrentUrlEquals("/admin/monthend/repayments/{$newOrderId}");
        $I->seeLink('Back', "/admin/monthend/{$assetId}");

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
        $I->seeNumberOfElements('#payments-list tbody tr', (int) $prefunders);
        $I->assertEquals(
            3,
            $I->grabTextFrom('[data-field-name="shares-remaining-in-current-order"]'),
        );
        $I->assertEquals(
            $prefunders,
            explode('/', $I->grabTextFrom('[data-field-name="payments-completed"]'))[1],
        );

        // Quick create should sen you straight to the generate page
        $newOrderId = $I->createPaymentOrder(
            'Lodge de Lac - Cumbria',
            PaymentType::Repayment,
            true,
        );
        $I->seeCurrentUrlEquals("/admin/monthend/repayments/{$newOrderId}/generate");
    }

    public function createRepaymentPaymentNoPrefunders(FunctionalTester $I): void
    {
        $newOrderId = $I->createPaymentOrder(
            'Royal Eversea Glades - Cambridge',
            PaymentType::Repayment,
        );
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);

        // Currently won't prevent you from trying to make a payment order if there are no prefunders
        $I->click('Save Changes');
        $I->click('Save Changes');
        $I->fillField('#payment_order_generate_shares', '1');
        $I->click('Generate Payments');

        $I->seeCurrentUrlEquals("/admin/monthend/repayments/{$newOrderId}");
        $I->seeLink('Back', "/admin/monthend/{$assetId}");
        $I->see(
            'Unable to run generate payments. Trying to liquidate more shares than still circulating',
        );
    }
}
