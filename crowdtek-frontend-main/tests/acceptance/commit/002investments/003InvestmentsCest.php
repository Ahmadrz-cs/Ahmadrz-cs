<?php

use AppBundle\Entity\Enum\TradeStatus;
use Facebook\WebDriver\WebDriverKeys;

// 002InvestmentsCest.php

class InvestmentsCest
{
    public function _before(AcceptanceTester $I) {}

    public function _after(AcceptanceTester $I) {}

    /**
     * @group investment
     */
    public function testInvestmentValidation(AcceptanceTester $I)
    {
        /**
         * 1. Must Download PDF
         * 2. Must provide valid amount
         * 3. Must accept terms
         */
        $I->loginWithCredentials($I->approved_investor_low_balance, $I->admin_user_password, false, skipScaCheck: false);

        // We historically used /secondary-asset/44/overview which is Silverhood Down
        $I->amOnPage('/properties/33');
        $I->waitForText('To invest in this asset');
        $I->clickWithLeftButton(['css' => 'label[for="docsReviewed"]']);
        $I->click('Invest', 'form.invest-steps');
        $I->wait(1);
        $I->seeCurrentUrlEquals('/properties/33/invest/019d4338-3033-7972-bff8-b5f13ed946da');
        $I->seeLink('Cancel', '/properties/33');
        $I->fillField('#investment_retail_numberOfShares', 0);
        $I->scrollTo('#total-shares-input', 0, -40);
        $I->click('Invest Now', 'form');
        $I->waitForText('Must invest at least 1 share');
        $I->seeCurrentUrlEquals('/properties/33/invest/019d4338-3033-7972-bff8-b5f13ed946da');

        // Check max capping
        $sharesAvailable = $I->grabAttributeFrom('#opportunity-info', 'data-shares');
        $I->fillField('#investment_retail_numberOfShares', 1000000);
        $I->clickWithLeftButton(['css' => '#investment-total-value']); // defocus input just in case
        $I->seeInField('#investment_retail_numberOfShares', $sharesAvailable);
    }

    /**
     * @group investment
     */
    public function testInsufficientFundsInvestment(AcceptanceTester $I)
    {
        $I->loginWithCredentials($I->approved_investor_low_balance, $I->admin_user_password, false, skipScaCheck: false);

        $I->amOnPage('/properties/33');
        $I->waitForText('To invest in this asset');
        $I->clickWithLeftButton(['css' => 'label[for="docsReviewed"]']);
        $I->click('Invest', 'form.invest-steps');
        $I->wait(1);
        $I->seeCurrentUrlEquals('/properties/33/invest/019d4338-3033-7972-bff8-b5f13ed946da');
        $I->fillField('#investment_retail_numberOfShares', 1000000);
        $I->scrollTo('#total-shares-input', 0, -40);
        $I->click('Invest Now', 'form');

        $I->wait(1);
        $I->seeCurrentUrlEquals('/properties/33');
        $I->waitForText('Insufficient wallet balance');

        $I->amOnPage('/logout');
    }

    /**
     * @group secondary_market
     * @group secondary_market_linear
     * @group secondary_market_branched
     */
    public function testRegularInvestmentSca(AcceptanceTester $I)
    {
        /**
         * Pre-and-post test checks
         * - Holdings in portfolio (if new property, it should be a new row in the table)
         * - Rows in the unsettled investments section (investment should add one)
         * - Wallet balance
         * - Wallet transaction history
         */
        $I->loginWithCredentials($I->reg_user_name, $I->admin_user_password, false, skipScaCheck: false);

        // Clear portfolio cache in case of previous test runs
        $I->amOnPage("/my-portfolio?refreshPortfolio=1");
        $I->wait(1);
        $I->waitForText("Combination of the total currently invested");

        // Grab pre data
        $walletBalanceStart = $I->grabAttributeFrom('[data-field-name="wallet-balance"]', 'data-value');
        $I->scrollTo('#portfolio-positions', 0, -50);
        $propertiesInvestedStart = $I->grabMultiple('#portfolio-positions tbody tr', 'data-asset-id');
        $I->scrollTo('#pending-investments', 0, -50);
        $unsettledStart = array_filter($I->grabMultiple('#pending-investments .card-list article', 'data-uuid'));
        if (in_array('33', $propertiesInvestedStart)) {
            // We can check if the shares change
            $sharesStart = str_replace(
                ',',
                '',
                $I->grabTextFrom('#portfolio-positions [data-asset-id="33"] [data-label="Shares Owned"] span'),
            );
        }

        // Invest and then return to portfolio
        $I->investInAsset(350, '33', $I::BEN_MP_EMAIL);
        $I->amOnPage("/my-portfolio");
        $I->wait(1);
        $I->waitForText("Combination of the total currently invested");

        // Grab post data
        $walletBalanceEnd = $I->grabAttributeFrom('[data-field-name="wallet-balance"]', 'data-value');
        $I->scrollTo('#portfolio-positions', 0, -50);
        $propertiesInvestedEnd = $I->grabMultiple('#portfolio-positions tbody tr', 'data-asset-id');
        $I->scrollTo('#pending-investments', 0, -50);
        $unsettledEnd = array_filter($I->grabMultiple('#pending-investments .card-list article', 'data-uuid'));

        $I->assertGreaterThan($walletBalanceEnd, $walletBalanceStart);
        // Unsettled investments do not count towards the current positions list as it's considered "pending"
        $I->assertEquals(count($propertiesInvestedEnd), count($propertiesInvestedStart));
        if (in_array('33', $propertiesInvestedStart)) {
            // Row already exists, so instead check that the values (just shares for now) haven't changed
            $sharesEnd = str_replace(
                ',',
                '',
                $I->grabTextFrom('#portfolio-positions [data-asset-id="33"] [data-label="Shares Owned"] span'),
            );
            // Unsettled investments do not count towards the current positions list as it's considered "pending"
            $I->assertEquals($sharesEnd, $sharesStart);
        }
        $I->assertEquals(count($unsettledEnd), count($unsettledStart) + 1);

        // check transaction history
        $I->amOnPage('/my-profile/transactions');
        $I->waitForText('Transaction History');
        $I->scrollTo('a[href="/transaction-export"]');
        $I->see('Investment', ['css' => 'tbody :first-child :nth-child(2)']);
        $I->see('Silverhood Down - Brighton', ['css' => 'tbody :first-child :nth-child(3)']);

        $I->amOnPage('/logout');
    }

