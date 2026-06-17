<?php

namespace App\Tests\Functional\Cms\Documents;

use App\Tests\Support\FunctionalTester;

class InvestmentDocDetailsCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    /**
     * @group detailview
     * @group document
     */
    public function checkDocEdit(FunctionalTester $I)
    {
        $I->amOnPage('/admin/investmentdocument/1/edit');

        $description = bin2hex(random_bytes(8)) . 'description';
        $tag = bin2hex(random_bytes(8)) . 'tag';

        //check fields can be edited
        $I->fillField('input#investment_document_document_description', $description);
        $I->fillField('input#investment_document_document_tag', $tag);
        $I->click('button#investment_document_submit');

        $I->amOnPage('/admin/investmentdocument/1/edit');
        $I->seeInField('input#investment_document_document_description', $description);
        $I->seeInField('input#investment_document_document_tag', $tag);

        $I->seeElement('#timestamp');
        $I->seeElement('#blame');
    }

    /**
     * @group detailview
     * @group document
     */
    public function checkDocDownload(FunctionalTester $I)
    {
        $I->amOnPage('/admin/investmentdocument/2/edit');
        $I->click('Download');
        $I->amOnPage('/admin/investmentdocument/4/edit');
        $I->click('Download');
        $I->seeResponseCodeIsSuccessful();
        $I->dontSee('Unabled to retrieve document from file store');
    }
}
