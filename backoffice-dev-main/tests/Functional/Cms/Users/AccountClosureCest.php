<?php

namespace App\Tests\Functional\Cms\Users;

use App\Entity\Enum\AccountCleanupAction;
use App\Tests\Support\FunctionalTester;

class AccountClosureCest
{
    private ?string $userId = null;

    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
        $this->userId = $I->grabFromDatabase('users', 'id', [
            'username' => $I::USER_REG_KYC_RED,
        ]);
    }

    public function _after(FunctionalTester $I)
    {
        // Manually update username back to original (restore process)
        $I->amOnPage("/admin/users/{$this->userId}/update-username");
        $I->fillField('#username_username', $I::USER_REG_KYC_RED);
        $I->click('Update Username');
        $I->seeCurrentUrlEquals("/admin/users/{$this->userId}/account-closure");
        $I->see($I::USER_REG_KYC_RED, '[data-field-name=username]');

        $I->amOnPage("/admin/users/{$this->userId}/account-closure");
        $enabled = $I->grabTextFrom("[data-field-name='is-blocked?']") == 'No';
        if (!$enabled) {
            $I->click('Toggle Account Block');
        }
    }

    public function checkAccountClosureCleanup(FunctionalTester $I)
    {
        $I->amOnPage("/admin/users/{$this->userId}/account-closure");
        $I->click('Configure Custom Username');
        $I->seeCurrentUrlEquals("/admin/users/{$this->userId}/update-username");
        $I->see('Update Username');
        $I->see($I::USER_REG_KYC_RED, '[data-field-name=current-username]');
        $I->seeLink('Discard Changes', "/admin/users/{$this->userId}/account-closure");

        $I->fillField('#username_username', $I::USER_REG_KYC_RED);
        $I->click('Update Username');
        $I->seeCurrentUrlEquals("/admin/users/{$this->userId}/update-username");
        $I->see('Username already exists');

        $newUsername =
            'deletion_test_' . bin2hex(random_bytes(8)) . '@test.yielderverse.co.uk';
        $I->fillField('#username_username', $newUsername);
        $I->click('Update Username');
        $I->seeCurrentUrlEquals("/admin/users/{$this->userId}/account-closure");

        $I->dontSeeLink('Review Deletion Options');
        $I->click('Toggle Account Block');
        $I->click('Review Deletion Options');
        // This user should have no restrictions on account cleanup
        $I->seeCurrentUrlEquals(
            "/admin/users/{$this->userId}/account-closure/retention/none",
        );
        $I->seeLink('Cancel', "/admin/users/{$this->userId}/account-closure");
        $I->dontSeeCheckboxIsChecked("input[type='checkbox']");

        $I->click('Select All Internal Actions');
        foreach (AccountCleanupAction::internalActions() as $action) {
            $I->seeCheckboxIsChecked('#account_closure_cleanup_actions_'
            . $action->name);
        }
        foreach (AccountCleanupAction::externalActions() as $action) {
            $I->dontSeeCheckboxIsChecked('#account_closure_cleanup_actions_'
            . $action->name);
            $I->checkOption('#account_closure_cleanup_actions_' . $action->name);
        }
        $I->dontSeeCheckboxIsChecked('#account_closure_cleanup_confirmationMailchimp');
        $I->dontSeeCheckboxIsChecked(
            '#account_closure_cleanup_confirmationExtraWallets',
        );
        $I->dontSeeCheckboxIsChecked('#account_closure_cleanup_confirmation');

        $I->click('Deselect All Actions');
        $I->dontSeeCheckboxIsChecked("input[type='checkbox']");
        $I->click('Select All Internal Actions');

        $I->checkOption('#account_closure_cleanup_confirmationMailchimp');
        $I->checkOption('#account_closure_cleanup_confirmationExtraWallets');
        $I->checkOption('#account_closure_cleanup_confirmation');
        $I->click('Delete User Data');
        $I->seeCurrentUrlEquals("/admin/users/{$this->userId}/account-closure");
        $I->see('Successfully cleaned up and deleted data for user');

        $nullifiedFields = [
            'name',
            'gender',
            'title',
            'nationality',
            'date-of-birth',
            'mobile-phone',
            'phone-1',
            'phone-2',
            'company-address',
        ];
        foreach ($nullifiedFields as $field) {
            $I->see('N/A', '[data-field-name="' . $field . '"]');
        }
        $currentDate = new \DateTime()->format('Ymd');
        $regexPattern =
            '/^' . "{$this->userId}_{$currentDate}" . "_\d{6}\@closed\.example\.com$/";
        $I->assertMatchesRegularExpression(
            $regexPattern,
            $I->grabTextFrom('[data-field-name="username"]'),
        );
        $I->assertMatchesRegularExpression(
            $regexPattern,
            $I->grabTextFrom('[data-field-name="contact-email"]'),
        );
    }
}
