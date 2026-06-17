<?php

namespace App\Tests\Functional\Ops\Product;

use App\Entity\BaseEntity;
use App\Entity\Enum\AssetStatus;
use App\Entity\Lifecycle\OfferingLifecycle;
use App\Tests\Support\FunctionalTester;

class ProductDashboardCest
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
        $sampleAssetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);
        $I->amOnPage("/admin/products/{$sampleAssetId}");

        // Check hub nav links
        $links = [
            'Overview' => '',
            'Documents' => '/documents',
            'Shareholders' => '/shareholders',
            'Trade Orders' => '/trade-orders',
            'Share Trades' => '/share-trades',
            'Investments' => '/investments',
            'Listings' => '/listings',
            'Payments' => '/payments',
            'Payment Orders' => '/payment-orders',
            'Transfer Orders' => '/transfer-orders',
        ];
        foreach ($links as $linkText => $linkPath) {
            $I->see($linkText, '#hub-nav');
            $I->seeLink($linkText, "/admin/products/{$sampleAssetId}{$linkPath}");
        }

        // Check sections and titles present
        $sections = [
            'financial-information' => 'Financial Information',
            'status' => 'Status',
            'about-asset' => 'About Asset',
            'trading-rules' => 'Trading Rules',
            'documents' => 'Documents',
            'wallets' => 'Wallets',
        ];
        // check section headers
        foreach ($sections as $sectionId => $sectionTitle) {
            $I->see($sectionTitle, "section#{$sectionId} h3");
        }

        // Check links in each section
        $links = [
            'Edit Financial Info' => [
                'section' => 'financial-information',
                'href' => "products/{$sampleAssetId}/editor/financials",
            ],
            'Go to Monthend Dashboard' => [
                'section' => 'financial-information',
                'href' => "monthend/{$sampleAssetId}",
            ],
            'Go to Launch Centre' => [
                'section' => 'status',
                'href' => "products/{$sampleAssetId}/editor/launch",
            ],
            'Edit Asset Info' => [
                'section' => 'about-asset',
                'href' => "products/{$sampleAssetId}/editor/about",
            ],
            'Edit Asset Location' => [
                'section' => 'about-asset',
                'href' => "products/{$sampleAssetId}/editor/location",
            ],
            'Edit Trading Rules' => [
                'section' => 'trading-rules',
                'href' => "products/{$sampleAssetId}/editor/rules",
            ],
            'Edit Wallets' => [
                'section' => 'wallets',
                'href' => "products/{$sampleAssetId}/editor/wallets",
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

        $I->see('Launched', 'section#status h3');
        // // Royal Eversea glade should be fully funded
        $assetValuation = $I->grabTextFrom('[data-field-name="asset-valuation"]');
        // $I->see("{$assetValuation}/{$assetValuation} (100.00%)");

        // If not launched, should say so in status section
        $sampleAssetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Sagittarius Eystar - Horizon',
        ]);
        $I->amOnPage("/admin/products/{$sampleAssetId}");
        $I->see('Not Yet Launched', 'section#status h3');
        // Sale progress should show 0%
        $assetValuation = $I->grabTextFrom('[data-field-name="asset-valuation"]');
        // $I->see("£0.00/{$assetValuation} (0.00%)");

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
            $I->see($walletType, '#wallets table tbody [data-field="walletName"]');
        }
    }

    /**
     * Modify the asset/offering and check derived fields are updating properly
     * These derivations are done at template level
     * Some may get refactored to be in service level helpers
     *
     * Derived fields include
     * - Term remaining with progress bar (requires a launch and close date)
     * - Visibility (derived with boolean logic of asset and offering)
     * - Documents checklist
     */

    /**
     * @group dashboard
     */
    public function checkDerivedFieldsTermRemaining(FunctionalTester $I): void
    {
        $sampleAssetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);

        // Check term remaining behaviour
        // Update the asset with specific term start and duration
        $startDate = new \DateTimeImmutable('-12 months');
        $closeDate = new \DateTimeImmutable('+36 months');
        $I->updateInDatabase(
            'assets',
            ['termStart' => $startDate->format('Y-m-d'), 'investmentTerm' => 48],
            ['id' => $sampleAssetId],
        );
        $I->amOnPage("/admin/products/{$sampleAssetId}");
        $I->see($startDate->format('Y-m-d'), '[data-field-name="term-start"]');
        $I->see($closeDate->format('Y-m-d'), '[data-field-name="term-end"]');
        $I->see('25%', '[data-field-name="term-progress"]'); // 12 months of 48 elapsed
        $I->see('35 months', '[data-field-name="term-remaining"]');
    }

    /**
     * @group dashboard
     */
    public function checkDerivedFieldsVisibility(FunctionalTester $I): void
    {
        $sampleAssetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);

        // Check visibility behaviour
        $I->updateInDatabase(
            'assets',
            ['visibility' => BaseEntity::VISIBILITY_AUTO],
            ['id' => $sampleAssetId],
        );
        $I->amOnPage("/admin/products/{$sampleAssetId}");
        $I->see('Everyone', '[data-field-name="visible-to"]');

        // Change visibility to VIP
        $I->updateInDatabase(
            'assets',
            ['visibility' => BaseEntity::VISIBILITY_VIP],
            ['id' => $sampleAssetId],
        );
        $I->amOnPage("/admin/products/{$sampleAssetId}");
        $I->see('Top Yielders Only', '[data-field-name="visible-to"]');

        // Change visibility to admin
        $I->updateInDatabase(
            'assets',
            ['visibility' => BaseEntity::VISIBILITY_ADMIN],
            ['id' => $sampleAssetId],
        );
        $I->amOnPage("/admin/products/{$sampleAssetId}");
        $I->see('Admin Only', '[data-field-name="visible-to"]');
    }

    /**
     * @group dashboard
     */
    public function checkDerivedFieldsDocuments(FunctionalTester $I): void
    {
        $sampleAssetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);

        // Check documents checklist
        // Change the tags and descriptions to break them
        // Royal Eversea Glades - Cambridge has everything configured by default
        $assetDocIds = $I->grabColumnFromDatabase('asset_docs', 'document_id', [
            'asset_id' => $sampleAssetId,
        ]);
        // Break the Financial Summary
        $allFSDocs = $I->grabColumnFromDatabase('documents', 'id', [
            'tag' => 'calculations',
            'description' => 'Financial Summary',
        ]);
        // echo PHP_EOL;
        // print_r($allFSDocs);
        // print_r($assetDocIds);
        // print_r(array_values(array_intersect($assetDocIds, $allFSDocs)));
        // echo PHP_EOL;
        // $I->fail('before db changes');
        $I->updateInDatabase(
            'documents',
            ['description' => 'Something missing'],
            ['id' => array_values(array_intersect($assetDocIds, $allFSDocs))[0]],
        );
        // Break the IM
        // Note that array_values is used to reset the index to start from 0 after intersecting
        $allIMDocs = $I->grabColumnFromDatabase('documents', 'id', [
            'tag' => 'read_to_activate',
            'description' => 'Information Memorandum',
        ]);
        $I->updateInDatabase(
            'documents',
            ['description' => 'Something missing'],
            ['id' => array_values(array_intersect($assetDocIds, $allIMDocs))[0]],
        );
        // Break the AoA
        $allAoADocs = $I->grabColumnFromDatabase('documents', 'id', [
            'tag' => 'read_to_activate',
            'description' => 'Articles of Association',
        ]);
        $I->updateInDatabase(
            'documents',
            ['tag' => 'calculations'],
            ['id' => array_values(array_intersect($assetDocIds, $allAoADocs))[0]],
        );
        // Break the logo
        $allLogoDocs = $I->grabColumnFromDatabase('documents', 'id', ['tag' => 'logo']);
        $I->updateInDatabase(
            'documents',
            ['tag' => 'nologo'],
            ['id' => array_values(array_intersect($assetDocIds, $allLogoDocs))[0]],
        );
        // Break the property photo
        $allPropertyPhotoDocs = $I->grabColumnFromDatabase('documents', 'id', [
            'tag' => 'property_photos',
        ]);
        $I->updateInDatabase(
            'documents',
            ['tag' => 'Something missing'],
            [
                'id' => array_values(array_intersect(
                    $assetDocIds,
                    $allPropertyPhotoDocs,
                ))[0],
            ],
        );
        $I->amOnPage("/admin/products/{$sampleAssetId}");
        $I->see('Missing', '[data-field-name="asset-logo"]');
        $I->see('Missing', '[data-field-name="articles-of-association"]');
        $I->see('Missing', '[data-field-name="information-memorandum"]');
        $I->see('Missing', '[data-field-name="financial-summary"]');
        $I->see('Missing', '[data-field-name="property-photos"]');
        // Undo all the breakages to reset
        $I->updateInDatabase(
            'documents',
            ['description' => 'Financial Summary'],
            ['id' => array_values(array_intersect($assetDocIds, $allFSDocs))[0]],
        );
        $I->updateInDatabase(
            'documents',
            ['description' => 'Information Memorandum'],
            ['id' => array_values(array_intersect($assetDocIds, $allIMDocs))[0]],
        );
        $I->updateInDatabase(
            'documents',
            ['description' => 'Articles of Association'],
            ['id' => array_values(array_intersect($assetDocIds, $allAoADocs))[0]],
        );
        $I->updateInDatabase(
            'documents',
            ['tag' => 'read_to_activate'],
            ['id' => array_values(array_intersect($assetDocIds, $allAoADocs))[0]],
        );
        $I->updateInDatabase(
            'documents',
            ['tag' => 'logo'],
            ['id' => array_values(array_intersect($assetDocIds, $allLogoDocs))[0]],
        );
        $I->updateInDatabase(
            'documents',
            ['tag' => 'property_photos'],
            [
                'id' => array_values(array_intersect(
                    $assetDocIds,
                    $allPropertyPhotoDocs,
                ))[0],
            ],
        );
        $I->amOnPage("/admin/products/{$sampleAssetId}");
        $I->see('Found', '[data-field-name="asset-logo"]');
        $I->see('Found', '[data-field-name="articles-of-association"]');
        $I->see('Found', '[data-field-name="information-memorandum"]');
        $I->see('Found', '[data-field-name="financial-summary"]');
        $I->see('Found', '[data-field-name="property-photos"]');
    }

    /**
     * Empty asset: Silverhood Down - Brighton
     * Full Asset: Lodge de Lac - Cumbria
     *
     * Empty vs populated has at least 1 entry on each of the 4 sub-sections
     * - Shareholders
     * - Investments
     * - Listings
     * - Payments
     */

    /**
     * @group dashboard
     */
    public function checkStatusLogsSection(FunctionalTester $I): void
    {
        $emptyAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Silverhood Down - Brighton',
        ]);
        // $fullAsset = $I->grabFromDatabase('assets', 'id', [
        //     'name' => 'Lodge de Lac - Cumbria',
        // ]);

        // Should have no issues loading a "new" asset with no entries
        $I->amOnPage("/admin/products/{$emptyAsset}/status-logs");

        // Check sections and titles present
        $sections = [
            'status-logs' => 'Status Logs',
        ];
        // check section headers
        foreach ($sections as $sectionId => $sectionTitle) {
            $I->see($sectionTitle, "section#{$sectionId} h3");
        }

        // check table headers
        $headers = [
            'Id',
            'Status',
            'Notes',
            'Transitioned By',
            'OccuredAt',
            'CreatedAt',
            'Actions',
        ];
        // check table headers
        foreach ($headers as $name) {
            $I->see($name, 'table thead th');
        }

        $I->seeElement('#status-logs [data-field-name="current-status"]');
        $currentStatus = $I->grabTextFrom(
            '#status-logs [data-field-name="current-status"]',
        );

        $tableEntriesStart = count($I->grabMultiple('table tbody tr'));
        $I->click('Create State Transition and Log');
        $testId = bin2hex(random_bytes(8));
        $I->selectOption('#asset_status_log_status', 'cancelled');
        $I->fillField(
            '#asset_status_log_notes',
            "Test create asset status log {$testId}",
        );
        $I->click('Create New Asset Status Log');
        $I->seeCurrentUrlEquals("/admin/products/{$emptyAsset}/status-logs");
        $tableEntriesEnd = count($I->grabMultiple('table tbody tr'));
        $I->assertEquals(1, $tableEntriesEnd - $tableEntriesStart);

        $I->see('Cancelled', '[data-field-name="current-status"]');

        // Create a second one
        $I->click('Create State Transition and Log');
        $I->selectOption('#asset_status_log_status', 'active');
        $I->fillField(
            '#asset_status_log_notes',
            "Test create asset status log 2 {$testId}",
        );
        $I->click('Create New Asset Status Log');

        $I->see('Active', '[data-field-name="current-status"]');
        // Then update the first
        $I->click('Edit', 'table tbody tr:nth-last-child(2)');
        $I->see('Active', '[data-field-name="current-status"]');
        $I->seeOptionIsSelected('#asset_status_log_status', 'Cancelled');
        $testId2 = bin2hex(random_bytes(8));
        $I->seeInField(
            '#asset_status_log_notes',
            "Test create asset status log {$testId}",
        );
        $I->fillField('#asset_status_log_notes', "Updated asset status log {$testId2}");
        $I->click('Save Changes');
        // Asset current status is not updated, but notes text change is
        $I->see('Active', '[data-field-name="current-status"]');
        $I->see($testId2, 'table tbody tr:nth-last-child(2)');

        // Set all status logs back to the original for Silverhook Down
        $I->updateInDatabase(
            'asset_status_log',
            ['status' => lcfirst($currentStatus)],
            ['asset_id' => $emptyAsset],
        );
    }

    /**
     * @group dashboard
     */
    public function checkShareholdersSection(FunctionalTester $I): void
    {
        $emptyAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Silverhood Down - Brighton',
        ]);
        $fullAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Lodge de Lac - Cumbria',
        ]);

        // Should have no issues loading a "new" asset with no entries
        $I->amOnPage("/admin/products/{$emptyAsset}/shareholders");

        // Check sections and titles present
        $sections = [
            'shareholders' => 'Shareholders',
        ];
        // check section headers
        foreach ($sections as $sectionId => $sectionTitle) {
            $I->see($sectionTitle, "section#{$sectionId} h3");
        }

        // check table headers
        $headers = [
            'Shareholder',
            'Current Shareholding',
            'Original Shareholding',
            'Divested Shareholding',
            'Divestment Trades',
            'Repaid Shareholding',
        ];
        // check table headers
        foreach ($headers as $name) {
            $I->see($name, 'table thead th');
        }

        // Table should have at least 1 row if populated (i.e. it is loading in)
        $I->amOnPage("/admin/products/{$fullAsset}/shareholders");
        $I->seeNumberOfElements('table#trade-shareholdings-list tbody tr', [1, 10]); // minimum 1 entry, 10 max per page

        $I->seeElement('#trade-shareholders');
    }

    /**
     * @group dashboard
     */
    public function checkInvestmentsSection(FunctionalTester $I): void
    {
        $emptyAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Silverhood Down - Brighton',
        ]);
        $fullAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Lodge de Lac - Cumbria',
        ]);

        // Should have no issues loading a "new" asset with no entries
        $I->amOnPage("/admin/products/{$emptyAsset}/investments");

        // Check sections and titles present
        $sections = [
            'investments' => 'Investments',
        ];
        // check section headers
        foreach ($sections as $sectionId => $sectionTitle) {
            $I->see($sectionTitle, "section#{$sectionId} h3");
        }

        // check table headers
        $headers = [
            'Id',
            'Invested On',
            'Type',
            'Buyer',
            'Seller',
            'Amount',
            'Shares',
            'Offering',
            'Status',
        ];
        // check table headers
        foreach ($headers as $name) {
            $I->see($name, 'table thead th');
        }

        // Table should have at least 1 row if populated (i.e. it is loading in)
        $I->amOnPage("/admin/products/{$fullAsset}/investments");
        $I->seeNumberOfElements('table tbody tr', [1, 10]); // minimum 1 entry, 10 max per page
    }

    /**
     * @group dashboard
     */
    public function checkListingsSection(FunctionalTester $I): void
    {
        $emptyAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Silverhood Down - Brighton',
        ]);
        $fullAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Lodge de Lac - Cumbria',
        ]);

        // Should have no issues loading a "new" asset with no entries
        $I->amOnPage("/admin/products/{$emptyAsset}/listings");

        // Check sections and titles present
        $sections = [
            'listings' => 'Listings',
        ];
        // check section headers
        foreach ($sections as $sectionId => $sectionTitle) {
            $I->see($sectionTitle, "section#{$sectionId} h3");
        }

        // check table headers
        $headers = [
            'Id',
            'Created',
            'Seller',
            'Shares',
            'Offered',
            'Sold',
            'Investment',
            'Visibility',
            'Status',
            'Actions',
        ];
        // check table headers
        foreach ($headers as $name) {
            $I->see($name, 'table thead th');
        }

        // Table should have at least 1 row if populated (i.e. it is loading in)
        $I->amOnPage("/admin/products/{$fullAsset}/listings");
        $I->seeNumberOfElements('table tbody tr', [1, 10]); // minimum 1 entry, 10 max per page

        // Find a suitable offering in the fullAsset to publish
        $draftOfferingStatusIds = $I->grabColumnFromDatabase('offerings_status', 'id', [
            'lifecycleStatus' => OfferingLifecycle::STATE_SUBMITTED,
        ]);
        $draftOfferingIds = $I->grabColumnFromDatabase('offerings', 'id', [
            'name' => 'Lodge de Lac - Cumbria',
        ]);
        $draftOfferingId = array_values(array_intersect(
            $draftOfferingIds,
            $draftOfferingStatusIds,
        ))[0];

        // Try the visibility toggle
        $I->click(
            'Set visibility admin',
            '#listings tbody tr[data-object-id="' . $draftOfferingId . '"]',
        );
        $I->seeCurrentUrlEquals("/admin/products/{$fullAsset}/listings");
        $I->see('Visibility successfully changed to ' . BaseEntity::VISIBILITY_ADMIN);
        $I->see(
            'Admin Only',
            '#listings tbody tr[data-object-id="'
            . $draftOfferingId
            . '"] [data-field="visibility"]',
        );
        $I->click(
            'Set visibility auto',
            '#listings tbody tr[data-object-id="' . $draftOfferingId . '"]',
        );
        $I->seeCurrentUrlEquals("/admin/products/{$fullAsset}/listings");
        $I->see('Visibility successfully changed to ' . BaseEntity::VISIBILITY_AUTO);
        $I->see(
            'Auto',
            '#listings tbody tr[data-object-id="'
            . $draftOfferingId
            . '"] [data-field="visibility"]',
        );

        // Try the filters
        $I->seeLink('All Listings', "/admin/products/{$fullAsset}/listings");
        $I->click('First Party Only', '#product-listing-filter');
        $I->seeCurrentUrlEquals(
            "/admin/products/{$fullAsset}/listings?sell_investment=0",
        );
        // Should only be 1 result with the seller as "Yielders"
        $I->see('Yielders', 'td[data-field="seller"]');
        $I->seeNumberOfElements('#listings table tbody tr', 1);
        $I->click('Relistings Only', '#product-listing-filter');
        $I->seeCurrentUrlEquals(
            "/admin/products/{$fullAsset}/listings?sell_investment=1",
        );
        // Should not see Yielders in any of the seller fields
        $I->dontSee('Yielders', 'td[data-field="seller"]');
    }

    /**
     * @group dashboard
     */
    public function checkPaymentsSection(FunctionalTester $I): void
    {
        $emptyAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Silverhood Down - Brighton',
        ]);
        $fullAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Lodge de Lac - Cumbria',
        ]);

        // Should have no issues loading a "new" asset with no entries
        $I->amOnPage("/admin/products/{$emptyAsset}/payments");

        // Check sections and titles present
        $sections = [
            'dividends' => 'Dividends',
            'payments' => 'Payments',
        ];
        // check section headers
        foreach ($sections as $sectionId => $sectionTitle) {
            $I->see($sectionTitle, "section#{$sectionId} h3");
        }

        // check table headers
        $headers = [
            'Id',
            'Type',
            'Due Date',
            'Payee',
            'Amount',
            'Shareholding',
            'Transaction Id',
            'Created At',
        ];
        // check table headers
        foreach ($headers as $name) {
            $I->see($name, '#payments table thead th');
        }

        // Table should have at least 1 row if populated (i.e. it is loading in)
        $I->amOnPage("/admin/products/{$fullAsset}/payments");
        $I->seeNumberOfElements('#payments table tbody tr', [1, 10]); // minimum 1 entry, 10 max per page
    }

    /**
     * @group dashboard
     */
    public function checkPaymentOrderSection(FunctionalTester $I): void
    {
        $emptyAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Silverhood Down - Brighton',
        ]);
        $fullAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Lodge de Lac - Cumbria',
        ]);

        // Should have no issues loading a "new" asset with no entries
        $I->amOnPage("/admin/products/{$emptyAsset}/payment-orders");

        // Check sections and titles present
        $sections = [
            'payment-orders' => 'Payment Orders',
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
            'Scheduled For',
            'Payments',
            'Status',
            'Actions',
        ];
        // check table headers
        foreach ($headers as $name) {
            $I->see($name, '#payment-orders table thead th');
        }

        // Table should have at least 1 row if populated (i.e. it is loading in)
        $I->amOnPage("/admin/products/{$fullAsset}/payment-orders");
        $I->seeNumberOfElements('#payment-orders table tbody tr', [1, 10]); // minimum 1 entry, 10 max per page
    }

    /**
     * @group dashboard
     */
    public function checkTransferOrderSection(FunctionalTester $I): void
    {
        $emptyAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Silverhood Down - Brighton',
        ]);
        $fullAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Lodge de Lac - Cumbria',
        ]);

        // Should have no issues loading a "new" asset with no entries
        $I->amOnPage("/admin/products/{$emptyAsset}/transfer-orders");

        // Check sections and titles present
        $sections = [
            'transfer-orders' => 'Transfer Orders',
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
            'Scheduled For',
            'Transfers',
            'Status',
            'Actions',
        ];
        // check table headers
        foreach ($headers as $name) {
            $I->see($name, '#transfer-orders table thead th');
        }

        // Table should have at least 1 row if populated (i.e. it is loading in)
        $I->amOnPage("/admin/products/{$fullAsset}/transfer-orders");
        $I->seeNumberOfElements('#transfer-orders table tbody tr', [1, 10]); // minimum 1 entry, 10 max per page
    }

    /**
     * @group dashboard
     */
    public function checkDocumentsSection(FunctionalTester $I): void
    {
        $sampleAssetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Lodge de Lac - Cumbria',
        ]);

        $I->amOnPage("/admin/products/{$sampleAssetId}/documents");
        $I->seeLink(
            'Add Documents',
            "/admin/products/{$sampleAssetId}/editor/documents",
        );
        // Check sections and titles present
        $sections = [
            'documents-checklist' => 'Documents Checklist',
            'asset-documents' => 'Asset Documents',
        ];
        // check section headers
        foreach ($sections as $sectionId => $sectionTitle) {
            $I->see($sectionTitle, "section#{$sectionId} h3");
        }
        // check table of the checklist
        $I->seeNumberOfElements('#documents-checklist table tbody tr', 5);
        $I->see('Asset Logo', '#documents-checklist tr[data-doc-type="logo"]');
        $I->see(
            'Articles of Association',
            '#documents-checklist tr[data-doc-type="articles-of-association"]',
        );
        $I->see(
            'Information Memorandum',
            '#documents-checklist tr[data-doc-type="information-memorandum"]',
        );
        $I->see(
            'Financial Summary',
            '#documents-checklist tr[data-doc-type="financial-summary"]',
        );
        $I->see(
            'Property Photos',
            '#documents-checklist tr[data-doc-type="property-photos"]',
        );
        $headers = [
            'Type',
            'Required Number',
            'Required Tag',
            'Required Description',
            'Matches Found',
            'Requirements met?',
        ];
        // check table headers
        foreach ($headers as $name) {
            $I->see($name, '#documents-checklist table thead th');
        }

        // check table headers of the doc lists
        $headers = [
            'doc id',
            'File name',
            'File description',
            'Tag',
            'Has Url',
            'Actions',
        ];
        // check table headers
        foreach ($headers as $name) {
            $I->see($name, '#asset-documents table thead th');
        }

        // Table should have at least 1 row if populated (i.e. it is loading in)
        $I->seeNumberOfElements('#asset-documents table tbody tr', [1, 20]); // at least 1
    }

    public function checkTradeOrdersSection(FunctionalTester $I): void
    {
        $emptyAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Silverhood Down - Brighton',
        ]);
        $fullAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Lodge de Lac - Cumbria',
        ]);

        $I->amOnPage("/admin/products/{$emptyAsset}/trade-orders");
        $I->seeElement('#trade-orders table');

        $I->amOnPage("/admin/products/{$fullAsset}/trade-orders");

        // Note the table columns and filters differ between generic, user, product views
        $elements = [
            'Id',
            'Type',
            'Direction',
            'User',
            'Progress',
            'Quantity Available',
            'Quantity Traded',
            'Quantity Listed',
            'Price',
            'Derived Value',
            'Fees',
            'Taxes',
            'Status',
            'Created',
        ];
        // check table headers
        $I->loopCheckElements($elements, 'thead th');

        $filterLabels = [
            'Trade order id',
            'User id',
            'Username',
            'Direction',
            'Status',
            'Type',
            'CreatedAt Start',
            'CreatedAt End',
            'Items Per Page',
            'Order by',
            'Order Direction',
        ];
        // check table filters
        $I->loopCheckElements($filterLabels, '//form//label|//form//legend');

        // $I->seeNumberOfElements('#trade-orders-list tbody tr', 10);
        // $I->selectOption('form select[name=perPage]', '5');
        // $I->click('Apply Filters');
        // $I->seeNumberOfElements('#trade-orders-list tbody tr', 5);
        // // check max page bracketing (to deal with filter changing)
        // $I->amOnPage('/admin/trade-orders?page=1000');
        // // Sends you to last page
        // $I->seeElement(['css' => '.pagination li:last-child.disabled']);
    }

    public function checkShareTradesSection(FunctionalTester $I): void
    {
        $emptyAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Silverhood Down - Brighton',
        ]);
        $fullAsset = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Lodge de Lac - Cumbria',
        ]);

        $I->amOnPage("/admin/products/{$emptyAsset}/share-trades");
        $I->seeElement('#share-trades table');

        $I->amOnPage("/admin/products/{$fullAsset}/share-trades");

        // Note the table columns and filters differ between generic, user, product views
        $elements = [
            'Id',
            'Sell Order',
            'Buy Order',
            'Seller',
            'Buyer',
            'Quantity',
            'Price',
            'Value',
            'Fees',
            'Taxes',
            'Status',
            'Created',
        ];
        // check table headers
        $I->loopCheckElements($elements, 'thead th');

        $filterLabels = [
            'Share trade id',
            'User id',
            'Sell Order id',
            'Seller id',
            'Seller username',
            'Buy order id',
            'Buyer id',
            'Buyer username',
            'Sell order type',
            'Buy order type',
            'Status',
            'CreatedAt Start',
            'CreatedAt End',
            'Items Per Page',
            'Order by',
            'Order Direction',
        ];
        // check table filters
        $I->loopCheckElements($filterLabels, '//form//label|//form//legend');

        // $I->seeNumberOfElements('#trade-orders-list tbody tr', 10);
        // $I->selectOption('form select[name=perPage]', '5');
        // $I->click('Apply Filters');
        // $I->seeNumberOfElements('#trade-orders-list tbody tr', 5);
        // // check max page bracketing (to deal with filter changing)
        // $I->amOnPage('/admin/trade-orders?page=1000');
        // // Sends you to last page
        // $I->seeElement(['css' => '.pagination li:last-child.disabled']);
    }
}
