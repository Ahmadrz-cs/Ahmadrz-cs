<?php

namespace App\Tests\Functional\Ops\MonthEnd;

use App\Tests\Support\FunctionalTester;

class MonthendHubCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    /**
     * @group listview
     */
    public function checkListViewElements(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/monthend/assets');

        $elements = [
            'Id',
            'Name',
            'SPV Id',
            'Status',
            'Buying',
            'Selling',
            'Task Tracker',
            'Actions',
        ];
        // check table headers
        $I->loopCheckElements($elements, 'thead th');

        $I->seeLink(
            'Create Income Transfer',
            '/admin/monthend/income-transfers/create',
        );
        $I->seeLink('Create Dividend', '/admin/monthend/dividends/create');
        $I->seeLink('Create Fee Collection', '/admin/monthend/fee-collections/create');
        $I->seeLink(
            'Create Income Disaggregation',
            '/admin/monthend/income-disaggregations/create',
        );

        // Check filter presets have correct urls
        $I->seeLink('Featured', '/admin/monthend/assets?featured=1');
        $I->seeLink('Residential', '/admin/monthend/assets?assetType=Residential');
        $I->seeLink('Commercial', '/admin/monthend/assets?assetType=Commercial');
        $I->seeLink('Prelaunch (Draft)', '/admin/monthend/assets?status%5B0%5D=draft');
        $I->seeLink(
            'Prefunding (Acquiring)',
            '/admin/monthend/assets?status%5B0%5D=acquiring',
        );
        $I->seeLink(
            'Retail (Active and Closing)',
            '/admin/monthend/assets?status%5B0%5D=active&status%5B1%5D=closing',
        );
        $I->seeLink(
            'Retail (Active only)',
            '/admin/monthend/assets?status%5B0%5D=active',
        );
        $I->seeLink(
            'Retail (Closing only)',
            '/admin/monthend/assets?status%5B0%5D=closing',
        );
        $I->seeLink(
            'Completed (Archived)',
            '/admin/monthend/assets?status%5B0%5D=archived',
        );

        $assetIds = $I->grabColumnFromDatabase('assets', 'id');
        rsort($assetIds);
        $I->seeLink('Review Monthend', "/admin/monthend/{$assetIds[0]}");

        // Check task trackers loaded
        $numberOfRows = count($I->grabMultiple('table#assets-list tbody tr'));
        $I->seeNumberOfElements(
            'table#assets-list tbody tr .task-tracker',
            $numberOfRows,
        );
        // Should be 6 steps in the tracker - just a sanity check, not exhaustive
        $I->seeNumberOfElements(
            'table#assets-list tbody tr:first-child .task-tracker .progress-bar',
            5,
        );
    }

    /**
     * @group listview
     * @dataProvider filterProvider
     */
    public function checkListViewAssetFilters(
        FunctionalTester $I,
        \Codeception\Example $example,
    ): void {
        if (empty($example['dbquery'])) {
            $example['dbquery'] = $example['filters'];
        }
        $expected = $I->grabNumRecords('assets', $example['dbquery']);
        $I->amOnPage('/admin/monthend/assets?' . http_build_query($example['filters']));
        $resultsCountText = $I->grabTextFrom('#list-meta-results');
        $actual = (int) explode(' ', $resultsCountText)[0];
        $I->assertEquals($expected, $actual);
    }

    protected function filterProvider(): array
    {
        return [
            [
                'filters' => ['name' => 'ever'],
                'dbquery' => ['name like' => '%ever%'],
            ],
        ];
    }

    public function checkMonthendHubSections(FunctionalTester $I): void
    {
        $navLinks = [
            'Dashboard' => '/admin/monthend',
            'Assets' => '/admin/monthend/assets',
            'Fee Collections' => '/admin/monthend/fee-collections',
            'Dividends' => '/admin/monthend/dividends',
            'Settlements' => '/admin/monthend/settlements',
            'Repayments' => '/admin/monthend/repayments',
            'Divestments' => '/admin/monthend/divestments',
            'Income Disaggregations' => '/admin/monthend/income-disaggregations',
        ];
        $I->amOnPage('/admin/monthend');
        $I->seeElement('section#monthend-checklist');
        foreach ($navLinks as $text => $path) {
            $I->seeLink($text, $path);
        }
        $I->see('Dashboard', '#hub-nav .nav-link.active');
        $I->seeLink('View', '/admin/monthend/income-disaggregations');
        $I->seeLink('View', '/admin/monthend/dividends');
        $I->seeLink('View', '/admin/monthend/settlements');
        $I->seeLink('View', '/admin/monthend/repayments');
        $I->seeLink('View', '/admin/monthend/fee-collections');

        $I->click('Assets');
        $I->amOnPage('/admin/monthend/assets');
        foreach ($navLinks as $text => $path) {
            $I->seeLink($text, $path);
        }
        $I->see('Assets', '#hub-nav .nav-link.active');

        // Income disaggregations section
        $I->click('Income Disaggregations');
        $I->seeCurrentUrlEquals('/admin/monthend/income-disaggregations');
        foreach ($navLinks as $text => $path) {
            $I->seeLink($text, $path);
        }
        $I->see('Income Disaggregations', '#hub-nav .nav-link.active');

        $I->seeElement('section#transfer-orders');
        $I->seeLink(
            'Create Income Disaggregation',
            '/admin/monthend/income-disaggregations/create',
        );
        $I->seeElement('table#transfer-order-list');

        // Dividends section
        $I->click('Dividends', '#hub-nav');
        $I->seeCurrentUrlEquals('/admin/monthend/dividends');
        foreach ($navLinks as $text => $path) {
            $I->seeLink($text, $path);
        }
        $I->see('Dividends', '#hub-nav .nav-link.active');

        $I->seeElement('section#dividend-summary');
        $I->seeLink('Create Dividend', '/admin/monthend/dividends/create');
        $I->seeLink('See More Analytics', '/admin/analytics/dividends');
        $I->seeElement('table#dividend-list');

        // Settlements section
        $I->click('Settlements', '#hub-nav');
        $I->seeCurrentUrlEquals('/admin/monthend/settlements');
        foreach ($navLinks as $text => $path) {
            $I->seeLink($text, $path);
        }
        $I->see('Settlements', '#hub-nav .nav-link.active');

        $I->seeElement('section#pending-settlements');
        $I->seeLink('Create Settlement', '/admin/monthend/settlements/create');
        $I->seeElement('table#asset-settlement-list');

        // Repayments section
        $I->click('Repayments', '#hub-nav');
        $I->seeCurrentUrlEquals('/admin/monthend/repayments');
        foreach ($navLinks as $text => $path) {
            $I->seeLink($text, $path);
        }
        $I->see('Repayments', '#hub-nav .nav-link.active');

        $I->seeElement('section#pending-repayments');
        $I->seeLink('Create Repayment', '/admin/monthend/repayments/create');
        $I->seeLink('Review All Tracked Shares', '/admin/holding/summary');
        $I->seeElement('table#prefunder-repayment-list');

        // Divestments section
        $I->click('Divestments', '#hub-nav');
        $I->seeCurrentUrlEquals('/admin/monthend/divestments');
        foreach ($navLinks as $text => $path) {
            $I->seeLink($text, $path);
        }
        $I->see('Divestments', '#hub-nav .nav-link.active');

        $I->seeElement('section#divestments-summary');
        $I->seeLink('Create Divestment', '/admin/monthend/divestments/create');
        $I->seeLink('Review All Tracked Shares', '/admin/holding/summary');
        $I->seeElement('table#divestment-list');

        // Fee Collections section
        $I->click('Fee Collections');
        $I->seeCurrentUrlEquals('/admin/monthend/fee-collections');
        foreach ($navLinks as $text => $path) {
            $I->seeLink($text, $path);
        }
        $I->see('Fee Collections', '#hub-nav .nav-link.active');

        $I->seeElement('section#transfer-orders');
        $I->seeLink('Create Fee Collection', '/admin/monthend/fee-collections/create');
        $I->seeElement('table#transfer-order-list');

        // Review Activity section
        $I->click('Review Activity');
        $I->seeCurrentUrlEquals('/admin/monthend/review');
        foreach ($navLinks as $text => $path) {
            $I->seeLink($text, $path);
        }
        $I->see('Review Activity', '#hub-nav .nav-link.active');
    }

    public function checkOverviewChecklist(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/monthend');

        $startActivityStatuses = [
            'income-deposits' => '',
            'income-disaggregations' => '',
            'dividends' => '',
            'settlements' => '',
            'repayments' => '',
            'fee-collections' => '',
        ];
        foreach ($startActivityStatuses as $activity => $status) {
            // $I->see($status, "[data-activity-name={$activity}] [data-checklist-field=status] button");
            $currentStatus = trim($I->grabTextFrom(
                "[data-activity-name={$activity}] [data-checklist-field=status] button",
            ));
            $startActivityStatuses[$activity] = $currentStatus;
        }

        // Check manual checklist changes
        $endActivityStatuses = [
            'income-deposits' => 'Completed',
            'income-disaggregations' => 'Completed',
            'dividends' => 'Completed',
            'settlements' => 'Started',
            'repayments' => 'Skipped',
            'fee-collections' => 'Skipped',
        ];
        foreach ($startActivityStatuses as $activity => $status) {
            // Check starting point
            $I->see(
                $status,
                "[data-activity-name={$activity}] [data-checklist-field=status] button",
            );
            // Update as per status defined in endActivityStatuses
            $I->click(
                "Mark as {$endActivityStatuses[$activity]}",
                "[data-activity-name={$activity}] a",
            );
            // Check redirect worked
            $I->seeCurrentUrlEquals('/admin/monthend');
            // Check status changes were saved
            $I->see(
                $endActivityStatuses[$activity],
                "[data-activity-name={$activity}] [data-checklist-field=status] button",
            );
        }

        // Then revert back as clean up
        foreach ($startActivityStatuses as $activity => $status) {
            $I->click("Mark as {$status}", "[data-activity-name={$activity}] a");
        }
    }

    public function checkReviewActivityPage(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/monthend/review');

        $sections = [
            'query-configuration',
            'income-disaggregations',
            'dividends',
            'investment-settlements',
            'prefunder-repayments',
            'fee-collections',
            'divestments',
            'share-transfers',
        ];
        foreach ($sections as $sectionId) {
            $I->seeElement("section#{$sectionId}");
        }

        // Sanity check the filter presets
        $I->click('Last 6 Months', 'section#query-configuration');
        // Should be fixtures for past 6 months
        // Plus the total across the period (so 6 + 1 maximum)
        $I->seeNumberOfElements('section#dividends table tbody tr', [6, 7]);

        // Sanity check the date filters
        $expectedStartMonth = new \DateTime('-5 months');
        $expectedEndMonth = new \DateTime('-2 months');
        // the day should make no difference as they are auto-bounded internally
        $I->fillField('#startMonth', $expectedStartMonth->format('Y-m-14'));
        $I->fillField('#endMonth', $expectedEndMonth->format('Y-m-02'));
        $I->click('Apply Filters', 'section#query-configuration');
        $I->see(
            $expectedStartMonth->format('Y-m'),
            'section#dividends table tbody tr:last-child',
        );
        $I->see(
            $expectedEndMonth->format('Y-m'),
            'section#dividends table tbody tr:nth-child(2)',
        );
        $I->see(
            'Total in search range',
            'section#dividends table tbody tr:first-child',
        );
    }
}
