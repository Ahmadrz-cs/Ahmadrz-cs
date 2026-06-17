<?php

namespace App\Tests\Functional\Ops\TransferOrder;

use App\Entity\Enum\TransferType;
use App\Tests\Support\FunctionalTester;

class TransferOrderCreateCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function createTransferOrder(FunctionalTester $I): void
    {
        /**
         * Go to index
         * Click on button "Create Transfer Order"
         * Fill in all fields with specific data
         * Click on button "Create Transfer Order"
         * Check defaults in manage order
         * Check order is on the index/list view
         */
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name like' => 'Lodge de Lac%',
        ]);
        $scheduledFor = '2020-08-14';
        $description = 'Automated test create transfer order';

        $I->amOnPage('/admin/transfer-orders');
        $I->click('Create Transfer Order');
        $I->seeCurrentUrlEquals('/admin/transfer-orders/create');

        $I->seeOptionIsSelected(
            '#transfer_order_transferType',
            ucfirst(TransferType::Custom->value),
        );
        $I->selectOption('#transfer_order_asset', ['value' => (string) $assetId]);
        $I->fillField('#transfer_order_scheduledFor', $scheduledFor);
        $I->fillField('#transfer_order_description', $description);
        $I->click('Create Transfer Order');
        $transferOrderId = $I->grabTextFrom('[data-field-name="order-id"]');
        $I->seeCurrentUrlEquals('/admin/transfer-orders/'
        . $transferOrderId
        . '/manage');
        $I->seeLink('Go to Monthend Dashboard', "/admin/monthend/{$assetId}");

        $expected = [
            'asset' => "#$assetId",
            'scheduled-for' => $scheduledFor,
            'description' => $description,
            'status' => 'draft',
            'most-recently-completed' => '-',
            'transfers-pending' => '0',
            'transfers-completed' => '0',
            'total-to-transfer-(£)' => '0.00',
            'total-transfers' => '0',
        ];
        foreach ($expected as $fieldName => $value) {
            $I->see($value, '[data-field-name="' . $fieldName . '"]');
        }

        $I->amOnPage('/admin/transfer-orders');
        $I->see($transferOrderId, '#transfer-order-list tbody tr td:nth-child(2)');
    }
}
