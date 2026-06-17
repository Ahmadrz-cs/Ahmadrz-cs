<?php

namespace App\Tests\Functional\Ops\MonthEnd;

use App\Tests\Support\FunctionalTester;

class MonthendDashboardCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    /**
     * @group dashboard
     */
    public function checkOverviewSections(FunctionalTester $I): void
    {
        $sampleOfferingId = $I->grabFromDatabase('offerings', 'id', [
            'name' => 'Royal Eversea Glades - Cambridge',
            'inv_id' => null,
        ]);
        $sampleAssetId = $I->grabFromDatabase('offerings', 'asset_id', [
            'id' => $sampleOfferingId,
        ]);
        $I->amOnPage("/admin/monthend/{$sampleAssetId}");

        // Check sections and titles present
        $sections = [
            'monthend-checklist' => 'Monthend Checklist',
            'about-asset' => 'About Asset',
            'wallets' => 'Wallets',
        ];
        // check section headers
        foreach ($sections as $sectionId => $sectionTitle) {
            $I->see($sectionTitle, "section#{$sectionId} h3");
        }

        // Check links in each section
        $links = [
            'View Asset Product' => [
                'section' => 'about-asset',
                'href' => "products/{$sampleAssetId}",
            ],
            'View Wallet Balances' => [
                'section' => 'wallets',
                'href' => "asset/{$sampleAssetId}/manage-wallets",
            ],
        ];
        foreach ($links as $linkText => $linkInfo) {
            $I->see($linkText, "section#{$linkInfo['section']} a");
            $I->seeLink($linkText, "/admin/{$linkInfo['href']}");
        }

        // Make sure all 7 wallets are in the checklist
        foreach ([
            'hold',
            'settlement',
            'deposit',
            'expenses',
            'tax',
            'distribution',
            'treasury',
        ] as $walletType) {
            $I->see($walletType, '#wallets table tbody td:first-child');
        }

        // Check navigation links
        $navLinks = [
            'Overview' => "/admin/monthend/{$sampleAssetId}",
            'Income Transfers' => "/admin/monthend/{$sampleAssetId}/income-transfers",
            'Dividends' => "/admin/monthend/{$sampleAssetId}/dividends",
            'Prefunder Repayments' => "/admin/monthend/{$sampleAssetId}/repayments",
            'Divestments and Exits' => "/admin/monthend/{$sampleAssetId}/divestments",
            'Share Transfers' => "/admin/monthend/{$sampleAssetId}/share-transfers",
            'Review Activity' => "/admin/monthend/{$sampleAssetId}/review",
        ];
        foreach ($navLinks as $linkText => $linkPath) {
            $I->see($linkText, '#hub-nav');
            $I->seeLink($linkText, $linkPath);
        }
    }

    public function checkWarnings(FunctionalTester $I): void
    {
        $emptyAsset = $I->grabFromDatabase('assets', 'id', [
            // 'name' => 'Sagittarius Eystar - Horizon',
            'mangoPayWalletId' => null,
        ]);
        $fullAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Lodge de Lac - Cumbria',
        ]);

        // http://back.dev.local/admin/products/16/editor/wallets
        $I->amOnPage("/admin/monthend/{$emptyAsset}");
        $I->seeElement('section#wallet-warning');
        $I->see('Missing Wallets', 'section#wallet-warning');
        $I->see('Configure Wallets', 'section#wallet-warning');
        $I->seeLink(
            'Configure Wallets',
            "/admin/products/{$emptyAsset}/editor/wallets",
        );

        $I->amOnPage("/admin/monthend/{$fullAsset}");
        $I->dontSeeElement('section#wallet-warning');
        $I->dontSeeLink(
            'Configure Wallets',
            "/admin/products/{$fullAsset}/editor/wallets",
        );
    }

    /**
     * @group dashboard
     */
    public function checkOverviewChecklist(FunctionalTester $I): void
    {
        /**
         * Check empty state doesn't crash
         * Check populated state
         */
        $emptyAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Silverhood Down - Brighton',
        ]);
        $fullAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Lodge de Lac - Cumbria',
        ]);

        // No monthend activities for this month
        $I->amOnPage("/admin/monthend/{$emptyAsset}");
        $I->see('Create Income Transfer', '[data-activity-name="income-transfer"] a');
        $I->see('Create Dividend', '[data-activity-name="dividends"] a');
        $I->see('Create Repayment', '[data-activity-name="repayments"] a');
        $I->seeLink(
            'Create Income Transfer',
            "/admin/monthend/income-transfers/create/{$emptyAsset}",
        );
        $I->seeLink(
            'Create Dividend',
            "/admin/monthend/dividends/create/{$emptyAsset}",
        );
        $I->seeLink(
            'Create Settlement',
            "/admin/monthend/settlements/create/{$emptyAsset}",
        );
        $I->seeLink(
            'Transfer Repayment Funds',
            "/admin/monthend/repayments/transfer/{$emptyAsset}/create",
        );
        $I->seeLink(
            'Create Repayment',
            "/admin/monthend/repayments/create/{$emptyAsset}",
        );
        $I->seeLink(
            'Create Share Transfer',
            "/admin/monthend/share-transfers/create/{$emptyAsset}",
        );
        $I->see('Toggle Checklist Mode', '#monthend-checklist');
        $I->seeLink(
            'Toggle Checklist Mode',
            "admin/monthend/{$emptyAsset}/toggle-auto-checklist",
        );
        $startActivityStatuses = [
            'income-transfer' => 'Pending',
            'dividends' => 'Pending',
            'settlements' => 'Pending',
            'repayments' => 'Pending',
            'share-transfers' => 'Pending',
        ];
        foreach ($startActivityStatuses as $activity => $status) {
            $I->see(
                $status,
                "[data-activity-name={$activity}] [data-checklist-field=status]",
            );
        }

        // Check manual checklist changes
        $endActivityStatuses = [
            'income-transfer' => 'Completed',
            'dividends' => 'Completed',
            'settlements' => 'Started',
            'repayments' => 'Skipped',
            'share-transfers' => 'Skipped',
        ];
        foreach ($startActivityStatuses as $activity => $status) {
            // Check starting point
            $I->see(
                $status,
                "[data-activity-name={$activity}] [data-checklist-field=status]",
            );
            // Update as per status defined in endActivityStatuses
            $I->click(
                "Mark as {$endActivityStatuses[$activity]}",
                "[data-activity-name={$activity}] a",
            );
            // Check redirect worked
            $I->seeCurrentUrlEquals("/admin/monthend/{$emptyAsset}");
            // Check status changes were saved
            $I->see(
                $endActivityStatuses[$activity],
                "[data-activity-name={$activity}] [data-checklist-field=status]",
            );
        }

        // Change some back to pending
        $I->click('Mark as Pending', '[data-activity-name=dividends] a');
        $I->click('Mark as Pending', '[data-activity-name=settlements] a');
        // Then skip remainder that are pending
        $I->click('Mark All Pending as Skipped');
        $I->see(
            'Skipped',
            '[data-activity-name=dividends] [data-checklist-field=status]',
        );
        $I->see(
            'Skipped',
            '[data-activity-name=settlements] [data-checklist-field=status]',
        );

        // Checklist mode toggle is still available
        $I->see('Toggle Checklist Mode', '#monthend-checklist');
        $I->seeLink(
            'Toggle Checklist Mode',
            "admin/monthend/{$emptyAsset}/toggle-auto-checklist",
        );

        // Then revert back as clean up
        foreach ($startActivityStatuses as $activity => $status) {
            $I->click("Mark as {$status}", "[data-activity-name={$activity}] a");
        }

        // Monthend activities present
        $I->amOnPage("/admin/monthend/{$fullAsset}");
        // Income transfer section
        $I->see('View Income Transfers', '[data-activity-name="income-transfer"] a');
        $I->seeElement('[data-activity-name="income-transfer"] .progress');
        // Dividend section
        $I->see('View Dividend Payments', '[data-activity-name="dividends"] a');
        $I->seeElement('[data-activity-name="dividends"] .progress');
        // Settlements section
        $I->see('View Settlements', '[data-activity-name="settlements"] a');
        $I->seeElement('[data-activity-name="settlements"] .progress');
        // Repayment section
        $I->see('View Repayment', '[data-activity-name="repayments"] a');
        $I->seeElement('[data-activity-name="repayments"] .progress');
        // Share Transfer section
        $I->see('View Share Transfer', '[data-activity-name="share-transfers"] a');
        $I->seeElement('[data-activity-name="share-transfers"] .progress');

        $syncedActivityStatuses = [
            'income-transfer' => 'Started',
            'dividends' => 'Started',
            'settlements' => 'Pending',
            'repayments' => 'Started',
            'share-transfers' => 'Started',
        ];
        foreach ($syncedActivityStatuses as $activity => $status) {
            $I->see(
                $status,
                "[data-activity-name={$activity}] [data-checklist-field=status]",
            );
        }

        // Switch to manual mode
        $I->see(
            'Auto',
            'section#monthend-checklist [data-field-name="checklist-mode"]',
        );
        $I->click('Toggle Checklist Mode');
        $I->see(
            'Manual',
            'section#monthend-checklist [data-field-name="checklist-mode"]',
        );
        foreach ($syncedActivityStatuses as $activity => $status) {
            $I->see(
                $status,
                "[data-activity-name={$activity}] [data-checklist-field=status]",
            );
        }

        // Can now force back to pending without being overriden by the auto updater
        $I->click('Mark as Pending', '[data-activity-name=income-transfer] a');
        $I->click('Mark as Pending', '[data-activity-name=dividends] a');
        $I->click('Mark as Pending', '[data-activity-name=repayments] a');
        $I->click('Mark as Pending', '[data-activity-name=share-transfers] a');
        $I->see(
            'Pending',
            '[data-activity-name=income-transfer] [data-checklist-field=status]',
        );
        $I->see(
            'Pending',
            '[data-activity-name=dividends] [data-checklist-field=status]',
        );
        $I->see(
            'Pending',
            '[data-activity-name=repayments] [data-checklist-field=status]',
        );
        $I->see(
            'Pending',
            '[data-activity-name=share-transfers] [data-checklist-field=status]',
        );

        // Re-enable auto update and it should resync
        $I->click('Toggle Checklist Mode');
        $I->see(
            'Auto',
            'section#monthend-checklist [data-field-name="checklist-mode"]',
        );
        foreach ($syncedActivityStatuses as $activity => $status) {
            $I->see(
                $status,
                "[data-activity-name={$activity}] [data-checklist-field=status]",
            );
        }
    }

    /**
     * @group dashboard
     */
    public function checkIncomeTransfersSection(FunctionalTester $I): void
    {
        $emptyAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Silverhood Down - Brighton',
        ]);
        $fullAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);

        // Should have no issues loading a "new" asset with no entries
        $I->amOnPage("/admin/monthend/{$emptyAsset}/income-transfers");
        $I->seeLink(
            'Create Income Transfer',
            "/admin/monthend/income-transfers/create?assetId={$emptyAsset}",
        );
        // Empty asset won't have current monthend section
        $I->dontSeeElement('section#current-monthend');

        // Check sections and titles present
        $sections = [
            'transfer-orders' => 'All Income Transfers',
        ];
        // check section headers
        foreach ($sections as $sectionId => $sectionTitle) {
            $I->see($sectionTitle, "section#{$sectionId} h3");
        }

        // check table headers
        $headers = [
            'Id',
            'Description',
            'Scheduled for',
            'Transfers',
            'Status',
            'Actions',
        ];
        // check table headers
        foreach ($headers as $name) {
            $I->see($name, 'table thead th');
        }

        // Table should have at least 1 row if populated (i.e. it is loading in)
        $I->amOnPage("/admin/monthend/{$fullAsset}/income-transfers");
        $I->seeNumberOfElements('table tbody tr', [1, 10]); // minimum 1 entry, 10 max per page

        // Check current monthend section
        $I->seeElement('section#current-monthend [data-field-name="current-monthend"]');
        $I->seeElement('section#current-monthend [data-field-name="status"]');
        $I->seeElement(
            'section#current-monthend [data-field-name="transfers-completed"]',
        );
        $I->seeElement(
            'section#current-monthend [data-field-name="total-transferred-(£)"]',
        );
    }

    /**
     * @group dashboard
     */
    public function checkDividendsSection(FunctionalTester $I): void
    {
        $emptyAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Silverhood Down - Brighton',
        ]);
        $fullAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);

        // Should have no issues loading a "new" asset with no entries
        $I->amOnPage("/admin/monthend/{$emptyAsset}/dividends");
        $I->seeLink(
            'Create Dividend',
            "/admin/monthend/dividends/create?assetId={$emptyAsset}",
        );
        // Empty asset won't have current monthend section
        $I->dontSeeElement('section#current-monthend');

        // Check sections and titles present
        $sections = [
            'payment-orders' => 'All Dividend Payment Orders',
        ];
        // check section headers
        foreach ($sections as $sectionId => $sectionTitle) {
            $I->see($sectionTitle, "section#{$sectionId} h3");
        }

        // check table headers
        $headers = [
            'Id',
            'Type',
            'Description',
            'Scheduled for',
            'Payments',
            'Status',
            'Actions',
        ];
        // check table headers
        foreach ($headers as $name) {
            $I->see($name, '#payment-orders table thead th');
        }

        // Table should have at least 1 row if populated (i.e. it is loading in)
        $I->amOnPage("/admin/monthend/{$fullAsset}/dividends");
        $I->seeNumberOfElements('#payment-orders table tbody tr', [1, 10]); // minimum 1 entry, 10 max per page

        // Check current monthend section
        $I->seeElement('section#current-monthend [data-field-name="current-monthend"]');
        $I->seeElement('section#current-monthend [data-field-name="status"]');
        $I->seeElement(
            'section#current-monthend [data-field-name="payments-completed"]',
        );
        $I->seeElement('section#current-monthend [data-field-name="total-paid-(£)"]');
    }

    /**
     * @group dashboard
     */
    public function checkSettlementsSection(FunctionalTester $I): void
    {
        $emptyAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Silverhood Down - Brighton',
        ]);
        $fullAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);

        // Should have no issues loading a "new" asset with no entries
        $I->amOnPage("/admin/monthend/{$emptyAsset}/settlements");
        $I->seeLink(
            'Create Settlement',
            "/admin/monthend/settlements/create?assetId={$emptyAsset}",
        );
        // // Empty asset should still have a quck-info box for investments to settle (just says 0)
        // $I->see('View Investments to Settle', 'section#investments-to-settle a');
        // $I->seeElement('section#investments-to-settle [data-field-name="investments-to-settle"]');
        // Empty asset won't have current monthend section
        $I->dontSeeElement('section#current-monthend');

        // Check sections and titles present
        $sections = [
            'transfer-orders' => 'All Settlement Orders',
        ];
        // check section headers
        foreach ($sections as $sectionId => $sectionTitle) {
            $I->see($sectionTitle, "section#{$sectionId} h3");
        }

        // check table headers
        $headers = [
            'Id',
            'Description',
            'Scheduled for',
            'Transfers',
            'Status',
            'Actions',
        ];
        // check table headers
        foreach ($headers as $name) {
            $I->see($name, 'table thead th');
        }

        // Table should have at least 1 row if populated (i.e. it is loading in)
        $I->createSettlementOrder('Royal Eversea Glades - Cambridge');
        $I->amOnPage("/admin/monthend/{$fullAsset}/settlements");
        $I->seeNumberOfElements('table tbody tr', [1, 10]); // minimum 1 entry, 10 max per page

        // // Check investments to settle section
        // $I->see('View Investments to Settle', 'section#investments-to-settle a');
        // $I->seeElement('section#investments-to-settle [data-field-name="investments-to-settle"]');

        // Check current monthend section
        $I->see('View Current Monthend', 'section#current-monthend a');
        $I->seeElement('section#current-monthend [data-field-name="current-monthend"]');
        $I->seeElement('section#current-monthend [data-field-name="status"]');
        $I->seeElement(
            'section#current-monthend [data-field-name="transfers-completed"]',
        );
        $I->seeElement(
            'section#current-monthend [data-field-name="total-transferred-(£)"]',
        );
    }

    /**
     * @group dashboard
     */
    public function checkRepaymentsSection(FunctionalTester $I): void
    {
        $emptyAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Silverhood Down - Brighton',
        ]);
        $fullAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Lodge de Lac - Cumbria',
        ]);

        // Should have no issues loading a "new" asset with no entries
        $I->amOnPage("/admin/monthend/{$emptyAsset}/repayments");
        $I->seeLink(
            'Create Prefunder Repayment',
            "/admin/monthend/repayments/create?assetId={$emptyAsset}",
        );
        $I->seeLink(
            'Transfer Repayment Funds',
            "/admin/monthend/repayments/transfer/{$emptyAsset}/create",
        );
        // Empty asset won't have current monthend section or repayment tracking as it has no prefunders
        $I->dontSeeElement('section#current-monthend');
        $I->dontSeeElement('section#repayment-progress');

        // Check sections and titles present
        $sections = [
            'payment-orders' => 'All Prefunder Repayments',
        ];
        // check section headers
        foreach ($sections as $sectionId => $sectionTitle) {
            $I->see($sectionTitle, "section#{$sectionId} h3");
        }

        // check table headers
        $headers = [
            'Id',
            'Type',
            'Description',
            'Scheduled for',
            'Payments',
            'Status',
            'Actions',
        ];
        // check table headers
        foreach ($headers as $name) {
            $I->see($name, 'table thead th');
        }

        // Table should have at least 1 row if populated (i.e. it is loading in)
        $I->amOnPage("/admin/monthend/{$fullAsset}/repayments");
        $I->seeNumberOfElements('table tbody tr', [1, 10]); // minimum 1 entry, 10 max per page

        // Check current monthend section
        $I->seeElement('section#current-monthend [data-field-name="current-monthend"]');
        $I->seeElement('section#current-monthend [data-field-name="status"]');
        $I->seeElement(
            'section#current-monthend [data-field-name="payments-completed"]',
        );
        $I->seeElement('section#current-monthend [data-field-name="total-paid-(£)"]');

        // Check repayment tracking
        $I->seeElement(
            'section#repayment-progress [data-field-name="original-shares-to-repay"]',
        );
        $I->seeElement(
            'section#repayment-progress [data-field-name="shares-already-repaid"]',
        );
        $I->seeElement(
            'section#repayment-progress [data-field-name="shares-still-to-repay"]',
        );
    }

    /**
     * @group dashboard
     */
    public function checkDivestmentsSection(FunctionalTester $I): void
    {
        $emptyAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Sagittarius Eystar - Horizon',
        ]);
        $fullAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Lodge de Lac - Cumbria',
        ]);

        // Should have no issues loading a "new" asset with no entries
        $I->amOnPage("/admin/monthend/{$emptyAsset}/divestments");
        $I->seeLink(
            'Create Divestment',
            "/admin/monthend/divestments/create?assetId={$emptyAsset}",
        );
        // Empty asset won't have current monthend section or repayment tracking as it has no prefunders
        $I->dontSeeElement('section#current-monthend');
        $I->dontSeeElement('section#repayment-progress');

        // Check sections and titles present
        $sections = [
            'payment-orders' => 'All Divestment Payment Orders',
        ];
        // check section headers
        foreach ($sections as $sectionId => $sectionTitle) {
            $I->see($sectionTitle, "section#{$sectionId} h3");
        }

        // check table headers
        $headers = [
            'Id',
            'Type',
            'Description',
            'Scheduled for',
            'Payments',
            'Status',
            'Actions',
        ];
        // check table headers
        foreach ($headers as $name) {
            $I->see($name, 'table thead th');
        }

        // Table should have at least 1 row if populated (i.e. it is loading in)
        $I->amOnPage("/admin/monthend/{$fullAsset}/divestments");
        $I->seeNumberOfElements('table tbody tr', [1, 10]); // minimum 1 entry, 10 max per page
    }

    /**
     * @group dashboard
     */
    public function checkShareTransfersSection(FunctionalTester $I): void
    {
        $fullAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Lodge de Lac - Cumbria',
        ]);
        $I->amOnPage("/admin/monthend/{$fullAsset}/share-transfers");
        $I->seeLink(
            'Create Share Transfer',
            "/admin/monthend/share-transfers/create?assetId={$fullAsset}",
        );

        // Check sections and titles present
        $sections = [
            'share-transfer-orders' => 'All Share Transfer Orders',
        ];
        // check section headers
        foreach ($sections as $sectionId => $sectionTitle) {
            $I->see($sectionTitle, "section#{$sectionId} h3");
        }

        // check table headers
        $headers = [
            'Id',
            'Description',
            'Related Monthend',
            'Period',
            'Shares',
            'Transfers',
            'Status',
            'Actions',
        ];
        // check table headers
        foreach ($headers as $name) {
            $I->see($name, 'table thead th');
        }

        // Table should have at least 1 row if populated (i.e. it is loading in)
        // Create one ensure they are loading in correctly
        $currentCount = (int) explode(' ', $I->grabTextFrom('#list-meta-results'))[0];
        $I->amOnPage("/admin/monthend/share-transfers/create?assetId={$fullAsset}");
        $I->seeLink('Back', "/admin/monthend/{$fullAsset}");
        $I->seeLink('Abandon', "/admin/monthend/{$fullAsset}");
        $I->click('Create Share Transfer Order');
        $I->amOnPage("/admin/monthend/{$fullAsset}/share-transfers");
        $I->seeNumberOfElements('table tbody tr', [1, 10]); // minimum 1 entry, 10 max per page
        $I->see((string) $currentCount + 1, '#list-meta-results');
    }

    public function checkReviewActivitySection(FunctionalTester $I): void
    {
        $fullAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);
        $I->amOnPage("/admin/monthend/{$fullAsset}/review");

        $sections = [
            'query-configuration',
            'dividends',
            'investment-settlements',
            'prefunder-repayments',
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
