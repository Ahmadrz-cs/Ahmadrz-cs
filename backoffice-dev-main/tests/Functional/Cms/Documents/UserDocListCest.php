<?php

namespace App\Tests\Functional\Cms\Documents;

use App\Tests\Support\FunctionalTester;

class UserDocListCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    /**
     * @group detailview
     * @group document
     */
    public function checkAddDocument(FunctionalTester $I)
    {
        $I->amOnPage('/admin/userdocument');
        $I->seeLink('Add Document');
        $expected = 4;
        $I->amOnPage('/admin/userdocument/add?user=' . $expected);
        $actual = $I->grabAttributeFrom(
            '#user_document_user option[selected]',
            'value',
        );
        $I->assertEquals($expected, $actual);

        $description = bin2hex(random_bytes(8)) . 'description';
        $tag = bin2hex(random_bytes(8)) . 'tag';

        $I->attachFile(
            'input#user_document_document_0_file',
            'uploads/public/fixtures/Test_PDF.pdf',
        );
        $I->fillField('input#user_document_document_0_description', $description);
        $I->fillField('input#user_document_document_0_tag', $tag);
        $I->click('button#user_document_submit');
    }

    /**
     * @group listview
     * @group document
     * @depends checkAddDocument
     */
    public function checkDeleteDocument(FunctionalTester $I)
    {
        $I->amOnPage('/admin/userdocument');
        $docID = $I->grabTextFrom('tbody :nth-child(1) td:nth-child(1) a');
        $I->amOnPage('/admin/userdocument/' . $docID . '/delete');
        $I->dontSee($docID, 'tbody :nth-child(1) td:nth-child(1) a');
    }

    /**
     * @group listview
     */
    public function checkListViewElements(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/userdocument');

        $I->seeLink('Add Document', '/admin/userdocument/add');

        $elements = [
            '#',
            'User',
            'File name',
            'File description',
            'Tag',
            'Has Url',
            'Actions',
        ];
        $I->loopCheckElements($elements, 'thead th');

        $filterLabels = [
            'Document tag',
            'User id',
        ];
        $I->loopCheckElements($filterLabels, 'form label');

        $I->amOnPage('/admin/userdocument');
        $I->seeNumberOfElements('#userlist_doc tbody tr', 10);
        $I->selectOption('form select[name=perPage]', '5');
        $I->click('Apply Filters');
        $I->seeNumberOfElements('#userlist_doc tbody tr', 5);

        // check max page bracketing (to deal with filter changing)
        $I->amOnPage('/admin/userdocument?page=1000');
        // Sends you to last page
        $I->seeElement(['css' => '.pagination li:last-child.disabled']);
    }

    /**
     * @group listview
     * @dataProvider filterProvider
     */
    public function checkListViewFilters(
        FunctionalTester $I,
        \Codeception\Example $example,
    ): void {
        if (empty($example['dbquery'])) {
            $example['dbquery'] = $example['filters'];
        }
        $count = $I->grabNumRecords('user_docs', $example['dbquery']);
        $I->amOnPage('/admin/userdocument?' . http_build_query($example['filters']));
        $I->seeNumberOfElements('#userlist_doc tbody tr', min(10, $count));
        $I->see($count, '#list-meta-results');
    }

    /**
     * @group listview
     */
    public function checkUserDocPresetFilters(FunctionalTester $I)
    {
        $I->amOnPage('/admin/userdocument');
        $I->seeLink('Proof of Id', '/admin/userdocument?documentTag=proof_of_identity');
        $I->seeLink(
            'Proof of Address',
            '/admin/userdocument?documentTag=proof_of_address',
        );
        $I->seeLink(
            'Proof of Company',
            '/admin/userdocument?documentTag=proof_of_company',
        );
        $I->seeLink('Proof of Funds', '/admin/userdocument?documentTag=proof_of_funds');
    }

    protected function filterProvider(): array
    {
        return [
            [
                'filters' => [
                    'userId' => 1,
                ],
                'dbquery' => [
                    'user_id' => 1,
                ],
            ],
        ];
    }
}
