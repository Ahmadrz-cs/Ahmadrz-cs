<?php

namespace App\Tests\Functional\Ops\Upgrades;

use App\Entity\Enum\WalletUserVersion;
use App\Tests\Support\FunctionalTester;

class UpgradeUserCategoryCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function checkUserCategoryUpgradeStatus(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/upgrades/mangopay-user-category');
        $I->seeLink('Review Upgrades', '/admin/upgrades/mangopay-user-category/review');
        $I->seeElement('#users-list');

        $elements = [
            'Id',
            'Name',
            'Contact Email',
            'Join Date',
            'Last Login',
            'Mangopay User Id',
            'Mangopay User Version',
        ];
        // check table headers
        $I->loopCheckElements($elements, 'thead th');
    }

    public function checkUserCategoryUpgradeExecute(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/upgrades/mangopay-user-category/review');

        // Retrieve the values we expect the upgrade to use
        $sampleId = $I->grabFromDatabase('users', 'id', [
            'walletUserVersion' => WalletUserVersion::Original->value,
            'mangopayUserId !=' => 'null',
        ]);

        // Grab the current status
        $originalUsersRemaining = $I->grabTextFrom(
            '[data-field-name="original-users"]',
        );
        $upgradedUsers = $I->grabTextFrom(
            '[data-field-name="usercategory-upgraded-users"]',
        );

        // Update one of the non-admin users who won't been upgraded yet
        $I->fillField('input#upgrade_user_category_id', $sampleId);
        $I->click('Apply Upgrades to Selection');

        // Check updated page and user feedback
        $I->seeInCurrentUrl('/admin/upgrades/mangopay-user-category/review');
        $I->see('1 users upgraded');
        $I->see(
            (int) $originalUsersRemaining - 1,
            '[data-field-name="original-users"]',
        );
        $I->see(
            (int) $upgradedUsers + 1,
            '[data-field-name="usercategory-upgraded-users"]',
        );

        // Try again with the same payout
        $I->fillField('input#upgrade_user_category_id', $sampleId);
        $I->click('Apply Upgrades to Selection');
        $I->see('0 users upgraded');

        // Try a multi-upgrade
        $numberToUpgrade = 2;
        $I->fillField('input#upgrade_user_category_id', ''); // Clear the id field so we can upgrade more than 1 at a time
        $I->fillField('input#upgrade_user_category_amount', $numberToUpgrade);
        $I->click('Apply Upgrades to Selection');
        $I->see("{$numberToUpgrade} users upgraded");
    }
}
