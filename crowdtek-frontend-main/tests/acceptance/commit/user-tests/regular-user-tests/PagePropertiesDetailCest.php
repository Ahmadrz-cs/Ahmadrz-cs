<?php


class PagePropertiesDetailCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->loginWithCredentials($I->reg_user_name, $I->admin_user_password);
    }

    public function _after(AcceptanceTester $I) {}

    /**
     * @group properties
     */
    public function checkPropertyOverview(\Step\Acceptance\StaticPages $I)
    {
        $I->amOnPage('/properties/1');
        $I->seeElement("#product-info article:first-child .card .card-img-top"); // asset logo/photo
        $I->seeElement("#product-info article:first-child .card-body .card-title");
        $I->seeElement('#product-info article:first-child .card-body table [data-field-name="net-projected-yield"]');
        $I->seeElement('#product-info article:first-child .card-body table [data-field-name="target-end-date"]');
        $I->seeElement('#product-info article:first-child .card-body table [data-field-name="price"]');
        $I->seeElement('#product-info article:first-child .card-body table [data-field-name="shares-available"]');

        // Offer/listing selection
        $I->seeElement('#selected-order [data-field-name="share-price"]');
        $I->seeElement('#selected-order [data-field-name="shares-available"]');
        $I->seeElement('#selected-order [data-field-name="offered-by"]');
        $I->see('Currently Selected', '#selected-order');

        // Docs for investment form
        $I->seeElement('#product-invest-review form');
        $I->seeElementInDOM('#product-invest-review form input#docsReviewed');
        $I->see('Invest', '#product-invest-review form button');

        // Calculator
        $I->scrollTo(".calc-detail");
        $I->see("Investment Calculator");
        $I->seeElement(".calc");

        // Info tabs
        $I->scrollTo("#propertyTab", 0, -160);
        $I->click("a#ProductDetails-tab");
        $I->seeElement("div#productDtls.active");
        $I->click("a#location-tab");
        $I->seeElement("div#location.active");
        $I->click("a#documents-tab");
        $I->seeElement("div#documents.active");

        // Gallery
        $I->scrollTo("//div[@class='d-block d-md-flex flex-column align-items-center mt-0 mt-lg-8']");
        $I->see("Property Gallery");
    }

    /**
     * @group properties
     */
    public function checkArchivedPropertyOverview(\Step\Acceptance\StaticPages $I)
    {
        // New Orchid House is an archived asset
        $I->amOnPage('/properties/35');
        $I->seeElement("#product-info article:first-child .card .card-img-top");
        $I->seeElement("#product-info article:first-child .card-body .card-title");
        $I->seeElement('#product-info article:first-child .card-body table [data-field-name="net-projected-yield"]');
        $I->seeElement('#product-info article:first-child .card-body table [data-field-name="target-end-date"]');
        $I->seeElement('#product-info article:first-child .card-body table [data-field-name="price"]');
        $I->dontSeeElement('#product-info article:first-child .card-body table [data-field-name="shares-available"]');

        // No offer/listing selection for archived - just shows as sold out
        $I->see("No offers available");
        $I->dontSeeElement('#selected-order [data-field-name="share-price"]');
        $I->dontSeeElement('#selected-order [data-field-name="shares-available"]');
        $I->dontSeeElement('#selected-order [data-field-name="offered-by"]');
        $I->dontSee('Currently Selected', '#selected-order');

        // Docs for investment form
        $I->dontSeeElement('#product-invest-review form');
        $I->dontSeeElementInDOM('#product-invest-review form input#docsReviewed');
        $I->see('This asset is currently closed for new investments');

        // Calculator
        $I->scrollTo(".calc-detail");
        $I->see("Investment Calculator");
        $I->seeElement(".calc");

        // Info tabs
        $I->scrollTo("#propertyTab", 0, -160);
        $I->click("a#ProductDetails-tab");
        $I->seeElement("div#productDtls.active");
        $I->click("a#location-tab");
        $I->seeElement("div#location.active");
        $I->click("a#documents-tab");
        $I->seeElement("div#documents.active");

        // Gallery
        $I->scrollTo("//div[@class='d-block d-md-flex flex-column align-items-center mt-0 mt-lg-8']");
        $I->see("Property Gallery");
    }
}
