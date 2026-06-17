<?php

use AppBundle\Entity\Enum\ScaStatus;

class ScaEnrollmentCest
{
    private ?int $userId = null;
    private ?int $reviewId = null;

    public function _before(AcceptanceTester $I)
    {
        // Select a user who is SCA enrolled and disable their enrollment status
        // We'll use Jim who is a VIP who can view top yielder opportunities
        $this->userId = $I->grabFromDatabase('users', 'id', ['username' => $I->approved_investor_2]);
        $I->updateInDatabase(
            'users',
            ['scaStatus' => ScaStatus::Inactive->value],
            [
                'id' => $this->userId,
            ],
        );
        // Change the Mangopay user ID to one that isn't SCA enrolled
        $I->updateInDatabase(
            'users',
            ['mangoPayUserId' => 'user_m_01HW5QVGKQCGD5TQ9V6ZPDMTK9'],
            [
                'id' => $this->userId,
            ],
        );
    }

    public function _after(AcceptanceTester $I)
    {
        // Reset SCA enrollment status for reruns and other tests
        $I->updateInDatabase(
            'users',
            ['scaStatus' => ScaStatus::Active->value],
            [
                'id' => $this->userId,
            ],
        );
        // Change the Mangopay user ID back to the normal one for Jim
        $I->updateInDatabase(
            'users',
            ['mangoPayUserId' => 'user_m_01HW3FDNVC6EG3QWBESED863WC'],
            [
                'id' => $this->userId,
            ],
        );
    }

    public function checkScaEnrollmentPrompt(AcceptanceTester $I)
    {
        $I->loginWithCredentials($I->approved_investor_2, $I->admin_user_password, false);
        $retail = $I->grabFromDatabase(
            'assets',
            'id',
            ["name" => "Partingdale House A - Reading"],
        );
        $prefunding = $I->grabFromDatabase(
            'assets',
            'id',
            ["name" => "Quayside Apartments A - Bristol"],
        );

        // User should see prompt when trying to invest
        // Retail investing
        $I->amOnPage("/properties/{$retail}");
        $I->waitForElement("#checkup-prompt");
        $I->see('Please update your investor profile', '#checkup-prompt');
        $I->seeLink("Go to your Profile", "/my-profile/dashboard");
        // VIP investing
        $I->amOnPage("/properties/{$prefunding}");
        $I->waitForElement("#checkup-prompt");
        $I->see('Please update your investor profile', '#checkup-prompt');
        $I->seeLink("Go to your Profile", "/my-profile/dashboard");

        // // Relisting is also blocked if not SCA enrolled
        // $I->amOnPage('/my-investments');
        // $I->waitForElement(".transaction-history");
        // $I->scrollTo('#pills-investments table', 0, -100);
        // $I->click('#pills-investments table tbody tr:first-child td[data-label="Sell Investment"] a');
        // $I->wait($I->animation_time);
        // $I->clickWithLeftButton('#check_confirm_sell_investment');
        // $I->click('#sell_my_invest_btn');
        // $I->see('Please update your investor profile', '#checkup-prompt');
        // $I->seeLink("Go to your Profile", "/my-profile/dashboard");

        // From your profile, there should be an SCA enrollment prompt
        $I->amOnPage('/my-profile/dashboard');
        $I->waitForElement('#sca-enrollment-prompt');
        $I->click("Start SCA Setup");
        // Clicking it should send you to the Mangopay SCA session
        $I->waitForText('Start your verification');

        $expectedReturnUrl = urlencode("{$I->getAppBaseHost()}/sca/enrollment/callback");
        /**
         * Regex is
         * - delimited by ~
         * - accepts anything at the start with .+ (any single character one or more times)
         * - Anything between \Q and \E is treated as a string literal to avoid manually escaping all the URL encoded symbols like %
         */
        $regex = "~.+\Q&returnUrl={$expectedReturnUrl}\E~";
        $I->seeCurrentUrlMatches($regex);
    }
}
