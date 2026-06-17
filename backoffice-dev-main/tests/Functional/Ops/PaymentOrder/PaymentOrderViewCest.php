<?php

namespace App\Tests\Functional\Ops\PaymentOrder;

use App\Tests\Support\FunctionalTester;

class PaymentOrderViewCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function testPaymentOrderIndex(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/payment-order');
        $I->seeLink('Export Payment Orders', '/admin/payment-order/export');
        $elements = [
            'Id',
            'Type',
            'Asset',
            'Description',
            'Scheduled For',
            'Payments',
            'Status',
            'Actions',
        ];
        $I->loopCheckElements($elements, 'thead th');

        $filterLabels = [
            'Payment Order id',
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
        $I->see('Show/Hide Type Filters', 'form button');
        $I->see('Show/Hide Status Filters', 'form button');
    }

    public function testPaymentOrderInfo(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/payment-order/1/manage');
        $I->seeLink('Export Payments List', '/admin/payment-order/1/export');
        $elements = [
            'Id',
            'Payee',
            'Payee Wallet Id',
            'Amount to pay',
            'Proportion',
            'Shareholding',
            'Updated',
            'Status',
            'Actions',
        ];
        $I->loopCheckElements($elements, 'thead th');
    }
}
