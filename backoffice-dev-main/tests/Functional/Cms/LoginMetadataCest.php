<?php

namespace App\Tests\Functional\Cms;

use App\Tests\Support\FunctionalTester;

class LoginMetadataCest
{
    public function checkLastLoginUpdate(FunctionalTester $I)
    {
        $mailcatcher = $I->getMailcatcherClient();
        $mailcatcher->delete('/messages');
        $start = new \DateTime('-1 day');
        $I->updateInDatabase(
            'users',
            ['last_login' => $start->format('Y-m-d H:i:s')],
            ['username' => $I::USER_ANALYST],
        );

        $I->amOnPage('/login');
        $I->fillField('_username', $I::USER_ANALYST);
        $I->fillField('_password', $I::TEST_PASSWORD);
        $I->click('Login');
        $midway = $I->grabFromDatabase('users', 'last_login', [
            'username' => $I::USER_ANALYST,
        ]);
        $I->assertEquals($start->getTimestamp(), strtotime($midway));
        $I->loginMfaStep();
        $end = $I->grabFromDatabase('users', 'last_login', [
            'username' => $I::USER_ANALYST,
        ]);
        $I->assertGreaterThan($start->getTimestamp(), strtotime($end));
    }
}
