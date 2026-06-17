<?php

namespace App\Tests\Functional\Ops\MonthEnd\IncomeTransfer;

use App\Entity\Enum\TransferOrderPreset;
use App\Entity\Enum\TransferType;
use App\Service\MonthEndService;
use App\Tests\Support\FunctionalTester;

class IncomeTransferCreateCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function createIncomeTransfer(FunctionalTester $I): void
    {
        $newOrderId = $I->createIncomeTransferOrder('Royal Eversea Glades - Cambridge');
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);

        $transferType = $I->grabFromDatabase('transfer_order', 'transferType', [
            'id' => $newOrderId,
        ]);
        $I->assertEquals(TransferType::AssetIncomeProcessing->value, $transferType);

        // Should be on the template picker page
        $I->see('Transfer order successfully created', '.alert');
        $I->seeCurrentUrlEquals(
            "/admin/monthend/income-transfers/{$newOrderId}/template",
        );
        $I->seeLink('Back', "/admin/monthend/income-transfers/{$newOrderId}");

        // Default template
        $I->see('Default', '#default-template h3');
        $I->see('Build with Default Template', '#default-template a');
        $I->seeLink(
            'Build with Default Template',
            "/admin/monthend/income-transfers/{$newOrderId}/template-default",
        );
        // Default asset linked is Royal Eversea Glades - Cambridge
        // This asset has all wallets configured so all default transfers should be listed
        $I->seeNumberOfElements('#default-template table tbody tr', 6);
        $expectedDescriptions = [
            'accountancy' => 'Accountancy fees',
            'insurance' => 'Insurance',
            'corptax' => 'Corporation tax',
            'maintenance' => 'Maintenance accrual',
            'dividend' => 'Shareholder dividend',
            'management' => 'Yielders management fees',
        ];
        foreach ($expectedDescriptions as $transferDescription) {
            $I->see($transferDescription, '#default-template table tbody tr');
        }

        // Blank template
        $I->see('Blank', '#blank-template h3');
        $I->see('Use Blank Template', '#blank-template a');
        $I->seeLink(
            'Use Blank Template',
            "/admin/monthend/income-transfers/{$newOrderId}",
        );

        $I->click('Build with Default Template');
        $I->seeCurrentUrlEquals(
            "/admin/monthend/income-transfers/{$newOrderId}/builder/starting-balance",
        );
        $I->seeLink('Back', "/admin/monthend/income-transfers/{$newOrderId}");
        $I->amOnPage("/admin/monthend/income-transfers/{$newOrderId}");

        // Check auto-fill behaviour
        $I->assertEquals(
            date('Y-m'),
            $I->grabTextFrom('[data-field-name="scheduled-monthend"]'),
        );
        $I->assertEquals(
            TransferOrderPreset::IncomeTransfer->value,
            $I->grabTextFrom('[data-field-name="description"]'),
        );

        // Quick create should sen you straight to the template page
        $newOrderId = $I->createIncomeTransferOrder(
            'Royal Eversea Glades - Cambridge',
            true,
        );
        $I->seeCurrentUrlEquals(
            "/admin/monthend/income-transfers/{$newOrderId}/template",
        );
    }
}
