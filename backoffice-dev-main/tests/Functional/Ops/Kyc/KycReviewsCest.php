<?php

namespace App\Tests\Functional\Ops\Kyc;

use App\Entity\Enum\KycReviewType;
use App\Tests\Support\FunctionalTester;

class KycReviewsCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function checkKycListAndView(FunctionalTester $I)
    {
        $I->amOnPage('/admin/kyc/reviews');
        $tableHeaders = [
            'Id',
            'Subject',
            'Review Type',
            'Reviewed By',
            'Decision',
            'Status',
            'Completed At',
            'Actions',
        ];
        foreach ($tableHeaders as $th) {
            $I->see($th, '#kyc-reviews-list thead th');
        }
        $reviewId = $I->grabAttributeFrom(
            '#kyc-reviews-list tbody tr:first-child',
            'data-object-id',
        );
        $I->seeLink('View', "/admin/kyc/reviews/{$reviewId}");
        $I->seeLink('Review', "/admin/kyc/reviews/{$reviewId}?mode=review");
        $I->click('View', '#kyc-reviews-list tbody tr:first-child');
        $I->seeCurrentUrlEquals("/admin/kyc/reviews/{$reviewId}");
        $I->see('View KYC Review');

        // Check the auto-redirect link
        $expectedRoutes = [
            KycReviewType::Onboarding->value => '/admin/kyc/onboarding/',
            KycReviewType::Vip->value => '/admin/kyc/vip/',
            KycReviewType::Recurring->value => '/admin/kyc/recurring/',
            KycReviewType::Adhoc->value => '/admin/kyc/reviews/',
        ];
        foreach ($expectedRoutes as $reviewType => $route) {
            $reviewId = $I->grabFromDatabase('kyc_review', 'id', [
                'reviewType' => $reviewType,
            ]);
            $I->amOnPage("/admin/kyc/reviews?id={$reviewId}");
            $I->click('Review', '#kyc-reviews-list tbody tr:first-child');
            $I->seeCurrentUrlEquals("{$route}{$reviewId}");
        }
    }

    public function createAndEdit(FunctionalTester $I): void
    {
        $randomString = bin2hex(random_bytes(8));
        $I->amOnPage('/admin/kyc/reviews');
        $startingCount = $I->grabNumRecords('kyc_review');
        $newCount = $startingCount + 1;
        $I->click('Create KYC Review');
        $I->seeLink('Abandon', '/admin/kyc/reviews');
        $I->seeLink('Back', '/admin/kyc/reviews');
        $I->seeCurrentUrlEquals('/admin/kyc/reviews/create');
        $I->fillField(
            '#kyc_review_form_notes',
            "{$randomString} Automated test KYC review",
        );
        $I->selectOption('#kyc_review_form_status', 'Ready');
        $I->selectOption('#kyc_review_form_subject', '4');
        $I->checkOption('#kyc_review_form_identityReview');
        $I->checkOption('#kyc_review_form_dueDiligenceLevelReview');
        $I->checkOption('#kyc_review_form_transactionsReview');
        $I->click('Create KYC Review');

        // Post create checks
        $I->seeCurrentUrlEquals("/admin/kyc/reviews/{$newCount}");
        $I->seeLink('Edit Review', "/admin/kyc/reviews/{$newCount}/edit");
        $I->seeLink('Back', '/admin/kyc/reviews');

        $I->click('Edit Review');
        // Pre-update checks
        $I->seeInField(
            '#kyc_review_form_notes',
            "{$randomString} Automated test KYC review",
        );
        $I->see('#4', '#kyc_review_form_subject option[selected]');
        $I->seeOptionIsSelected('#kyc_review_form_reviewType', 'Adhoc');
        $I->seeOptionIsSelected('#kyc_review_form_status', 'Ready');
        $I->seeCheckboxIsChecked('#kyc_review_form_identityReview');
        $I->cantSeeCheckboxIsChecked('#kyc_review_form_addressReview');
        $I->cantSeeCheckboxIsChecked('#kyc_review_form_countryReview');
        $I->cantSeeCheckboxIsChecked('#kyc_review_form_kycProviderReview');
        $I->seeCheckboxIsChecked('#kyc_review_form_dueDiligenceLevelReview');
        $I->seeCheckboxIsChecked('#kyc_review_form_transactionsReview');

        // Update the review
        $I->selectOption('#kyc_review_form_reviewType', 'Recurring');
        $I->selectOption('#kyc_review_form_status', 'Open');
        $I->selectOption('#kyc_review_form_subject', '1');
        $I->fillField(
            '#kyc_review_form_notes',
            "{$randomString} Automated test KYC review modified",
        );
        $I->uncheckOption('#kyc_review_form_identityReview');
        $I->checkOption('#kyc_review_form_addressReview');
        $I->checkOption('#kyc_review_form_countryReview');
        $I->checkOption('#kyc_review_form_kycProviderReview');
        $I->uncheckOption('#kyc_review_form_dueDiligenceLevelReview');
        $I->uncheckOption('#kyc_review_form_transactionsReview');
        $I->click('Save Changes');
        $I->seeCurrentUrlEquals("/admin/kyc/reviews/{$newCount}");

        // Post edit checks
        $I->click('Edit Review');
        $I->seeOptionIsSelected('#kyc_review_form_reviewType', 'Recurring');
        $I->seeOptionIsSelected('#kyc_review_form_status', 'Open');
        $I->see('#1', '#kyc_review_form_subject option[selected]');
        $I->seeInField(
            '#kyc_review_form_notes',
            "{$randomString} Automated test KYC review modified",
        );
        $I->cantSeeCheckboxIsChecked('#kyc_review_form_identityReview');
        $I->seeCheckboxIsChecked('#kyc_review_form_addressReview');
        $I->seeCheckboxIsChecked('#kyc_review_form_countryReview');
        $I->seeCheckboxIsChecked('#kyc_review_form_kycProviderReview');
        $I->cantSeeCheckboxIsChecked('#kyc_review_form_dueDiligenceLevelReview');
        $I->cantSeeCheckboxIsChecked('#kyc_review_form_transactionsReview');
    }
}
