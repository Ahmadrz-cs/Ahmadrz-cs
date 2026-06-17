<?php


class PageHomepageUserExperienceCest
{
    public function _before(\Step\Acceptance\StaticPages $I)
    {
        $I->amOnPage('/');
    }

    public function _after(AcceptanceTester $I) {}

    /**
     * @group homepage
     */
    public function checkHero(\Step\Acceptance\StaticPages $I)
    {
        $I->wantTo('Check hero');
        $I->amOnPage('/');
        $I->seeElement(".hero-banner");
        $I->scrollTo(".hero-banner .btn");
    }

    /**
     * @group homepage
     */
    public function checkFeaturedProperties(\Step\Acceptance\StaticPages $I)
    {
        $I->scrollTo("#featured-products");
        $I->waitForText("Featured Properties");
        $I->seeElement("#featured-products article:first-child .card .card-img-top"); // asset logo/photo
        $I->seeElement("#featured-products article:first-child .card-body .card-title");
        $I->seeElement('#featured-products article:first-child .card-body table [data-field-name="net-projected-yield"]');
        $I->seeElement('#featured-products article:first-child .card-body table [data-field-name="target-end-date"]');
        $I->dontSeeElement('#featured-products article:first-child .card-body table [data-field-name="price"]');
        $I->dontSeeElement('#featured-products article:first-child .card-body table [data-field-name="shares-available"]');
        $I->dontSeeLink('View More Properties', '/current-properties');
    }

    /**
     * @group homepage
     */
    public function checkHowItWorks(\Step\Acceptance\StaticPages $I)
    {
        /**
         * How it works turqoise-teal section
         */

        $I->wantTo('Check How it works');
        $I->amOnPage('/');
        $I->scrollTo("#how-it-works-process");
        $I->see("Learn more", "#how-it-works-process a");
    }

    // /**
    //  * @group homepage
    //  */
    // public function checkPublicity(\Step\Acceptance\StaticPages $I)
    // {
    //     /**
    //      * Truspilot section
    //      * Media mentions section
    //      */


    //     $I->amOnPage('/');
    //     $I->scrollTo("a.btn-underline.invert");
    //     $I->waitForElementVisible("section.testimonial h2");
    //     $I->waitForElementVisible("//div[@class='text-center mb-2']/img");
    //     $I->waitForText("And people are talking about our award winning platform", 5, "div.container.py-7");
    //     $I->waitForElementVisible("div#clients :nth-child(1)");
    //     $I->seeNumberOfElements("div#clients a", 7);
    // }

    // /**
    //  * @group homepage
    //  */
    // public function checkSocialMedia(\Step\Acceptance\StaticPages $I)
    // {

    //   $I->wantTo("Check Social Media");
    //   $I->amOnPage('/');
    //   $I->scrollTo("//ul[@class='social list-inline d-flex align-items-center justify-content-center my-1']/li[1]/p");
    //   $I->seeElement("//ul[@class='social list-inline d-flex align-items-center justify-content-center my-1']/li[2]");
    //   $I->seeElement("//ul[@class='social list-inline d-flex align-items-center justify-content-center my-1']/li[3]");
    //   $I->seeElement("//ul[@class='social list-inline d-flex align-items-center justify-content-center my-1']/li[4]");
    //   $I->seeElement("//ul[@class='social list-inline d-flex align-items-center justify-content-center my-1']/li[5]");
    //   $I->seeElement("//div[@class='social d-flex mb-1 mb-md-0']");
    // }
}
