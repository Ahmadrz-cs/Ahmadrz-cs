<?php

namespace App\Tests\Functional\Ops\MonthEnd\Dividend;

use App\Entity\Enum\PaymentType;
use App\Tests\Support\FunctionalTester;

class DividendPaymentCreateCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function createDividendPayment(FunctionalTester $I): void
    {
        $newOrderId = $I->createPaymentOrder('Royal Eversea Glades - Cambridge');
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);

        // Should be on monthend date page
        $I->see('Payment order successfully created', '.alert');
        $I->seeCurrentUrlEquals(
            "/admin/payment-orders/{$newOrderId}/date?setup=1&redirectRoute=admin_monthend_dividend_manage",
        );
        $I->seeLink('Back', "/admin/monthend/dividends/{$newOrderId}");

        // Next is the payment description editor
        $I->click('Save Changes');
        $I->see('Payment order successfully updated', '.alert');
        $I->seeLink('Back', "/admin/monthend/dividends/{$newOrderId}");
        $I->seeCurrentUrlEquals(
            "/admin/payment-orders/{$newOrderId}/description?setup=1&redirectRoute=admin_monthend_dividend_manage",
        );

        // Then onto the payment generation
        $I->click('Save Changes');
        $I->see('Payment order successfully updated', '.alert');
        $I->seeLink('Back', "/admin/monthend/dividends/{$newOrderId}");
        $I->seeCurrentUrlEquals(
            "/admin/monthend/dividends/{$newOrderId}/generate?setup=1&redirectRoute=admin_monthend_dividend_manage",
        );

        // Finally reach the overview
        $I->fillField('#payment_order_generate_amount', '0.08');
        $I->selectOption(
            'form input[name="payment_order_generate[method]"]',
            'distribute',
        );
        $I->click('Generate Payments');
        $I->seeCurrentUrlEquals("/admin/monthend/dividends/{$newOrderId}");
        $I->seeLink('Back', "/admin/monthend/{$assetId}");

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
            0.08,
            explode('/', $I->grabTextFrom('[data-field-name="total-paid-(£)"]'))[1],
        );
        $I->assertEquals(
            $shareholders,
            explode('/', $I->grabTextFrom('[data-field-name="payments-completed"]'))[1],
        );

        // Quick create should sen you straight to the generate page
        $newOrderId = $I->createPaymentOrder(
            'Royal Eversea Glades - Cambridge',
            PaymentType::Dividend,
            true,
        );
        $I->seeCurrentUrlEquals("/admin/monthend/dividends/{$newOrderId}/generate");
    }

    public function createDividendPaymentNoShareholders(FunctionalTester $I): void
    {
        $newOrderId = $I->createPaymentOrder(
            'Sagittarius Eystar - Horizon',
            PaymentType::Dividend,
        );
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Sagittarius Eystar - Horizon',
        ]);

        // Currently won't prevent you from trying to make a payment order if there are no shareholders
        $I->click('Save Changes');
        $I->click('Save Changes');

        $I->seeCurrentUrlEquals("/admin/monthend/dividends/{$newOrderId}");
        $I->seeLink('Back', "/admin/monthend/{$assetId}");
        $I->see(
            'Unable to run generate payments. There are no shareholders in this asset',
        );
    }
}
