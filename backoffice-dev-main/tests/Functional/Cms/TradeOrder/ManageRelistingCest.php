<?php

namespace App\Tests\Functional\Cms\TradeOrder;

use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Tests\Support\FunctionalTester;

class ManageRelistingCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function checkPendingRelistingList(FunctionalTester $I)
    {
        $I->amOnPage('/admin/trade-orders');
        $I->click('Manage Pending Relistings');
        $I->seeCurrentUrlEquals('/admin/trading/trade-orders/sell');
        $I->seeLink(
            'View All Relistings',
            '/admin/trade-orders?'
                . http_build_query([
                    'direction' => [TradeDirection::Sell->value],
                    'type' => [
                        TradeOrderType::Market->value,
                        TradeOrderType::Limit->value,
                        TradeOrderType::StopLoss->value,
                    ],
                ]),
        );

        $I->seeElement('#pending-sell-orders article [data-field-name="asset-name"]');
        $I->seeElement('#pending-sell-orders article [data-field-name="asset-id"]');
        $I->seeElement('#pending-sell-orders article [data-field-name="asset-spv"]');
        $I->seeElement('#pending-sell-orders article [data-field-name="asset-status"]');
        $I->seeElement(
            '#pending-sell-orders article [data-field-name="asset-trading-status"]',
        );
        $I->seeElement(
            '#pending-sell-orders article [data-field-name="asset-min-invest"]',
        );
        $I->seeElement('#pending-sell-orders article [data-field-name="username"]');
        $I->seeElement('#pending-sell-orders article [data-field-name="user-id"]');
        $I->seeElement(
            '#pending-sell-orders article [data-field-name="user-account-status"]',
        );
        $I->seeElement(
            '#pending-sell-orders article [data-field-name="user-lifecycle-status"]',
        );
        $I->seeElement('#pending-sell-orders article [data-field-name="order-value"]');
        $I->seeElement('#pending-sell-orders article [data-field-name="order-price"]');
        $I->seeElement(
            '#pending-sell-orders article [data-field-name="order-quantity"]',
        );
        $I->seeElement('#pending-sell-orders article [data-field-name="order-fees"]');
        $I->seeElement('#pending-sell-orders article [data-field-name="order-taxes"]');
        $I->seeElement('#pending-sell-orders article [data-field-name="order-type"]');
        $I->seeElement('#pending-sell-orders article [data-field-name="order-status"]');
        $I->seeElement(
            '#pending-sell-orders article [data-field-name="order-created-at"]',
        );
        $I->seeElement('#pending-sell-orders article [data-field-name="order-id"]');
        $I->seeElement(
            '#pending-sell-orders article [data-field-name="order-min-shares"]',
        );
        $I->seeElement('#pending-sell-orders article [data-field-name="order-uuid"]');

        $orderId = $I->grabAttributeFrom(
            '#pending-sell-orders article:first-child',
            'data-order-id',
        );
        $assetId = substr(
            $I->grabTextFrom(
                '#pending-sell-orders article:first-child [data-field-name="asset-id"]',
            ),
            3,
        );
        $useId = substr(
            $I->grabTextFrom(
                '#pending-sell-orders article:first-child [data-field-name="user-id"]',
            ),
            3,
        );

        $I->seeLink('View Asset', "/admin/products/{$assetId}/trade-orders");
        $I->seeLink('View User', "/admin/users/{$useId}/dashboard/trade-orders");
        $I->seeLink('View Order', "/admin/trade-orders/{$orderId}");
        $I->seeLink(
            'Publish',
            "/admin/trade-orders/{$orderId}/status-logs/create/active?redirectRoute=admin_trading_hub_sell_orders_pending",
        );
        $I->seeLink(
            'Cancel',
            "/admin/trade-orders/{$orderId}/status-logs/create/cancelled?redirectRoute=admin_trading_hub_sell_orders_pending",
        );

        // $I->seeLink('Edit Status', "/admin/trade-orders/{$orderId}/status-logs/create");
    }

    public function checkQuickPublish(FunctionalTester $I)
    {
        $I->amOnPage('/admin/trading/trade-orders/sell');
        $countBefore = count($I->grabMultiple('#pending-sell-orders article'));
        $firstRelistingId = $I->grabAttributeFrom(
            '#pending-sell-orders article:first-child',
            'data-order-id',
        );
        $I->click('Publish', '#pending-sell-orders article:first-child');
        $I->see('Status is now active');
        $I->seeCurrentUrlEquals('/admin/trading/trade-orders/sell');
        $countAfter = count($I->grabMultiple('#pending-sell-orders article'));
        $I->assertEquals(1, $countBefore - $countAfter);

        // Check the status has updated
        $I->amOnPage("/admin/trade-orders/{$firstRelistingId}");
        $I->see('Active', '#trade-order-info [data-field-name="status"]');

        // Try to do an invalid transition to draft
        $I->amOnPage(
            "/admin/trade-orders/{$firstRelistingId}/status-logs/create/draft?redirectRoute=admin_trading_hub_sell_orders_pending",
        );
        $I->seeCurrentUrlEquals('/admin/trading/trade-orders/sell');
        $I->see('Quick transitioning from active to draft is not supported');

        // Change relisting back to draft
        $I->updateInDatabase(
            'trade_order_status_log',
            ['status' => TradeOrderStatus::Draft->value],
            ['tradeOrder_id' => $firstRelistingId],
        );

        $I->amOnPage('/admin/trading/trade-orders/sell');
        $countBefore = count($I->grabMultiple('#pending-sell-orders article'));
        $I->click('Cancel', '#pending-sell-orders article:first-child');
        $I->see('Status is now cancelled');
        $I->seeCurrentUrlEquals('/admin/trading/trade-orders/sell');
        $countAfter = count($I->grabMultiple('#pending-sell-orders article'));
        $I->assertEquals(1, $countBefore - $countAfter);
        // Check the status has updated
        $I->amOnPage("/admin/trade-orders/{$firstRelistingId}");
        $I->see('Cancelled', '#trade-order-info [data-field-name="status"]');

        // Try to do an invalid transition back to Submitted
        $I->amOnPage(
            "/admin/trade-orders/{$firstRelistingId}/status-logs/create/submitted?redirectRoute=admin_trading_hub_sell_orders_pending",
        );
        $I->seeCurrentUrlEquals('/admin/trading/trade-orders/sell');
        $I->see('Quick transitioning from cancelled to submitted is not supported');

        // Change relisting back to submitted for reruns
        $I->updateInDatabase(
            'trade_order_status_log',
            ['status' => TradeOrderStatus::Submitted->value],
            ['tradeOrder_id' => $firstRelistingId],
        );
    }
}
