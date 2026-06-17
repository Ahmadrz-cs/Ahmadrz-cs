<?php

namespace App\Tests\Functional\Ops\MonthEnd\IncomeDisaggregation;

use App\Entity\Enum\TransferOrderPreset;
use App\Entity\Enum\TransferType;
use App\Tests\Support\FunctionalTester;

class IncomeDisaggregationCreateCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function createIncomeDisaggregation(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/monthend/income-disaggregations');
        $I->click('Create Income Disaggregation');
        $I->seeCurrentUrlEquals('/admin/monthend/income-disaggregations/create');

        $I->seeLink('Back', '/admin/monthend/income-disaggregations');
        $I->seeLink('Abandon', '/admin/monthend/income-disaggregations');
        $I->click('Create Transfer Order');

        $transferOrders = $I->grabColumnFromDatabase('transfer_order', 'id', [
            'description' => TransferOrderPreset::IncomeDisaggregation->value,
        ]);
        sort($transferOrders);
        $newOrderId = array_pop($transferOrders);

        $transferType = $I->grabFromDatabase('transfer_order', 'transferType', [
            'id' => $newOrderId,
        ]);
        $I->assertEquals(TransferType::IncomeDisaggregation->value, $transferType);
        $I->see('Transfer order successfully created', '.alert');
        $I->seeCurrentUrlEquals("/admin/monthend/income-disaggregations/{$newOrderId}");
    }
}
