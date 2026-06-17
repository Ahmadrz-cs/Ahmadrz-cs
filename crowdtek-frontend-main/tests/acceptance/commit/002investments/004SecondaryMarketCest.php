<?php

class SecondaryMarketCest
{
    public function _before(AcceptanceTester $I) {}

    public function _after(AcceptanceTester $I) {}

    /**
     * @group secondary_market
     */
    public function checkSellActionFormValidation(AcceptanceTester $I)
    {
        $I->loginWithCredentials($I->reg_user_name, $I->admin_user_password);
        // Jim is a shareholder in Royal Way Gardens - Cambridge with id 31
        $I->amOnPage("/properties/31/sell");
        $I->wait(1);
        $sharesAvailable = $I->grabAttributeFrom('#shareholding-info', 'data-shareholding');

        // Currently the minimum is £100, so for £2.12 a share is 48 shares
        $I->see("Minimum of 48 shares");
        $I->scrollTo("#relisting_numberOfShares", 0, -120);
        $I->fillField("#relisting_numberOfShares", 1);
        // Click away to trigger JS
        $I->clickWithLeftButton('#sale-value');
        $I->seeInField("#relisting_numberOfShares", "48");
        // Repeat with monetary value
        $I->fillField("#sale-value", "1");
        $I->clickWithLeftButton('#relisting_numberOfShares');
        $I->seeInField("#relisting_numberOfShares", "48");

        // Share amount is also capped at the shares available
        $I->fillField("#relisting_numberOfShares", "30809");
        $I->clickWithLeftButton('#sale-value');
        $I->seeInField("#relisting_numberOfShares", $sharesAvailable);
        // Repeat with monetary value
        $I->fillField("#sale-value", "1000000");
        $I->clickWithLeftButton('#relisting_numberOfShares');
        $I->seeInField("#relisting_numberOfShares", $sharesAvailable);

        // check that you can't submit 0 (not enabled due to min sell requirement)
        // $I->waitForText('Must relist at least 1 share');
    }

    public function testVipRelistingFeeExemption(AcceptanceTester $I)
    {
        $I->loginWithCredentials($I->approved_investor_2, $I->admin_user_password, skipScaCheck: false);
        // Jim is a shareholder in Sandfox fields with id 32
        $I->amOnPage("/my-portfolio/positions/32");
        $I->click("Sell", '#portfolio-info');
        $I->dontSee('Relisted this month', 'table');
        $I->see("You are currently exempt from further fees");
        $I->fillField("#relisting_numberOfShares", 500);
        $relistingFee = $I->grabTextFrom('#charge_listing');
        $I->assertEquals(0, (int)$relistingFee);
    }

    public function testInsufficientFundsToPayRelistingFee(AcceptanceTester $I)
    {
        $I->loginWithCredentials(
            $I->approved_investor_low_balance,
            $I->admin_user_password,
            false,
            skipScaCheck: false,
        );
        // Ed is a shareholder in Sandfox fields with id 32
        $I->amOnPage("/properties/32/sell");
        $I->wait(1);
        // $I->click("Sell", '#portfolio-info');
        $I->scrollTo("#relisting_numberOfShares", 0, -80);
        $I->fillField("#relisting_numberOfShares", 100);
        $I->scrollTo("#relisting_acceptTerms", 0, -80);
        $I->checkOption("#relisting_acceptTerms");
        $I->click("Submit Sell Order");
        $I->waitForText('Insufficient wallet balance to make payment');
    }

    /**
     * @group secondary_market
     */
    public function testSecondaryMarketTradeCancellation(AcceptanceTester $I)
    {
        /**
         * Ben user has a secondary market listing in Lodge de Lac
         * Holly user has bought some of the shares in that listing - but remains unsettled
         * Cancel this as the progress on that listing will go down
         */
        $I->loginWithCredentials($I->reg_user_name, $I->admin_user_password, false);

        // Go straight to Lodge de Lac portfolio position page
        $I->amOnPage('/my-portfolio/positions/26?refreshPortfolio=1');
        $I->wait(1);
        $I->scrollTo('#open-sell-orders');
        // There's a specific order id that we are using for the test
        // Can alternatively use first-child as there's no other relistings here
        $progressStart = $I->grabTextFrom('#open-sell-orders article[data-uuid="019d4338-3029-7dae-93be-9f875c4a70a7"] [data-field-name="progress"]');
        $sharesRemainingStart = $I->grabTextFrom('#open-sell-orders article[data-uuid="019d4338-3029-7dae-93be-9f875c4a70a7"] [data-field-name="shares-remaining"]');

        // Cancel the share trade
        $I->updateInDatabase(
            'share_trade_status_log',
            ['status' => 'cancelled'],
            ['id' => 39],
        );
        $I->amOnPage('/my-portfolio/positions/26?refreshPortfolio=1');
        $I->wait(1);
        $I->scrollTo('#open-sell-orders');

        // Check the progress of the sell order has changed
        $progressEnd = $I->grabTextFrom('#open-sell-orders article[data-uuid="019d4338-3029-7dae-93be-9f875c4a70a7"] [data-field-name="progress"]');
        $sharesRemainingEnd = $I->grabTextFrom('#open-sell-orders article[data-uuid="019d4338-3029-7dae-93be-9f875c4a70a7"] [data-field-name="shares-remaining"]');

        $I->assertNotEquals($progressStart, $progressEnd);
        $I->assertNotEquals($sharesRemainingStart, $sharesRemainingEnd);
    }

