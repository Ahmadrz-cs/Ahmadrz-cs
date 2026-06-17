<?php

namespace App\Tests\Functional\Ops\Kyc;

use App\Tests\Support\FunctionalTester;

class KycDashboardCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function checkDashboards(FunctionalTester $I)
    {
        // Main dashboard
        $I->amOnPage('/admin/kyc');
        $primaryNav = [
            'Dashboard' => '/admin/kyc',
            'Onboarding' => '/admin/kyc/onboarding',
            'Top Yielders' => '/admin/kyc/vip',
            'Recurring' => '/admin/kyc/recurring',
            'Reviews' => '/admin/kyc/reviews',
        ];
        foreach ($primaryNav as $text => $url) {
            $I->seeLink($text, $url);
        }
        $I->seeElement('#kyc-onboarding');
        $I->seeElement('#kyc-vip');
        $I->seeLink('Go to Users List', '/admin/users');
        $I->seeLink('See All Pending Reviews', '/admin/kyc/onboarding');
        $I->seeLink('See All Applications', '/admin/kyc/vip');
        $I->seeLink('See All KYC Reviews', '/admin/kyc/reviews');
        $I->seeLink('See All KYC Reports', '/admin/kyc-reports');
        $I->seeLink('See All Contego Logs', '/admin/contegoLog');
        $I->seeLink(
            'See Refused Mangopay KYC Docs',
            '/admin/kyc/mangopay/documents?'
                . http_build_query([
                    'filters' => ['Status' => 'REFUSED'],
                ]),
        );

        // Check main dashboard - onboarding section
        $I->seeElement('#kyc-onboarding [data-field-name="pending-manual-review"]');
        $I->seeElement(
            '#kyc-onboarding [data-field-name="registered-and-missing-kyc-profile"]',
        );
        $I->seeElement('#kyc-onboarding table#kyc-onboarding-list');
        $pendingCount = $I->grabTextFrom('[data-field-name="pending-manual-review"]');
        $I->seeNumberOfElements('#kyc-onboarding-list tbody tr', match (true) {
            $pendingCount > 1 => 2,
            $pendingCount == 1 => 1,
            default => 0,
        });
        $userId = $I->grabAttributeFrom(
            '#kyc-onboarding-list tbody tr:first-child',
            'data-object-id',
        );
        $I->seeLink('Review', "/admin/kyc/onboarding/{$userId}");

        // Check main dashboard - top yielder section
        $I->seeElement('#kyc-vip [data-field-name="top-yielder-applications"]');
        $I->seeElement('#kyc-vip table#kyc-vip-list');
        $pendingCount = $I->grabTextFrom(
            '[data-field-name="top-yielder-applications"]',
        );
        $I->seeNumberOfElements('#kyc-vip-list tbody tr', match (true) {
            $pendingCount > 1 => 2,
            $pendingCount == 1 => 1,
            default => 0,
        });
        $userId = $I->grabAttributeFrom(
            '#kyc-vip-list tbody tr:first-child',
            'data-object-id',
        );
        $I->seeLink('Review', "/admin/kyc/vip/{$userId}");

        // Check main dashboard - kyc reviews section
        $I->seeElement('#recent-kyc-reviews table#kyc-reviews-list');
        $recordCount = $I->grabNumRecords('kyc_review');
        $I->seeNumberOfElements('#kyc-reviews-list tbody tr', match (true) {
            $recordCount > 1 => 2,
            $recordCount == 1 => 1,
            default => 0,
        });
        $reviewId = $I->grabAttributeFrom(
            '#kyc-reviews-list tbody tr:first-child',
            'data-object-id',
        );
        $I->seeLink('View', "/admin/kyc/reviews/{$reviewId}");

        // Check main dashboard - kyc reports section
        $I->seeElement('#recent-kyc-reports table#kyc-reports-list');
        $recordCount = $I->grabNumRecords('kyc_report');
        $I->seeNumberOfElements('#kyc-reports-list tbody tr', match (true) {
            $recordCount > 1 => 2,
            $recordCount == 1 => 1,
            default => 0,
        });
        $reportId = $I->grabAttributeFrom(
            '#kyc-reports-list tbody tr:first-child',
            'data-object-id',
        );
        $I->seeLink('View', "/admin/kyc-reports/{$reportId}");

        // Onboarding dashboard
        $I->amOnPage('/admin/kyc/onboarding');
        $tableHeaders = [
            'Id',
            'Name',
            'Contact Email',
            'Join Date',
            'Last Login',
            'User Type',
            'KYC Status',
            'Actions',
        ];
        foreach ($tableHeaders as $th) {
            $I->see($th, '#kyc-user-list thead th');
        }

        $I->see('Review', '#kyc-user-list tbody tr');
        $userId = $I->grabAttributeFrom(
            '#kyc-user-list tbody tr:first-child',
            'data-object-id',
        );
        $I->seeLink('Review', "/admin/kyc/onboarding/{$userId}");

        // Top Yielders dashboard
        $I->amOnPage('/admin/kyc/vip');
        $tableHeaders = [
            'Id',
            'Name',
            'Contact Email',
            'Join Date',
            'Last Login',
            'User Type',
            'KYC Status',
            'Actions',
        ];
        foreach ($tableHeaders as $th) {
            $I->see($th, '#kyc-user-list thead th');
        }

        $I->see('Review', '#kyc-user-list tbody tr');
        $userId = $I->grabAttributeFrom(
            '#kyc-user-list tbody tr:first-child',
            'data-object-id',
        );
        $I->seeLink('Review', "/admin/kyc/vip/{$userId}");
    }
}
