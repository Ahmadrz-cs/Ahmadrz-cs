<?php


class PortfolioCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->loginWithCredentials($I->reg_user_name, $I->admin_user_password);
    }

    /**
     * @group portfolio
     */
    public function checkPortfolioDashboard(\Step\Acceptance\StaticPages $I)
    {
        // Top Yielders not available to non-VIPs
        $I->amOnPage('/my-portfolio');
        // $I->wait(1);
        $I->seeLink(strtoupper('Dashboard'), '/my-portfolio');
        $I->seeLink(strtoupper('History'), '/my-portfolio/trade-history');
        $I->dontSeeLink(strtoupper('Top Yielders'), '/my-portfolio/top-yielders');

        $I->see('Dashboard', '#portfolio-nav .nav-link.active');
        $I->see('History', '#portfolio-nav .nav-link');
        $I->dontSee('History', '#portfolio-nav .nav-link.active');
        $I->dontSee('Top Yielders', '#portfolio-nav .nav-link');

        // Account summary stats
        $I->seeNumberOfElements("#portfolio-stats-board > div", 5);
        $I->see("Portfolio value", "#portfolio-stats-board");
        $I->see("Total currently invested", "#portfolio-stats-board");
        $I->see("Rental Earnings", "#portfolio-stats-board");
        $I->see("Total Earnings", "#portfolio-stats-board");
        $I->see("Wallet balance", "#portfolio-stats-board");

        // Shareholdings section
        $I->scrollTo("table#portfolio-positions", 0, -80);
        $title = $I->grabTextFrom("//h3[@class='text-center text-light mb-3']");
        $I->assertRegExp('/^You currently have \(\d+\) active properties in your portfolio$/', $title);
        $I->seeLink("View", "/my-portfolio/positions/26"); // should be Lodge de Lac

        // Lower portfolio section
        // This is more extensively checked in the investment tests
        $I->scrollTo('#pending-investments', 0, -80);
        $I->see('Pending Investments', '#pending-investments');
        $I->see('Secondary Market Listings', '#open-sell-orders');
        $I->see('Recent Dividends', '#recent-dividends');

        // We'll breifly view the dashboard for single assets/positions
        $I->amOnPage("/my-portfolio/positions/26000");
        // going to non existent portfolio position will redirect back to the portfolio
        $I->seeCurrentUrlEquals("/my-portfolio");
        // We'll checkout Kolness by the Moor for Ben user
        $I->amOnPage("/my-portfolio/positions/30");
        $I->scrollTo('#property-nav', 0, -80);
        // Property nav section
        $I->see("Kolness by the Moor", "#property-nav article.current");
        $I->see("Showing", "#property-nav article.current");
        $I->click("Show Other Properties");
        $assetLinks = $I->grabMultiple('#more-properties a', 'href');
        // Should be at least Lodge de Lac we saw earlier
        $I->assertGreaterOrEquals(1, $assetLinks);
        $I->assertContains("/my-portfolio/positions/26", $assetLinks);

        // Property card - currently has the extra field "Trading Status"
        $I->see("Kolness by the Moor", "#asset-info");
        $I->see("Trading Status", "#asset-info");

        // Portfolio position info
        $I->see("Your Investment", "#portfolio-info");
        $I->seeElement("#portfolio-info table#current-position");
        $I->seeElement("#portfolio-info table#returns-info");
        $I->seeElement("#portfolio-info table#trading-info");

        // Other sections
        $I->scrollTo('#pending-investments', 0, -80);
        $I->see("Pending Investments", "#pending-investments");
        $I->see("Secondary Market Listings", "#open-sell-orders");
        $I->see("Recent Dividends", "#recent-dividends");
    }

    /**
     * @group portfolio
     */
    public function checkPortfolioHistory(\Step\Acceptance\StaticPages $I)
    {
        $I->amOnPage('/my-portfolio/trade-history');
        // $I->wait(1);
        $I->seeLink(strtoupper('Dashboard'), '/my-portfolio');
        $I->seeLink(strtoupper('History'), '/my-portfolio/trade-history');
        $I->dontSeeLink(strtoupper('Top Yielders'), '/my-portfolio/top-yielders');

        $I->see('Dashboard', '#portfolio-nav .nav-link');
        $I->dontSee('Dashboard', '#portfolio-nav .nav-link.active');
        $I->see('History', '#portfolio-nav .nav-link.active');
        $I->dontSee('Top Yielders', '#portfolio-nav .nav-link');

        $I->scrollTo('table#trade-history', 0, -80);
        $I->see('Trade History', 'h3');

        $expectedFields = [
            'Direction',
            'Property Name',
            'Price',
            'Shares',
            'Value',
            'Creation Date',
            'Settlement Date',
        ];
        foreach ($expectedFields as $field) {
            $I->see($field, 'table#trade-history thead');
        }

        $I->click("Configure Export");
        $I->seeCurrentUrlEquals('/my-portfolio/trade-history/export');
        $I->click("Export CSV");
        $I->seeCurrentUrlEquals('/my-portfolio/trade-history/export');
        $I->see("Trade History Export"); // Still see the page contents
        $I->dontSee("Date range cannot be greater than 12 months.");
        $I->dontSee("Please enter a valid date.");

        // Set a date range where the user has actual investments to test the export
        // Dockerised chromium defaults to US date format mm/dd/yyyy
        $I->fillField('#query_trade_history_createdAt_gte', '10/10/2019');
        $I->fillField('#query_trade_history_createdAt_lt', '05/05/2020');
        $I->click("Export CSV");
        $I->seeCurrentUrlEquals('/my-portfolio/trade-history/export');
        $I->see("Trade History Export"); // Still see the page contents
        $I->dontSee("Date range cannot be greater than 12 months.");
        $I->dontSee("Please enter a valid date.");

        // Check range too big
        // Dockerised chromium defaults to US date format mm/dd/yyyy
        $I->fillField('#query_trade_history_createdAt_gte', '10/10/2019');
        $I->fillField('#query_trade_history_createdAt_lt', '05/05/2025');
        $I->click("Export CSV");
        $I->seeCurrentUrlEquals('/my-portfolio/trade-history/export');
        $I->see("Trade History Export"); // Still see the page contents
        $I->dontSee("Please enter a valid date.");
        $I->see("Date range cannot be greater than 12 months.");

        // Check end before start
        // Dockerised chromium defaults to US date format mm/dd/yyyy
        $I->fillField('#query_trade_history_createdAt_gte', '10/10/2024');
        $I->fillField('#query_trade_history_createdAt_lt', '05/05/2024');
        $I->click("Export CSV");
        $I->seeCurrentUrlEquals('/my-portfolio/trade-history/export');
        $I->see("Trade History Export"); // Still see the page contents
        $I->dontSee("Please enter a valid date.");
        $I->see("End Date must be after Start Date");
    }
}
