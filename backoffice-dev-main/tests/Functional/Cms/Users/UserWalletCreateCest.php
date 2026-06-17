<?php

namespace App\Tests\Functional\Cms\Users;

use App\Tests\Support\FunctionalTester;

class UserWalletCreateCest
{
    private string $originalMpUserId = '';
    private string $originalMpWalletId = '';

    public function _before(FunctionalTester $I)
    {
        $this->originalMpUserId = $I->grabFromDatabase('users', 'mangoPayUserId', [
            'username' => $I::USER_REG1,
        ]);
        $this->originalMpWalletId = $I->grabFromDatabase('users', 'mangoPayWalletId', [
            'username' => $I::USER_REG1,
        ]);
        $I->loginAdmin();
    }

    public function _after(FunctionalTester $I)
    {
        // Restore originals
        $I->updateInDatabase(
            'users',
            ['mangoPayUserId' => $this->originalMpUserId],
            ['username' => $I::USER_REG1],
        );
        $I->updateInDatabase(
            'users',
            ['mangoPayWalletId' => $this->originalMpWalletId],
            ['username' => $I::USER_REG1],
        );
    }

    public function checkMangopayWalletCreation(FunctionalTester $I)
    {
        $testUserId = $I->grabFromDatabase('users', 'id', [
            'username' => $I::USER_REG1,
        ]);

        // We'll use a known SCA enrolled test Mangopay user (on our sandbox account)
        $I->updateInDatabase(
            'users',
            ['mangoPayUserId' => 'user_m_01JXZ6CD6AP6ESYASVPPJ4W2WD'],
            ['username' => $I::USER_REG1],
        );
        $I->updateInDatabase(
            'users',
            ['mangoPayWalletId' => null],
            ['username' => $I::USER_REG1],
        );

        $I->amOnPage("/admin/users/{$testUserId}/dashboard");
        $I->see('N/A', '[data-field-name="mangopay-wallet-id"]');
        $I->seeLink('Create Wallet', "/admin/users/{$testUserId}/wallets/create-all");
        $I->click('Create Wallet');
        $I->see('successfully setup');
        $I->dontSee('N/A', '[data-field-name="mangopay-wallet-id"]');
    }
}
