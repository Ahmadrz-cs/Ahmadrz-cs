<?php

namespace App\Tests\Functional\Ops\BankAccounts;

use App\Entity\Enum\ActionRequest;
use App\Entity\Enum\BankAccountStatus;
use App\Tests\Support\FunctionalTester;

class BankAccountsCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->loginAdmin();
    }

    public function checkBankAccountListElements(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/bank-accounts');
        $I->seeLink('Register New Bank Account', '/admin/bank-accounts/create');

        $elements = [
            'Id',
            'User',
            'Type',
            'Mangopay Id',
            'Owner Type',
            'Status',
            'Action Requests',
            'Created',
            'Actions',
        ];
        $locator = '#bank-accounts-list thead tr th';
        $I->loopCheckElements($elements, $locator);

        $I->seeLink('Browse Recipient Schemas', '/admin/bank-accounts/schema');
    }

    public function checkBankAccountLifecycleFlow(FunctionalTester $I): void
    {
        $randomString = bin2hex(random_bytes(8));
        $superadminId = $I->getUserIdByUsername($I::USER_SUPER_ADMIN);
        $regularUserId = $I->getUserIdByUsername($I::USER_REG1);
        $I->amOnPage('/admin/bank-accounts');
        $I->click('Register New Bank Account');
        $I->seeCurrentUrlEquals('/admin/bank-accounts/create');

        // See default options
        $I->seeOptionIsSelected('#bank_account_accountType', 'GB');
        $I->seeOptionIsSelected('#bank_account_accountHolderType', 'Personal');

        // Fill form
        $I->selectOption('#bank_account_accountHolderType', 'Business');
        $I->selectOption('#bank_account_user', (string) $regularUserId);
        // $I->fillField('#bank_account_accountNumber', 'invalid');
        // $I->fillField('#bank_account_bankIdentifierCode', '222');
        $gbAccountNumber = '55779911';
        $gbSortCode = '200000';
        $I->fillField('#bank_account_accountNumber', $gbAccountNumber);
        $I->fillField('#bank_account_bankIdentifierCode', $gbSortCode);
        $I->fillField('#bank_account_description', $randomString . 'TestBAR');
        // Should be able to fill custom account holder form fields during create
        $I->seeElement('input#bank_account_accountHolderName');
        $I->seeElement('input#bank_account_accountHolderAddress_address1');
        $I->click('Create Bank Account Registration');
        // No longer any form validation for account number and bic
        // $I->see('not a valid International Bank Account Number', 'label[for=bank_account_accountNumber] ~ .invalid-feedback');
        // $I->see('not 8 digits', 'label[for=bank_account_accountNumber] ~ .invalid-feedback');
        // $I->see('not a valid Business Identifier Code', 'label[for=bank_account_bankIdentifierCode] ~ .invalid-feedback');
        // $I->see('not 6 digits', 'label[for=bank_account_bankIdentifierCode] ~ .invalid-feedback');

        // $gbAccountNumber = '44558822';
        // $gbSortCode = '004412';
        // $I->fillField('#bank_account_accountNumber', $gbAccountNumber);
        // $I->fillField('#bank_account_bankIdentifierCode', $gbSortCode);
        // $I->click('Create Bank Account Registration');
        $newBARId = $I->grabTextFrom('[data-field-name="registration-id"]');
        $I->seeCurrentUrlEquals("/admin/bank-accounts/{$newBARId}");

        // Check if duplicate prevention works
        $I->amOnPage('/admin/bank-accounts/create');
        $I->selectOption('#bank_account_user', (string) $regularUserId);
        $I->fillField('#bank_account_accountNumber', $gbAccountNumber);
        $I->fillField('#bank_account_bankIdentifierCode', $gbSortCode);
        $I->click('Create Bank Account Registration');
        $I->seeCurrentUrlEquals('/admin/bank-accounts/create');
        $I->see('similar one already exists');

        $I->amOnPage("/admin/bank-accounts/{$newBARId}");
        $I->see('GB', '[data-field-name="bank-account-country"]');
        $I->see('GB', '[data-field-name="account-type"]');
        $I->see('Business', '[data-field-name="account-holder-type"]');
        $I->see('Pending', '[data-field-name="status"]');
        $I->see('N/A', '[data-field-name="mangopay-recipient-id"]');
        $I->see($I::USER_REG1, '[data-field-name="associated-user"]');
        // This should be the exact fingerprint created with the test GB account details
        $I->see('b67d5d3c508d2e7f90f95ecca686d35d', '[data-field-name="fingerprint"]');
        $I->see('	GBP GB _ 9911', '[data-field-name="display-name"]');
        $I->seeLink('View Associated User', "/admin/users/{$regularUserId}/dashboard");

        // No address object should be created
        $addressId = $I->grabFromDatabase('bank_account', 'accountHolderAddress_id', [
            'id' => $newBARId,
        ]);
        $I->assertNull($addressId);

        // Non-superadmin will be considered a normal user bank account registration
        $I->amOnPage('/admin/bank-accounts');
        $I->seeLink('Manage', "/admin/bank-accounts/{$newBARId}");
        $I->see('User', '[data-object-id="' . $newBARId . '"]');

        $validAccountNumber = 'FR7630004000031234567890143';
        $validBic = 'BNPAFRPP';
        $I->amOnPage("/admin/bank-accounts/{$newBARId}/edit");
        $I->selectOption('#bank_account_user', (string) $superadminId);
        $I->selectOption('#bank_account_accountType', 'IBAN');
        $I->selectOption('#bank_account_country', 'FR');
        $I->selectOption('#bank_account_accountHolderType', 'Personal');
        $I->fillField('#bank_account_accountNumber', $validAccountNumber);
        $I->fillField('#bank_account_bankIdentifierCode', $validBic);

        $I->fillField(
            '#bank_account_accountHolderName',
            $randomString . 'customAccHolder',
        );
        $I->fillField('#bank_account_accountHolderAddress_address1', 'Test addr1');
        $I->fillField('#bank_account_accountHolderAddress_city', 'Test addrcity');
        $I->fillField(
            '#bank_account_accountHolderAddress_postCode',
            'Test addrpostcode',
        );
        $I->selectOption('#bank_account_accountHolderAddress_country', 'GB');

        $I->click('Save Changes');

        $addressId = $I->grabFromDatabase('bank_account', 'accountHolderAddress_id', [
            'id' => $newBARId,
        ]);
        $I->assertNotNull($addressId);
        $I->seeCurrentUrlEquals("/admin/bank-accounts/{$newBARId}");
        $I->see('Successfully updated bank account registration');
        $I->see('FR', '[data-field-name="bank-account-country"]');
        $I->see('IBAN', '[data-field-name="account-type"]');
        $I->see('Personal', '[data-field-name="account-holder-type"]');
        $I->see($validAccountNumber, '[data-field-name="account-number"]');
        $I->see($validBic, '[data-field-name="bank-identifier-code"]');
        $I->see(
            $randomString . 'customAccHolder',
            '[data-field-name="account-holder-name"]',
        );
        $I->see('Test addr1', '[data-field-name="account-holder-address"]');
        // This should be the exact fingerprint created with the test FR IBAN+BIC details
        $I->see('8ed3134fbb22c8ad6b8a462a4c8eace0', '[data-field-name="fingerprint"]');
        $I->see('	GBP FR _ 0143', '[data-field-name="display-name"]');

        $I->amOnPage("/admin/bank-accounts/{$newBARId}/edit");
        $I->click('Remove Custom Account Holder');
        $I->seeCurrentUrlEquals("/admin/bank-accounts/{$newBARId}");
        $addressId = $I->grabFromDatabase('bank_account', 'accountHolderAddress_id', [
            'id' => $newBARId,
        ]);
        $I->assertNull($addressId);
        $I->dontSee(
            $randomString . 'customAccHolder',
            '[data-field-name="account-holder-name"]',
        );
        $I->dontSee('Test addr1', '[account-holder-address]');

        // Superadmin will be considered a system bank account registration
        $I->amOnPage('/admin/bank-accounts');
        $I->seeLink('Manage', "/admin/bank-accounts/{$newBARId}");
        $I->see('System', '[data-object-id="' . $newBARId . '"]');

        // Check state transitions
        $I->amOnPage("/admin/bank-accounts/{$newBARId}");
        $checklist = [
            'bank_account_review_bankStatement',
            'bank_account_review_accountHolderName',
            'bank_account_review_accountHolderAddress',
            'bank_account_review_accountNumber',
            'bank_account_review_bankId',
        ];
        foreach ($checklist as $check) {
            $I->checkOption("#registration-status #{$check}");
        }

        // Prepare for mailcatcher test
        $mailcatcher = $I->getMailcatcherClient();
        $mailcatcher->delete('/messages');

        $I->dontSeeLink('Sync Status with Mangopay');
        $I->uncheckOption('#bank_account_review_notifyUser');
        $I->click('Approve Bank Account');
        $I->seeCurrentUrlEquals("/admin/bank-accounts/{$newBARId}");
        $I->see('Approved', '[data-field-name="status"]');
        $I->dontSeeLink('Sync Status with Mangopay');

        // Check NO (approval) notification received
        $messages = json_decode(
            $I->getMailcatcherClient()->get('/messages')->getBody(),
        );
        $I->assertCount(0, $messages);

        // Check enabling will create and link a Mangopay recipient and initiate SCA verification
        // Note that we can't actually finish SCA verification due to lack of JS in PHPBrowser
        $I->click('Enable Bank Account');
        // If we're on the SCA verification page hosted by Mangopay, we should see 2 query parameters
        $I->seeInCurrentUrl('?token');
        $I->seeInCurrentUrl('&returnUrl');

        // We'll set the status to active to continue tests (allow us to disable)
        $I->updateInDatabase(
            'bank_account',
            [
                'status' => BankAccountStatus::Active->value,
            ],
            ['id' => $newBARId],
        );

        // And return to the bank accounts page
        $I->amOnPage("/admin/bank-accounts/{$newBARId}");
        $I->see('Active', '[data-field-name="status"]');
        $I->dontSee('N/A', '[data-field-name="mangopay-recipient-id"]');
        $I->click('Sync Status with Mangopay');
        $I->see('Sync with mangopay completed');

        $recipientId = $I->grabTextFrom('[data-field-name="mangopay-recipient-id"]');
        $I->seeLink(
            'View Mangopay Recipient',
            "/admin/bank-accounts/mangopay/recipients/{$recipientId}",
        );

        $I->click('Disable Bank Account');
        $I->seeCurrentUrlEquals("/admin/bank-accounts/{$newBARId}/disable");
        $I->dontSeeCheckboxIsChecked('#action_confirmation_notifyUser');
        $I->click('Disable Bank Account');
        $I->seeCurrentUrlEquals("/admin/bank-accounts/{$newBARId}");
        $I->see('Closed', '[data-field-name="status"]');
        // Note that the provider ID is kept even after disabling
        $I->see($recipientId, '[data-field-name="mangopay-recipient-id"]');
        $I->seeLink('Sync Status with Mangopay');
        // Account details should be cleared
        $I->see('N/A', '[data-field-name="account-number"]');
        $I->see('N/A', '[data-field-name="bank-identifier-code"]');

        // Prepare for mailcatcher test
        $mailcatcher = $I->getMailcatcherClient();
        $mailcatcher->delete('/messages');

        // We'll repeat the disable but this time with a notification
        $I->updateInDatabase(
            'bank_account',
            [
                'status' => BankAccountStatus::Active->value,
            ],
            ['id' => $newBARId],
        );
        $I->amOnPage("/admin/bank-accounts/{$newBARId}/disable");
        $I->checkOption('#action_confirmation_notifyUser');
        $I->click('Disable Bank Account');

        // Check notification received
        $messages = json_decode(
            $I->getMailcatcherClient()->get('/messages')->getBody(),
        );
        $I->assertCount(1, $messages);
        $I->assertEquals(
            'Your bank account registration has been closed',
            $messages[0]->subject,
        );
        $I->assertEquals('<' . $I::USER_SUPER_ADMIN . '>', $messages[0]->recipients[0]);

        // Reopen account for next round of testing
        $I->click('Reopen Bank Account');
        $I->seeCurrentUrlEquals("/admin/bank-accounts/{$newBARId}");
        $I->see('Pending', '[data-field-name="status"]');

        // Prepare for mailcatcher test
        $mailcatcher = $I->getMailcatcherClient();
        $mailcatcher->delete('/messages');

        // Need to repopulate the bank account details after reopening a closed registration
        $I->click('Validate with Mangopay');
        $I->see('validation failed');
        $I->amOnPage("/admin/bank-accounts/{$newBARId}/edit");
        $I->fillField('#bank_account_accountNumber', $validAccountNumber);
        $I->fillField('#bank_account_bankIdentifierCode', $validBic);
        $I->click('Save Changes');
        $I->click('Validate with Mangopay');
        $I->see('validation passed');
        $I->click('Approve Bank Account');
        $I->see('Approved', '[data-field-name="status"]');

        // Check notification received
        $messages = json_decode(
            $I->getMailcatcherClient()->get('/messages')->getBody(),
        );
        $I->assertCount(1, $messages);
        $I->assertEquals(
            'Your bank account registration has been approved',
            $messages[0]->subject,
        );
        $I->assertEquals('<' . $I::USER_SUPER_ADMIN . '>', $messages[0]->recipients[0]);

        $I->click('Unapprove Bank Account');
        $I->seeCurrentUrlEquals("/admin/bank-accounts/{$newBARId}");
        $I->see('Pending', '[data-field-name="status"]');

        // See if changing account details will drop validated back to pending
        $I->click('Validate with Mangopay');
        $I->see('validation passed');
        $I->see('Validated', '[data-field-name="status"]');
        $I->amOnPage("/admin/bank-accounts/{$newBARId}/edit");
        $I->fillField('#bank_account_accountNumber', $validAccountNumber);
        $I->fillField('#bank_account_bankIdentifierCode', $gbSortCode);
        $I->click('Save Changes');
        $I->see('Pending', '[data-field-name="status"]');

        // Try requesting actions
        $mailcatcher->delete('/messages');
        $I->click('Request User Actions');
        $I->seeCurrentUrlEquals("/admin/bank-accounts/{$newBARId}/request-action");
        $I->checkOption('#action_request_actionRequests_proof_of_address');
        $I->click('Save Changes');
        $I->seeCurrentUrlEquals("/admin/bank-accounts/{$newBARId}");
        $I->see(ActionRequest::ProofAddress->value, '#additional-metadata');
        // Should be no notification if not enabled
        $messages = json_decode(
            $I->getMailcatcherClient()->get('/messages')->getBody(),
        );
        $I->assertCount(0, $messages);
        // Can send a notification by checking the option
        // Existing actions should be prefilled
        $I->amOnPage("/admin/bank-accounts/{$newBARId}/request-action");
        $I->seeCheckboxIsChecked('#action_request_actionRequests_proof_of_address');
        $I->checkOption('#action_request_notifyUser');
        $I->click('Save Changes');
        $messages = json_decode(
            $I->getMailcatcherClient()->get('/messages')->getBody(),
        );
        $I->assertCount(1, $messages);
        $I->assertEquals(
            'Your bank account registration has been updated',
            $messages[0]->subject,
        );
        // Clearing actions won't send a notification
        $mailcatcher->delete('/messages');
        $I->amOnPage("/admin/bank-accounts/{$newBARId}/request-action");
        $I->uncheckOption('#action_request_actionRequests_proof_of_address');
        $I->checkOption('#action_request_notifyUser');
        $I->click('Save Changes');
        $messages = json_decode(
            $I->getMailcatcherClient()->get('/messages')->getBody(),
        );
        $I->assertCount(0, $messages);
        $I->dontSee('actionRequests', '#additional-metadata');

        // Prepare for mailcatcher test
        $mailcatcher = $I->getMailcatcherClient();
        $mailcatcher->delete('/messages');

        $I->click('Reject Bank Account');
        $I->seeCurrentUrlEquals("/admin/bank-accounts/{$newBARId}/reject");
        $I->checkOption('#action_confirmation_notifyUser');
        $I->click('Reject Bank Account');
        $I->seeCurrentUrlEquals("/admin/bank-accounts/{$newBARId}");
        $I->see('Rejected', '[data-field-name="status"]');
        // Account details should be cleared
        $I->see('N/A', '[data-field-name="account-number"]');
        $I->see('N/A', '[data-field-name="bank-identifier-code"]');

        // Check notification received
        $messages = json_decode(
            $I->getMailcatcherClient()->get('/messages')->getBody(),
        );
        $I->assertCount(1, $messages);
        $I->assertEquals(
            'Your bank account registration has been rejected',
            $messages[0]->subject,
        );
        $I->assertEquals('<' . $I::USER_SUPER_ADMIN . '>', $messages[0]->recipients[0]);
    }

    public function checkBankAccountSync(FunctionalTester $I): void
    {
        $sampleUserId = $I->getUserIdByUsername($I::USER_LOW_BALANCE);
        $sampleBankAccountId = 'rec_01KJST1T9CHNSZK2Q86WFSS2TZ';
        $I->amOnPage(
            "/admin/bank-accounts/{$sampleUserId}/sync-new/{$sampleBankAccountId}",
        );
        $newBARId = $I->grabTextFrom('[data-field-name="registration-id"]');
        $I->seeCurrentUrlEquals("/admin/bank-accounts/{$newBARId}");
        $I->see($sampleBankAccountId, '[data-field-name="mangopay-recipient-id"]');
        $I->see('Active', '[data-field-name="status"]');
        $I->see('GBP GB _ 9911', '[data-field-name="display-name"]');
        $I->see('b67d5d3c508d2e7f90f95ecca686d35d', '[data-field-name="fingerprint"]');
        $I->see('N/A', '[data-field-name="account-number"]');
        $I->see('N/A', '[data-field-name="bank-identifier-code"]');
        // Shouldn't be able to sync it again
        $I->amOnPage(
            "/admin/bank-accounts/{$sampleUserId}/sync-new/{$sampleBankAccountId}",
        );
        $I->see('No need to sync');
        $I->seeCurrentUrlEquals("/admin/bank-accounts/{$newBARId}");

        // Clean up by removing the the registration
        $I->updateInDatabase(
            'bank_account',
            [
                'status' => BankAccountStatus::Closed->value,
                'providerId' => null,
            ],
            ['id' => $newBARId],
        );

        // Cannot try multi-sync until Mangopay allows filtering of listRecipients
        // As repeat tests slowly push active ones out of initial list
        // // Try multisync
        // // This 2nd bank account id also belongs to the low balance user
        // $secondaryBankAccountId = 'rec_01KJSSRKZYAV0DCJW7BAZA75T1';
        // $I->amOnPage("/admin/users/{$sampleUserId}/dashboard/bank-accounts");
        // $I->seeLink(
        //     'Batch Sync Registrations',
        //     "/admin/bank-accounts/{$sampleUserId}/sync-multi",
        // );
        // $I->click('Batch Sync Registrations');
        // $I->seeCurrentUrlEquals("/admin/users/{$sampleUserId}/dashboard/bank-accounts");
        // $I->see($sampleBankAccountId, '#bank-accounts-list');
        // $I->see($secondaryBankAccountId, '#bank-accounts-list');
        // // Check both sync outcome
        // $newBARId1 = $I->grabFromDatabase('bank_account', 'id', [
        //     'user_id' => $sampleUserId,
        //     'providerId' => $sampleBankAccountId,
        // ]);
        // $I->amOnPage("/admin/bank-accounts/{$newBARId1}");
        // $I->see('Active', '[data-field-name="status"]');
        // $I->see('GBP GB _ 9911', '[data-field-name="display-name"]');
        // $I->see('b67d5d3c508d2e7f90f95ecca686d35d', '[data-field-name="fingerprint"]');
        // $I->see('N/A', '[data-field-name="account-number"]');
        // $I->see('N/A', '[data-field-name="bank-identifier-code"]');
        // $newBARId2 = $I->grabFromDatabase('bank_account', 'id', [
        //     'user_id' => $sampleUserId,
        //     'providerId' => $secondaryBankAccountId,
        // ]);
        // $I->amOnPage("/admin/bank-accounts/{$newBARId2}");
        // $I->see('Active', '[data-field-name="status"]');
        // $I->see('GBP FR _ 0143', '[data-field-name="display-name"]');
        // $I->see('f0a3429ee641a7202ec2ff8b3628a8d9', '[data-field-name="fingerprint"]');
        // $I->see('N/A', '[data-field-name="account-number"]');
        // $I->see('N/A', '[data-field-name="bank-identifier-code"]');
        // // Clean up by removing the the registration
        // $I->updateInDatabase(
        //     'bank_account',
        //     [
        //         'status' => BankAccountStatus::Closed->value,
        //         'providerId' => null,
        //     ],
        //     ['id' => $newBARId1],
        // );
        // $I->updateInDatabase(
        //     'bank_account',
        //     [
        //         'status' => BankAccountStatus::Closed->value,
        //         'providerId' => null,
        //     ],
        //     ['id' => $newBARId2],
        // );
    }
}
