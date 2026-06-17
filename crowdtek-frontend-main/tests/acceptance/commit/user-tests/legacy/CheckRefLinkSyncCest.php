<?php


class CheckRefLinkSyncCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    public function _after(AcceptanceTester $I)
    {
        $I->amOnPage('/logout');
    }

    public function checkReferralLinkLoginSync(AcceptanceTester $I)
    {
        $I->loginWithCredentials($I->reg_user_name, $I->admin_user_password);
        
        if ($I->cmsCheck) {
            $token = $I->getUserToken($I->reg_user_name, $I->admin_user_password);
            if ($token) {
                $user = $I->getUserInfoByAPI($token);
                $user_info = $I->convertUserInfoToDict($user['info']);
 
                $I->assertRegExp('/^BCharlton\d+$/', $user_info['referral_link']);
            } else {
                $I->assertTrue($token, "Unable to login");
            }
        }
    }
}
