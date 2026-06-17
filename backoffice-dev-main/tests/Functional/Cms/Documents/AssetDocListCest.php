<?php

namespace App\Tests\Functional\Cms\Documents;

use App\Tests\Support\FunctionalTester;

class AssetDocListCest
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
        $I->amOnPage('/admin/assetdocument');
        $I->seeLink('Add Document');
        $expected = 4;
        $I->amOnPage('/admin/assetdocument/add?asset=' . $expected);
        $actual = $I->grabAttributeFrom(
            '#asset_document_asset option[selected]',
            'value',
        );
        $I->assertEquals($expected, $actual);

        $description = bin2hex(random_bytes(8)) . 'description';
        $tag = bin2hex(random_bytes(8)) . 'tag';
        $I->attachFile(
            'input#asset_document_document_file',
            'uploads/public/fixtures/Test_PDF.pdf',
        );
        $I->fillField('input#asset_document_document_description', $description);
        $I->fillField('input#asset_document_document_tag', $tag);
        $I->click('button#asset_document_submit');
    }

    /**
     * @group listview
     * @group document
     * @depends checkAddDocument
     */
    public function checkDeleteDocument(FunctionalTester $I)
    {
        $I->amOnPage('/admin/assetdocument');
        $docID = $I->grabTextFrom('tbody :nth-child(1) td:nth-child(1) a');
        $I->amOnPage('/admin/assetdocument/' . $docID . '/delete');
        $I->dontSee($docID, 'tbody :nth-child(1) td:nth-child(1) a');
        $I->seeCurrentUrlEquals('/admin/assetdocument');
    }

    /**
     * @group listview
     */
    public function checkListViewElements(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/assetdocument');

        $I->seeLink('Add Document', '/admin/assetdocument/add');

        $elements = [
            '#',
            'Asset',
            'File name',
            'File description',
            'Tag',
            'Has Url',
            'Actions',
        ];
        $I->loopCheckElements($elements, 'thead th');

        $filterLabels = [
            'Document tag',
            'Asset id',
        ];
        $I->loopCheckElements($filterLabels, 'form label');

        $I->amOnPage('/admin/assetdocument');
        $I->seeNumberOfElements('#assetlist_doc tbody tr', 10);
        $I->selectOption('form select[name=perPage]', '5');
        $I->click('Apply Filters');
        $I->seeNumberOfElements('#assetlist_doc tbody tr', 5);

        // check max page bracketing (to deal with filter changing)
        $I->amOnPage('/admin/assetdocument?page=1000');
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
        $count = $I->grabNumRecords('asset_docs', $example['dbquery']);
        $I->amOnPage('/admin/assetdocument?' . http_build_query($example['filters']));
        $I->seeNumberOfElements('#assetlist_doc tbody tr', min(10, $count));
        $I->see($count, '#list-meta-results');
    }

    /**
     * @group listview
     */
    public function checkAssetDocPresetFilters(FunctionalTester $I)
    {
        $I->amOnPage('/admin/assetdocument');
        $I->seeLink('Logo', '/admin/assetdocument?documentTag=logo');
        $I->seeLink(
            'Property photos',
            '/admin/assetdocument?documentTag=property_photos',
        );
        $I->seeLink(
            'Read to Activate',
            '/admin/assetdocument?documentTag=read_to_activate',
        );
    }

    protected function filterProvider(): array
    {
        return [
            [
                'filters' => [
                    'assetId' => 1,
                ],
                'dbquery' => [
                    'asset_id' => 1,
                ],
            ],
        ];
    }
}
