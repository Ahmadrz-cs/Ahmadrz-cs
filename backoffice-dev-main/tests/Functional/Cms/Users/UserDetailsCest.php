<?php

namespace App\Tests\Functional\Cms\Users;

use App\Tests\Support\FunctionalTester;

class UserDetailsCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    /**
     * @group detailview
     */
    public function checkEditUser(FunctionalTester $I)
    {
        $I->amOnPage('/admin/users');

        $id = $I->getUserId('notVip');
        $I->amOnPage('/admin/users/' . $id . '/edit');

        $I->see('Add Document', "a[href='/admin/userdocument/add?user=" . $id . "']");
        $I->seeLink('Edit User Role', "/admin/users/{$id}/roles");
        $I->seeLink('Go to User Dashboard', "/admin/users/{$id}/dashboard");
        $I->seeLink('Review KYC Status', "/admin/users/{$id}/dashboard/kyc");
        $I->seeLink('Review Account Closure', "/admin/users/{$id}/account-closure");

        $I->fillField('input#user_additionalName', 'middle name');

        $I->selectOption('Is VIP', '1');
        $I->click('button#user_submit');
        $I->vipConfirmationEmail();

        //check the update happened
        $I->amOnPage('/admin/users/' . $id . '/edit');
        $I->canSeeInField('input#user_additionalName', 'middle name');
        $I->canSeeOptionIsSelected('Is VIP', 'Yes');

        $I->seeElement('#timestamp');
        $I->seeElement('#blame');
    }

    /**
     * @group detailview
     */
    public function checkEditGdpr(FunctionalTester $I)
    {
        $I->amOnPage('/admin/users');

        $id = $I->getUserId('RANDOM');
        $I->amOnPage('/admin/users/' . $id . '/edit');

        $I->selectOption('GDPR accepted', '1');
        $I->click('button#user_submit');

        //check the update happened
        $I->amOnPage('/admin/users/' . $id . '/edit');
        $I->canSeeOptionIsSelected('GDPR accepted', 'Yes');
    }

    /**
     * @group detailview
     */
    public function checkManagedUsersLink(FunctionalTester $I)
    {
        $I->amOnPage('/admin/users');

        $subjectUser = $I->getUserIdByUsername($I::USER_REG3);
        $managerUser = $I->getUserIdByUsername($I::USER_ADMIN);
        $I->amOnPage('/admin/users/' . $subjectUser . '/edit');

        $I->fillField('#user_managedBy', $managerUser);
        $I->click('button#user_submit');

        // check managing users label
        $I->amOnPage('/admin/users');
        $I->see('Managing Users', '.badge');
        $I->seeElement('a[href="/admin/users/' . $managerUser . '/edit"] + .badge');

        // check view managed users link
        $I->amOnPage('/admin/users/' . $managerUser . '/edit');
        $I->see('This user is managing');
        $I->see('View Managed Users', 'a');
        $I->click('View Managed Users');
        $I->seeCurrentUrlEquals('/admin/users/' . $managerUser . '/managed-users');
        $I->see($I::USER_REG3);
    }

    /**
     * @group detailview
     */
    public function testStatusRecord(FunctionalTester $I)
    {
        $statuses = [
            'email_not_verified',
            'email_verified',
            'registration_complete',
            'approved',
            'blocked',
        ];
        foreach ($statuses as $status) {
            $sampleId = $I->grabFromDatabase('users_statuses', 'id', [
                'lifecycleStatus' => $status,
            ]);
            // $dashName = str_replace('_', '-', $status);
            $I->amOnPage("/admin/users/$sampleId/edit");
            $I->see(
                ucwords(str_replace('_', ' ', $status)),
                '#status-record tbody tr.active',
            );
        }
    }
}
