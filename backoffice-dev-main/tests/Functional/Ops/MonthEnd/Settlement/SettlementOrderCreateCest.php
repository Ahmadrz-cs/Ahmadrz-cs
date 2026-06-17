<?php

namespace App\Tests\Functional\Ops\MonthEnd\Settlement;

use App\Entity\Enum\TransferOrderPreset;
use App\Entity\Enum\TransferType;
use App\Tests\Support\FunctionalTester;

class SettlementOrderCreateCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function createSettlementOrder(FunctionalTester $I): void
    {
        $newOrderId = $I->createSettlementOrder('Royal Eversea Glades - Cambridge');
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);

        $transferType = $I->grabFromDatabase('transfer_order', 'transferType', [
            'id' => $newOrderId,
        ]);
        $I->assertEquals(TransferType::InvestmentSettlement->value, $transferType);

        // Should be on the template picker page
        $I->see('Settlement order successfully created', '.alert');
        $I->seeCurrentUrlEquals("/admin/monthend/settlements/{$newOrderId}");
        $I->seeLink('Back', "/admin/monthend/{$assetId}");

        // Check auto-fill behaviour
        $I->assertEquals(
            date('Y-m'),
            $I->grabTextFrom('[data-field-name="scheduled-monthend"]'),
        );
        $I->assertEquals(
            TransferOrderPreset::InvestmentSettlement->value,
            $I->grabTextFrom('[data-field-name="description"]'),
        );

        // Quick create should sen you straight to the generate page
        $newOrderId = $I->createSettlementOrder(
            'Royal Eversea Glades - Cambridge',
            true,
        );
        $I->seeCurrentUrlEquals("/admin/monthend/settlements/{$newOrderId}/generate");
    }
}
