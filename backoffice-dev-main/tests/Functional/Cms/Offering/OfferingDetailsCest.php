<?php

namespace App\Tests\Functional\Cms\Offering;

use App\Tests\Support\FunctionalTester;

class OfferingDetailsCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    /**
     * @group detailview
     */
    public function checkOfferingDetailViewElements(FunctionalTester $I)
    {
        $I->amOnPage('/admin/offering/add');

        $elements = [
            'Asset',
            'Name',
            'Category',
            'Funding goal',
            'External commitments',
            'Visibility',
            'Created by id',
            'Is Featured',
            'Is Secondary Market Listing',
            'Investment',
            'Valuation',
            'Equity offered',
            'No of shares',
            'Price per share',
            'Gross projected return',
            'Gross rent projected',
            'Net rent projected',
            'Offering term',
            'Open date',
            'Close date',
            'Minimum commit',
            'Maximum commit',
            'Max over funding',
            'Last updated:',
            'Created:',
        ];

        $I->loopCheckElements($elements);
    }

    /**
     * @group detailview
     */
    public function checkOfferingEdit(FunctionalTester $I)
    {
        $I->amOnPage('/admin/offering');

        //Take a random offering
        $id = $I->getOfferingId('RANDOM');

        $I->amOnPage('/admin/offering/' . $id . '/edit');

        $I->see(
            'Add Document',
            "a[href='/admin/offeringdocument/add?offering=" . $id . "']",
        );

        //Filling up the form
        $I->fillField('input#offering_name', 'test new offering update 2');
        $I->fillField('input#offering_fundingGoal', '10000000');
        $I->fillField('input#offering_maxOverFunding', '30000000');
        $I->fillField('input#offering_grossProjectReturn', 10);
        $I->fillField('input#offering_grossRentProjected', 5);
        $I->fillField('input#offering_netRentProjected', 10);
        $I->selectOption('input[name="offering[offeringType]"]', 'prefunding');
        $I->click('button#offering_submit');

        //Checking changes
        $I->amOnPage('/admin/offering/' . $id . '/edit');
        $I->canSeeInField('input#offering_fundingGoal', '10000000.00');
        $I->canSeeInField('input#offering_maxOverFunding', '30000000.00');
        $I->canSeeInField('input#offering_name', 'test new offering update 2');
        $I->canSeeInField('input#offering_grossProjectReturn', '10.00');
        $I->canSeeInField('input#offering_grossRentProjected', '5.00');
        $I->canSeeInField('input#offering_netRentProjected', '10.00');
        $I->seeOptionIsSelected('input[name="offering[offeringType]"]', 'prefunding');

        //Checking name changes in DB
        // $I->grabAttributeFrom('#offering_name', 'test new offering update 2');
        $name = $I->getOfferingName($id);
        $I->assertEquals($name, 'test new offering update 2');
    }

    /**
     * @group detailview
     */
    public function checkAddOffering(FunctionalTester $I)
    {
        $I->amOnPage('/admin/offering/add');

        //Filling up the form
        $I->selectOption('#offering_asset', '1');
        $I->fillField('input#offering_name', 'test new offering 2');
        $I->fillField('input#offering_fundingGoal', '10000000');
        $I->fillField('input#offering_noOfShares', '1000000');
        $I->fillField('input#offering_pricePerShare', '1');
        $I->fillField('input#offering_maxOverFunding', '20000000');

        $I->click('button#offering_submit');
        $I->amOnPage('/admin/offering?isSecondaryMrkt=');
        $I->see('test new offering 2', 'tbody tr td');
    }

    /**
     * @group detailview
     */
    public function testStatusRecord(FunctionalTester $I)
    {
        $statuses = [
            'draft',
            'submitted',
            'approved',
            'published',
            // 'archived', // missing from offering lifecycle status
            'cancelled',
            'rejected',
            'closed',
            'restricted',
        ];
        foreach ($statuses as $status) {
            $sampleId = $I->grabFromDatabase('offerings_status', 'id', [
                'lifecycleStatus' => $status,
            ]);
            // $dashName = str_replace('_', '-', $status);
            $I->amOnPage("/admin/offering/$sampleId/edit");
            $I->see(
                ucwords(str_replace('_', ' ', $status)),
                '#status-record tbody tr.active',
            );
        }
    }
}
