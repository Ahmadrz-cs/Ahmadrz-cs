<?php

use AppBundle\Entity\Enum\BankAccountStatus;
use Symfony\Component\Uid\Uuid;

class LinkedBankAccountsCest
{
    public function _before(AcceptanceTester $I) {}

    public function _after(AcceptanceTester $I) {}

    /**
     * @group profile
     */
    public function checkLinkedAccountLimit(AcceptanceTester $I)
    {
        // Ben user should have 3 accounts (which is our limit)
        // This seems to be a bit flakey as sometimes the sync doesn't work for all 3 accounts
        $I->loginWithCredentials($I->reg_user_name, $I->admin_user_password, skipScaCheck: false);
        $I->amOnPage('/my-profile/bank-accounts');
        $I->dontSeeLink("Add new Bank Account", '/my-profile/bank-accounts/new');
        $I->seeLink("Withdraw Funds", '/withdraw-funds');

        // At least 3, if for whatever reason more have been added
        // If this fails, then ben user could do with a cleanup
        $I->scrollTo("#linked-bank-accounts-list", 0, -60);
        $I->seeNumberOfElements("#linked-bank-accounts-list tbody tr", [3, 10]);

        // Check redirects if attempting to add while at limit
        $I->amOnPage("/my-profile/bank-accounts/new");
        $I->seeCurrentUrlEquals("/my-profile/bank-accounts");

        $I->waitForText("You have already linked 3 or more bank accounts and cannot add any more");
        $I->amOnPage("/my-profile/bank-accounts/new/gb");
        $I->seeCurrentUrlEquals("/my-profile/bank-accounts");
        $I->waitForText("You have already linked 3 or more bank accounts and cannot add any more");

        $I->amOnPage("/my-profile/bank-accounts/new/iban");
        $I->seeCurrentUrlEquals("/my-profile/bank-accounts");
        $I->waitForText("You have already linked 3 or more bank accounts and cannot add any more");

        // Check viewing non-existent bank account returns 404
        $I->amOnPage("/my-profile/bank-accounts/1000001");
        $I->seeInTitle("404");
    }

    /**
     * @group profile
     */
    public function checkAddressUpdateTool(AcceptanceTester $I)
    {
        $I->loginWithCredentials($I->approved_investor_1, $I->admin_user_password, skipScaCheck: false);
        $I->amOnPage('/my-profile/bank-accounts');
        $I->scrollTo("#current-address", 0, -60);
        $I->see('No proof of address uploaded in the last 30 days', '#recent-poa-indicator');
        $I->click("Update Address");
        $I->seeCurrentUrlEquals("/my-profile/bank-accounts/update-address");
        $I->seeLink("Cancel", '/my-profile/bank-accounts');

        $I->scrollTo('form[name="user_address_update"]', 0, -60);
        $I->attachFile("#user_address_update_proofOfAddress", "specimen_passport.jpg");
        $I->click("Submit");
        $I->waitForText("Successfully submitted new proof of address");
        $I->seeCurrentUrlEquals("/my-profile/bank-accounts");

        // Recent proof of address indicator should have updated
        $I->amOnPage('/my-profile/bank-accounts');
        $I->scrollTo("#current-address", 0, -60);
        $I->see('proof of address was recently uploaded', '#recent-poa-indicator');
        $I->click("Update Address");
        $I->see('proof of address was recently uploaded', '#recent-poa-indicator');
    }

