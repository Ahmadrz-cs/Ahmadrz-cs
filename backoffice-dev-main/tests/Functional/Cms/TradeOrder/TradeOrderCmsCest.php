<?php

namespace App\Tests\Functional\Cms\Investment;

use App\Entity\Enum\TradeDirection;
use App\Tests\Support\FunctionalTester;

class TradeOrderCmsCest
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
        $I->amOnPage('/admin/trade-orders');

        $elements = [
            'Id',
            'Type',
            'Direction',
            'Asset',
            'User',
            'Progress',
            'Quantity Available',
            'Quantity Traded',
            'Quantity Listed',
            'Price',
            'Derived Value',
            'Fees',
            'Taxes',
            'Status',
            'Created',
        ];
        // check table headers
        $I->loopCheckElements($elements, 'thead th');

        $filterLabels = [
            'Trade order id',
            'Asset id',
            'Asset name',
            'User id',
            'Username',
            'Direction',
            'Status',
            'Type',
            'CreatedAt Start',
            'CreatedAt End',
            'Items Per Page',
            'Order by',
            'Order Direction',
        ];
        // check table filters
        $I->loopCheckElements($filterLabels, '//form//label|//form//legend');
        $I->seeLink('Create Trade Order', '/admin/trade-orders/create');

        // $I->seeNumberOfElements('#trade-orders-list tbody tr', 10);
        // $I->selectOption('form select[name=perPage]', '5');
        // $I->click('Apply Filters');
        // $I->seeNumberOfElements('#trade-orders-list tbody tr', 5);
        // // check max page bracketing (to deal with filter changing)
        // $I->amOnPage('/admin/trade-orders?page=1000');
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
    //     $I->seeNumberOfElements('#investmentlist tbody tr', min(50, $count)); // at most 50 results
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
        $sampleUser = $I->grabFromDatabase('users', 'id', [
            'username' => $I::USER_REG1,
        ]);
        $sampleAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Sandfox Fields - Kent',
        ]);
        $sampleUser2 = $I->grabFromDatabase('users', 'id', [
            'username' => $I::USER_REG2,
        ]);
        $sampleAsset2 = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Partingdale House - Reading',
        ]);

        $I->amOnPage('/admin/trade-orders');
        $I->click('Create Trade Order');
        $I->seeCurrentUrlEquals('/admin/trade-orders/create');

        $I->selectOption('#trade_order_direction', 'Buy');
        $I->selectOption('#trade_order_type', 'Off Market');
        $I->selectOption('#trade_order_asset', (string) $sampleAsset);
        $I->selectOption('#trade_order_user', (string) $sampleUser);
        $I->selectOption('#trade_order_status', 'submitted');
        $I->fillField('#trade_order_numberOfShares', '156');
        $I->fillField('#trade_order_pricePerShare', '1.29');

        $I->click('Create Trade Order');
        $I->seeElement('#trade-order-overview #trade-order-info');
        $I->seeElement('#trade-order-overview #status-logs');
        $I->seeElement('#related-share-trades');
        $id = $I->grabTextFrom('#trade-order-info [data-field-name="id"]');
        $I->seeCurrentUrlEquals("/admin/trade-orders/{$id}");
        $I->seeLink('View Asset', "/admin/products/{$sampleAsset}/trade-orders");
        $I->seeLink('View User', "/admin/users/{$sampleUser}/dashboard/trade-orders");

        // Check what was created
        $I->see('Buy', '#trade-order-info [data-field-name="direction"]');
        $I->see('Offmarket', '#trade-order-info [data-field-name="order-type"]');
        $I->see('Submitted', '#trade-order-info [data-field-name="status"]');
        $I->see($sampleAsset, '#trade-order-info [data-field-name="asset"]');
        $I->see($I::USER_REG1, '#trade-order-info [data-field-name="user"]');
        $I->see('156', '#trade-order-info [data-field-name="share-quantity"]');
        $I->see('1.29', '#trade-order-info [data-field-name="share-price"]');
        $I->see('201.24', '#trade-order-info [data-field-name="derived-trade-value"]');
        $I->see('0.00', '#trade-order-info [data-field-name="fees"]');
        $I->see('0.00', '#trade-order-info [data-field-name="taxes"]');
        $I->see('', '#trade-order-info [data-field-name="notes"]');
        $I->see('N/A', '#trade-order-info [data-field-name="min-share-quantity"]');
        $I->see('N/A', '#trade-order-info [data-field-name="max-share-quantity"]');
        $I->see('N/A', '#trade-order-info [data-field-name="transaction-reference"]');
        $I->see('N/A', '#trade-order-info [data-field-name="transaction"]');
        $I->see('N/A', '#trade-order-info [data-field-name="expiration"]');

        // Make edits
        $I->click('Edit Trade Order', '#trade-order-overview');
        $expiryString = '2026-01-15 22:00';
        $editNote = 'test trade order edit ' . bin2hex(random_bytes(6));
        $editRef = 'test trade order transaction ref ' . bin2hex(random_bytes(6));
        $I->seeCurrentUrlEquals("/admin/trade-orders/{$id}/edit");
        // Status is not editable afterwards, must use status logs instead
        $I->dontSeeElement('#trade_order_status');
        $I->selectOption('#trade_order_direction', 'Sell');
        $I->selectOption('#trade_order_type', 'Limit');
        $I->selectOption('#trade_order_asset', (string) $sampleAsset2);
        $I->selectOption('#trade_order_user', (string) $sampleUser2);
        $I->fillField('#trade_order_numberOfShares', '120');
        $I->fillField('#trade_order_pricePerShare', '1.3');
        $I->fillField('#trade_order_fees', '1.76');
        $I->fillField('#trade_order_taxes', '5');
        $I->fillField('#trade_order_minimumShares', '50');
        $I->fillField('#trade_order_maximumShares', '120');
        $I->fillField('#trade_order_transaction', '12');
        $I->fillField('#trade_order_transactionReference', $editRef);
        $I->fillField('#trade_order_expiration', $expiryString);
        $I->fillField('#trade_order_notes', $editNote);
        $I->click('Save Changes');

        // Check again
        $I->seeCurrentUrlEquals("/admin/trade-orders/{$id}");
        $I->see('Sell', '#trade-order-info [data-field-name="direction"]');
        $I->see('Limit', '#trade-order-info [data-field-name="order-type"]');
        $I->see('Submitted', '#trade-order-info [data-field-name="status"]');
        $I->see($sampleAsset2, '#trade-order-info [data-field-name="asset"]');
        $I->see($I::USER_REG2, '#trade-order-info [data-field-name="user"]');
        $I->see('120', '#trade-order-info [data-field-name="share-quantity"]');
        $I->see('1.3', '#trade-order-info [data-field-name="share-price"]');
        $I->see('156.00', '#trade-order-info [data-field-name="derived-trade-value"]');
        $I->see('1.76', '#trade-order-info [data-field-name="fees"]');
        $I->see('5.00', '#trade-order-info [data-field-name="taxes"]');
        $I->see($editNote, '#trade-order-info [data-field-name="notes"]');
        $I->see('50', '#trade-order-info [data-field-name="min-share-quantity"]');
        $I->see('120', '#trade-order-info [data-field-name="max-share-quantity"]');
        $I->see(
            $editRef,
            '#trade-order-info [data-field-name="transaction-reference"]',
        );
        $I->see('12', '#trade-order-info [data-field-name="transaction"]');
        $I->see($expiryString, '#trade-order-info [data-field-name="expiration"]');

        // Remove transaction relation to allow reruns
        $I->click('Edit Trade Order', '#trade-order-overview');
        $I->fillField('#trade_order_transaction', '');
        $I->click('Save Changes');

        // Edit the status log
        $I->click('Edit', '#status-logs tbody tr:first-child');
        $I->seeElement('#metadata-info');
        $statusId = $I->grabTextFrom(
            '#metadata-info [data-field-name="status-log-id"]',
        );
        $I->seeCurrentUrlEquals("/admin/trade-orders/status-logs/{$statusId}");
        $I->see('System', '#metadata-info [data-field-name="transitioned-by"]');
        $I->seeLink('Cancel', "/admin/trade-orders/{$id}");
        $I->seeLink('Back', "/admin/trade-orders/{$id}");
        // Status is not editable
        $I->seeElement('#status_log_status[disabled]');
        $I->seeOptionIsSelected('#status_log_status', 'Submitted');
        // Edit the others
        $dtString = '2025-12-14 12:00:05';
        $note = 'test status log edit ' . bin2hex(random_bytes(6));
        $I->fillField('#status_log_occuredAt', $dtString);
        $I->fillField('#status_log_notes', $note);
        $I->click('Save Changes');

        $I->seeCurrentUrlEquals("/admin/trade-orders/{$id}");
        $I->see(
            $dtString,
            '#status-logs tbody tr:first-child [data-field-name="occured-at"]',
        );
        $I->see($note, '#status-logs tbody tr:first-child [data-field-name="notes"]');

        // Add a new status log
        $I->click('Update Status');
        $I->seeCurrentUrlEquals("/admin/trade-orders/{$id}/status-logs/create");
        $I->see(
            $I::USER_SUPER_ADMIN,
            '#metadata-info [data-field-name="transitioned-by"]',
        );
        $I->seeLink('Abandon', "/admin/trade-orders/{$id}");
        $I->seeLink('Back', "/admin/trade-orders/{$id}");
        $dtString2 = '2025-12-29 18:09:14';
        $note2 = 'test status log create ' . bin2hex(random_bytes(6));
        $I->selectOption('#status_log_status', 'suspended');
        $I->fillField('#status_log_occuredAt', $dtString2);
        $I->fillField('#status_log_notes', $note2);
        $I->click('Create Trade Order Status Log');

        $I->seeCurrentUrlEquals("/admin/trade-orders/{$id}");
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

    public function checkEditPrefundingComplementary(FunctionalTester $I): void
    {
        /**
         * Get the trade order pair from Nixis asset
         * where Holly user has prefunded 1k which is being fully liquidated
         */
        $sampleUser = $I->grabFromDatabase('users', 'id', [
            'username' => $I::USER_REG2,
        ]);
        $sampleAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Nixis Plutona - Bristol',
        ]);

        $prefundingBuy = $I->grabFromDatabase('trade_order', 'id', [
            'user_id' => $sampleUser,
            'asset_id' => $sampleAsset,
            'direction' => TradeDirection::Buy->value,
            'notes' => 'Round 1k prefunding',
        ]);
        $prefundingSell = $I->grabFromDatabase('trade_order', 'id', [
            'user_id' => $sampleUser,
            'asset_id' => $sampleAsset,
            'direction' => TradeDirection::Sell->value,
            'notes' => 'Round 1k prefunding',
        ]);
        // This trade order should also have a transaction fixture (one of the only ones that do)
        $buyTransaction = $I->grabFromDatabase('trade_order', 'transaction_id', [
            'id' => $prefundingBuy,
        ]);

        $I->amOnPage("/admin/trade-orders/{$prefundingBuy}");
        $I->see(
            $prefundingSell,
            '#trade-order-info [data-field-name="prefunding-complementary-order-id"]',
        );
        $I->see(
            "#{$buyTransaction}",
            '#trade-order-info [data-field-name="transaction"]',
        );
        $I->seeLink(
            'View Prefunding Complement',
            "/admin/trade-orders/{$prefundingSell}",
        );
        $I->seeLink('View Transaction', "/admin/transactions/{$buyTransaction}");

        // If you clear the relations, the links go as well
        $I->click('Edit Trade Order');
        $I->fillField('#trade_order_transaction', '');
        $I->fillField('#trade_order_complementaryOrder', '');
        $I->click('Save Changes');

        $I->see(
            'N/A',
            '#trade-order-info [data-field-name="prefunding-complementary-order-id"]',
        );
        $I->see('N/A', '#trade-order-info [data-field-name="transaction"]');
        $I->dontSeeLink('View Prefunding Complement');
        $I->dontSeeLink('View Transaction');

        // Reinstate the relations
        $I->click('Edit Trade Order');
        $I->fillField('#trade_order_transaction', $buyTransaction);
        $I->fillField('#trade_order_complementaryOrder', $prefundingSell);
        $I->click('Save Changes');
    }
}