    public function testStampDuty(AcceptanceTester $I)
    {
        $I->loginWithCredentials($I->reg_user_name, $I->admin_user_password, false);

        // Ben has an unsettled share trade in Kolness for 848.54
        // We can update the createdAt date to this month so it counts towards stamp duty
        $I->updateInDatabase(
            'share_trade',
            ['createdAt' => new \DateTime()->format('Y-m-d H:i:s')],
            ['id' => '44'],
        );
        // Clear portfolio cache
        $I->amOnPage("/my-portfolio?refreshPortfolio=1");

        // Head straight to the kolness primary listing
        $I->amOnPage('/properties/30/invest/019d4338-301b-74fb-afa6-498ad65e8a9f');
        $I->waitForElement("#investment-total-value");
        $I->fillField('#investment-total-value', '153'); // This should bump the total for the month to 1001
        // defocus amount field to trigger js
        $I->clickWithLeftButton(['css' => '#investment_retail_numberOfShares']);
        $I->scrollTo('#total-shares-input', 0, -40);
        $stampAmt = $I->grabTextFrom('#summary-stamp-duty');
        $total = $I->grabTextFrom('#summary-total');
        $totalToPay = '162.46';
        $I->assertEquals('10.00', $stampAmt);
        $I->assertEquals($totalToPay, $total); // This is (99 shares * 1.54) + 10
        $I->see('848.54', '#already-invested');
        $I->click('Invest Now', 'form');

        // May not need SCA, so copy handler from AcceptanceTester::
        $I->checkAndHandleInvestmentSca($I::BEN_MP_EMAIL);

        // Check the amount taken from the wallet includes stamp duty
        $I->amOnPage('/my-profile/transactions');
        $I->see("-£{$totalToPay}", '#transHistory tbody tr:first-child');
    }

    public function testMinMaxCommitLimit(AcceptanceTester $I)
    {
        $I->loginWithCredentials($I->reg_user_name, $I->admin_user_password, false);

        // Specific offering with min AND max commit set to 100 and 1000 shares respectively
        // Should be the 2nd primary sell order for Partingdale House
        // Share price is 1.44
        // Min and max commit should be enforced by JS
        $I->amOnPage("/properties/29/invest/019d4338-3038-7200-9c71-5a6fb08385ca");
        // Prefilled to min commit
        $I->seeInField('#investment_retail_numberOfShares', '100');
        $I->seeInField('#investment-total-value', '144.00');
        // Due to the JS, must use keypresses rather than webdriver actions to change field values
        $I->pressKey('#investment_retail_numberOfShares', WebDriverKeys::DELETE, WebDriverKeys::DELETE, WebDriverKeys::DELETE, '88');
        // defocus field to trigger js
        $I->clickWithLeftButton(['css' => '#already-invested']);
        $I->seeInField('#investment_retail_numberOfShares', '100');
        $I->seeInField('#investment-total-value', '144.00');

        $I->amOnPage("/properties/29/invest/019d4338-3038-7200-9c71-5a6fb08385ca");
        $I->pressKey('#investment_retail_numberOfShares', '95714');
        // defocus amount field to trigger js
        $I->clickWithLeftButton(['css' => '#already-invested']);
        $I->seeInField('#investment_retail_numberOfShares', '1000');
        $I->seeInField('#investment-total-value', '1440.00');
    }
}
