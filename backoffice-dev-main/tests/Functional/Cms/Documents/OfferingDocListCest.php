<?php

namespace App\Tests\Functional\Cms\Documents;

use App\Tests\Support\FunctionalTester;

class OfferingDocListCest
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
        $I->amOnPage('/admin/offeringdocument');
        $I->seeLink('Add Document');
        $expected = 4;
        $I->amOnPage('/admin/offeringdocument/add?offering=' . $expected);
        $actual = $I->grabAttributeFrom(
            '#offering_document_offering option[selected]',
            'value',
        );
        $I->assertEquals($expected, $actual);

        $description = bin2hex(random_bytes(8)) . 'description';
        $tag = bin2hex(random_bytes(8)) . 'tag';
        $I->attachFile(
            'input#offering_document_document_0_file',
            'uploads/public/fixtures/Test_PDF.pdf',
        );
        $I->fillField('input#offering_document_document_0_description', $description);
        $I->fillField('input#offering_document_document_0_tag', $tag);
        $I->click('button#offering_document_submit');
    }

    /**
     * @group listview
     * @group document
     * @depends checkAddDocument
     */
    public function checkDeleteDocument(FunctionalTester $I)
    {
        $I->amOnPage('/admin/offeringdocument');
        $docID = $I->grabTextFrom('tbody :nth-child(1) td:nth-child(1) a');
        $I->amOnPage('/admin/offeringdocument/' . $docID . '/delete');
        $I->dontSee($docID, 'tbody :nth-child(1) td:nth-child(1) a');
        $I->seeCurrentUrlEquals('/admin/offeringdocument');
    }

    /**
     * @group listview
     */
    public function checkListViewElements(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/offeringdocument');

        $I->seeLink('Add Document', '/admin/offeringdocument/add');

        $elements = [
            '#',
            'Offering',
            'File name',
            'File description',
            'Tag',
            'Has Url',
            'Actions',
        ];
        $I->loopCheckElements($elements, 'thead th');

        $filterLabels = [
            'Document tag',
            'Offering id',
        ];
        $I->loopCheckElements($filterLabels, 'form label');

        $I->amOnPage('/admin/offeringdocument');
        $I->seeNumberOfElements('#offeringlist_doc tbody tr', 10);
        $I->selectOption('form select[name=perPage]', '5');
        $I->click('Apply Filters');
        $I->seeNumberOfElements('#offeringlist_doc tbody tr', 5);

        // check max page bracketing (to deal with filter changing)
        $I->amOnPage('/admin/offeringdocument?page=1000');
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
        $count = $I->grabNumRecords('offering_docs', $example['dbquery']);
        $I->amOnPage(
            '/admin/offeringdocument?' . http_build_query($example['filters']),
        );
        $I->seeNumberOfElements('#offeringlist_doc tbody tr', min(10, $count));
        $I->see($count, '#list-meta-results');
    }

    /**
     * @group listview
     */
    public function checkOfferingDocPresetFilters(FunctionalTester $I)
    {
        $I->amOnPage('/admin/offeringdocument');
        $I->seeLink('Calculations', '/admin/offeringdocument?documentTag=calculations');
    }

    protected function filterProvider(): array
    {
        return [
            [
                'filters' => [
                    'offeringId' => 1,
                ],
                'dbquery' => [
                    'off_id' => 1,
                ],
            ],
        ];
    }
}
