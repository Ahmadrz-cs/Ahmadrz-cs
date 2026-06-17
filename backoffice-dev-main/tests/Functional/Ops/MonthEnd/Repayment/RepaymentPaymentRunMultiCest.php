<?php

namespace App\Tests\Functional\Ops\MonthEnd\Repayment;

use App\Entity\Enum\PaymentType;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeStatus;
use App\Tests\Support\FunctionalTester;

class RepaymentPaymentRunMultiCest
{
    private string $fillerShareTradeId = '';
    private string $newShareTradeId = '';
    private string $prefundingSellOrderId = '';

    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();

        // We have a special share trade that is for 5250 out of 5263 shares for Freya's smaller sell order
        // We can transition this to settled so it counts towards the sell order total
        // And makes it easier to close
        $fillerProxyOrderId = $I->grabFromDatabase('trade_order', 'id', [
            'direction' => '1',
            'type' => 'proxy',
            'notes' => 'test proxy buybacks',
        ]);
        $this->fillerShareTradeId = $I->grabFromDatabase('share_trade', 'id', [
            'buyOrder_id' => $fillerProxyOrderId,
        ]);
        $this->prefundingSellOrderId = $I->grabFromDatabase(
            'share_trade',
            'sellOrder_id',
            [
                'id' => $this->fillerShareTradeId,
            ],
        );

        // Note that this will set ALL status logs for that share trade back to unsettled
        $I->updateInDatabase(
            'share_trade_status_log',
            ['status' => TradeStatus::Settled->value],
            ['shareTrade_id' => $this->fillerShareTradeId],
        );
    }

    public function _after(FunctionalTester $I)
    {
        // Change the share-trade back to cancelled to allow reruns
        $I->updateInDatabase(
            'share_trade_status_log',
            ['status' => TradeStatus::Cancelled->value],
            ['shareTrade_id' => $this->fillerShareTradeId],
        );
        $I->updateInDatabase(
            'share_trade_status_log',
            ['status' => TradeStatus::Cancelled->value],
            ['shareTrade_id' => $this->newShareTradeId],
        );
        // Change the trade order back to active
        $I->updateInDatabase(
            'trade_order_status_log',
            ['status' => TradeOrderStatus::Active->value],
            ['tradeOrder_id' => $this->prefundingSellOrderId],
        );
    }

    public function testRunOrderMultiSellorder(FunctionalTester $I): void
    {
        // Check that the prefunding sell order transitions to completed if it is full
        // 32 should be enough to overflow the smaller sell order onto the bigger one
        $sharesToRepay = 32;

        // Grab current repayment progress
        $newOrderId = $I->createPaymentOrder(
            'Lodge de Lac - Cumbria',
            PaymentType::Repayment,
        );
        $I->amOnPage("/admin/monthend/repayments/{$newOrderId}/generate");
        $I->fillField('#payment_order_generate_shares', $sharesToRepay);
        $I->click('Generate Payments');

        // Should be 2 payments generated, 2 for freya, 1 for lorna
        $I->assertCount(3, $I->grabMultiple('#payments-list tbody tr'));
        // Should see this smaller sell order appear in our generated payments
        $I->see($I::USER_VIP, '#payments-list tr:first-child [data-field="payee"]');
        $I->see(
            $this->prefundingSellOrderId,
            '#payments-list tr:first-child [data-field="sellOrderId"]',
        );
        // We'll then edit the amounts to reduce big wallet balance changes
        $I->click('Edit', '#payments-list tbody tr:first-child');
        $I->fillField('#payment_request_amount', 0.02);
        $I->click('Save Changes');

        $I->click('Approve Payment Order');

        // Do some pre-checks
        $I->amOnPage("/admin/trade-orders/{$this->prefundingSellOrderId}");
        $I->see('Active', '#trade-order-info [data-field-name="status"]');

        $I->amOnPage("/admin/monthend/repayments/{$newOrderId}");
        $I->click('Pay Single', '#payments-list tbody tr:first-child');

        $I->amOnPage("/admin/trade-orders/{$this->prefundingSellOrderId}");
        $I->see('Completed', '#trade-order-info [data-field-name="status"]');

        $this->newShareTradeId = $I->grabTextFrom(
            '#share-trades tr:last-child [data-field="id"] a',
        );
    }
}
