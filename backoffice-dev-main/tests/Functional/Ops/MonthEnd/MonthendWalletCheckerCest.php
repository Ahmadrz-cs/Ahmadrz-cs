<?php

namespace App\Tests\Functional\Ops\MonthEnd;

use App\Tests\Support\FunctionalTester;

class MonthendWalletCheckerCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function checkWalletBalanceChecker(FunctionalTester $I): void
    {
        $newOrderId = $I->createFeeCollectionOrder();
        $I->amOnPage("/admin/monthend/fee-collections/{$newOrderId}/generate");
        $transferToModify = $I->grabAttributeFrom(
            '#transfers-list tbody tr:first-child',
            'data-object-id',
        );
        $originalCreditWalletId = $I->grabFromDatabase(
            'transfer_request',
            'creditWalletId',
            [
                'id' => $transferToModify,
            ],
        );
        $I->updateInDatabase(
            'transfer_request',
            ['creditWalletId' => 'unknownTestWallet'],
            ['id' => $transferToModify],
        );
        $I->click('Generate All Transfers');

        $I->click('Check Wallet Balances');
        $I->seeCurrentUrlEquals("/admin/monthend/{$newOrderId}/wallet-checker");

        // See warning box about using up mangopay api calls
        $I->seeElement('section#api-warning');
        $I->seeElement(
            'section#api-warning [data-field-name="mangopay-api-calls-made"]',
        );
        $expectedWallets = $I->grabTextFrom(
            'section#api-warning [data-field-name="mangopay-api-calls-made"]',
        );
        $I->seeLink('Check Mangopay API Rate Limits', '/admin/status');

        // See warning about unknown wallet
        $I->see('Wallet with id unknownTestWallet could not be retrieved', '.alert');

        // Check the wallet balances table
        $I->seeNumberOfElements(
            '#debit-wallet-amount-list tbody tr',
            (int) $expectedWallets,
        );
        // The row with the invalid wallet should have a balance warning
        $I->see(
            'Insufficient Balance',
            '#debit-wallet-amount-list tbody tr[data-wallet-id="unknownTestWallet"]',
        );

        // See the transfers table is available for reference
        $I->seeElement('table#transfers-list');
        $I->see(
            'unknownTestWallet',
            'table#transfers-list tbody tr [data-field="debitWalletId"]',
        );

        // Revert database changes
        $I->updateInDatabase(
            'transfer_request',
            ['creditWalletId' => $originalCreditWalletId],
            ['id' => $transferToModify],
        );
    }
}
