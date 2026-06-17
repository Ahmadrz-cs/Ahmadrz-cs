<?php


class PageAboutUsContactUsCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->amOnPage('/contact-us');
        $I->waitForText('Contact Us');
    }

    public function _after(AcceptanceTester $I)
    {
    }

    /**
     * @group about_us
     */
    public function checkContactInfo(\Step\Acceptance\StaticPages $I)
    {
        $I->seeElement(".hero-banner");
        $I->seeElement("#phone-number p a[href*='+442072054650']");
        $I->seeElement("#email p a[href*='team@yielders.co.uk']");
        // $I->seeElement("#chat-box #start_live_chat");
        $I->seeElement(".col-12.text-center h3");
        // $I->scrollTo(".col-12.text-center h3");
        // $I->seeElement(".gmap_canvas");
    }
}