    /**
     * @group profile
     */
    public function checkAddAndRemoveAccount(AcceptanceTester $I)
    {
        // Holly user should only usually have 1 account that is NOT the Mangopay default test account
        $I->loginWithCredentials($I->approved_investor_1, $I->admin_user_password, skipScaCheck: false);

        // Add an account
        $I->amOnPage('/my-profile/bank-accounts');
        $I->click("Add new Bank Account");
        $I->waitForElement("#account-type-gb");
        $I->seeCurrentUrlEquals("/my-profile/bank-accounts/new");
        $I->seeLink("Cancel", '/my-profile/bank-accounts');
        $I->scrollTo("#account-type-gb", 0, -100);
        $I->seeLink("Add GB Bank Account", '/my-profile/bank-accounts/new/gb');
        $I->seeLink("Add IBAN Bank Account", '/my-profile/bank-accounts/new/iban');

        $last4 = $I->submitBankAccount();
        $I->waitForText("Successfully submitted new bank account registration");
        $I->seeCurrentUrlEquals("/my-profile/bank-accounts");
        $I->see("GBP GB •••• {$last4}", "#linked-bank-accounts-list tbody tr");

        // Check view details page (should be the latest one in the list)
        $I->amOnPage('/my-profile/bank-accounts');
        $I->scrollTo("#linked-bank-accounts-list", 0, -60);
        $I->click("View", "#linked-bank-accounts-list tbody tr:last-child");
        $I->seeLink("Back to all Linked Accounts", '/my-profile/bank-accounts');
        $I->seeCurrentUrlMatches("~^\/my-profile\/bank-accounts\/[0-9a-f]{8}-([0-9a-f]{4}-){3}[0-9a-f]{12}$~");
        $uuid = $I->getUrlSegments($I->grabFromCurrentUrl())[0];
        $I->see("Personal", '#bank-account-info [data-field-name="type"]');
        $I->see("Validated", '#bank-account-info [data-field-name="status"]');
        $I->see("GBP GB •••• {$last4}", '#bank-account-info [data-field-name="display-name"]');
        $I->see(AcceptanceTester::MANGOPAY_TEST_BANK_ACCOUNT, '#bank-account-info [data-field-name="account-number"]');
        $I->see(AcceptanceTester::MANGOPAY_TEST_SORT_CODE, '#bank-account-info [data-field-name="sort-code/bic"]');
        $I->see("GB", '#bank-account-info [data-field-name="country"]');
        $I->see("GBP", '#bank-account-info [data-field-name="currency"]');

        // You shouldn't be able to add the same account again
        $I->submitBankAccount();
        $I->waitForText("Unable to submit new bank account registration");
        $I->seeCurrentUrlEquals("/my-profile/bank-accounts");
        $I->dontSee("Successfully submitted new bank account registration");

        // Check account is NOT available in the withdraw dropdown
        $I->amOnPage('/withdraw-funds');
        $I->dontSee("GBP GB •••• {$last4}", "select#bank_account_withdrawal_account");

        // Check proof of address promotp
        $I->updateInDatabase(
            "bank_account",
            ["metadata" => json_encode(["actionRequests" => ["proof_of_address"]])],
            ["uuid" => Uuid::fromString($uuid)->toBinary()],
        );
        $I->amOnPage('/my-profile/bank-accounts');
        // Implicitly check the prompt is there by using the id as a selector
        $I->scrollTo("#proof-of-address-prompt", 0, -60);
        $I->see('Update Address', '#proof-of-address-prompt a');
        // Prompt should also appear in the individual view
        $I->scrollTo("#linked-bank-accounts-list", 0, -60);
        $I->click("View", "#linked-bank-accounts-list tbody tr:last-child");
        $I->scrollTo("#proof-of-address-prompt", 0, -60);
        $I->see('Update Address', '#proof-of-address-prompt a');

        // Set status to `approved` in database
        $I->updateInDatabase(
            "bank_account",
            ["status" => BankAccountStatus::Approved->value],
            ["uuid" => Uuid::fromString($uuid)->toBinary()],
        );
        // Check account is still NOT available in the withdraw dropdown
        $I->amOnPage('/withdraw-funds');
        $I->dontSee("GBP GB •••• {$last4}", "select#bank_account_withdrawal_account");

        // Activate account and go through SCA (available from the list view or single view)
        $I->amOnPage("/my-profile/bank-accounts");
        $I->seeLink("Activate", "/my-profile/bank-accounts/{$uuid}/activate");
        $I->amOnPage("/my-profile/bank-accounts/{$uuid}");
        $I->seeLink("Activate", "/my-profile/bank-accounts/{$uuid}/activate");

        $I->click("Activate");
        $I->completeScaVerification($I::HOLLY_MP_EMAIL);
        $I->waitForText("Successfully activated linked bank account", 10);
        $I->seeCurrentUrlEquals("/my-profile/bank-accounts");

        // Check account is now active and IS available in the withdraw dropdown
        $I->amOnPage('/withdraw-funds');
        $I->see("GBP GB •••• {$last4}", "select#bank_account_withdrawal_account");

        // Unlink account
        $I->amOnPage("/my-profile/bank-accounts/{$uuid}");
        $I->scrollTo("#account-unlinking", 0, -60);
        $I->checkOption("#form_confirm");
        $I->click("Unlink Bank Account");
        $I->wait(1);
        $I->seeCurrentUrlEquals("/my-profile/bank-accounts");
        $I->waitForText("Successfully unlinked bank account");
        // Bit daft, but reloading the page is the most reliable way to get rid of the modals
        $I->amOnPage("/my-profile/bank-accounts");
        $I->scrollTo("#linked-bank-accounts-list", 0, -60);
        $I->dontSee("GBP GB •••• {$last4}", "#linked-bank-accounts-list tbody tr");

        // Check account is no longer available in the withdraw dropdown
        $I->amOnPage('/withdraw-funds');
        $I->dontSee("GBP GB •••• {$last4}", "select#bank_account_withdrawal_account");

        // Try to add IBAN account
        $ibanLast4 = $I->submitBankAccount(
            AcceptanceTester::MANGOPAY_TEST_IBAN,
            AcceptanceTester::MANGOPAY_TEST_BIC,
            false,
        );
        $I->waitForText("Successfully submitted new bank account registration");
        $I->seeCurrentUrlEquals("/my-profile/bank-accounts");
        // Note the default test IBAN is a French one
        $I->see("GBP FR •••• {$ibanLast4}", "#linked-bank-accounts-list tbody tr");
    }
}
