<?php

namespace App\Tests\Functional\Ops\MonthEnd\Divestment;

use App\Entity\Enum\PaymentType;
use App\Tests\Support\FunctionalTester;

class DivestmentPaymentCreateCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function createDivestmentPayment(FunctionalTester $I): void
    {
        $newOrderId = $I->createPaymentOrder(
            'Lodge de Lac - Cumbria',
            PaymentType::Divestment,
        );
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Lodge de Lac - Cumbria',
        ]);

        // Should be on monthend date page
        $I->see('Payment order successfully created', '.alert');
        $I->seeCurrentUrlEquals(
            "/admin/payment-orders/{$newOrderId}/date?setup=1&redirectRoute=admin_monthend_divestment_manage",
        );
        $I->seeLink('Back', "/admin/monthend/divestments/{$newOrderId}");

        // Next is the payment description editor
        $I->click('Save Changes');
        $I->see('Payment order successfully updated', '.alert');
        $I->seeLink('Back', "/admin/monthend/divestments/{$newOrderId}");
        $I->seeCurrentUrlEquals(
            "/admin/payment-orders/{$newOrderId}/description?setup=1&redirectRoute=admin_monthend_divestment_manage",
        );

        // Then onto the payment generation
        $I->click('Save Changes');
        $I->see('Payment order successfully updated', '.alert');
        $I->seeLink('Back', "/admin/monthend/divestments/{$newOrderId}");
        $I->seeCurrentUrlEquals(
            "/admin/monthend/divestments/{$newOrderId}/generate?setup=1&redirectRoute=admin_monthend_divestment_manage",
        );

        // Finally reach the overview
        $I->fillField('#payment_order_generate_shares', '3');
        $I->fillField('#payment_order_generate_amount', '1.84');
        $I->click('Generate Payments');
        $I->seeCurrentUrlEquals("/admin/monthend/divestments/{$newOrderId}");
        $I->seeLink('Back', "/admin/monthend/{$assetId}");

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
        $I->seeNumberOfElements('#payments-list tbody tr', (int) $shareholders);
        $I->assertEquals(
            '1.84',
            explode('/', $I->grabTextFrom('[data-field-name="total-paid-(£)"]'))[1],
        );
        $I->assertEquals(
            $shareholders,
            explode('/', $I->grabTextFrom('[data-field-name="payments-completed"]'))[1],
        );

        // If you try to generate more shares than are available in circulation, you get an error
        $I->amOnPage("/admin/monthend/divestments/{$newOrderId}/generate");
        $I->fillField('#payment_order_generate_shares', '10111000111');
        $I->fillField('#payment_order_generate_amount', '10');
        $I->click('Generate Payments');
        $I->see(
            'Unable to run generate payments. Trying to liquidate more shares than still circulating',
        );

        // Cannot send any email notifications when no payments completed/paid yet
        $I->click('Manage Email Notifications');
        $I->seeCurrentUrlEquals("/admin/monthend/payments/{$newOrderId}/notifications");
        $I->dontSee('Send', '#payments-notification-list a');
    }

    public function createDivestmentPaymentNoShareholders(FunctionalTester $I): void
    {
        $newOrderId = $I->createPaymentOrder(
            'Sagittarius Eystar - Horizon',
            PaymentType::Divestment,
        );
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Sagittarius Eystar - Horizon',
        ]);

        // Currently won't prevent you from trying to make a payment order if there are no shareholders
        $I->click('Save Changes');
        $I->click('Save Changes');

        $I->seeCurrentUrlEquals("/admin/monthend/divestments/{$newOrderId}");
        $I->seeLink('Back', "/admin/monthend/{$assetId}");
        $I->see(
            'Unable to run generate payments. There are no shareholders in this asset',
        );
    }
}
