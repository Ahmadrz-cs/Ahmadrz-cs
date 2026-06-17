<?php


class UserLoginCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    public function _after(AcceptanceTester $I)
    {
    }

    
    public function checkInvalidUsernameError(AcceptanceTester $I)
    {
        $I->amOnPage('/login');
        try {
            $I->seeElement('input', ['name' => '_username']);
        } catch (\Exception $e) {
            $I->amOnPage('/logout');
            $I->amOnPage('/login');
        }
        $I->fillField(['name' => '_username'], "abc@xyz.com");
        $I->fillField(['name' => '_password'], "abcxyz");
        $I->click('button[type=submit]');

        $I->seeCurrentUrlEquals('/login');
        $I->waitForText('The username or password you entered was invalid');
    }

    public function checkInvalidPasswordError(AcceptanceTester $I)
    {
        $I->amOnPage('/login');
        try {
            $I->seeElement('input', ['name' => '_username']);
        } catch (\Exception $e) {
            $I->amOnPage('/logout');
            $I->amOnPage('/login');
        }
        $I->fillField(['name' => '_username'], $I->reg_user_name);
        $I->fillField(['name' => '_password'], "abcxyz");
        $I->click('button[type=submit]');

        $I->seeCurrentUrlEquals('/login');
        $I->waitForText('The username or password you entered was invalid');
    }
}
