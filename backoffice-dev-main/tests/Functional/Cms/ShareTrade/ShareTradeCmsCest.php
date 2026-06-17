<?php

namespace App\Tests\Functional\Cms\Investment;

use App\Tests\Support\FunctionalTester;

class ShareTradeCmsCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    /**
     * @group listview
     */
    public function checkListViewElements(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/share-trades');

        $elements = [
            'Id',
            'Asset',
            'Sell Order',
            'Buy Order',
            'Seller',
            'Buyer',
            'Quantity',
            'Price',
            'Value',
            'Fees',
            'Taxes',
            'Status',
            'Created',
        ];
        // check table headers
        $I->loopCheckElements($elements, 'thead th');

        $filterLabels = [
            'Share trade id',
            'Asset id',
            'Asset name',
            'User id',
            'Sell Order id',
            'Seller id',
            'Seller username',
            'Buy order id',
            'Buyer id',
            'Buyer username',
            'Sell order type',
            'Buy order type',
            'Status',
            'CreatedAt Start',
            'CreatedAt End',
            'Items Per Page',
            'Order by',
            'Order Direction',
        ];
        // check table filters
        $I->loopCheckElements($filterLabels, '//form//label|//form//legend');
        $I->seeLink('Create Share Trade', '/admin/share-trades/create');

        // $I->seeNumberOfElements('#share-trades-list tbody tr', 10);
        // $I->selectOption('form select[name=perPage]', '5');
        // $I->click('Apply Filters');
        // $I->seeNumberOfElements('#share-trades-list tbody tr', 5);
        // // check max page bracketing (to deal with filter changing)
        // $I->amOnPage('/admin/share-trades?page=1000');
        // // Sends you to last page
        // $I->seeElement(['css' => '.pagination li:last-child.disabled']);
    }

    // /**
    //  * @group listview
    //  * @dataProvider filterProvider
    //  */
    // public function checkListViewFilters(
    //     FunctionalTester $I,
    //     \Codeception\Example $example,
    // ): void {
    //     if (empty($example['dbquery'])) {
    //         $example['dbquery'] = $example['filters'];
    //     }
    //     $count = $I->grabNumRecords('investments', $example['dbquery']);
    //     $I->amOnPage(
    //         '/admin/investment?'
    //         . http_build_query($example['filters'])
    //         . '&perPage=50',
    //     );
    //     $I->seeNumberOfElements('#share-trades-list tbody tr', min(50, $count)); // at most 50 results
    //     $I->see($count, '#list-meta-results');
    // }
    // protected function filterProvider(): array
    // {
    //     return [
    //         [
    //             'filters' => ['id' => '1'],
    //         ],
    //         [
    //             'filters' => [
    //                 'userId' => 1,
    //                 'type' => ['prefunding'],
    //             ],
    //             'dbquery' => [
    //                 'user_id' => 1,
    //                 'type' => 'prefunding',
    //             ],
    //         ],
    //         [
    //             'filters' => [
    //                 'offeringId' => 2,
    //             ],
    //             'dbquery' => [
    //                 'off_id' => 2,
    //             ],
    //         ],
    //         [
    //             'filters' => [
    //                 'createdAt_gte' => date_format(new \DateTime('-4 days'), 'Y-m-d'),
    //                 'createdAt_lt' => date_format(new \DateTime('-1 days'), 'Y-m-d'),
    //             ],
    //             'dbquery' => [
    //                 'createdAt >=' => date_format(
    //                     new \DateTime('-4 days')->setTime(0, 0),
    //                     \DateTime::ATOM,
    //                 ),
    //                 'createdAt <' => date_format(
    //                     new \DateTime('-1 days')->setTime(0, 0),
    //                     \DateTime::ATOM,
    //                 ),
    //             ],
    //         ],
    //     ];
    // }

    public function checkCreateAndEdit(FunctionalTester $I): void
    {
        // We'll create a buy and sell order independently for this test
        $sampleUser = $I->grabFromDatabase('users', 'id', [
            'username' => $I::USER_REG1,
        ]);
        $sampleUser2 = $I->grabFromDatabase('users', 'id', [
            'username' => $I::USER_REG2,
        ]);
        $sampleAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Sandfox Fields - Kent',
        ]);
        $superadmin = $I->grabFromDatabase('users', 'id', [
            'username' => $I::USER_SUPER_ADMIN,
        ]);
        $sampleAsset2 = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Partingdale House - Reading',
        ]);

        // Superadmin's sell order
        // Note that you could use any user at the moment
        // As there's no checks on whether they have shares available
        $I->amOnPage('/admin/trade-orders/create');
        $I->selectOption('#trade_order_direction', 'Sell');
        $I->selectOption('#trade_order_type', 'Initial');
        $I->selectOption('#trade_order_asset', (string) $sampleAsset);
        $I->selectOption('#trade_order_user', (string) $superadmin);
        $I->selectOption('#trade_order_status', 'active');
        $I->fillField('#trade_order_numberOfShares', '1000');
        $I->fillField('#trade_order_pricePerShare', '1.25');
        $I->click('Create Trade Order');
        $sellId = $I->grabTextFrom('#trade-order-info [data-field-name="id"]');

        // Buy order
        $I->amOnPage('/admin/trade-orders/create');
        $I->selectOption('#trade_order_direction', 'Buy');
        $I->selectOption('#trade_order_type', 'Market');
        $I->selectOption('#trade_order_asset', (string) $sampleAsset);
        $I->selectOption('#trade_order_user', (string) $sampleUser);
        $I->selectOption('#trade_order_status', 'active');
        $I->fillField('#trade_order_numberOfShares', '450');
        $I->fillField('#trade_order_pricePerShare', '1.25');
        $I->click('Create Trade Order');
        $buyId = $I->grabTextFrom('#trade-order-info [data-field-name="id"]');

        $I->amOnPage('/admin/share-trades');
        $I->click('Create Share Trade');
        $I->seeCurrentUrlEquals('/admin/share-trades/create');
        $I->fillField('#share_trade_sellOrder', (string) $sellId);
        $I->fillField('#share_trade_buyOrder', (string) $buyId);
        $I->fillField('#share_trade_numberOfShares', '450');
        $I->fillField('#share_trade_pricePerShare', '1.25');
        // Set the tradeValue to a negative number to auto-calculate
        $I->fillField('#share_trade_tradeValue', '-1');
        $I->selectOption('#share_trade_status', 'Unsettled');
        $I->click('Create Share Trade');

        $I->seeElement('#share-trade-overview #share-trade-info');
        $I->seeElement('#share-trade-overview #status-logs');
        $I->seeElement('#related-trade-orders #sell-order-info');
        $I->seeElement('#related-trade-orders #buy-order-info');
        $id = $I->grabTextFrom('#share-trade-info [data-field-name="id"]');
        $I->seeCurrentUrlEquals("/admin/share-trades/{$id}");
        $I->seeLink('View Asset', "/admin/products/{$sampleAsset}/share-trades");
        $I->seeLink('View Seller', "/admin/users/{$superadmin}/dashboard/share-trades");
        $I->seeLink('View Buyer', "/admin/users/{$sampleUser}/dashboard/share-trades");
        $I->seeLink('View Sell Order', "/admin/trade-orders/{$sellId}");
        $I->seeLink('View Buy Order', "/admin/trade-orders/{$buyId}");

        // Check what was created
        $I->see('Unsettled', '#share-trade-info [data-field-name="status"]');
        $I->see($sampleAsset, '#share-trade-info [data-field-name="asset"]');
        $I->see($I::USER_REG1, '#share-trade-info [data-field-name="buyer"]');
        $I->see($I::USER_SUPER_ADMIN, '#share-trade-info [data-field-name="seller"]');
        $I->see('450', '#share-trade-info [data-field-name="share-quantity"]');
        $I->see('1.25', '#share-trade-info [data-field-name="share-price"]');
        $I->see('562.50', '#share-trade-info [data-field-name="trade-value"]');
        $I->see('Yes', '#share-trade-info [data-field-name="derived-trade-value"]');

        // Prep 2 new trade orders
        // Sell order
        $I->amOnPage('/admin/trade-orders/create');
        $I->selectOption('#trade_order_direction', 'Sell');
        $I->selectOption('#trade_order_type', 'Initial');
        $I->selectOption('#trade_order_asset', (string) $sampleAsset2);
        $I->selectOption('#trade_order_user', (string) $superadmin);
        $I->selectOption('#trade_order_status', 'active');
        $I->fillField('#trade_order_numberOfShares', '1000');
        $I->fillField('#trade_order_pricePerShare', '1.25');
        $I->click('Create Trade Order');
        $sellId2 = $I->grabTextFrom('#trade-order-info [data-field-name="id"]');
        // Buy order
        $I->amOnPage('/admin/trade-orders/create');
        $I->selectOption('#trade_order_direction', 'Buy');
        $I->selectOption('#trade_order_type', 'Market');
        $I->selectOption('#trade_order_asset', (string) $sampleAsset2);
        $I->selectOption('#trade_order_user', (string) $sampleUser2);
        $I->selectOption('#trade_order_status', 'active');
        $I->fillField('#trade_order_numberOfShares', '450');
        $I->fillField('#trade_order_pricePerShare', '1.25');
        $I->click('Create Trade Order');
        $buyId2 = $I->grabTextFrom('#trade-order-info [data-field-name="id"]');

        // Then make edits
        $I->amOnPage("/admin/share-trades/{$id}");
        $I->click('Edit Share Trade');
        $I->seeCurrentUrlEquals("/admin/share-trades/{$id}/edit");
        $I->seeLink('Cancel', "/admin/share-trades/{$id}");
        $I->seeLink('Back', "/admin/share-trades/{$id}");
        // Can't edit status, must use status logs instead
        $I->dontSeeElement('#share_trade_status');
        $I->fillField('#share_trade_sellOrder', (string) $sellId2);
        $I->fillField('#share_trade_buyOrder', (string) $buyId2);
        $I->fillField('#share_trade_numberOfShares', '250');
        $I->fillField('#share_trade_pricePerShare', '1.35');
        // If we set the tradeValue, it will keep whatever was set, won't be calculated
        $I->fillField('#share_trade_tradeValue', '100.50');
        $I->click('Save Changes');

        // Check again
        // Note that there's a dnager of invalid share trades
        // if the buy and sell side are for different assets
        $I->see('Unsettled', '#share-trade-info [data-field-name="status"]');
        $I->see($sampleAsset2, '#share-trade-info [data-field-name="asset"]');
        $I->see($I::USER_REG2, '#share-trade-info [data-field-name="buyer"]');
        $I->see($I::USER_SUPER_ADMIN, '#share-trade-info [data-field-name="seller"]');
        $I->see('250', '#share-trade-info [data-field-name="share-quantity"]');
        $I->see('1.35', '#share-trade-info [data-field-name="share-price"]');
        $I->see('100.50', '#share-trade-info [data-field-name="trade-value"]');
        $I->see('No', '#share-trade-info [data-field-name="derived-trade-value"]');

        // Edit the status log
        $I->click('Edit', '#status-logs tbody tr:first-child');
        $I->seeElement('#metadata-info');
        $statusId = $I->grabTextFrom(
            '#metadata-info [data-field-name="status-log-id"]',
        );
        $I->seeCurrentUrlEquals("/admin/share-trades/status-logs/{$statusId}");
        $I->see('System', '#metadata-info [data-field-name="transitioned-by"]');
        $I->seeLink('Cancel', "/admin/share-trades/{$id}");
        $I->seeLink('Back', "/admin/share-trades/{$id}");
        // Status is not editable
        $I->seeElement('#status_log_status[disabled]');
        $I->seeOptionIsSelected('#status_log_status', 'Unsettled');
        // Edit the others
        $dtString = '2025-12-14 12:00:05';
        $note = 'test status log edit ' . bin2hex(random_bytes(6));
        $I->fillField('#status_log_occuredAt', $dtString);
        $I->fillField('#status_log_notes', $note);
        $I->click('Save Changes');

        $I->seeCurrentUrlEquals("/admin/share-trades/{$id}");
        $I->see(
            $dtString,
            '#status-logs tbody tr:first-child [data-field-name="occured-at"]',
        );
        $I->see($note, '#status-logs tbody tr:first-child [data-field-name="notes"]');

        // Add a new status log
        $I->click('Update Status');
        $I->seeCurrentUrlEquals("/admin/share-trades/{$id}/status-logs/create");
        $I->see(
            $I::USER_SUPER_ADMIN,
            '#metadata-info [data-field-name="transitioned-by"]',
        );
        $I->seeLink('Abandon', "/admin/share-trades/{$id}");
        $I->seeLink('Back', "/admin/share-trades/{$id}");
        $dtString2 = '2025-12-29 18:09:14';
        $note2 = 'test status log create ' . bin2hex(random_bytes(6));
        $I->selectOption('#status_log_status', 'suspended');
        $I->fillField('#status_log_occuredAt', $dtString2);
        $I->fillField('#status_log_notes', $note2);
        $I->click('Create Share Trade Status Log');

        $I->seeCurrentUrlEquals("/admin/share-trades/{$id}");
        $I->see(
            'Suspended',
            '#status-logs tbody tr:last-child [data-field-name="status"]',
        );
        $I->see(
            $I::USER_SUPER_ADMIN,
            '#status-logs tbody tr:last-child [data-field-name="transitioned-by"]',
        );
        $I->see(
            $dtString2,
            '#status-logs tbody tr:last-child [data-field-name="occured-at"]',
        );
        $I->see($note2, '#status-logs tbody tr:last-child [data-field-name="notes"]');
    }
}
