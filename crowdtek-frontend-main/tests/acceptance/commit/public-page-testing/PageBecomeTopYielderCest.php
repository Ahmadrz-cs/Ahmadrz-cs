<?php


class PageBecomeTopYielderCest
{
    public function _before(\Step\Acceptance\StaticPages $I)
    {
        $I->amOnPage('/become-top-yielder');
        $I->waitForText('Becoming a Top Yielder');
    }

    public function _after(\Step\Acceptance\StaticPages $I)
    {
    }

    /**
     * @group top_yielder
     */
    public function checkBecomeTopYielderSections(\Step\Acceptance\StaticPages $I)
    {
        $I->seeElement("//div[@class='hero-banner become-top-yielder banner-medium bg-fixed bg-overlay']");
        $I->seeElement(".hero-banner");
        $I->seeElement("//div[@class='top-yielder-block pt-2 pt-lg-7 mb-7']");
        $I->scrollTo("//div[@class='benefits-section top-yielder-block']");
        $I->waitForText("The Benefits");
        $I->seeElement("//div[@class='full-block top-yielder bg-light pt-4 mb-0 mb-lg-8 pb-4 pb-lg-0']");
        $I->seeElement("//div[@class='top-yielder-block my-properties b-top  pt-5 mt-0 mt-lg-8']");
    }
}
