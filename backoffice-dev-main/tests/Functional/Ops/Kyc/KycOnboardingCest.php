<?php

namespace App\Tests\Functional\Ops\Kyc;

use App\Service\MailerService;
use App\Tests\Support\FunctionalTester;

class KycOnboardingCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function checkOnboardingReviewPageElements(FunctionalTester $I)
    {
        // User who has already been KYC verified
        $userId = $I->grabFromDatabase('users', 'id', ['username' => $I::USER_REG1]);
        $I->amOnPage("/admin/kyc/onboarding/{$userId}");
        $I->see('User has already been verified');

        // Check page elements that always show up
        $kycNav = [
            'Supporting Info',
            'Country',
            'Contego-Northrow',
            'Mangopay',
        ];
        foreach ($kycNav as $tabButtonText) {
            $I->see($tabButtonText, '#kyc-onboarding-review-nav button');
        }
        $I->see('Start Review', '#tab-supporting-info');
        $I->see('Fill in Report Card', '#tab-country');
        $I->see('Fill in Report Card', '#tab-contego');
        $I->see('Complete the Report Card', '#tab-mangopay');

        $I->see('Contego-Northrow has verified this user.', '#tab-contego');
        $I->see('Mangopay has verified this user', '#tab-mangopay');

        $I->see('Checklist', '#kyc-review-report-card');
        $I->see('All steps must be completed', '#kyc-review-report-card');
        $I->see('Due Diligence Level', '#kyc-review-report-card');

        // Report card should be empty at the start
        $checklist = [
            'kyc_onboarding_review_supportedCountry',
            'kyc_onboarding_review_contegoKyc',
            'kyc_onboarding_review_mangopayKyc',
        ];
        foreach ($checklist as $check) {
            $I->dontSeeCheckboxIsChecked("#kyc-review-report-card #{$check}");
        }
        $I->dontSeeOptionIsSelected(
            '#kyc-review-report-card [name="kyc_onboarding_review[dueDiligenceLevel]"]',
            1,
        );
        $I->dontSeeOptionIsSelected(
            '#kyc-review-report-card [name="kyc_onboarding_review[dueDiligenceLevel]"]',
            2,
        );
        $I->see('Approve', '#kyc-review-report-card #kyc_onboarding_review_pass');
        $I->see('Reject', '#kyc-review-report-card #kyc_onboarding_review_fail');

        // Check warnings and advice boxes
        $userId = $I->grabFromDatabase('users', 'id', [
            'username' => $I::USER_REG_KYC_AMBER,
        ]);
        $I->amOnPage("/admin/kyc/onboarding/{$userId}");
        $I->see('User Not Ready for Review');
        $I->see(
            'User has no documents and is not yet ready for a new KYC review',
            '#tab-supporting-info',
        );
        $I->see(
            'This user has a RAG score of AMBER. You should check the result on Contego-Northrow to see the reason why',
            '#step-contego',
        );

        $userId = $I->grabFromDatabase('users', 'id', [
            'username' => $I::USER_REG_KYC_RED,
        ]);
        $I->amOnPage("/admin/kyc/onboarding/{$userId}");
        $I->see(
            'This user has a RAG score of RED. It is likely that this user should be rejected',
            '#step-contego',
        );
    }

    public function checkOnboardingReviewSubmission(FunctionalTester $I)
    {
        // User who has already been KYC verified
        $userId = $I->grabFromDatabase('users', 'id', [
            'username' => $I::USER_REG_KYC_RED,
        ]);
        $kycProfileId = $I->grabFromDatabase('users', 'kycProfile_id', [
            'id' => $userId,
        ]);
        $I->amOnPage("/admin/kyc/onboarding/{$userId}");

        $checklist = [
            'kyc_onboarding_review_supportedCountry',
            'kyc_onboarding_review_contegoKyc',
            'kyc_onboarding_review_mangopayKyc',
        ];
        foreach ($checklist as $check) {
            $I->checkOption("#kyc-review-report-card #{$check}");
        }
        $I->selectOption(
            '#kyc-review-report-card [name="kyc_onboarding_review[dueDiligenceLevel]"]',
            '2',
        );
        $notes = 'Test notes' . bin2hex(random_bytes(8));
        $I->fillField('#kyc_onboarding_review_notes', $notes);
        $I->click('Reject', '#kyc-review-report-card');

        $I->seeCurrentUrlEquals('/admin/kyc/onboarding');
        $I->assertEquals('0', $I->grabFromDatabase('kyc_profile', 'verified', [
            'id' => $kycProfileId,
        ]));
        $I->assertEquals('2', $I->grabFromDatabase('kyc_profile', 'dueDiligenceLevel', [
            'id' => $kycProfileId,
        ]));
        $I->assertNotEmpty($I->grabFromDatabase('kyc_profile', 'lastReviewedAt', [
            'id' => $kycProfileId,
        ]));
        $I->assertNotEmpty($I->grabFromDatabase('kyc_profile', 'verifiedBy_id', [
            'id' => $kycProfileId,
        ]));
        // Failed KYC users will appear in the "failed manual review" list
        $I->amOnPage('/admin/kyc/onboarding');
        $I->click('Failed KYC Review', '#filter-presets');
        $I->see($I::USER_REG_KYC_RED, '#kyc-user-list');
        $I->seeLink('Review', "/admin/kyc/onboarding/{$userId}");

        // Check KYC review generated
        $I->amOnPage('/admin/kyc/reviews');
        $I->click('View', '#kyc-reviews-list tbody tr:first-child');
        $I->see($I::USER_REG_KYC_RED, '[data-field-name="subject"]');
        $I->see('Completed', '[data-field-name="status"]');
        $I->see('Onboarding', '[data-field-name="review-type"]');
        $I->see($I::USER_SUPER_ADMIN, '[data-field-name="reviewed-by"]');
        $I->see($notes, '[data-field-name="notes"]');
        $I->see('Fail', '[data-field-name="decision"]');
        foreach ([
            'Identity',
            'Address',
            'Country',
            'Kyc provider',
            'Due diligence level',
        ] as $reviewName) {
            $I->see($reviewName, '[data-field-name="reviews-performed"]');
        }
        foreach (['Kyc survey', 'Transactions'] as $reviewName) {
            $I->see($reviewName, '[data-field-name="reviews-skipped"]');
        }

        // Toggle set user registration complete to false
        $I->updateInDatabase(
            'users_statuses',
            ['isRegCompleted' => 0],
            ['id' => $userId],
        );
        $I->updateInDatabase(
            'users_statuses',
            ['regCompletedOn' => null],
            ['id' => $userId],
        );
        $mailcatcher = $I->getMailcatcherClient();
        $mailcatcher->delete('/messages');

        $I->amOnPage("/admin/kyc/onboarding/{$userId}");

        // Approve
        $checklist = [
            'kyc_onboarding_review_supportedCountry',
            'kyc_onboarding_review_contegoKyc',
            'kyc_onboarding_review_mangopayKyc',
        ];
        foreach ($checklist as $check) {
            $I->checkOption("#kyc-review-report-card #{$check}");
        }
        $I->selectOption(
            '#kyc-review-report-card [name="kyc_onboarding_review[dueDiligenceLevel]"]',
            '1',
        );
        $I->click('Approve', '#kyc-review-report-card');
        $I->seeCurrentUrlEquals('/admin/kyc/onboarding');
        $I->assertEquals('1', $I->grabFromDatabase('kyc_profile', 'verified', [
            'id' => $kycProfileId,
        ]));
        $I->assertEquals('1', $I->grabFromDatabase('kyc_profile', 'dueDiligenceLevel', [
            'id' => $kycProfileId,
        ]));
        $I->assertEquals('1', $I->grabFromDatabase('users_statuses', 'isApproved', [
            'id' => $userId,
        ]));
        $I->assertEquals('1', $I->grabFromDatabase('users_statuses', 'isRegCompleted', [
            'id' => $userId,
        ]));
        $I->assertNotEmpty($I->grabFromDatabase('users_statuses', 'approvedOn', [
            'id' => $userId,
        ]));
        $I->assertNotEmpty($I->grabFromDatabase('users_statuses', 'regCompletedOn', [
            'id' => $userId,
        ]));

        $emailMetadata = json_decode(
            (string) $mailcatcher->get('/messages/1.json')->getBody(),
            true,
        );
        $subjectLine = $I->grabFromDatabase('mails', 'subject', [
            'slug' => MailerService::TYPE_OB_COMPLETE,
        ]);
        $I->assertEquals($subjectLine, $emailMetadata['subject']);

        // Don't get email if only being approved (or nothing happens to either approved or reg complete)
        $I->updateInDatabase('users_statuses', ['isApproved' => 0], ['id' => $userId]);
        $I->updateInDatabase(
            'users_statuses',
            ['approvedOn' => null],
            ['id' => $userId],
        );
        $mailcatcher->delete('/messages');

        $I->amOnPage("/admin/kyc/onboarding/{$userId}");
        foreach ($checklist as $check) {
            $I->checkOption("#kyc-review-report-card #{$check}");
        }
        $I->selectOption(
            '#kyc-review-report-card [name="kyc_onboarding_review[dueDiligenceLevel]"]',
            '1',
        );
        $I->click('Approve', '#kyc-review-report-card');
        $I->seeCurrentUrlEquals('/admin/kyc/onboarding');

        $I->assertCount(0, json_decode(
            (string) $mailcatcher->get('/messages')->getBody(),
            true,
        ));

        // Check KYC review generated
        $I->amOnPage('/admin/kyc/reviews');
        $I->click('View', '#kyc-reviews-list tbody tr:first-child');
        $I->see($I::USER_REG_KYC_RED, '[data-field-name="subject"]');
        $I->see('Completed', '[data-field-name="status"]');
        $I->see('Onboarding', '[data-field-name="review-type"]');
        $I->see($I::USER_SUPER_ADMIN, '[data-field-name="reviewed-by"]');
        $I->see('Pass', '[data-field-name="decision"]');
        foreach ([
            'Identity',
            'Address',
            'Country',
            'Kyc provider',
            'Due diligence level',
        ] as $reviewName) {
            $I->see($reviewName, '[data-field-name="reviews-performed"]');
        }
        foreach (['Kyc survey', 'Transactions'] as $reviewName) {
            $I->see($reviewName, '[data-field-name="reviews-skipped"]');
        }
    }
}
