<?php

namespace App\Tests\Functional\Ops\TransferOrder;

use App\Tests\Support\FunctionalTester;

class TransferOrderViewCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function testTransferOrderIndex(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/transfer-orders');
        $I->seeLink('Export Transfer Orders', '/admin/transfer-orders/export');
        $elements = [
            'Id',
            'Type',
            'Asset',
            'Description',
            'Scheduled For',
            'Transfers',
            'Status',
            'Actions',
        ];
        $I->loopCheckElements($elements, 'thead th');

        $filterLabels = [
            'Transfer Order id',
            'Asset id',
            'Asset name',
            'Type',
            'Status',
            'CreatedAt Start',
            'CreatedAt End',
            'Items Per Page',
            'Order by',
            'Order Direction',
        ];
        // check table filters
        $I->loopCheckElements($filterLabels, 'form label');
        $I->see('Show/Hide Status Filters', 'form button');
    }

    public function testTransferOrderInfo(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/transfer-orders/1/manage');
        $I->seeLink('Export Transfers List', '/admin/transfer-orders/1/export');
        $elements = [
            'Id',
            'Debit Wallet Id',
            'Credit Wallet Id',
            'Mode',
            'Description',
            'Amount',
            'Proportion',
            'Updated',
            'Status',
            'Actions',
        ];
        $I->loopCheckElements($elements, 'thead th');
    }
}
