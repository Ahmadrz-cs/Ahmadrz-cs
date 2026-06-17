<?php


class PagePortfolioInvestmentsCest
{
    // public function _before(AcceptanceTester $I) {}

    // public function _after(AcceptanceTester $I) {}

    // /**
    //  * @group portfolio
    //  */
    // public function checkPortfolioNav(\Step\Acceptance\StaticPages $I)
    // {
    //     /**
    //      * Check correct nav item is highlighted (i.e. has the class "active")
    //      */
    //     $I->loginWithCredentials($I->reg_user_name, $I->admin_user_password);
    //     $I->amOnPage('/my-investments');
    //     $I->see("My Investments", "#pills-investments-tab");
    // }

    // /**
    //  * @group portfolio
    //  */
    // public function checkMyInvestmentsContent(\Step\Acceptance\StaticPages $I)
    // {
    //     /**
    //      * Check for the section title "My Investments"
    //      * Check the "Export Investments As CSV" button is there
    //      * Check property filter dropdown is present
    //      * Check the investments count (use regex for the numbers) "Showing \d+ of \d+ investmensts (\d+ active)"
    //      * Check table headers are there
    //      */

    //     //getting a text from Showing 6 of 6 investments (6 active)

    //     $I->loginWithCredentials($I->reg_user_name, $I->admin_user_password);
    //     $I->amOnPage('/my-investments');
    //     $I->see("My INvestments");
    //     $I->seeElement("a[href='/investment-history']");
    //     $I->seeElement("select#filter_asset_name");
    //     $investments = $I->grabTextFrom("//p[@class='text-md']");
    //     $I->assertRegExp('/^Showing \d+ of \d+ investments \(\d+ active\)$/', $investments);

    //     $headers = [
    //         'Id',
    //         'Date',
    //         'Original Value',
    //         'Current Value',
    //         'Divested Value',
    //         'Original Shares',
    //         'Current Shares',
    //         'Shares Offered',
    //         'Asset Name',
    //         'Status',
    //         // 'View Cert',
    //         'Sell Investment',
    //     ];
    //     foreach (range(1, 11) as $i) {
    //         $header = $I->grabTextFrom("table thead th:nth-child(" . $i . ")");
    //         $I->assertEquals($headers[$i - 1], $header);
    //     }
    // }

    // public function checkMyInvestmentsDownloads(\Step\Acceptance\StaticPages $I)
    // {
    //     $I->loginWithCredentials($I->reg_user_name, $I->admin_user_password);
    //     $I->amOnPage('/my-investments');

    //     // Checking export button
    //     $I->scrollTo("a.nav-link.active");
    //     $I->click("a[href='/investment-history']");
    //     $I->amOnPage('/my-investments');

    //     // Check share cert
    //     // $I->scrollTo("a.nav-link.active");
    //     // $I->click("table.table tbody tr:first-child a[title='Generate']");
    //     // $I->amOnPage('/my-investments');
    // }

    // /**
    //  * @group portfolio
    //  */
    // public function checkFullyDivestedAsset(\Step\Acceptance\StaticPages $I)
    // {
    //     /**
    //      * Check hollys fully divested Royal Way Gardens has sold in sell column
    //      */
    //     $I->loginWithCredentials($I->approved_investor_1, $I->admin_user_password);
    //     $I->amOnPage('/my-investments');
    //     $I->waitForElement(".transaction-history");
    //     $I->see("My Investments", "#pills-investments-tab");
    //     $I->scrollTo("table.table");
    //     $investmentId = $I->grabFromDatabase('investments', 'id', ['transaction_id' => 'TestFullyDivested']);
    //     /**
    //      * Check current shares is 0
    //      */
    //     $currentShares = $I->grabTextFrom("tr[data-investment-id='" . $investmentId . "'] td[data-label='Current Shares'] span");
    //     $I->assertEquals($currentShares, 0);
    //     /**
    //      * Check sold is shown (not sell button)
    //      */
    //     $sellStatus = $I->grabTextFrom("tr[data-investment-id='" . $investmentId . "'] td[data-label='Sell Investment'] span.text-uppercase ");
    //     $I->assertEquals($sellStatus, "SOLD");
    // }
}