    /**
     * @group secondary_market
     * @group secondary_market_linear
     */
    public function testLinearSecondaryMarketTransaction(AcceptanceTester $I)
    {
        /**
         * Summary of actions
         * 1. UserA lists some shares in Lodge de Lac
         * 2. UserB buys some shares in that sell order
         */

        // 2. UserA sells part of their investment
        $I->loginWithCredentials($I->reg_user_name, $I->admin_user_password, false);

        // Lodge de Lac is asset 26, minimum sale is £100 worth of shares, but we'll sell 110 shares worth £127.60
        $I->sellShares(26, 110);

        // Check the newly created listing
        $I->amOnPage("/my-portfolio/positions/26");
        $I->wait(1);
        $I->assertEquals(
            "Submitted",
            $I->grabTextFrom('#open-sell-orders article:first-child [data-field-name="status"]'),
        );
        $I->assertEquals(
            "110",
            $I->grabTextFrom('#open-sell-orders article:first-child [data-field-name="shares"]'),
        );
        $sellOrderId = $I->grabAttributeFrom('#open-sell-orders article:first-child', 'data-uuid');

        // Should also see the new sell order in the main portfolio
        $I->amOnPage('/my-portfolio');
        $I->wait(1);
        $I->scrollTo('#open-sell-orders');
        $I->seeElement('#open-sell-orders article[data-uuid="' . $sellOrderId . '"]');

        // Update the status to active (effectively publishing it)
        // Get all the trade order status logs so you can get the most recent
        $tradeOrderLogIds = $I->grabColumnFromDatabase('trade_order_status_log', 'id');
        $latestLog = array_last($tradeOrderLogIds);
        $I->updateInDatabase(
            'trade_order_status_log',
            ['status' => 'active'],
            ['id' => $latestLog],
        );

        // Check portfolio position
        $I->amOnPage('/my-portfolio/positions/26?refreshPortfolio=1');
        $I->wait(1);
        $sellerStartShares = str_replace(',', '', $I->grabTextFrom('#current-position [data-field-name="current-shares"]'));

        // SWITCHING to UserB
        $I->amOnPage('/logout');
        $I->loginWithCredentials($I->approved_investor_1, $I->admin_user_password, false, skipScaCheck: false);

        // Check if user is already shareholder in Lodge de Lac
        // Note that we'll need to use refresh to clear the app cache
        $I->amOnPage('/my-portfolio/positions/26?refreshPortfolio=1');
        $I->wait(3);
        $buyerStartShares = 0;
        try {
            $I->seeCurrentUrlEquals("/my-portfolio/positions/26");
            $buyerStartShares = str_replace(',', '', $I->grabTextFrom('#current-position [data-field-name="current-shares"]'));
            // Usually means it's been redirected back to portfolio as it's not a valid position for this user
        } catch (\Throwable $th) {
            $buyerStartShares = 0;
        }

        // Try to invest in the secondary market listing for Lodge de Lac
        $I->amOnPage("/properties/26");
        $I->waitForText('To invest in this asset');
        // See if the new offering exists, it will usually be an extra offering, as another one exists
        $extraOrders = $I->grabMultiple("#more-orders article");
        $numberOfExtraOrders = count($extraOrders);
        $I->click("Show {$numberOfExtraOrders} more offers");
        $I->seeLink("Select Offer", "/properties/26?selected={$sellOrderId}");
        $I->amOnPage("/properties/26?selected={$sellOrderId}");
        $I->clickWithLeftButton(['css' => 'label[for="docsReviewed"]']);
        $I->click('Invest', 'form.invest-steps');
        $I->wait(1);
        $I->seeCurrentUrlEquals("/properties/26/invest/{$sellOrderId}");
        $I->fillField('#investment_retail_numberOfShares', 24);
        $I->wait(1);
        $I->scrollTo('#total-shares-input', 0, -40);
        $I->clickWithLeftButton('#investment-total-value');
        $I->click('Invest Now', 'form');
        $I->wait(2);

        // Mark the new share trade as settled
        $tradeStatusLogIds = $I->grabColumnFromDatabase('share_trade_status_log', 'id');
        $latestLog = array_last($tradeStatusLogIds);
        $I->updateInDatabase(
            'share_trade_status_log',
            ['status' => 'settled'],
            ['id' => $latestLog],
        );

        // Check it in our portfolio
        $I->wait(1);
        $I->amOnPage('/my-portfolio/positions/26?refreshPortfolio=1');
        $I->wait(1);
        $buyerEndShares = str_replace(',', '', $I->grabTextFrom('#current-position [data-field-name="current-shares"]'));
        $I->assertEquals((int)$buyerStartShares + 24, $buyerEndShares);

        // SWITCHING to UserA
        $I->amOnPage('/logout');
        $I->loginWithCredentials($I->reg_user_name, $I->admin_user_password, false, skipScaCheck: false);
        $I->wait(1);

        // Check portfolio changes
        $I->amOnPage('/my-portfolio/positions/26?refreshPortfolio=1');
        $I->wait(1);
        $sellerEndShares = str_replace(',', '', $I->grabTextFrom('#current-position [data-field-name="current-shares"]'));
        $I->assertEquals((int)$sellerStartShares - 24, $sellerEndShares);
        $I->scrollTo('#open-sell-orders');
        $sharesRemaining = $I->grabTextFrom('#open-sell-orders article[data-uuid="' . $sellOrderId . '"] [data-field-name="shares-remaining"]');
        $I->assertEquals(110 - 24, $sharesRemaining);
    }
}
