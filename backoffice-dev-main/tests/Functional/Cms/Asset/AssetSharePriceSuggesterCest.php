<?php

namespace App\Tests\Functional\Cms\Asset;

use App\Tests\Support\FunctionalTester;

class AssetSharePriceSuggesterCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function checkSharePriceSuggester(FunctionalTester $I)
    {
        $I->amOnPage('/admin/utilities/asset-share-price');
        $elements = [
            'Target Raise / Funding Goal',
            'Minimum Permitted Share Price',
            'Maximum Permitted Share Price',
        ];
        $I->loopCheckElements($elements, 'label');

        // Prime number
        $I->fillField('#fundingGoal', '128563');
        $I->click('Generate Suggestions');
        $I->seeCurrentUrlEquals('/admin/utilities/asset-share-price');
        $I->see('£1.29 - £2.58', '[data-field-name="search-range"]');
        $I->seeNumberOfElements('#share-price-suggestions tbody tr', 0);
        $I->see(
            'No suggested share prices could be found. Try expanding the search range',
        );

        $I->fillField('#fundingGoal', '128563');
        $I->fillField('#sharePriceFloor', '0.8');
        $I->fillField('#sharePriceCap', '2.55');
        $I->click('Generate Suggestions');
        $I->see('£0.80 - £2.55', '[data-field-name="search-range"]');
        $I->see('1', '[data-field-name="suitable-prices-found"]');
        $I->see('£1.00', '[data-field-name="lowest-suitable-price"]');
        $I->see('£1.00', '[data-field-name="highest-suitable-price"]');
        $I->seeNumberOfElements('#share-price-suggestions tbody tr', 1);
        $I->see('a prime number');

        // Divisible number
        $I->amOnPage('/admin/utilities/asset-share-price');
        $I->fillField('#fundingGoal', '156152');
        $I->click('Generate Suggestions');
        $I->see('£1.57 - £3.13', '[data-field-name="search-range"]');
        $I->see('4', '[data-field-name="suitable-prices-found"]');
        $I->see('£1.60', '[data-field-name="lowest-suitable-price"]');
        $I->see('£2.98', '[data-field-name="highest-suitable-price"]');
        $I->seeNumberOfElements('#share-price-suggestions tbody tr', 4);

        $elements = [
            'Share Price',
            'Shares to Issue',
            'Minimum Commit',
        ];
        $I->loopCheckElements($elements, '#share-price-suggestions thead th');
    }
}
