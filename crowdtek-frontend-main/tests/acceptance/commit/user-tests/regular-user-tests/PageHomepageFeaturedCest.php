<?php

class PageHomepageFeaturedCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->loginWithCredentials($I->reg_user_name, $I->admin_user_password, false);
        $I->amOnPage('/');
    }

    public function _after(AcceptanceTester $I) {}

    /**
     * @group homepage
     */
    public function checkFeaturedPropertiesAndLink(AcceptanceTester $I)
    {
        $I->scrollTo("#featured-products");
        $I->waitForText("Featured Properties");
        $I->seeElement("#featured-products article:first-child .card .card-img-top"); // asset logo/photo
        $I->seeElement("#featured-products article:first-child .card-body .card-title");
        $I->seeElement('#featured-products article:first-child .card-body table [data-field-name="net-projected-yield"]');
        $I->seeElement('#featured-products article:first-child .card-body table [data-field-name="target-end-date"]');
        $I->dontSeeElement('#featured-products article:first-child .card-body table [data-field-name="price"]');
        $I->dontSeeElement('#featured-products article:first-child .card-body table [data-field-name="shares-available"]');
        $I->seeLink('View More Properties', '/current-properties');
    }
}
