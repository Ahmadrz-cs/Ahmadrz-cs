<?php

namespace App\Tests\Functional\Ops\Kyc;

use App\Tests\Support\FunctionalTester;
use DateTime;

class KycRecurringCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function checkRecurringKycList(FunctionalTester $I)
    {
        $randomString = bin2hex(random_bytes(8));
        $startingCount = $I->grabNumRecords('kyc_review');
        $newCount = $startingCount + 1;
        $I->amOnPage('/admin/kyc/recurring');
        $I->seeElement('section#pending-reviews');
        $tableHeaders = [
            'Review Id',
            'User Id',
            'Subject Name',
            'Subject Contact Email',
            'Created At',
            'Last Login',
            'User Type',
            'User KYC Status',
            'Review Status',
            'Actions',
        ];
        foreach ($tableHeaders as $th) {
            $I->see($th, '#pending-reviews-list thead th');
        }
        $I->click('Create KYC Review with Preset');
        $I->fillField(
            '#kyc_review_preset_form_notes',
            "{$randomString} Automated test recurring KYC review",
        );
        $I->click('Create KYC Review');
        $I->seeCurrentUrlEquals("/admin/kyc/recurring/{$newCount}");
        $I->amOnPage('/admin/kyc/recurring');
        $reviewId = $I->grabAttributeFrom(
            '#pending-reviews-list tbody tr:first-child',
            'data-object-id',
        );
        $I->seeLink('Review', "/admin/kyc/recurring/{$reviewId}");
        $I->click('Review', '#pending-reviews-list tbody tr:first-child');
        $I->seeCurrentUrlEquals("/admin/kyc/recurring/{$reviewId}");
    }

    public function checkRecurringReviewSubmission(FunctionalTester $I): void
    {
        $randomString = bin2hex(random_bytes(8));
        $startingCount = $I->grabNumRecords('kyc_review');
        $newCount = $startingCount + 1;
        $currentDatetime = time();
        $regularUserId = $I->getUserIdByUsername($I::USER_REG1);
        $I->amOnPage('/admin/kyc/recurring');
        $I->click('Create KYC Review with Preset');
        $I->seeCurrentUrlEquals('/admin/kyc/recurring/quick-create');
        $I->selectOption('#kyc_review_preset_form_subject', "{$regularUserId}");
        $I->selectOption('#kyc_review_preset_form_preset', 'mangopay_id_doc_renewal');
        $I->checkOption('#kyc_review_preset_form_skipDuplicateCheck');
        $I->fillField(
            '#kyc_review_preset_form_notes',
            "{$randomString} Automated test recurring KYC review",
        );
        $I->click('Create KYC Review');
        $I->seeCurrentUrlEquals("/admin/kyc/recurring/{$newCount}");

        // Trying to create a similar recurring Kyc review will return a warning instead of creating
        $I->amOnPage('/admin/kyc/recurring/quick-create');
        $I->selectOption('#kyc_review_preset_form_subject', "{$regularUserId}");
        $I->selectOption('#kyc_review_preset_form_preset', 'mangopay_id_doc_renewal');
        $I->uncheckOption('#kyc_review_preset_form_skipDuplicateCheck');
        $I->click('Create KYC Review');
        $I->seeCurrentUrlEquals('/admin/kyc/recurring/quick-create');
        $I->see(
            'Failed to create new recurring KYC review. Similar recurring KYC review(s) already exist',
        );

        // Prepare for mailcatcher test
        $mailcatcher = $I->getMailcatcherClient();
        $mailcatcher->delete('/messages');
        $I->amOnPage("/admin/kyc/recurring/{$newCount}");
        $I->seeLink(
            'Edit Review',
            "/admin/kyc/reviews/{$newCount}/edit?redirectRoute=admin_kyc_recurring_review",
        );
        $I->click('Manage Notifications');
        // Note that it should be returning to the recurring reviews page
        $I->seeCurrentUrlEquals(
            "/admin/kyc/reviews/{$newCount}/notifications?redirectRoute=admin_kyc_recurring_review",
        );
        $I->click('Send Notification');
        $I->seeCurrentUrlEquals("/admin/kyc/recurring/{$newCount}");

        $messages = json_decode(
            $I->getMailcatcherClient()->get('/messages')->getBody(),
        );
        $I->assertCount(1, $messages);
        $I->assertEquals('Confirm your personal details', $messages[0]->subject);
        $I->assertEquals('<' . $I::USER_REG1 . '>', $messages[0]->recipients[0]);

        $I->checkOption('#kyc_dynamic_review_identityReview');
        $I->checkOption('#kyc_dynamic_review_kycProviderReview');
        $I->click('Approve');
        $I->assertEquals(true, $I->grabFromDatabase('kyc_review', 'decision', [
            'id' => $newCount,
        ]));
        $I->assertGreaterThanOrEqual(
            $currentDatetime,
            strtotime($I->grabFromDatabase('kyc_review', 'completedAt', [
                'id' => $newCount,
            ])),
        );
        $I->assertNotEmpty($I->grabFromDatabase('kyc_review', 'reviewedBy_id', [
            'id' => $newCount,
        ]));
        $I->assertEquals('completed', $I->grabFromDatabase('kyc_review', 'status', [
            'id' => $newCount,
        ]));

        // If you go back to the recurring review page, you can no longer see the report card form
        // But can still edit the status
        $I->amOnPage("/admin/kyc/recurring/{$newCount}");
        $I->dontSeeElement('form[name="kyc_dynamic_review"]');
        // Reopening the review will make the report checklist appear
        $I->click('Edit Review');
        $I->selectOption('#kyc_review_form_status', 'Ready');
        $I->click('Save Changes');
        $I->seeElement('form[name="kyc_dynamic_review"]');
        // Closing it again will hide it
        $I->click('Edit Review');
        $I->selectOption('#kyc_review_form_status', 'Closed');
        $I->click('Save Changes');
        $I->dontSeeElement('form[name="kyc_dynamic_review"]');
    }
}
