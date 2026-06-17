<?php


class PageProfileCategorisationCest
{
    public function _before(\Step\Acceptance\StaticPages $I)
    {
        $I->loginWithCredentials($I->reg_user_name, $I->admin_user_password, false);
        $I->amOnPage('/my-profile/categorisation');
    }

    public function _after(AcceptanceTester $I) {}

    /**
     * @group profile
     */
    public function checkCategorisationForm(\Step\Acceptance\StaticPages $I)
    {
        $I->see("Investor Type", ".navClick.active");

        // $currentCategory = $I->grabTextFrom('#current-category');
        // Click through each option
        $I->selectOption('input[name="user_categorisation[category]"]', 'Restricted');
        $I->waitForText('Restricted Investor', 10, '#category-summary');
        $I->selectOption('input[name="user_categorisation[category]"]', 'Sophisticated');
        $I->waitForText('Sophisticated Investor', 10, '#category-summary');
        $I->selectOption('input[name="user_categorisation[category]"]', 'High net worth');
        $I->waitForText('High Net Worth Investor', 10, '#category-summary');

        $I->selectOption('input[name="user_categorisation[category]"]', 'High net worth');
        $I->scrollTo('button[type="submit"]', 0, -150);
        $I->click('Continue');
        $I->waitForText('Investor Type Confirmation');
        $I->see('High Net Worth Investor');
        $I->selectOption('input[name="category_hnw[hnwType]"]', 'income');
        $I->fillField('#category_hnw_amount', '300000');
        $I->scrollTo('button[type="submit"]', 0, -150);
        $I->click('Confirm Investor Type');

        $I->waitForText("Investor type successfully updated");
        $I->amOnPage('/my-profile/categorisation');
        $I->see('High net worth', '#current-category');
    }
}
