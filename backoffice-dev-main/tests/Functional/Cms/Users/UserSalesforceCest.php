<?php

namespace App\Tests\Functional\Cms\Users;

use App\Tests\Support\FunctionalTester;

/**
 * Suspended while dev user account dealing with Salesforce VPN issues
 */
class UserSalesforceCest
{
    // public function _before(FunctionalTester $I)
    // {
    //     $I->loginAdmin();
    // }
    // /**
    //  * @group salesforce
    //  */
    // public function checkSalesforceSyncOnFieldEdit(FunctionalTester $I)
    // {
    //     //Get our Salesforce test user
    //     $userId = $I->getUserId('SALESFORCE');
    //     //Updating some core fields
    //     $I->amOnPage('/admin/users/' . $userId . '/edit');
    //     $I->fillField('input#user_honoricPrefix', 'Dr');
    //     $I->fillField('input#user_firstname', 'Sally-Anne');
    //     $I->fillField('input#user_lastname', 'Forsyth');
    //     $I->selectOption('select#user_gender', 'FEMALE');
    //     $I->fillField('input#user_birthDate', '1992-01-30');
    //     $I->fillField('input#user_phone1', '+442072054650');
    //     $I->fillField('input#user_phone2', '+447911123456');
    //     // $I->fillField('input#user_mangoPayUserId', '12345678');
    //     // $I->fillField('input#user_mangoPayWalletId', '24686420');
    //     $I->uncheckOption('input#user_investor_cxbRestrictedUser');
    //     $I->checkOption('input#user_investor_cxbSophisticatedInvestor');
    //     $I->checkOption('input#user_investor_corporateInvestor');
    //     $I->click('button#user_submit');
    //     $I->canSee('Users list');
    //     $I->canSee('User successfully synced with Salesforce');
    //     //check Salesforce in sync
    //     $sf_user = $I->saleForceAction(
    //         'GET',
    //         'Contact',
    //         $I->salesforce_params['test_user_id'],
    //     );
    //     $I->assertEquals('Dr', $sf_user['honoricPrefix__c']);
    //     $I->assertEquals('Sally-Anne', $sf_user['FirstName']);
    //     $I->assertEquals('Forsyth', $sf_user['LastName']);
    //     $I->assertEquals('FEMALE', $sf_user['gender__c']);
    //     $I->assertStringContainsString('1992-01-30', $sf_user['birthDate__c']);
    //     $I->assertStringContainsString('2072054650', $sf_user['Phone']);
    //     $I->assertStringContainsString('7911123456', $sf_user['MobilePhone']);
    //     // $I->assertEquals("12345678", $sf_user['mangoPayUserId__c']);
    //     // $I->assertEquals("24686420", $sf_user['mangoPayWalletId__c']);
    //     $I->assertEquals(0, $sf_user['cxbWorthInvestor__c']);
    //     $I->assertEquals(1, $sf_user['cxbSophisticatedInvestor__c']);
    //     $I->assertEquals(0, $sf_user['cxbRestrictedUser__c']);
    //     $I->assertEquals(1, $sf_user['corporateInvestor__c']);
    //     //Undo changes
    //     $I->amOnPage('/admin/users/' . $userId . '/edit');
    //     $I->fillField('input#user_honoricPrefix', 'Ms');
    //     $I->fillField('input#user_firstname', 'Sally');
    //     $I->fillField('input#user_lastname', 'Forsyth');
    //     $I->selectOption('select#user_gender', 'FEMALE');
    //     $I->fillField('input#user_birthDate', '');
    //     $I->fillField('input#user_phone1', '');
    //     $I->fillField('input#user_phone2', '');
    //     // $I->fillField('input#user_mangoPayUserId', '');
    //     // $I->fillField('input#user_mangoPayWalletId', '');
    //     $I->uncheckOption('input#user_investor_cxbSophisticatedInvestor');
    //     $I->uncheckOption('input#user_investor_corporateInvestor');
    //     $I->click('button#user_submit');
    //     $I->canSee('Users list');
    //     //check Salesforce still in sync
    //     $sf_user = $I->saleForceAction(
    //         'GET',
    //         'Contact',
    //         $I->salesforce_params['test_user_id'],
    //     );
    //     $I->assertEquals('Ms', $sf_user['honoricPrefix__c']);
    //     $I->assertEquals('Sally', $sf_user['FirstName']);
    // }
    // /**
    //  * @group salesforce
    //  */
    // public function testSalesforceSyncVipAction(FunctionalTester $I)
    // {
    //     //Get our Salesforce test user
    //     $userId = $I->getUserId('SALESFORCE');
    //     // Get VIP state at start
    //     $I->amOnPage('/admin/users/' . $userId . '/edit');
    //     $vipStatus = $I->grabAttributeFrom('#user_isVIP option[selected]', 'value');
    //     $sfVipStatus = $I->saleForceAction(
    //         'GET',
    //         'Contact',
    //         $I->salesforce_params['test_user_id'],
    //     )['isVIP__c'];
    //     // Determine how we toggle VIP state
    //     if ($vipStatus != $sfVipStatus) {
    //         // if they're already different, just force a sync by submiting the edit form
    //         $I->amOnPage('/admin/users/' . $userId . '/edit');
    //         $I->click('button#user_submit');
    //         $I->canSee('User successfully synced with Salesforce');
    //     } else {
    //         // otherwise click on the relevant make-vip link
    //         $I->amOnPage(
    //             '/admin/users?username=' . mb_substr($I::USER_SALESFORCE, 0, 5),
    //         );
    //         $I->click('a[href="/admin/users/' . $userId . '/user_vip"]');
    //         $I->canSee('User successfully synced with Salesforce');
    //         $I->seeCurrentUrlEquals('/admin/users');
    //         $I->amOnPage('/admin/users/' . $userId . '/edit');
    //         $vipStatus = $I->grabAttributeFrom('#user_isVIP option[selected]', 'value');
    //     }
    //     //check Salesforce sync
    //     $sfVipStatus = $I->saleForceAction(
    //         'GET',
    //         'Contact',
    //         $I->salesforce_params['test_user_id'],
    //     )['isVIP__c'];
    //     $I->assertEquals($vipStatus, $sfVipStatus);
    // }
    // /**
    //  * @group salesforce
    //  */
    // public function testSalesforceSyncApproveAction(FunctionalTester $I)
    // {
    //     //Get our Salesforce test user
    //     $userId = $I->getUserId('SALESFORCE');
    //     $cmsStatus = $I->getUserStatusField($userId, 'isApproved');
    //     $sfStatus = $I->saleForceAction(
    //         'GET',
    //         'Contact',
    //         $I->salesforce_params['test_user_id'],
    //     )['IsApproved__c'];
    //     // if already different, force a sync by submitting edit form
    //     if ($cmsStatus != $sfStatus) {
    //         $I->amOnPage('/admin/users/' . $userId . '/edit');
    //         $I->click('button#user_submit');
    //         $I->canSee('User successfully synced with Salesforce');
    //     } else {
    //         $I->amOnPage('/admin/users/' . $userId . '/user_approve');
    //         $I->canSee('User successfully synced with Salesforce');
    //         $cmsStatus = $I->getUserStatusField($userId, 'isApproved');
    //     }
    //     //check Salesforce sync
    //     $sfStatus = $I->saleForceAction(
    //         'GET',
    //         'Contact',
    //         $I->salesforce_params['test_user_id'],
    //     )['IsApproved__c'];
    //     $I->assertEquals($cmsStatus, $sfStatus);
    // }
    // /**
    //  * @group salesforce
    //  */
    // public function testSalesforceSyncBlockAction(FunctionalTester $I)
    // {
    //     //Get our Salesforce test user
    //     $userId = $I->getUserId('SALESFORCE');
    //     $cmsStatus = $I->getUserStatusField($userId, 'isBlocked');
    //     $sfStatus = $I->saleForceAction(
    //         'GET',
    //         'Contact',
    //         $I->salesforce_params['test_user_id'],
    //     )['isBlocked__c'];
    //     // if already different, force a sync by submitting edit form
    //     if ($cmsStatus != $sfStatus) {
    //         $I->amOnPage('/admin/users/' . $userId . '/edit');
    //         $I->click('button#user_submit');
    //         $I->canSee('User successfully synced with Salesforce');
    //     } else {
    //         $I->amOnPage('/admin/users/' . $userId . '/user_block');
    //         $I->canSee('User successfully synced with Salesforce');
    //         $cmsStatus = $I->getUserStatusField($userId, 'isBlocked');
    //     }
    //     //check Salesforce sync
    //     $sfStatus = $I->saleForceAction(
    //         'GET',
    //         'Contact',
    //         $I->salesforce_params['test_user_id'],
    //     )['isBlocked__c'];
    //     $I->assertEquals($cmsStatus, $sfStatus);
    // }
    // /**
    //  * @group salesforce
    //  */
    // public function testSalesforceSyncRegisterAction(FunctionalTester $I)
    // {
    //     //Get our Salesforce test user
    //     $userId = $I->getUserId('SALESFORCE');
    //     $cmsStatus = $I->getUserStatusField($userId, 'isRegCompleted');
    //     $sfStatus = $I->saleForceAction(
    //         'GET',
    //         'Contact',
    //         $I->salesforce_params['test_user_id'],
    //     )['isRegCompleted__c'];
    //     // if already different, force a sync by submitting edit form
    //     if ($cmsStatus != $sfStatus) {
    //         $I->amOnPage('/admin/users/' . $userId . '/edit');
    //         $I->click('button#user_submit');
    //         $I->canSee('User successfully synced with Salesforce');
    //     } else {
    //         $I->amOnPage('/admin/users/' . $userId . '/user_registercomplete');
    //         $I->canSee('User successfully synced with Salesforce');
    //         $cmsStatus = $I->getUserStatusField($userId, 'isRegCompleted');
    //     }
    //     //check Salesforce sync
    //     $sfStatus = $I->saleForceAction(
    //         'GET',
    //         'Contact',
    //         $I->salesforce_params['test_user_id'],
    //     )['isRegCompleted__c'];
    //     $I->assertEquals($cmsStatus, $sfStatus);
    // }
}
