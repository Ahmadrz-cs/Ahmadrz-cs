<?php

namespace App\Tests\Functional\Ops\MonthEnd\ShareTransfer;

use App\Entity\Enum\TradeStatus;
use App\Tests\Support\FunctionalTester;

class ShareTransferReviewCest
{
    private string $fillerShareTradeId = '';
    private string $fillerShareTradeShares = '';

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
        $this->fillerShareTradeShares = $I->grabFromDatabase(
            'share_trade',
            'numberOfShares',
            [
                'id' => $this->fillerShareTradeId,
            ],
        );

        // Note that this will set ALL status logs for that share trade back to settled
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
            'share_trade',
            ['numberOfShares' => $this->fillerShareTradeShares],
            ['id' => $this->fillerShareTradeId],
        );
        $I->updateInDatabase(
            'share_trade_status_log',
            ['status' => TradeStatus::Cancelled->value],
            ['shareTrade_id' => $this->fillerShareTradeId],
        );
    }

    public function checkShareTransferOrder(FunctionalTester $I): void
    {
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Lodge de Lac - Cumbria',
        ]);
        $I->amOnPage("/admin/monthend/share-transfers/create/{$assetId}");
        // We'll shift everything back a month as the fixtures were setup to have been completed last month
        $I->fillField(
            '#share_transfer_order_form_periodStart',
            new \DateTime('first day of -2 month')->format('Y-m-d'),
        );
        $I->fillField(
            '#share_transfer_order_form_periodEnd',
            new \DateTime('first day of last month')->format('Y-m-d'),
        );
        $I->fillField(
            '#share_transfer_order_form_repaymentStart',
            new \DateTime('first day of last month')->format('Y-m-d'),
        );
        $I->fillField(
            '#share_transfer_order_form_repaymentEnd',
            new \DateTime('first day of this month')->format('Y-m-d'),
        );
        $I->fillField(
            '#share_transfer_order_form_description',
            'Automated test share transfer ' . bin2hex(random_bytes(4)),
        );
        $I->click('Save Changes');
        $orderId = $I->grabTextFrom('[data-field-name="order-id"]');
        $I->seeCurrentUrlEquals("/admin/monthend/share-transfers/{$orderId}");
        $I->seeLink(
            'Generate Share Transfers',
            "/admin/monthend/share-transfers/{$orderId}/generate/auto",
        );

        // Update the buy back order shares to match however many shares there are to buy back
        $sharesToBuyBack = $I->grabTextFrom(
            '#matching-share-trade-metadata [data-field-name="first-party-shares"]',
        );
        $I->updateInDatabase(
            'share_trade',
            ['numberOfShares' => $sharesToBuyBack],
            ['id' => $this->fillerShareTradeId],
        );
        // Reload apge after updating database
        $I->amOnPage("/admin/monthend/share-transfers/{$orderId}");

        $I->seeElement('table#investment-trade-list');
        $I->seeElement('table#proxy-buy-back-trade-list');
        $I->seeElement('table#pooled-buy-backs');
        $I->seeElement('table#pooled-investments');

        $I->see('Pooled', '#metadata [data-field-name="supported-setup-mode"]');

        // check table headers
        $headers = [
            'Id',
            'Seller',
            'Buyer',
            'Shares',
            'Status',
            'Actions',
        ];
        foreach ($headers as $header) {
            $I->see($header, 'table#share-transfers-list thead');
        }

        $I->click('Generate Share Transfers');
        $I->seeCurrentUrlEquals("/admin/monthend/share-transfers/{$orderId}");

        // Now sum all the shares in the share transfer table to make sure it adds up to the original total
        $shareTransfersCount = (int) $I->grabTextFrom(
            '[data-field-name="total-transfers"]',
        );
        $totalSharesToTransfer = 0;
        foreach (range(1, $shareTransfersCount) as $rowNumber) {
            $sharesInTransfer = (int) $I->grabTextFrom(
                "table#share-transfers-list tbody tr:nth-child({$rowNumber}) [data-field='shares']",
            );
            $totalSharesToTransfer += $sharesInTransfer;
            $I->see(
                'Pending',
                "table#share-transfers-list tbody tr:nth-child({$rowNumber}) [data-field='status']",
            );
            $I->see(
                'Mark as Completed',
                "table#share-transfers-list tbody tr:nth-child({$rowNumber})",
            );
        }
        $I->assertEquals($sharesToBuyBack, $totalSharesToTransfer);

        // Check individual toggling
        $I->click(
            'Mark as Completed',
            'table#share-transfers-list tbody tr:nth-child(1)',
        );
        $I->see(
            'Completed',
            "table#share-transfers-list tbody tr:nth-child(1) [data-field='status']",
        );
        $I->click('Mark as To Do', 'table#share-transfers-list tbody tr:nth-child(1)');
        $I->see(
            'Pending',
            "table#share-transfers-list tbody tr:nth-child(1) [data-field='status']",
        );

        // Check order toggling
        $I->click('Mark as Completed', '#metadata');
        // Once marked as completed, the setup box will disappear
        $I->dontSeeElement('#setup');
        // Individual transfer requests should also be updated
        foreach (range(1, $shareTransfersCount) as $rowNumber) {
            $I->see(
                'Completed',
                "table#share-transfers-list tbody tr:nth-child({$rowNumber}) [data-field='status']",
            );
        }
        // Should be an option to toggle back to pending
        $I->click('Mark as To Do', '#metadata');
        $I->seeElement('#setup');
        foreach (range(1, $shareTransfersCount) as $rowNumber) {
            $I->see(
                'Pending',
                "table#share-transfers-list tbody tr:nth-child({$rowNumber}) [data-field='status']",
            );
        }

        $I->click('Export Share Transfers (CSV)');
        $I->seeResponseCodeIsSuccessful();
        // Should've downloaded CSV, we an check for the header string which is comma separated
        $expectedHeaders = [
            'id',
            'orderId',
            'assetId',
            'assetName',
            'assetSpv',
            'assetSharePrice',
            'numberOfShares',
            'tradeValue',
            'calculatedInvestmentValue',
            'estimatedStampDuty',
            'investmentId',
            'shareTradeId',
            'buyerId',
            'buyerUsername',
            'buyerContactEmail',
            'buyerTitle',
            'buyerFirstName',
            'buyerLastName',
            'buyerAdressLine1',
            'buyerAdressLine2',
            'buyerAddressCity',
            'buyerAddressRegion',
            'buyerAddressPostCode',
            'buyerAddressCountry',
            'buyerCompanyName',
            'buyerCompanyRegNumber',
            'buyerCompanyAddress1',
            'buyerCompanyPostCode',
            'buyerCompanyCountry',
            'buyerCompanyApprovedOn',
            'sellerId',
            'sellerUsername',
            'sellerContactEmail',
            'sellerTitle',
            'sellerFirstName',
            'sellerLastName',
        ];
        $I->see(join(',', $expectedHeaders));

        // Load back to the share transfer page and try to download the XLS version
        $I->amOnPage("/admin/monthend/share-transfers/{$orderId}");
        $I->click('Export Share Transfers (XLS)');
        $I->seeResponseCodeIsSuccessful();
    }

    public function checkShareTransferOrderNoPrefunders(FunctionalTester $I): void
    {
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);
        $I->amOnPage("/admin/monthend/share-transfers/create/{$assetId}");
        // We'll shift everything back a month as the fixtures were setup to have been completed last month
        $I->fillField(
            '#share_transfer_order_form_periodStart',
            new \DateTime('first day of -2 month')->format('Y-m-d'),
        );
        $I->fillField(
            '#share_transfer_order_form_description',
            'Automated test share transfer without prefunders '
                . bin2hex(random_bytes(4)),
        );
        $I->click('Save Changes');

        $orderId = $I->grabTextFrom('[data-field-name="order-id"]');
        $I->seeCurrentUrlEquals("/admin/monthend/share-transfers/{$orderId}");

        $I->seeElement('table#investment-trade-list');
        $I->seeElement('table#proxy-buy-back-trade-list');
        // The pooled tables won't appear for direct transfers
        $I->dontSeeElement('table#pooled-buy-backs');
        $I->dontSeeElement('table#pooled-investments');
        $I->see('Direct', '#metadata [data-field-name="supported-setup-mode"]');

        $I->click('Generate Share Transfers');
        $I->seeCurrentUrlEquals("/admin/monthend/share-transfers/{$orderId}");

        $sharesToBuyBack =
            $I->grabTextFrom(
                '#matching-share-trade-metadata [data-field-name="first-party-shares"]',
            )
            + $I->grabTextFrom(
                '#matching-share-trade-metadata [data-field-name="relistings-shares"]',
            );
        $shareTransfersCount = (int) $I->grabTextFrom(
            '[data-field-name="total-transfers"]',
        );
        $totalSharesToTransfer = 0;
        foreach (range(1, $shareTransfersCount) as $rowNumber) {
            $sharesInTransfer = (int) $I->grabTextFrom(
                "table#share-transfers-list tbody tr:nth-child({$rowNumber}) [data-field='shares']",
            );
            $totalSharesToTransfer += $sharesInTransfer;
            $I->see(
                'Pending',
                "table#share-transfers-list tbody tr:nth-child({$rowNumber}) [data-field='status']",
            );
            $I->see(
                'Mark as Completed',
                "table#share-transfers-list tbody tr:nth-child({$rowNumber})",
            );
        }
        $I->assertEquals($sharesToBuyBack, $totalSharesToTransfer);

        $I->click('Export Share Transfers (CSV)');
        $I->seeResponseCodeIsSuccessful();

        $I->amOnPage("/admin/monthend/share-transfers/{$orderId}");
        $I->click('Export Share Transfers (XLS)');
        $I->seeResponseCodeIsSuccessful();

        $I->amOnPage("/admin/monthend/share-transfers/{$orderId}");
        $I->click('Clear All Share Transfers');
        $I->assertEquals(
            0,
            (int) $I->grabTextFrom('[data-field-name="total-transfers"]'),
        );
    }
}
