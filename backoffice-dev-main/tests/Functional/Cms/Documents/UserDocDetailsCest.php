<?php

namespace App\Tests\Functional\Cms\Documents;

use App\Tests\Support\FunctionalTester;

class UserDocDetailsCest
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
        $I->amOnPage('/admin/userdocument/1/edit');

        $description = bin2hex(random_bytes(8)) . 'description';
        $tag = bin2hex(random_bytes(8)) . 'tag';

        //check fields can be edited
        $I->fillField('input#user_document_document_description', $description);
        $I->fillField('input#user_document_document_tag', $tag);
        $I->click('button#user_document_submit');

        $I->amOnPage('/admin/userdocument/1/edit');
        $I->seeInField('input#user_document_document_description', $description);
        $I->seeInField('input#user_document_document_tag', $tag);

        $I->seeElement('#timestamp');
        $I->seeElement('#blame');
    }

    /**
     * @group detailview
     * @group document
     */
    public function checkDocDownload(FunctionalTester $I)
    {
        $I->amOnPage('/admin/userdocument/1/edit');
        $I->click('Download');
        $I->seeResponseCodeIsSuccessful();
        $I->dontSee('Unabled to retrieve document from file store');
    }
}
