<?php

namespace App\Tests\Functional\Ops\MonthEnd\Dividend;

use App\Tests\Support\FunctionalTester;

class DividendPaymentRunCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function testRunOrder(FunctionalTester $I): void
    {
        $newOrderId = $I->createPaymentOrder('Royal Eversea Glades - Cambridge');
        $I->amOnPage("/admin/monthend/dividends/{$newOrderId}/generate");
        $I->fillField('#payment_order_generate_amount', '0.15');
        $I->selectOption(
            'form input[name="payment_order_generate[method]"]',
            'distribute',
        );
        $I->click('Generate Payments');
        $I->click('Approve Payment Order');

        // Check payments summary at the start
        $shareholders = $I->grabTextFrom('[data-field-name="current-shareholders"]');
        $firstPaymentAmount = $I->grabTextFrom(
            '#payments-list tbody tr:first-child [data-field="amount"]',
        );
        $I->assertEquals(
            '0.00/0.15',
            $I->grabTextFrom('[data-field-name="total-paid-(£)"]'),
        );
        $I->assertEquals(
            "0/{$shareholders}",
            $I->grabTextFrom('[data-field-name="payments-completed"]'),
        );
        $I->assertEquals(
            '-',
            $I->grabTextFrom('[data-field-name="most-recently-completed"]'),
        );

        // Check payments summary after paying a single payment (the first one)
        $I->click('Pay Single', '#payments-list tbody tr:first-child');
        $I->assertEquals('In-progress', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals(
            "{$firstPaymentAmount}/0.15",
            $I->grabTextFrom('[data-field-name="total-paid-(£)"]'),
        );
        $I->assertEquals(
            "1/{$shareholders}",
            $I->grabTextFrom('[data-field-name="payments-completed"]'),
        );
        $I->assertStringContainsString(
            date('Y-m-d'),
            $I->grabTextFrom('[data-field-name="most-recently-completed"]'),
        );

        // Run the rest of the payment order
        $I->click('Run Payment Order');
        $I->seeCurrentUrlEquals("/admin/monthend/dividends/{$newOrderId}");
        $I->assertEquals('Completed', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals(
            '0.15/0.15',
            $I->grabTextFrom('[data-field-name="total-paid-(£)"]'),
        );
        $I->assertEquals(
            "{$shareholders}/{$shareholders}",
            $I->grabTextFrom('[data-field-name="payments-completed"]'),
        );

        // Should see a prompt to return to the monthend checklist
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);
        $I->seeLink('Back to Monthend Checklist', "/admin/monthend/{$assetId}");
    }
}
