<?php

use AppBundle\Entity\Enum\KycReviewStatus;
use AppBundle\Entity\Enum\KycReviewType;

class KycVerificationCest
{
    private ?int $userId = null;
    private ?int $reviewId = null;

    public function _before(AcceptanceTester $I)
    {
        // Set the KycReview fixture for the ben user to pending_user_action
        // This will trigger the frontend behaviour
        $this->userId = $I->grabFromDatabase('users', 'id', ['username' => $I->reg_user_name]);
        $this->reviewId = $I->grabFromDatabase('kyc_review', 'id', [
            'status' => KycReviewStatus::Open->value,
            'reviewType' => KycReviewType::Recurring->value,
            'identityReview' => 1,
            'subject_id' => $this->userId,
        ]);
        $I->updateInDatabase(
            'kyc_review',
            ['status' => KycReviewStatus::PendingSubjectAction->value],
            [
                'id' => $this->reviewId,
            ],
        );
    }

    public function _after(AcceptanceTester $I)
    {
        // Reset status to open for reruns
        $I->updateInDatabase(
            'kyc_review',
            ['status' => KycReviewStatus::Open->value],
            [
                'id' => $this->reviewId,
            ],
        );
    }

    public function checkVerificationsFlow(AcceptanceTester $I)
    {
        $I->loginWithCredentials($I->reg_user_name, $I->admin_user_password, false);
        // Immediate redirect on login
        $I->seeCurrentUrlEquals('/verifications');
        $I->seeLink('Verify Later', '/');

        // First stage redirect is categorisation
        $I->click('Start Verification');
        $I->seeCurrentUrlEquals('/verifications/identity');

        // Check you can also access from the profile
        $I->amOnPage('/my-profile/dashboard');
        $I->seeElement('#verification-prompt');
        $I->seeLink('Start Verification', '/verifications/identity');
        $I->click('Start Verification');

        $I->scrollTo('#document-upload');
        $I->attachFile("input#identity_verification_identityDocument", "specimen_passport.jpg");
        $I->seeLink('Verify Later', '/');
        $I->click('Submit Document');

        $I->wait(1);
        $I->seeCurrentUrlEquals('/verifications/complete');
        $I->seeLink('Return to Profile', '/my-profile/dashboard');

        $newStatus = $I->grabFromDatabase('kyc_review', 'status', ['id' => $this->reviewId]);
        $I->assertEquals(KycReviewStatus::Ready->value, $newStatus);

        // If you attempt to go to verification again, you'll be redirected to the homepage
        $I->amOnPage('/verifications');
        $I->wait(1);
        $I->seeCurrentUrlEquals('/');
        $I->amOnPage('/verifications/identity');
        $I->wait(1);
        $I->seeCurrentUrlEquals('/');
    }
}
