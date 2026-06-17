<?php

namespace App\Tests\Functional\Cms\Documents;

use App\Tests\Support\FunctionalTester;

class InvestmentDocListCest
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
        $settledInvestment = $I->searchDatabaseByStatus('investments', 'settled');
        $I->amOnPage('/admin/investment/' . $settledInvestment . '/add_document');

        $description = bin2hex(random_bytes(8)) . 'description';
        $tag = bin2hex(random_bytes(8)) . 'tag';

        $I->attachFile(
            'input#investment_document_document_0_file',
            'uploads/public/fixtures/Test_PDF.pdf',
        );
        $I->fillField('input#investment_document_document_0_description', $description);
        $I->fillField('input#investment_document_document_0_tag', $tag);
        $I->click('button#investment_document_submit');
        $I->seeResponseCodeIs(200);
    }

    /**
     * @group listview
     * @group document
     * @depends checkAddDocument
     */
    public function checkDeleteDocument(FunctionalTester $I)
    {
        $I->amOnPage('/admin/investmentdocument');
        $docID = $I->grabTextFrom('tbody :nth-child(1) td:nth-child(1) a');
        $I->amOnPage('/admin/investmentdocument/' . $docID . '/delete');
        $I->dontSee($docID, 'tbody :nth-child(1) td:nth-child(1) a');
    }

    /**
     * @group listview
     */
    public function checkListViewElements(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/investmentdocument');

        $I->seeLink(
            'Share Certificate Upload Centre',
            '/admin/investmentdocument/certificate-uploader',
        );

        $elements = [
            '#',
            'Investment',
            'File name',
            'File description',
            'Tag',
            'Has Url',
            'Actions',
        ];
        $I->loopCheckElements($elements, 'thead th');

        $filterLabels = [
            'Document tag',
            'Investment id',
            'User id',
        ];
        $I->loopCheckElements($filterLabels, 'form label');

        $I->amOnPage('/admin/investmentdocument');
        $I->seeNumberOfElements('#investmentlist_doc tbody tr', 10);
        $I->selectOption('form select[name=perPage]', '5');
        $I->click('Apply Filters');
        $I->seeNumberOfElements('#investmentlist_doc tbody tr', 5);

        // check max page bracketing (to deal with filter changing)
        $I->amOnPage('/admin/investmentdocument?page=1000');
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
        $count = $I->grabNumRecords('investment_docs', $example['dbquery']);
        $I->amOnPage(
            '/admin/investmentdocument?' . http_build_query($example['filters']),
        );
        $I->seeNumberOfElements('#investmentlist_doc tbody tr', min(10, $count));
        $I->see($count, '#list-meta-results');
    }

    /**
     * @group listview
     */
    public function checkInvestmentDocPresetFilters(FunctionalTester $I)
    {
        $I->amOnPage('/admin/investmentdocument');
        $I->seeLink(
            'Share Certificates',
            '/admin/investmentdocument?documentTag=share_certificate',
        );
    }

    protected function filterProvider(): array
    {
        return [
            [
                'filters' => [
                    'investmentId' => 1,
                ],
                'dbquery' => [
                    'investment_id' => 1,
                ],
            ],
        ];
    }
}
