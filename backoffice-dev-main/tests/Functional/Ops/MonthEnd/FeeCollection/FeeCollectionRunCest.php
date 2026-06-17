<?php

namespace App\Tests\Functional\Ops\MonthEnd\FeeCollection;

use App\Tests\Support\FunctionalTester;

class FeeCollectionRunCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function testRunOrder(FunctionalTester $I): void
    {
        $newOrderId = $I->createFeeCollectionOrder();
        $I->amOnPage("/admin/monthend/fee-collections/{$newOrderId}/generate");
        $matches = $I->grabTextFrom('[data-field-name="number-of-matches"]');
        $I->click('Generate All Transfers');
        $I->click('Approve Transfer Order');

        $I->amOnPage("/admin/monthend/fee-collections/{$newOrderId}/generate");
        $I->seeCurrentUrlEquals("/admin/monthend/fee-collections/{$newOrderId}");
        $I->see('Transfers can only be added when the order is in draft mode');

        $amounts = $I->grabMultiple('#transfers-list td[data-field="amount"]');
        $amounts = array_map(fn(string $value): string => str_replace(
            ',',
            '',
            $value,
        ), $amounts);
        $total = array_sum($amounts);
        $total = number_format($total, 2);

        // Check transfers summary at the start
        $I->assertEquals(
            "0.00/{$total}",
            $I->grabTextFrom('[data-field-name="total-transferred-(£)"]'),
        );
        $I->assertEquals(
            "0/{$matches}",
            $I->grabTextFrom('[data-field-name="transfers-completed"]'),
        );
        $I->assertEquals(
            '-',
            $I->grabTextFrom('[data-field-name="most-recently-completed"]'),
        );

        // Check transfers summary after paying a single transfer (the first one)
        $I->click('Transfer Single', '#transfers-list tbody tr:first-child');
        $I->assertEquals('In-progress', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals(
            "{$amounts[0]}/{$total}",
            $I->grabTextFrom('[data-field-name="total-transferred-(£)"]'),
        );
        $I->assertEquals(
            "1/{$matches}",
            $I->grabTextFrom('[data-field-name="transfers-completed"]'),
        );
        $I->assertStringContainsString(
            date('Y-m-d'),
            $I->grabTextFrom('[data-field-name="most-recently-completed"]'),
        );

        // Run the rest of the transfer order
        $I->click('Run Transfer Order');
        $I->seeCurrentUrlEquals("/admin/monthend/fee-collections/{$newOrderId}");
        $I->assertEquals('Completed', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals(
            "{$total}/{$total}",
            $I->grabTextFrom('[data-field-name="total-transferred-(£)"]'),
        );
        $I->assertEquals(
            "{$matches}/{$matches}",
            $I->grabTextFrom('[data-field-name="transfers-completed"]'),
        );

        // Should see a prompt to return to the monthend hub
        $I->seeLink('Back to Monthend Hub', '/admin/monthend');
    }
}
