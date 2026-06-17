<?php

use AppBundle\Entity\Enum\ScaStatus;


class WalletAccessCest
{
    private ?string $userId = null;
    private ?string $walletId = null;

    public function _before(AcceptanceTester $I)
    {
        $this->userId = $I->grabFromDatabase('users', 'id', ['username' => $I->reg_user_name]);
        $this->walletId = $I->grabFromDatabase('users', 'mangoPayWalletId', ['username' => $I->reg_user_name]);
    }

    public function _after(AcceptanceTester $I)
    {
        $I->updateInDatabase(
            'users',
            ['scaStatus' => ScaStatus::Active->value],
            ['id' => $this->userId],
        );
        $I->updateInDatabase(
            'users',
            ['mangoPayWalletId' => $this->walletId],
            ['id' => $this->userId],
        );
    }


    public function checkWalletBalanceCest(AcceptanceTester $I)
    {
        $I->loginWithCredentials($I->reg_user_name, $I->admin_user_password, skipScaCheck: false);

        // Check how wallet appears
        $I->see("Wallet (£", "#myHeader .topbar .topbar-list-item");

        // Set ScaStatus to Inactive
        $I->updateInDatabase(
            'users',
            ['scaStatus' => ScaStatus::Inactive->value],
            ['id' => $this->userId],
        );
        // Refresh by going to my profile
        $I->amOnPage("/my-profile/dashboard");
        $I->wait(1);
        $I->see("Wallet", "#myHeader .topbar .topbar-list-item");
        $I->dontSee("Wallet (£", "#myHeader .topbar .topbar-list-item");
        $I->scrollTo('#my-wallet');
        $I->seeLink("Start SCA Setup");

        // Remove the mangopay wallet as well
        $I->updateInDatabase(
            'users',
            ['mangoPayWalletId' => null],
            ['id' => $this->userId],
        );
        // Refresh by going to my profile
        $I->amOnPage("/my-profile/dashboard");
        $I->wait(1);
        $I->see("Wallet", "#myHeader .topbar .topbar-list-item");
        $I->dontSee("Wallet (£", "#myHeader .topbar .topbar-list-item");
        $I->scrollTo('#my-wallet');
        $I->see('Your wallet has not been setup yet', '#my-wallet');

        // Restoring Sca enrollment status won't help if no wallet exists
        $I->updateInDatabase(
            'users',
            ['scaStatus' => ScaStatus::Active->value],
            ['id' => $this->userId],
        );
        // Refresh by going to my profile
        $I->amOnPage("/my-profile/dashboard");
        $I->wait(1);
        $I->see("Wallet", "#myHeader .topbar .topbar-list-item");
        $I->dontSee("Wallet (£", "#myHeader .topbar .topbar-list-item");
        $I->scrollTo('#my-wallet');
        $I->see('Your wallet has not been setup yet', '#my-wallet');
    }
}
