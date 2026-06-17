<?php

namespace App\Tests\Functional\Cms;

use App\Tests\Support\FunctionalTester;

class ExportCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    /**
     * @dataProvider exportNameProvider
     */
    public function testExportButtonDownload(
        FunctionalTester $I,
        \Codeception\Example $example,
    ) {
        // Repeat for new reports hub
        $I->amOnPage('/admin/export/hub');

        $I->click('#customise-' . $example['exportName']);
        $I->click('Export Report');
        $I->seeResponseCodeIsSuccessful();
    }

    protected function exportNameProvider()
    {
        yield ['exportName' => 'share-trade-register'];
        yield ['exportName' => 'share-trades'];
        yield ['exportName' => 'trade-orders'];

        yield ['exportName' => 'assets'];
        yield ['exportName' => 'offerings'];
        yield ['exportName' => 'investments'];
        yield ['exportName' => 'users'];
        // yield ['exportName' => 'payouts'];
        yield ['exportName' => 'investment-payouts'];
        yield ['exportName' => 'transactions'];
        yield ['exportName' => 'contego-logs'];
        yield ['exportName' => 'legacy-shareholdings'];
        yield ['exportName' => 'legacy-share-trades'];
        yield ['exportName' => 'share-register'];
        yield ['exportName' => 'share-register-with-wallet-ids'];
        yield ['exportName' => 'legacy-extended-shareholdings'];
    }

    public function testReportBuilder(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/export/hub');
        $I->click('#customise-share-trade-register');
        // Note the space is url encoded as %20
        $I->seeCurrentUrlMatches('~^/admin/export/orm-builder/share_trade_register~');
        $I->see('Report Builder');

        // See at least 1 field customisation
        $I->seeElement('#export_report_customiser_reportFields_0');

        // Check other configurables
        $I->seeElement('#export_report_customiser_exportFormat');
        $I->seeElement('#export_report_customiser_createdAt_gte');
        $I->seeElement('#export_report_customiser_createdAt_lt');
        $I->seeElement('#export_report_customiser_assetId');
        $I->seeElement('#export_report_customiser_buyerId');
        $I->seeElement('#export_report_customiser_sellerId');

        $I->click('Clear all fields');
        $I->dontSeeCheckboxIsChecked('#export_report_customiser_reportFields_3');
        $I->click('Enable all fields');
        $I->seeCheckboxIsChecked('#export_report_customiser_reportFields_7');

        $I->click('Export Report');
        $I->seeResponseCodeIsSuccessful();
    }
}
