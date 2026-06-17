<?php

namespace App\Tests\Functional\Cms\KycReports;

use App\Tests\Support\FunctionalTester;

class KycReportCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    /**
     * @group listview
     */
    public function checkListTableHeaders(FunctionalTester $I)
    {
        $I->amOnPage('/admin/kyc-reports');
        $I->seeLink('Go to KYC Hub', '/admin/kyc');

        $elements = [
            'Id',
            'Subject',
            'Outcome',
            'KYC Provider',
            'Reference Id',
            'Check Type',
            'Result',
            'Score',
            'Checked At',
            'Actions',
        ];

        $locator = 'th';

        $I->loopCheckElements($elements, $locator);
    }

    /**
     * @group detailview
     */
    public function checkKycReportView(FunctionalTester $I)
    {
        $I->amOnPage('/admin/kyc-reports/1');
        $I->seeElement('#immutable-warning');
        $elements = [
            'Report Id',
            'KYC Check Subject',
            'Is KYC Verified',
            'Provider',
            'Reference Id',
            'Check Type',
            'Result',
            'Score',
            'Checked At',
            'Notes',
        ];

        $locator = '#kyc-report';

        $I->loopCheckElements($elements, $locator);
    }
}
