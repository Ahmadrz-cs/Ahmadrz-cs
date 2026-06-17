<?php

namespace App\Tests\Functional\Ops\Reports;

use App\Entity\Enum\ReportStatus;
use App\Tests\Support\FunctionalTester;

class ReportBuilderCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function checkReportsBrowsing(FunctionalTester $I): void
    {
        // Reports in CMS
        $I->amOnPage('/admin/reports');
        $links = [
            'Create Mangopay Transaction Report' => '/admin/reports/create/mangopay',
        ];

        foreach ($links as $linkText => $linkPath) {
            $I->seeLink($linkText, $linkPath);
        }

        $reportsListHeaders = [
            'Id',
            'Description',
            'Origin',
            'Resource Id',
            'Reference Id',
            'Created At',
            'Status',
            'Actions',
        ];
        foreach ($reportsListHeaders as $tableHeader) {
            $I->see($tableHeader, '#reports-list thead th');
        }

        $reportId = $I->grabAttributeFrom(
            '#reports-list tbody tr:first-child',
            'data-object-id',
        );
        $I->seeLink('More Info', "/admin/reports/{$reportId}");

        $reportId = $I->grabFromDatabase('report', 'id', [
            'status' => ReportStatus::Draft->value,
        ]);
        $I->amOnPage("/admin/reports/{$reportId}");
        $I->see('Check for Updates', '#report-info');
        $I->see('Delete Report', '#report-info form');

        // Report Sets in CMS
        $I->amOnPage('/admin/reports/sets');
        $reportsListHeaders = [
            'Id',
            'Type',
            'Asset',
            'Description',
            'Period Start',
            'Period End',
            'Reports',
            'Created At',
            'Status',
            'Actions',
        ];
        foreach ($reportsListHeaders as $tableHeader) {
            $I->see($tableHeader, '#report-set-list thead th');
        }
        $I->amOnPage('/admin/reports/sets/1');
        $I->seeElement('#report-set-info');
        $I->seeElement('#reports-info #reports-list');

        // Transaction Report Builder Tool
        $I->amOnPage('/admin/reports/mangopay/sets');
        $links = [
            'Configure New Report Builder' => '/admin/reports/mangopay/transaction-report/create',
            'View All Mangopay Reports' => '/admin/reports/mangopay',
        ];

        foreach ($links as $linkText => $linkPath) {
            $I->seeLink($linkText, $linkPath);
        }
        $I->seeElement('table#transaction-builder-list');
    }

    public function checkMangopayReportCreation(FunctionalTester $I): void
    {
        $description = 'test report description' . bin2hex(random_bytes('4'));
        $I->amOnPage('/admin/reports/create/mangopay');
        $I->seeLink('Abandon', '/admin/reports');
        $I->fillField('#mangopay_report_request_Tag', $description);
        $I->fillField(
            '#mangopay_report_request_Filters_WalletId',
            $I::FIXTURE_WALLETS['deposit'],
        );
        $afterDate = new \DateTime('-1 day')->getTimestamp();
        $I->fillField('#mangopay_report_request_Filters_AfterDate', date(
            'Y-m-d H:i',
            $afterDate,
        ));
        // Toggle the disable autosave option to stop a callbackUrl being set
        // For autoamted tests, the callbackUrl will be unreachable
        // So Mangopay will send a notification to the teams inbox which can be annoying
        $I->checkOption('#mangopay_report_request_disableAutoSave');
        $I->click('Create Report');
        $I->seeCurrentUrlMatches('~^/admin/reports/(\d+)~');

        $dataPoints = [
            'origin' => 'Mangopay',
            'resource' => $I::FIXTURE_WALLETS['deposit'],
        ];

        foreach ($dataPoints as $fieldName => $content) {
            $I->see($content, '[data-field-name="' . $fieldName . '"]');
        }

        $reportId = $I->grabTextFrom('[data-field-name="id"] td:nth-child(2)');
        $reference = $I->grabTextFrom('[data-field-name="reference"] td:nth-child(2)');
        $I->seeLink(
            'View Mangopay Report Config',
            "/admin/reports/mangopay/{$reference}",
        );
        $I->see($description, '[data-field-name="description"] td:nth-child(2)');

        $status = $I->grabTextFrom('[data-field-name="status"] td:nth-child(2)');
        if (ucfirst(ReportStatus::Available->value) == $status) {
            $I->assertNotEmpty($I->grabTextFrom(
                '[data-field-name="url"] td:nth-child(2)',
            ));
            $I->seeLink('Download Report', "/admin/reports/{$reportId}/download");
        } else {
            // If not yet available, check for updates after a second
            // Not guaranteed to have an update
            // Mangopay reports may take a few moments longer to generate
            sleep(1);
            $I->click('Check for Updates');
            $status = $I->grabTextFrom('[data-field-name="status"] td:nth-child(2)');
            if (ucfirst(ReportStatus::Available->value) == $status) {
                $I->assertNotEmpty($I->grabTextFrom(
                    '[data-field-name="url"] td:nth-child(2)',
                ));
            }
        }
    }

    public function checkTransactionReportBuilder(FunctionalTester $I): void
    {
        $description = 'Automated test ' . bin2hex(random_bytes(4));
        $I->amOnPage('/admin/reports/mangopay/transaction-report/create');
        $I->seeLink('Abandon', '/admin/reports/mangopay/sets');
        $I->selectOption('#mangopay_report_set_config_asset', ['value' => '1']);
        $I->fillField(
            '#mangopay_report_set_config_periodStart',
            new \DateTime('-7 days')->format('Y-m-d'),
        );
        $I->fillField(
            '#mangopay_report_set_config_periodEnd',
            new \DateTime('-2 days')->format('Y-m-d'),
        );
        $I->fillField('#mangopay_report_set_config_description', $description);
        $I->click('Save and Continue');

        $reportSets = $I->grabColumnFromDatabase('report_set', 'id', [
            'description' => $description,
        ]);
        sort($reportSets);
        $reportSetId = array_pop($reportSets);
        $I->seeCurrentUrlEquals(
            "/admin/reports/mangopay/transaction-report/{$reportSetId}/wallet",
        );
        $I->seeLink(
            'Cancel',
            "/admin/reports/mangopay/transaction-report/{$reportSetId}",
        );
        // Submitting here, won't save anything yet
        $I->click('Submit Wallet Choice');

        // Go back to the overview page
        $I->amOnPage("/admin/reports/mangopay/transaction-report/{$reportSetId}");
        $I->see('1', '[data-field-name="progress"]');
        $I->see('Draft', '[data-field-name="status"]');
        $I->seeNumberOfElements('#reports-list tbody tr', 0);
        $I->see('1', '[data-field-name="number-of-mangopay-reports-needed"]');
        $I->see('0', '[data-field-name="number-of-mangopay-reports-found"]');

        // Then resume the generate
        $I->click('Configure Mangopay Report Generator');
        $I->seeCurrentUrlEquals(
            "/admin/reports/mangopay/transaction-report/{$reportSetId}/wallet",
        );
        $I->selectOption('#form_walletId', ['value' => $I::FIXTURE_WALLETS['tax']]);
        $I->click('Submit Wallet Choice');
        $I->seeCurrentUrlEquals(
            "/admin/reports/mangopay/transaction-report/{$reportSetId}/report-config?walletId={$I::FIXTURE_WALLETS['tax']}",
        );
        $I->seeLink(
            'Cancel',
            "/admin/reports/mangopay/transaction-report/{$reportSetId}",
        );
        // Toggle the disable autosave option to stop a callbackUrl being set
        // For autoamted tests, the callbackUrl will be unreachable
        // So Mangopay will send a notification to the teams inbox which can be annoying
        $I->checkOption('#mangopay_report_request_disableAutoSave');
        // Check walletId prefill
        $I->seeInField(
            '#mangopay_report_request_Filters_WalletId',
            $I::FIXTURE_WALLETS['tax'],
        );
        // Should only require 1 report for the 5 day duration
        $I->see('1', '[data-field-name="mangopay-reports-to-create"]');
        $I->click('Create Mangopay Reports');
        $I->seeCurrentUrlEquals(
            "/admin/reports/mangopay/transaction-report/{$reportSetId}",
        );
        $I->see('View Reports', '#step-generate-reports a');
        $I->see('This step is complete', '#step-generate-reports');
        $I->seeNumberOfElements('#reports-list tbody tr', 1);
        $I->see('1', '[data-field-name="number-of-mangopay-reports-found"]');
        // The report builder should have an updated progress status
        $I->see('10', '[data-field-name="progress"]');
        $I->see('Pending', '[data-field-name="status"]');

        $I->click('Configure Merge and Processing');
        $I->seeCurrentUrlEquals(
            "/admin/reports/mangopay/transaction-report/{$reportSetId}/merge",
        );
        // Note that the report won't be available, so this will actually return an error message
        $I->click('Create Merged Report');
        $I->see('No available reports to merge');

        // Use fixtures for any further progress
        $completBuilderId = $I->grabFromDatabase('report_set', 'id', [
            'description' => 'Example completed transaction report builder',
            'progress' => 100,
        ]);
        $I->amOnPage("/admin/reports/mangopay/transaction-report/{$completBuilderId}");
        $I->see('Available', '[data-field-name="status"]');
        $I->see('Download Report', '#step-merge a');
        $I->see('Merged report is ready for download', '#step-merge');
    }
}
