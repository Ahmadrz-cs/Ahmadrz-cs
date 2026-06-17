<?php


class PagePropertiesListCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->loginWithCredentials($I->reg_user_name, $I->admin_user_password);
    }

    public function _after(AcceptanceTester $I) {}


    /**
     * @group properties
     */
    public function checkCurrentPropertiesList(\Step\Acceptance\StaticPages $I)
    {
        $I->amOnPage('/current-properties');
        $I->waitForText("Properties", 5, ".hero-banner-hd");

        $I->scrollTo("#products-list");

        $I->seeElement("#products-list article:first-child .card .card-img-top"); // asset logo/photo
        $I->seeElement("#products-list article:first-child .card-body .card-title");
        $I->seeElement('#products-list article:first-child .card-body table [data-field-name="net-projected-yield"]');
        $I->seeElement('#products-list article:first-child .card-body table [data-field-name="target-end-date"]');
        $I->seeElement('#products-list article:first-child .card-body table [data-field-name="price"]');
        $I->seeElement('#products-list article:first-child .card-body table [data-field-name="shares-available"]');
        $I->seeElement("#products-list article:first-child a");
    }

    /**
     * @group properties
     */
    public function checkArchivedPropertiesList(\Step\Acceptance\StaticPages $I)
    {
        $I->amOnPage('/current-properties');
        $I->waitForText("Properties", 5, ".hero-banner-hd");

        $I->scrollTo("#products-list");

        $I->seeElement("#products-list article:first-child .card .card-img-top"); // asset logo/photo
        $I->seeElement("#products-list article:first-child .card-body .card-title");
        $I->seeElement('#products-list article:first-child .card-body table [data-field-name="net-projected-yield"]');
        $I->seeElement('#products-list article:first-child .card-body table [data-field-name="target-end-date"]');
        $I->seeElement('#products-list article:first-child .card-body table [data-field-name="price"]');
        $I->seeElement('#products-list article:first-child .card-body table [data-field-name="shares-available"]');
        $I->seeElement("#products-list article:first-child a");
    }
}
