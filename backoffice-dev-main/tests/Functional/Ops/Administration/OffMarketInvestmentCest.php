<?php

namespace App\Tests\Functional\Ops\Administration;

use App\Tests\Support\FunctionalTester;

class OffMarketInvestmentCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function checkCanAddOffMarketInvestment(FunctionalTester $I)
    {
        $I->amOnPage('/admin/investment/add');
        $I->seeResponseCodeIs(200);
        $I->seeLink('Cancel', '/admin/investment');

        // The below investment is based on the fixtures set in admins.yml
        $I->selectOption('#investment_status_lifecycleStatus', 'settled');
        $I->fillField('input#investment_investmentValue', '110');
        $I->fillField('input#investment_share_amount', '100');
        $I->fillField('input#investment_orgPricePerShare', '1.1');
        $I->fillField('input#investment_term', '5');
        $I->click('button#investment_submit');
    }
}
