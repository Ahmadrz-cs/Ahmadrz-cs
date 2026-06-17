<?php

namespace App\Tests\Functional\Cms\Asset;

use App\Tests\Support\FunctionalTester;

class AssetDetailsCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    /**
     * @group detailview
     */
    public function checkAssetDetailViewElements(FunctionalTester $I)
    {
        $I->amOnPage('/admin/asset/add');

        $elements = [
            'Cancel',
            'Name',
            'Alternate name',
            'Brief description',
            'Company number',
            'Display name',
            'Detailed desc',
            'Org email',
            'Sector',
            'Tax id',
            'Telephone',
            'Funding goal',
            'Amount of shares',
            'Setup fee',
            'Admin fee',
            'Management fee',
            'Profit share',
            'Stamp duty user',
            'Asset type',
            'Investment term',
            'Financial Year Start',
            'Gross rental return p a',
            'Net rental return p a',
            'Gross capital appreciation',
            'Net capital appreciation',
            'Net capital appreciation yield',
            'Points of interest',
            'Featured',
            'Buy restricted',
            'Sell restricted',
            'Price per share',
            'Additional type',
            'Visibility',
            'Created by id',
            'Hold wallet id',
            'Settlement wallet id',
            'Deposit wallet id',
            'Expenses wallet id',
            'Tax wallet id',
            'Distribution wallet id',
            'Treasury wallet id',
            'Last updated:',
            'Created:',
        ];

        $I->loopCheckElements($elements);
    }

    /**
     * @group detailview
     */
    public function checkAssetAddNew(FunctionalTester $I)
    {
        $I->amOnPage('/admin/asset/add');

        $I->see('Launching a New Asset?', 'section#dashboard-prompt');

        $randomString = bin2hex(random_bytes(8));
        //Filling up the form
        $I->fillField('input#asset_name', "{$randomString} test new asset");

        $I->click('button#asset_submit');
        $I->amOnPage('/admin/asset');
        $I->see('test new asset');
    }

    /**
     * @group detailview
     */
    public function checkAssetEdit(FunctionalTester $I)
    {
        $randomString = bin2hex(random_bytes(8));

        // Create a new asset for editing to avoid conflicts
        $I->amOnPage('/admin/asset/add');
        $I->fillField('input#asset_name', "{$randomString} AstUpdate");
        $I->click('button#asset_submit');
        $id = $I->grabFromDatabase('assets', 'id', [
            'name' => "{$randomString} AstUpdate",
        ]);

        $I->amOnPage('/admin/asset/' . $id . '/edit');

        $I->see('Add Document', "a[href='/admin/assetdocument/add?asset=" . $id . "']");
        $I->seeLink('Go to Product Dashboard', "/admin/products/{$id}");

        // Fill fields
        $I->fillField('input#asset_name', "{$randomString} NameChanged");
        $I->fillField('input#asset_holdWalletId', '12345612' . $randomString);
        $I->fillField('input#asset_settlementWalletId', '12345612' . $randomString);
        $I->fillField('input#asset_depositWalletId', '12345612' . $randomString);
        $I->fillField('input#asset_expensesWalletId', '12345612' . $randomString);
        $I->fillField('input#asset_taxWalletId', '12345612' . $randomString);
        $I->fillField('input#asset_distributionWalletId', '12345612' . $randomString);
        $I->fillField('input#asset_treasuryWalletId', '12345612' . $randomString);

        $I->click('button#asset_submit');
        $I->canSeeResponseCodeIs(200);

        // Check changes saved
        $I->amOnPage('/admin/asset/' . $id . '/edit');
        $I->canSeeInField('input#asset_holdWalletId', '12345612' . $randomString);
        $I->canSeeInField('input#asset_settlementWalletId', '12345612' . $randomString);
        $I->canSeeInField('input#asset_depositWalletId', '12345612' . $randomString);
        $I->canSeeInField('input#asset_expensesWalletId', '12345612' . $randomString);
        $I->canSeeInField('input#asset_taxWalletId', '12345612' . $randomString);
        $I->canSeeInField(
            'input#asset_distributionWalletId',
            '12345612' . $randomString,
        );
        $I->canSeeInField('input#asset_treasuryWalletId', '12345612' . $randomString);
    }

    /**
     * @group detailview
     */
    public function testStatusRecord(FunctionalTester $I)
    {
        $statuses = [
            'draft',
            'submitted',
            'approved',
            'published',
            'archived',
            'cancelled',
            'rejected',
        ];
        foreach ($statuses as $status) {
            $sampleId = $I->grabFromDatabase('assets_status', 'id', [
                'lifecycleStatus' => $status,
            ]);
            // $dashName = str_replace('_', '-', $status);
            $I->amOnPage("/admin/asset/$sampleId/edit");
            $I->see(
                ucwords(str_replace('_', ' ', $status)),
                '#status-record tbody tr.active',
            );
        }

        $I->seeElement('#status-section [data-field-name="current-status"]');
        $I->seeElement('#status-section table#status-logs');

        $tableEntriesStart = count($I->grabMultiple('table#status-logs tbody tr'));
        $I->click('Create State Transition and Log');
        $testId = bin2hex(random_bytes(8));
        $I->selectOption('#asset_status_log_status', 'draft');
        $I->fillField(
            '#asset_status_log_notes',
            "Test create asset status log {$testId}",
        );
        $I->click('Create New Asset Status Log');
        $I->seeCurrentUrlEquals("/admin/asset/$sampleId/edit");
        $tableEntriesEnd = count($I->grabMultiple('table#status-logs tbody tr'));
        $I->assertEquals(1, $tableEntriesEnd - $tableEntriesStart);

        $I->see('Draft', '[data-field-name="current-status"]');

        // Create a second one
        $I->click('Create State Transition and Log');
        $I->selectOption('#asset_status_log_status', 'cancelled');
        $I->fillField(
            '#asset_status_log_notes',
            "Test create asset status log 2 {$testId}",
        );
        $I->click('Create New Asset Status Log');

        $I->see('Cancelled', '[data-field-name="current-status"]');
        // Then update the first
        $I->click('Edit', 'table#status-logs tbody tr:nth-last-child(2)');
        $I->see('Cancelled', '[data-field-name="current-status"]');
        $I->seeOptionIsSelected('#asset_status_log_status', 'Draft');
        $testId2 = bin2hex(random_bytes(8));
        $I->seeInField(
            '#asset_status_log_notes',
            "Test create asset status log {$testId}",
        );
        $I->fillField('#asset_status_log_notes', "Updated asset status log {$testId2}");
        $I->click('Save Changes');
        // Asset current status is not updated, but notes text change is
        $I->see('Cancelled', '[data-field-name="current-status"]');
        $I->see($testId2, 'table#status-logs tbody tr:nth-last-child(2)');
    }
}
