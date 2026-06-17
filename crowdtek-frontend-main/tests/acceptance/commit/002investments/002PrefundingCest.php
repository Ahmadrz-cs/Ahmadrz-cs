<?php

// 004PrefundingCest.php

class PrefundingCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->loginWithCredentials($I->approved_investor_2, $I->admin_user_password, skipScaCheck: false);
    }

    public function _after(AcceptanceTester $I) {}

    public function testTopYielderPortfolio(AcceptanceTester $I)
    {
        $I->amOnPage('/my-portfolio');
        $I->wait(1);
        $I->see('Top Yielders', '.nav-link');
        $I->click('Top Yielders', '#portfolio-nav');
        $I->see('Top Yielders', '.nav-link.active');
    }

    public function testPrefundingOverview(AcceptanceTester $I)
    {
        $I->amOnPage("/prefunding-properties");
        $I->scrollTo('#products-list');
        // This is Quayside Apartments A - Bristol which is prefunding
        $I->seeLink("See Property Details", "/properties/24");
        $I->amOnPage("/properties/24");
        $I->waitForText('To invest and prefund this asset');
        $I->scrollTo('#product-invest-review');
        $I->clickWithLeftButton(['css' => 'label[for="docsReviewed"]']);
        $I->click('Invest');
        $I->wait(1);
        $I->waitForElement("#prefunding-total-value");
        // This is the standard initial sell-order for Quayside Apartments A - Bristol
        $I->seeCurrentUrlEquals('/properties/24/prefund/019d4338-2ffd-7d95-b271-909daeab3488');

        $I->see('Asset Name', '#opportunity-info');
        $I->see('Shares Available', '#opportunity-info');
        $I->see('Share Price', '#opportunity-info');

        // see initial
        $I->seeInFormFields('form[name=prefunding]', [
            'prefunding[numberOfShares]' => '57',
            'prefunding-total-value' => '100.32',
            'prefunding[sharesToKeep]' => '0',
            'prefunding-keep-value' => '0.00',
        ]);
        // $I->seeInField(['id' => 'prefunding_numberOfShares'], '57');
        // $I->seeInField(['id' => 'prefunding-total-value'], '100.32');
        // $I->seeInField(['id' => 'prefunding_sharesToKeep'], '0');
        // $I->seeInField(['id' => 'prefunding-keep-value'], '0.00');
        $I->see('57', ['css' => '#summary-total :nth-child(2)']);
        $I->see('100.32', ['css' => '#summary-total :nth-child(3)']);
        $I->see('57', ['css' => '#summary-prefunding :nth-child(2)']);
        $I->see('100.32', ['css' => '#summary-prefunding :nth-child(3)']);
        $I->see('0', ['css' => '#summary-keep :nth-child(2)']);
        $I->see('0.00', ['css' => '#summary-keep :nth-child(3)']);

        // Checkup prompts if not PS22/10 ready
        $obpId = $I->grabFromDatabase('users', 'onboardingProfile_id', ['username' => $I->approved_investor_2]);
        $futureDt = new \DateTime('+6 hours');
        $I->updateInDatabase('onboarding_profile', ['cooloffEnd' => $futureDt->format('Y-m-d H:i:s')], ['id' => $obpId]);
        $I->updateInDatabase('onboarding_profile', ['cooloffAccepted' => 0], ['id' => $obpId]);
        // Refresh userInfo via /checkup
        $I->amOnPage('/checkup');
        $I->click('Update Now');
        $I->wait(1);
        $I->amOnPage("/properties/24");
        $I->wait(1);
        $I->waitForText('Cooling off period');
        $I->see('Cooling off period', '#checkup-prompt');
        $I->dontSee('Please update your investor profile', '#checkup-prompt');

        $oldDt = new \DateTime('-2 days');
        $I->updateInDatabase('onboarding_profile', ['cooloffEnd' => $oldDt->format('Y-m-d H:i:s')], ['id' => $obpId]);
        // Refresh userInfo via /checkup
        $I->amOnPage('/checkup');
        $I->click('Update Now');
        $I->wait(1);
        $I->amOnPage("/properties/24");
        $I->wait(1);
        $I->dontSee('Cooling off period', '#checkup-prompt');
        $I->see('Please update your investor profile', '#checkup-prompt');

        $I->updateInDatabase('onboarding_profile', ['cooloffAccepted' => 1], ['id' => $obpId]);
        // Refresh userInfo via /checkup
        $I->amOnPage('/checkup');
        $I->click('Update Now');
        $I->wait(1);
        $I->amOnPage("/properties/24");
        $I->dontSeeElement('#checkup-prompt');
    }

    public function testPrefundingFormRetention(AcceptanceTester $I)
    {
        $variants = [
            [
                'inputKeep' => "0",
                'totalShares' => "57",
                'totalValue' => "100.32",
                'keepShares' => "0",
                'keepValue' => "0.00",
                'prefundShares' => "57",
                'prefundValue' => "100.32",
            ],
            [
                'inputKeep' => "10",
                'totalShares' => "57",
                'totalValue' => "100.32",
                'keepShares' => "10",
                'keepValue' => "17.60",
                'prefundShares' => "47",
                'prefundValue' => "82.72",
            ],
            [
                'inputKeep' => "100",
                'totalShares' => "57",
                'totalValue' => "100.32",
                'keepShares' => "14",
                'keepValue' => "24.64",
                'prefundShares' => "43",
                'prefundValue' => "75.68",
            ],
        ];

        foreach ($variants as $e) {
            $I->amOnPage("/properties/24/prefund/019d4338-2ffd-7d95-b271-909daeab3488");
            $I->wait(1);
            $I->scrollTo(['css' => 'form[name=prefunding]']);
            $I->fillField(['name' => 'prefunding[sharesToKeep]'], $e['inputKeep']);
            $I->clickWithLeftButton(['name' => 'prefunding-keep-value']);
            $I->seeInFormFields('form[name=prefunding]', [
                'prefunding[numberOfShares]' => $e['totalShares'],
                'prefunding-total-value' => $e['totalValue'],
                'prefunding[sharesToKeep]' => $e['keepShares'],
                'prefunding-keep-value' => $e['keepValue'],
            ]);
            $I->see($e['totalShares'], ['css' => '#summary-total :nth-child(2)']);
            $I->see($e['totalValue'], ['css' => '#summary-total :nth-child(3)']);
            $I->see($e['prefundShares'], ['css' => '#summary-prefunding :nth-child(2)']);
            $I->see($e['prefundValue'], ['css' => '#summary-prefunding :nth-child(3)']);
            $I->see($e['keepShares'], ['css' => '#summary-keep :nth-child(2)']);
            $I->see($e['keepValue'], ['css' => '#summary-keep :nth-child(3)']);
        }
    }

    public function testPrefundingFormMinMaxCommit(AcceptanceTester $I)
    {
        $I->amOnPage("/properties/24/prefund/019d4338-2ffd-7d95-b271-909daeab3488");
        $I->wait(1);
        $I->scrollTo(['css' => 'form[name=prefunding]']);

        $I->fillField(['name' => 'prefunding[numberOfShares]'], '1000');
        $I->clickWithLeftButton(['name' => 'prefunding-keep-value']);
        $I->seeInFormFields('form[name=prefunding]', [
            'prefunding[numberOfShares]' => '852',
            'prefunding-total-value' => '1499.52',
            'prefunding[sharesToKeep]' => '0',
            'prefunding-keep-value' => '0.00',
        ]);
        $I->see('852', ['css' => '#summary-total :nth-child(2)']);
        $I->see('1499.52', ['css' => '#summary-total :nth-child(3)']);
        $I->see('852', ['css' => '#summary-prefunding :nth-child(2)']);
        $I->see('1499.52', ['css' => '#summary-prefunding :nth-child(3)']);
        $I->see('0', ['css' => '#summary-keep :nth-child(2)']);
        $I->see('0.00', ['css' => '#summary-keep :nth-child(3)']);

        $I->clearField(['name' => 'prefunding[numberOfShares]']);
        $I->clickWithLeftButton(['name' => 'prefunding-keep-value']);
        $I->wait(4);
        $I->seeInFormFields('form[name=prefunding]', [
            'prefunding[numberOfShares]' => '57',
            'prefunding-total-value' => '100.32',
            'prefunding[sharesToKeep]' => '0',
            'prefunding-keep-value' => '0.00',
        ]);
        $I->see('57', ['css' => '#summary-total :nth-child(2)']);
        $I->see('100.32', ['css' => '#summary-total :nth-child(3)']);
        $I->see('57', ['css' => '#summary-prefunding :nth-child(2)']);
        $I->see('100.32', ['css' => '#summary-prefunding :nth-child(3)']);
        $I->see('0', ['css' => '#summary-keep :nth-child(2)']);
        $I->see('0.00', ['css' => '#summary-keep :nth-child(3)']);
    }

    public function testPrefundOpportunity(AcceptanceTester $I)
    {
        // Clear portfolio cache in case of previous test runs
        $I->amOnPage("/my-portfolio?refreshPortfolio=1");
        $I->wait(1);
        $I->waitForText("Combination of the total currently invested");

        // Grab pre data
        $I->scrollTo('#pending-investments', 0, -50);
        $unsettledStart = array_filter($I->grabMultiple('#pending-investments .card-list article', 'data-uuid'));

        $I->amOnPage("/properties/24/prefund/019d4338-2ffd-7d95-b271-909daeab3488");
        $assetName = $I->grabTextFrom('#asset-name');
        $I->scrollTo(['css' => 'form[name=prefunding]']);
        $I->fillField(['name' => 'prefunding[sharesToKeep]'], '10');
        $I->click('Invest Now');
        $I->completeScaVerification($I::JIM_MP_EMAIL);
        $I->waitForText('investment was successfully submitted', 12);

        // check investments successfully created
        $I->seeCurrentUrlEquals('/my-portfolio');
        // Reload page to get rid of the modal
        $I->amOnPage('/my-portfolio');

        // Grab post data
        $I->scrollTo('#pending-investments', 0, -50);
        $unsettledEnd = array_filter($I->grabMultiple('#pending-investments .card-list article', 'data-uuid'));

        // Check most recent unsettled investment
        $I->assertEquals(count($unsettledStart) + 1, count($unsettledEnd));
        $I->see('Quayside Apartments A - Bristol', '#pending-investments .card-list article:first-child [data-field-name="asset-name"]');
        // Note that 57 shares is the min commit of £100
        $I->see('57', '#pending-investments .card-list article:first-child [data-field-name="shares"]');
        $I->see('Unsettled', '#pending-investments .card-list article:first-child [data-field-name="status"]');


        // check prefunding portion in top yielders part of portfolio
        $I->amOnPage('/my-portfolio/top-yielders');
        $I->seeLink(strtoupper('Top Yielders'), '/my-portfolio/top-yielders');
        $I->see('Top Yielders', '#portfolio-nav .nav-link.active');
        $I->see($assetName, ['css' => 'tr:nth-child(1) td[data-label="Asset Name"]']);
        $I->see('47', ['css' => 'tr:nth-child(1) td[data-label="Original Shares"]']);
    }
}
