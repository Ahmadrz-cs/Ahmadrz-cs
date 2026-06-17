<?php

namespace App\Tests\Functional\Ops\PaymentOrder;

use App\Tests\Support\FunctionalTester;

class PaymentOrderCreateCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function createPaymentOrder(FunctionalTester $I): void
    {
        /**
         * Go to index
         * Click on button "Create Payment Order"
         * Fill in all fields with specific data
         * Click on button "Create Payment Order"
         * Check defaults in manage order
         * Check order is on the index
         */
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name like' => 'Lodge de Lac%',
        ]);
        $paymentType = 'Divestment';
        $scheduledFor = '2020-08-14';
        $description = 'Automated test create payment order';

        $I->amOnPage('/admin/payment-order');
        $I->click('Create Payment Order');
        $I->seeCurrentUrlEquals('/admin/payment-order/create');

        $I->selectOption('form input[name="payment_order[paymentType]"]', $paymentType);
        $I->selectOption('#payment_order_asset', ['value' => (string) $assetId]);
        $I->fillField('#payment_order_scheduledFor', $scheduledFor);
        $I->fillField('#payment_order_description', $description);
        $I->click('Create Payment Order');
        $paymentOrderId = $I->grabTextFrom('[data-field-name="order-id"]');
        $I->seeCurrentUrlEquals('/admin/payment-order/' . $paymentOrderId . '/manage');
        $I->seeLink('Go to Monthend Dashboard', "/admin/monthend/{$assetId}");

        $expected = [
            'payment-type' => $paymentType,
            'asset' => "#$assetId",
            'scheduled-for' => $scheduledFor,
            'description' => $description,
            'status' => 'draft',
            'most-recently-paid' => '-',
            'payments-pending' => '0',
            'payments-paid' => '0',
            'total-due-(£)' => '0.00',
            'total-shareholding' => '0',
            'total-payments' => '0',
        ];
        foreach ($expected as $fieldName => $value) {
            $I->see($value, '[data-field-name="' . $fieldName . '"]');
        }

        $additionalStats = [
            'asset-current-shareholders',
        ];
        foreach ($additionalStats as $fieldName) {
            $I->seeElement('[data-field-name="' . $fieldName . '"]');
        }

        $I->amOnPage('/admin/payment-order');
        $I->see($paymentOrderId, '#payment-order-list tbody tr td:nth-child(2)');
    }
}
