<?php

namespace App\Tests\Functional\Ops\MonthEnd\IncomeDisaggregation;

use App\Tests\Support\FunctionalTester;

class IncomeDisaggregationRunCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function testRunOrder(FunctionalTester $I): void
    {
        $newOrderId = $I->createIncomeDisaggregationOrder();
        $I->addTransferToIncomeDisaggregationOrder('0.02');
        $I->addTransferToIncomeDisaggregationOrder('0.02');
        $I->click('Approve Transfer Order');

        $I->amOnPage(
            "/admin/monthend/income-disaggregations/{$newOrderId}/add-transfer",
        );
        $I->seeCurrentUrlEquals("/admin/monthend/income-disaggregations/{$newOrderId}");
        $I->see('Transfers can only be added when the order is in draft mode');

        $amounts = $I->grabMultiple('#transfers-list td[data-field="amount"]');
        $amounts = array_map(fn(string $value): string => str_replace(
            ',',
            '',
            $value,
        ), $amounts);
        $total = array_sum($amounts);
        $total = (string) round($total, 2);

        // Check transfers summary at the start
        $I->assertEquals(
            "0.00/{$total}",
            $I->grabTextFrom('[data-field-name="total-transferred-(£)"]'),
        );
        $I->assertEquals(
            '0/2',
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
            '1/2',
            $I->grabTextFrom('[data-field-name="transfers-completed"]'),
        );
        $I->assertStringContainsString(
            date('Y-m-d'),
            $I->grabTextFrom('[data-field-name="most-recently-completed"]'),
        );

        // Run the rest of the transfer order
        $I->click('Run Transfer Order');
        $I->seeCurrentUrlEquals("/admin/monthend/income-disaggregations/{$newOrderId}");
        $I->assertEquals('Completed', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals(
            "{$total}/{$total}",
            $I->grabTextFrom('[data-field-name="total-transferred-(£)"]'),
        );
        $I->assertEquals(
            '2/2',
            $I->grabTextFrom('[data-field-name="transfers-completed"]'),
        );

        // Should see a prompt to return to the monthend hub
        $I->seeLink('Back to Monthend Hub', '/admin/monthend');
    }
}
