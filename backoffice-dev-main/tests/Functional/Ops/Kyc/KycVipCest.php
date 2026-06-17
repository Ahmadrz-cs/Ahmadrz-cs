<?php

namespace App\Tests\Functional\Ops\Kyc;

use App\Service\MailerService;
use App\Tests\Support\FunctionalTester;

class KycVipCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function checkKycReview(FunctionalTester $I)
    {
        // User who is already a VIP user
        $userId = $I->grabFromDatabase('users', 'id', ['username' => $I::USER_VIP]);
        $I->amOnPage("/admin/kyc/vip/{$userId}");
        $I->see('This user is already a Top Yielder');

        // Check page elements that always show up
        $kycNav = [
            'Supporting Info',
            'Source of Funds',
        ];
        foreach ($kycNav as $tabButtonText) {
            $I->see($tabButtonText, '#kyc-vip-review-nav button');
        }
        $I->see('Start Review', '#tab-supporting-info');
        $I->see('Complete the Report Card', '#tab-source-of-funds');
        $I->seeElement('[data-field-name="main-address"]');
        $I->seeElement('[data-field-name="company-address"]');
        $I->seeElement('[data-field-name="application-statement-(words-of-own)"]');
        $I->seeElement('#tab-source-of-funds table#user-vip-docs-list');

        $I->see('Checklist', '#kyc-review-report-card');
        $I->see('All steps must be completed', '#kyc-review-report-card');

        // Report card should be empty at the start
        $checklist = [
            'kyc_vip_review_sourceOfFunds',
        ];
        foreach ($checklist as $check) {
            $I->dontSeeCheckboxIsChecked("#kyc-review-report-card #{$check}");
        }
        $I->see('Approve', '#kyc-review-report-card #kyc_vip_review_pass');
        $I->see('Reject', '#kyc-review-report-card #kyc_vip_review_fail');

        // Reject will make existing VIP non-VIP
        foreach ($checklist as $check) {
            $I->checkOption("#kyc-review-report-card #{$check}");
        }
        $I->click('Reject', '#kyc-review-report-card');
        $I->seeCurrentUrlEquals('/admin/kyc/vip');
        $I->assertEquals('0', $I->grabFromDatabase('users', 'isVIP', [
            'id' => $userId,
        ]));

        // That user should reappear in the Top Yielder applications
        // There's no rejection procedure yet besides manually clearing the wordsOfOwn field
        $I->see($I::USER_VIP, "#kyc-user-list tbody tr[data-object-id='{$userId}']");

        // Check KYC review generated
        $I->amOnPage('/admin/kyc/reviews');
        $I->click('View', '#kyc-reviews-list tbody tr:first-child');
        $I->see($I::USER_VIP, '[data-field-name="subject"]');
        $I->see('Completed', '[data-field-name="status"]');
        $I->see('Vip', '[data-field-name="review-type"]');
        $I->see($I::USER_SUPER_ADMIN, '[data-field-name="reviewed-by"]');
        $I->see('Fail', '[data-field-name="decision"]');
        foreach (['Transactions'] as $reviewName) {
            $I->see($reviewName, '[data-field-name="reviews-performed"]');
        }
        foreach ([
            'Identity',
            'Address',
            'Country',
            'Kyc provider',
            'Due diligence level',
            'Kyc survey',
        ] as $reviewName) {
            $I->see($reviewName, '[data-field-name="reviews-skipped"]');
        }

        // Can then re-promote user to VIP status
        $mailcatcher = $I->getMailcatcherClient();
        $mailcatcher->delete('/messages');
        $I->amOnPage('/admin/kyc/vip');
        $I->click('Review', "#kyc-user-list tbody tr[data-object-id='{$userId}']");
        $I->seeCurrentUrlEquals("/admin/kyc/vip/{$userId}");
        foreach ($checklist as $check) {
            $I->checkOption("#kyc-review-report-card #{$check}");
        }
        $I->click('Approve', '#kyc-review-report-card');
        $I->seeCurrentUrlEquals('/admin/kyc/vip');
        $I->assertEquals('1', $I->grabFromDatabase('users', 'isVIP', [
            'id' => $userId,
        ]));

        //Should disappear from applications
        $I->dontSee($I::USER_VIP, '#kyc-user-list tbody tr');

        // But (re)appear in the current/active VIP list
        $I->amOnPage('/admin/kyc/vip');
        $I->click('Current Top Yielders', '#filter-presets');
        $I->see($I::USER_VIP, "#kyc-user-list tbody tr[data-object-id='{$userId}']");

        // User should also receive an email notification
        $emailMetadata = json_decode(
            (string) $mailcatcher->get('/messages/1.json')->getBody(),
            true,
        );
        $subjectLine = $I->grabFromDatabase('mails', 'subject', [
            'slug' => MailerService::TYPE_VIP_CONFIRMATION,
        ]);
        $I->assertEquals($subjectLine, $emailMetadata['subject']);

        // Reverifying as top yielder will not resend a notification
        $mailcatcher->delete('/messages');
        $I->amOnPage("/admin/kyc/vip/{$userId}");
        foreach ($checklist as $check) {
            $I->checkOption("#kyc-review-report-card #{$check}");
        }
        $I->click('Approve', '#kyc-review-report-card');
        $I->assertCount(0, json_decode(
            (string) $mailcatcher->get('/messages')->getBody(),
            true,
        ));

        // Check KYC review generated
        $I->amOnPage('/admin/kyc/reviews');
        $I->click('View', '#kyc-reviews-list tbody tr:first-child');
        $I->see($I::USER_VIP, '[data-field-name="subject"]');
        $I->see('Completed', '[data-field-name="status"]');
        $I->see('Vip', '[data-field-name="review-type"]');
        $I->see($I::USER_SUPER_ADMIN, '[data-field-name="reviewed-by"]');
        $I->see('Pass', '[data-field-name="decision"]');
        foreach (['Transactions'] as $reviewName) {
            $I->see($reviewName, '[data-field-name="reviews-performed"]');
        }
        foreach ([
            'Identity',
            'Address',
            'Country',
            'Kyc provider',
            'Due diligence level',
            'Kyc survey',
        ] as $reviewName) {
            $I->see($reviewName, '[data-field-name="reviews-skipped"]');
        }
    }
}
