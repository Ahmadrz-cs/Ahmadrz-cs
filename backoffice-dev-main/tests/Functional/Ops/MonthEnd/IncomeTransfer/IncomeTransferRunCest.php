<?php

namespace App\Tests\Functional\Ops\MonthEnd\IncomeTransfer;

use App\Tests\Support\FunctionalTester;

class IncomeTransferRunCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function testRunOrder(FunctionalTester $I): void
    {
        $newOrderId = $I->createIncomeTransferOrder('Royal Eversea Glades - Cambridge');
        $I->amOnPage("/admin/monthend/income-transfers/{$newOrderId}");
        $I->addAssetTransferToOrder('Expenses', '0.02', 'tuppence');
        $I->addAssetTransferToOrder('Tax', '0.01', 'penny');
        $I->addAssetTransferToOrder('Treasury', '0.03', 'thruppence');
        $I->click('Approve Transfer Order');

        // Check transfers summary at the start
        $I->assertEquals(
            '0.00/0.06',
            $I->grabTextFrom('[data-field-name="total-transferred-(£)"]'),
        );
        $I->assertEquals(
            '0/3',
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
            '0.02/0.06',
            $I->grabTextFrom('[data-field-name="total-transferred-(£)"]'),
        );
        $I->assertEquals(
            '1/3',
            $I->grabTextFrom('[data-field-name="transfers-completed"]'),
        );
        $I->assertStringContainsString(
            date('Y-m-d'),
            $I->grabTextFrom('[data-field-name="most-recently-completed"]'),
        );

        // Run the rest of the transfer order
        $I->click('Run Transfer Order');
        $I->seeCurrentUrlEquals("/admin/monthend/income-transfers/{$newOrderId}");
        $I->assertEquals('Completed', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals(
            '0.06/0.06',
            $I->grabTextFrom('[data-field-name="total-transferred-(£)"]'),
        );
        $I->assertEquals(
            '3/3',
            $I->grabTextFrom('[data-field-name="transfers-completed"]'),
        );

        // Should see a prompt to return to the monthend checklist
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);
        $I->seeLink('Back to Monthend Checklist', "/admin/monthend/{$assetId}");
    }
}
