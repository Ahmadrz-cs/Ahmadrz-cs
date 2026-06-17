<?php

namespace App\Tests\Functional\Ops\PaymentOrder;

use App\Tests\Support\FunctionalTester;

class PaymentOrderPayMidwayCrashCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();

        // Change user's wallet to invalid one
        // USER_REG2 (holly) is the 3rd largest shareholder in Royal Eversea Glades - Cambridge
        $I->updateInDatabase(
            'users',
            ['mangoPayWalletId' => 'invalid wallet'],
            ['username' => $I::USER_REG2],
        );
    }

    public function _after(FunctionalTester $I)
    {
        // Revert user's wallet back to valid one
        $I->updateInDatabase(
            'users',
            ['mangoPayWalletId' => $I::FIXTURE_WALLETS['holly']],
            ['username' => $I::USER_REG2],
        );
    }

    public function testRunOrderCrashProgressSaved(FunctionalTester $I): void
    {
        // Royal Eversea has 5 shareholders, holly (USER_REG2) is the 3rd largest shareholder
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name like' => 'Royal Eversea Glades%',
        ]);

        // We'll shortcut the payment order creation by using the dividend orders flow/UI
        $I->amOnPage("/admin/monthend/dividends/create?assetId={$assetId}");
        $I->click('Create Payment Order');
        $I->click('Save Changes');
        $I->click('Save Changes');
        $I->fillField('#payment_order_generate_amount', '0.86');
        $I->click('Generate Payments');

        // Sanity check that the user is indeed in the middle of the 5
        $I->seeNumberOfElements('#payments-list tbody tr', 5);
        $I->see($I::USER_REG2, '#payments-list tbody tr:nth-child(3)');

        $I->click('Approve Payment Order');
        $I->click('Run Payment Order');
        $I->see('Unable to run payment order');

        // Only the 3rd (that we modified) will have failed due to the issue with USER_REG2's wallet
        // Order will continue afterwards
        foreach (range(1, 5) as $row) {
            if ($row == 3) {
                $I->see(
                    'Failed',
                    "#payments-list tbody tr:nth-child({$row}) td[data-field='status']",
                );
                $I->see('Pay Single', "#payments-list tbody tr:nth-child({$row}) a");
            } else {
                $I->see(
                    'Paid',
                    "#payments-list tbody tr:nth-child({$row}) td[data-field='status']",
                );
                $I->see('View Payout', "#payments-list tbody tr:nth-child({$row}) a");
            }
        }
    }
}
