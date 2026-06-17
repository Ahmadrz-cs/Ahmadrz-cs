<?php

use AppBundle\Entity\Enum\KycReviewStatus;
use AppBundle\Entity\Enum\KycReviewType;
use AppBundle\Entity\Enum\ScaStatus;

class LinkedBankAccountsRestrictionCest
{
    private ?int $userId = null;
    private ?int $reviewId = null;

    public function _before(AcceptanceTester $I)
    {
        // Set the KycReview fixture for the ben user to pending_user_action
        // This will trigger the frontend behaviour
        $this->userId = $I->grabFromDatabase('users', 'id', ['username' => $I->approved_investor_2]);
        $this->reviewId = $I->grabFromDatabase('kyc_review', 'id', [
            'status' => KycReviewStatus::Open->value,
            'reviewType' => KycReviewType::Recurring->value,
            'identityReview' => 1,
            'subject_id' => $this->userId,
        ]);
        $I->updateInDatabase(
            'kyc_review',
            ['status' => KycReviewStatus::PendingSubjectAction->value],
            ['id' => $this->reviewId]
        );
        $I->updateInDatabase(
            'users',
            ['scaStatus' => ScaStatus::Inactive->value],
            ['id' => $this->userId]
        );
    }

    public function _after(AcceptanceTester $I)
    {
        // Reset status to open for reruns
        $I->updateInDatabase(
            'kyc_review',
            ['status' => KycReviewStatus::Open->value],
            ['id' => $this->reviewId]
        );
        // Reset SCA enrollment status for reruns and other tests
        $I->updateInDatabase(
            'users',
            ['scaStatus' => ScaStatus::Active->value],
            ['id' => $this->userId]
        );
    }

    /**
     * @group profile
     */
    public function checkLinkingRestrictionsWithAccounts(AcceptanceTester $I)
    {
        $I->loginWithCredentials($I->approved_investor_2, $I->admin_user_password, false, skipScaCheck: false);

        // If bank accounts exist, no prompt to add more accounts
        // But also no prompts to do any verification or enrollment either
        // Mainly for people who just want to withdraw and close their account
        $I->amOnPage('/withdraw-funds');
        $I->seeLink("Manage Linked Bank Account", '/my-profile/bank-accounts');
        $I->dontSeeLink("Add new Bank Account", '/my-profile/bank-accounts/new');
        // $I->scrollTo('#sca-enrollment-prompt');
        // $I->scrollTo('#verification-prompt');
        // But existing accounts should still be there
        $I->scrollTo('form[name="bank_account_withdrawal"]');

        $I->amOnPage('/my-profile/bank-accounts');
        $I->dontSeeLink("Add new Bank Account", '/my-profile/bank-accounts/new');
        $I->scrollTo('#sca-enrollment-prompt');
        $I->scrollTo('#verification-prompt');
        // Existing accounts will still be visible (from syncing)
        $I->scrollTo("#linked-bank-accounts-list", 0, -60);
        // At least 1 for jim user
        $I->seeNumberOfElements("#linked-bank-accounts-list tbody tr", [1, 10]);
    }

    /**
     * @group profile
     */
    public function checkLinkingRestrictionsNoAccounts(AcceptanceTester $I)
    {
        $I->loginWithName($I->user_hamlin);

        // No accounts, so will see prompts even in withdraw funds
        $I->amOnPage('/withdraw-funds');
        $I->dontSeeLink("Add new Bank Account", '/my-profile/bank-accounts/new');
        $I->scrollTo('#sca-enrollment-prompt');
        // Form will not be there at all
        $I->dontSeeElementInDOM('form[name="bank_account_withdrawal"]');

        $I->amOnPage('/my-profile/bank-accounts');
        $I->dontSeeLink("Add new Bank Account", '/my-profile/bank-accounts/new');
        $I->scrollTo('#sca-enrollment-prompt');

        $I->amOnPage('/logout');
    }
}
