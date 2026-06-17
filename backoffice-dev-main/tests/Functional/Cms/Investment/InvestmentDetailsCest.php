<?php

namespace App\Tests\Functional\Cms\Investment;

use App\Tests\Support\FunctionalTester;

class InvestmentDetailsCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    /**
     * @group detailview
     */
    public function checkAddInvestmentDocumentAction(FunctionalTester $I)
    {
        $I->amOnPage('/admin/investment');

        $id = $I->getInvestmentId('FIRST');
        $I->amOnPage('/admin/investment/' . $id . '/add_document');

        $I->seeResponseCodeIs(200);
    }

    /**
     * @group detailview
     */
    public function checkInvestmentEdit(FunctionalTester $I)
    {
        $I->amOnPage('/admin/investment/1/edit');

        $I->see('Add Document', "a[href='/admin/investment/1/add_document']");

        // changing investment details
        $I->click('input#investment_type_0');
        $I->fillField('input#investment_investmentValue', 123.45);
        $I->fillField('input#investment_share_amount', 123);
        $I->fillField('input#investment_orgPricePerShare', 12);
        $I->fillField('input#investment_term', 3);
        $I->fillField('input#investment_comments', 'investment comment');
        $I->fillField('input#investment_numberOfShares', 123);
        $I->seeElement('input#investment_divested_amount', ['disabled' => 'disabled']);
        $I->seeElement('input#investment_divested_shares', ['disabled' => 'disabled']);
        $I->seeElement('#investment_status_lifecycleStatus', [
            'disabled' => 'disabled',
        ]);

        $I->click('button#investment_submit');
        $I->see('Investment updated successfully');

        // check changes
        $I->amOnPage('/admin/investment/1/edit');

        $I->seeInField('input#investment_investmentValue', '123.45');
        $I->seeInField('input#investment_share_amount', 123);
        $I->seeInField('input#investment_orgPricePerShare', '12.00');
        $I->seeInField('input#investment_term', 3);
        $I->seeInField('input#investment_comments', 'investment comment');
        $I->seeInField('input#investment_numberOfShares', 123);

        $I->seeElement('#timestamp');
        $I->seeElement('#blame');
    }

    /**
     * @group detailview
     */
    public function checkInvestmentEditSettlementDate(FunctionalTester $I)
    {
        /*
         * Javascript disables the Settled On field when loaded in a browser,
         * so these steps can not be replicated inside a browser without manually disabling the js.
         */
        $settledInvestment = $I->searchDatabaseByStatus('investments', 'settled');
        $I->amOnPage('/admin/investment/' . $settledInvestment . '/edit');

        $I->click('input#investment_type_0');
        $I->fillField('input#investment_share_amount', 123);
        $I->selectOption('select#investment_status_settledOn_month', '7');
        $I->selectOption('select#investment_status_settledOn_day', '5');
        $I->selectOption(
            'select#investment_status_settledOn_year',
            (string) idate('Y'),
        );

        $I->click('button#investment_submit');

        // check changes
        $I->amOnPage('/admin/investment/' . $settledInvestment . '/edit');

        $I->seeInField('select#investment_status_settledOn_month', '7');
        $I->seeInField('select#investment_status_settledOn_day', '5');
        $I->seeInField('select#investment_status_settledOn_year', (string) idate('Y'));
    }

    /**
     * @group detailview
     */
    public function testStatusRecord(FunctionalTester $I)
    {
        $statuses = [
            'open',
            'rejected',
            'approved',
            'withdrawn',
            'settled',
        ];
        foreach ($statuses as $status) {
            $sampleId = $I->grabFromDatabase('investments_status', 'id', [
                'lifecycleStatus' => $status,
            ]);
            // $dashName = str_replace('_', '-', $status);
            $I->amOnPage("/admin/investment/$sampleId/edit");
            $I->see(
                ucwords(str_replace('_', ' ', $status)),
                '#status-record tbody tr.active',
            );
        }
    }
}
