<?php

namespace App\Tests\Functional\Cms\ContegoLog;

use App\Tests\Support\FunctionalTester;

class ContegoLogListCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    /**
     * @group listview
     */
    public function checkElements(FunctionalTester $I)
    {
        $I->amOnPage('/admin/contegoLog');
        $I->seeLink('Go to KYC Hub', '/admin/kyc');
        $elements = [
            'Submitted On',
            'Profile Name',
            'Rag',
            'kyc Score',
            'kyc type',
            'ext reference id',
            'User',
        ];

        $locator = 'th';

        $I->loopCheckElements($elements, $locator);
    }
}
