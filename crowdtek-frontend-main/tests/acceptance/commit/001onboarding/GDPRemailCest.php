<?php

// 003DeclarationsCest.php

class GDPRemailCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->createUserAtStage('2', '0');
    }

    public function _after(AcceptanceTester $I) {}

    public function preferencesAndDeclarations(AcceptanceTester $I)
    {
        $I->clearMailCatcher();
        $I->amOnPage('/onboarding'); // actual page: /regulation-preference
        $I->wait(1);
        $I->seeCurrentUrlEquals('/onboarding/regulation-preference');
        $I->clickWithLeftButton('//form[@name="userPreference"]/label[contains(text(), "Email")]');
        $I->click('//button[text()="Next"]');
        $I->scrollTo('//form[@name="userPreference"]//button[@type="submit"]');
        $I->click('//button[text()="Next"]');
        $I->seeCurrentUrlEquals('/onboarding/categorisation');

        $I->amOnUrl($I->getMailcatcherUrl() . '/messages/1.html');
        $I->see('No Dice');
        $I->resetBaseHost();
    }

    public function cleanUpSalesforceObject(AcceptanceTester $I)
    {
        if ($I->cmsCheck) {
            $token = $I->getUserToken(sqs('2') . $I->new_user_yorran["email"], $I->new_user_yorran["password"]);
            if ($token) {
                $user = $I->getUserInfoByAPI($token);
                $user_info = $I->convertUserInfoToDict($user['info']);
                try {
                    $salesforce_id = $user_info['salesforce_id'];
                    $I->salesforceAction('DELETE', $I->salesforce_params["user_object"], $salesforce_id);
                } catch (\Throwable $th) {
                    echo "Salesforce not setup";
                }
            } else {
                $I->assertTrue($token, "Unable to login");
            }
        }
    }
}
