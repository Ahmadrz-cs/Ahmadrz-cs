<?php

namespace App\Tests\Functional\Ops\MonthEnd\FeeCollection;

use App\Entity\Enum\TransferOrderPreset;
use App\Entity\Enum\TransferType;
use App\Service\MonthEndService;
use App\Tests\Support\FunctionalTester;

class FeeCollectionCreateCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function createFeeCollection(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/monthend/assets');
        $I->click('Create Fee Collection');
        $I->seeCurrentUrlEquals('/admin/monthend/fee-collections/create');

        $I->seeLink('Back', '/admin/monthend/fee-collections');
        $I->seeLink('Abandon', '/admin/monthend/fee-collections');
        $I->click('Create Transfer Order');

        $transferOrders = $I->grabColumnFromDatabase('transfer_order', 'id', [
            'transferType' => TransferType::FeeCollection->value,
        ]);
        sort($transferOrders);
        $newOrderId = array_pop($transferOrders);

        $transferType = $I->grabFromDatabase('transfer_order', 'transferType', [
            'id' => $newOrderId,
        ]);

        $I->assertEquals(TransferType::FeeCollection->value, $transferType);
        $I->see('Transfer order successfully created', '.alert');
        $I->seeCurrentUrlEquals("/admin/monthend/fee-collections/{$newOrderId}/setup");
    }
}
