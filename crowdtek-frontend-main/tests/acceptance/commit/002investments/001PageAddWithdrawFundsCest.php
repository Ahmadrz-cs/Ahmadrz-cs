<?php


class PageAddWithdrawFundsCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->loginWithCredentials($I->reg_user_name, $I->admin_user_password, false, false);
    }

    public function _after(AcceptanceTester $I)
    {
    }

    // check amount field highlight
    // check "add funds" button blocks when form invalid/incomplete
    public function checkAddFundsFormValidation(AcceptanceTester $I)
    {
        $I->amOnPage('/add-funds-new');
        $I->scrollTo(['name' => 'add_funds_form']);

        // block if amount is 0 but other fields okay
        $I->submitCardPayinForm("4706750000000009", "0");
        $I->seeCurrentUrlEquals('/add-funds-new');
        $I->seeElement('//input[@id="amount"][contains(@class, "input_error_highlight")]');

        $I->amOnPage('/add-funds-new');
        $I->scrollTo(['name' => 'add_funds_form']);

        // Cannot be 0
        $I->fillField(['id' => 'amount'], 0);
        $I->clickWithLeftButton('#cardNumber');
        $I->seeElement('//input[@id="amount"][contains(@class, "input_error_highlight")]');

        // Cannot be greater than 1500
        $I->fillField(['id' => 'amount'], 50);
        $I->clickWithLeftButton('#cardNumber');
        $I->dontSeeElement('//input[@id="amount"][contains(@class, "input_error_highlight")]');

        // card form amount re-highlights >£1500
        $I->fillField(['id' => 'amount'], 1501);
        $I->clickWithLeftButton('#cardNumber');
        $I->seeElement('//input[@id="amount"][contains(@class, "input_error_highlight")]');
    }


    public function testWithdrawFunds(AcceptanceTester $I)
    {
        $walletBefore = $I->grabTextFrom('a.wallet-data');

        $I->createPayOut(50);
        $I->seeCurrentUrlEquals('/my-profile/transactions');
        // attempt 5 times over 10 seconds to handle delay in bankwire transfer
        foreach (range(1, 5) as $n) {
            try {
                $I->amOnPage('/withdraw-funds');
                $walletAfter = $I->grabTextFrom('a.wallet-data');
                $I->assertNotEquals($walletBefore, $walletAfter);
                break;
            } catch (\Exception $e) {
                $I->wait(2);
            }
        }
    }

    public function testWithdrawalLimit(AcceptanceTester $I)
    {
        $I->amOnPage('/withdraw-funds');
        $I->waitForText('Create A Payout');

        $I->seeLink("Manage Linked Bank Accounts", "/my-profile/bank-accounts");

        $I->selectOption('#bank_account_withdrawal_account', 'bankacc_m_01HW5RPBZ3JHTXG97AEV316761');
        $I->fillField('#bank_account_withdrawal_amount', 9);

        $I->click('Submit');

        $I->dontSee('Create payout successful');
    }

    /**
     * Transactions table needs to be updated to not be reorderable
     */
    public function addingFundsAndMakingPayouts(AcceptanceTester $I)
    {
        /**
         * Check add funds and withdrawals work and are tagged correctly
         * [1] Add funds card - action, tag
         * [2] Add funds bankwire - action, tag
         * [3] Withdrawal - action, tag
         */
        $I->loginWithCredentials($I->reg_user_name, $I->admin_user_password);
        $walletBefore = $I->grabTextFrom("a.wallet-data");

        $payin = $I->addFunds(1243.21);

        if ($payin) {
            $I->amOnPage('/my-profile/transactions');
            $I->waitForText('Transaction History');
            $I->see('Card Payment', ['css' => '.transaction-history tbody tr:first-child td:nth-child(2)']);
            // $I->amOnPage("/logout");
            // $I->loginWithCredentials($I->reg_user_name,$I->admin_user_password, false);
            $walletAfter = $I->grabTextFrom("a.wallet-data");
            $I->assertNotEquals($walletBefore, $walletAfter);

            // Checking for update in profile dashboard
            $I->amOnPage("/my-profile/dashboard");
            $walletBefore = $I->grabTextFrom("div.row div.bg-info p.h3");
            $I->addFunds();
            $I->amOnPage("/my-profile/dashboard");
            $walletAfter = $I->grabTextFrom("div.row div.bg-info p.h3");
            $I->assertNotEquals($walletBefore, $walletAfter);
        }

        // Check linking between payin types
        $I->amOnPage('/add-funds-new');
        $I->seeLink('Pay by Bankwire', '/add-funds/bankwire');

        $I->amOnPage('/add-funds/bankwire');
        $I->seeLink('Pay by Debit Visa / Mastercard', '/add-funds-new');

        // Check bankwire transfer steps
        $I->scrollTo('#bankwire-step-1');
        $I->seeElement('input', ['name' => 'form[amount]', 'value' => '100']);
        $I->fillField('#form_amount', '176');
        $I->click('Create Transfer Instruction');

        $I->waitForText('Add funds by bankwire payin: Next Steps');

        $I->scrollTo('#bankwire-step-2');
        $I->see('Owner', 'label');
        $I->see('Account Type', 'label');
        $I->see('Sort Code', 'label');
        $I->see('Account Number', 'label');
        $I->see('IBAN', 'label');
        $I->see('BIC', 'label');

        $I->scrollTo('#bankwire-step-3');
        $I->see('Amount (£)', 'label');
        $I->seeInField('#txtamount', '176');
        $I->see('Bankwire Reference', 'label');
        $I->assertNotEmpty($I->grabValueFrom('#txtwire'));

        $I->scrollTo('#bankwire-step-4');
        $I->seeLink('Return to Profile', '/my-profile/dashboard');

        // Check bankwire transfer record in transactions
        $I->amOnPage('/my-profile/transactions');
        $I->waitForText('Transaction History');
        $I->clickWithLeftButton(null, 20, 50);
        $I->wait(1);
        $I->see('Bankwire Transfer', ['css' => '.transaction-history tbody tr:first-child td:nth-child(2)']);

        // Check payout record in transactions
        $I->createPayOut(109.99);
        # Give a bit of time for Mangopay to process the withdrawal
        $I->wait(3);
        $I->amOnPage('/my-profile/transactions');
        $I->waitForText('Transaction History');
        $I->see('Withdrawal', ['css' => '.transaction-history tbody tr:first-child td:nth-child(2)']);
    }
}
