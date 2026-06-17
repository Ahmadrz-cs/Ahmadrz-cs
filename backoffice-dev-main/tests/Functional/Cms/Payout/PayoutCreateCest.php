<?php

namespace App\Tests\Functional\Cms\Payout;

use App\Tests\Support\FunctionalTester;

final class PayoutCreateCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->loginAdmin();
    }

    /**
     * @group editor
     * @group management
     */
    public function testAssetEditorCreate(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/payout');
        $I->click('Create Retrospective Payout');
        $I->seeCurrentUrlEquals('/admin/payout/add');

        $I->seeElement('#new-payout-guidelines');
        $I->seeLink('Cancel', '/admin/payout');

        $assetName = $I->grabFromDatabase('assets', 'name', ['id' => 1]);

        $superadminId = $I->grabFromDatabase('users', 'id', [
            'username' => $I::USER_SUPER_ADMIN,
        ]);

        $randomString = bin2hex(random_bytes(8));
        // Just fill the required fields
        $I->selectOption('#payout_asset', '1');
        $I->selectOption('#payout_creditedUser', (string) $superadminId);
        $I->fillField('#payout_payoutAmount', '1.001');
        $I->fillField('#payout_shareholding', 1);
        // Try transfer with string field
        $I->fillField('#payout_transactionId', 'xfer_m_01HQWSM8M4GX88ZAVWTTZW55H2');
        $I->fillField('#payout_additionalType', "{$randomString} new payout test");
        $I->click('Submit');

        $newPayoutId = $I->grabFromDatabase('payouts', 'id', [
            'additionalType' => "{$randomString} new payout test",
        ]);
        $I->seeCurrentUrlEquals("/admin/payout?id={$newPayoutId}");

        $I->amOnPage("/admin/payout/$newPayoutId/edit");
        $I->assertStringContainsString(
            $assetName,
            $I->grabTextFrom('#payout_asset [selected="selected"]'),
        );
        $I->assertStringContainsString(
            $I::USER_SUPER_ADMIN,
            $I->grabTextFrom('#payout_creditedUser [selected="selected"]'),
        );
        $I->seeInField('#payout_payoutAmount', '1.00');
        $I->seeInField('#payout_shareholding', 1);
        $I->seeInField('#payout_transactionId', 'xfer_m_01HQWSM8M4GX88ZAVWTTZW55H2');
        $I->seeInField('#payout_additionalType', "{$randomString} new payout test");
    }
}
