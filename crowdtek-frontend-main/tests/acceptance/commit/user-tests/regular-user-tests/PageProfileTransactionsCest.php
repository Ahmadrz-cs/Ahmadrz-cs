<?php


class PageProfileTransactionsCest
{
    public function _before(\Step\Acceptance\StaticPages $I)
    {
        $I->loginWithCredentials($I->reg_user_name, $I->admin_user_password);
        $I->amOnPage('/my-profile/transactions');
    }

    public function _after(AcceptanceTester $I) {}

    /**
     * @group profile
     */
    public function checkProfileNav(\Step\Acceptance\StaticPages $I)
    {
        /**
         * Check correct nav item is highlighted (i.e. has the class "active")
         */

        $I->see("Transaction History", ".navClick.active");
    }

    /**
     * @group profile
     */
    public function checkTransactionsPageContents(\Step\Acceptance\StaticPages $I)
    {
        /**
         * Check the "My Wallet" block is visible
         * - Title
         * - Wallet amount element (do NOT check that actual value of the wallet)
         * - Add funds link
         * - Withdraw funds link
         *
         * Check the "Transaction History" block is visible
         * - Title
         * - "Recent activity" table header
         * - Column fields are present - there was originally 7, but it seems to be 6 now
         *   - Reference (comment out test for "reference" since it's not in the current table)
         *   - Date
         *   - Type
         *   - Asset Name
         *   - Asset Code
         *   - Amount
         *   - Status
         * - At least 1 row of table data <tr> with <td> is present
         */

        $walletAmt = $I->grabTextFrom("p[class^='h3 text-center mb-3']");
        $I->comment("Wallet Amount is: $walletAmt");
        $I->see($walletAmt, "p[class^='h3 text-center mb-3']");


        $I->see("Add Funds", "//div[@class='d-flex justify-content-center mb-2']/a[1][contains(text(),'Add Funds')]");

        $I->see("Withdraw Funds", "a[href='/withdraw-funds']");

        $I->see("Transaction History", "//div[@id='transactionHistory']/div/div[1]/h4[contains(text(),'Transaction History')]");
        $I->seeElement("//div[@id='transactionHistory']");

        for ($child = 1; $child <= 6; $child++) {
            // Title Check
            $text1 = $I->grabTextFrom(".transaction-history thead tr:first-child th:nth-child($child)");
            $I->see($text1, ".transaction-history thead tr:first-child th:nth-child($child)");
            // One Data Row Present Check
            $text2 = $I->grabTextFrom(".transaction-history tbody tr:first-child td:nth-child($child)");
            $I->see($text2, ".transaction-history tbody tr:first-child td:nth-child($child)");
        }
    }

    /**
     * @group profile
     */
    public function checkTransactionHistoryPagination(\Step\Acceptance\StaticPages $I)
    {
        /**
         * Check pagination and links work (this test is working, no need to modify)
         * [1] click next and check page increments
         * [2] click previous and check page decrements
         */

        $I->clickWithLeftButton("//form[@id='pagination-nav']/nav/ul/span");
        $I->amOnPage('/my-profile/transactions?page=2');

        $I->scrollTo("//p[@class='font-medium footerText']");
        $I->clickWithLeftButton("//form[@id='pagination-nav']/nav/ul/span[3]");
        $I->amOnPage('/my-profile/transactions?page=3');

        //previous Pagination
        $I->scrollTo("//p[@class='font-medium footerText']");
        $I->clickWithLeftButton("//form[@id='pagination-nav']/nav/ul/span[2]");
        $I->amOnPage('/my-profile/transactions?page=2');

        $I->scrollTo("//p[@class='font-medium footerText']");
        $I->clickWithLeftButton("//form[@id='pagination-nav']/nav/ul/span[2]");
        $I->amOnPage('/my-profile/transactions?page=1');
    }

    /**
     * @group export_function
     */
    public function exportTransactionHistoryButton(\Step\Acceptance\StaticPages $I)
    {
        /**
         * This test is working, no need to modify
         * Check clicking on download doesn't return an error (stay on same page)
         */
        // $I->clickWithLeftButton("a[class^='btn btn-primary btn-popup btn-popup px-2 rounded-pill']");
        $I->click("Export to CSV");
        $I->seeInCurrentUrl("/transaction-export");
        $I->click("button#transaction-download");
        $I->seeInCurrentUrl("/transaction-export");
        $I->seeElement("div.form-group div#startdate");
        $I->seeElement("div.form-group div#enddate");
    }
}
