<?php

namespace App\Tests\Functional\Cms\Documents;

use App\Tests\Support\FunctionalTester;

class ShareCertificateUploaderCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    /**
     * @group listview
     * @group document
     */
    public function checkElements(FunctionalTester $I)
    {
        $I->amOnPage('/admin/investmentdocument/certificate-uploader');

        $elements = [
            'Id',
            'Asset name',
            'SPV',
            'Investor username',
            'Investor name',
            'Created',
            'Type',
            'Status',
        ];
        $locator = 'thead tr th';
        $I->loopCheckElements($elements, $locator);
    }

    /**
     * @group listview
     * @group document
     */
    public function uploadDocument(FunctionalTester $I)
    {
        $I->amOnPage('/admin/investmentdocument/certificate-uploader');
        $investmentId = $I->grabAttributeFrom([
            'css' => 'select option:nth-child(2)',
        ], 'value');
        $I->selectOption('form select[id=investment_certificate_investment]', [
            'value' => $investmentId,
        ]);
        $I->attachFile(
            'input#investment_certificate_document_file',
            'uploads/private/fixtures/share_certificate.jpg',
        );
        $I->click('button#investment_certificate_submit');
        $I->see('Succesfully uploaded document');

        // cleanup by deleting the just uploaded doc
        $investmentDocId = $I->grabFromDatabase('investment_docs', 'id', [
            'investment_id' => $investmentId,
        ]);
        $I->amOnPage('/admin/investmentdocument/' . $investmentDocId . '/delete');
        $I->seeCurrentUrlEquals('/admin/investmentdocument');
    }
}
