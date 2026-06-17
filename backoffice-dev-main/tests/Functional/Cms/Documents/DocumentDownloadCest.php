<?php

namespace App\Tests\Functional\Cms\Documents;

use App\Tests\Support\FunctionalTester;

class DocumentDownloadCest
{
    private ?string $docId = null;
    private ?string $docUrl = null;

    public function _before(FunctionalTester $I)
    {
        $this->docId = $I->grabFromDatabase('asset_docs', 'document_id', [
            'id' => '1',
        ]);
        $this->docUrl = $I->grabFromDatabase('documents', 'documentUrl', [
            'id' => $this->docId,
        ]);
        $I->loginAdmin();
    }

    public function _after(FunctionalTester $I)
    {
        $I->updateInDatabase(
            'documents',
            ['documentUrl' => $this->docUrl],
            ['id' => $this->docId],
        );
    }

    /**
     * @group detailview
     * @group document
     */
    public function checkDocDownloadWithoutUrl(FunctionalTester $I)
    {
        $I->updateInDatabase(
            'documents',
            ['documentUrl' => null],
            ['id' => $this->docId],
        );
        $I->amOnPage('/admin/assetdocument/1/edit');
        $I->click('Download');
        $I->seeCurrentUrlEquals('/admin');
        $I->see('Unable to retrieve document without a url');
    }
}
