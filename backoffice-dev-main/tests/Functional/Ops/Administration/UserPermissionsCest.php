<?php

namespace App\Tests\Functional\Ops\Administration;

use App\Tests\Support\FunctionalTester;
use Codeception\Example;
use Symfony\Component\HttpFoundation\Response;

class UserPermissionsCest
{
    public function _before(FunctionalTester $I) {}

    public function checkNormalUserLogin(FunctionalTester $I): void
    {
        $I->amOnPage('/login');
        $I->fillField('_username', $I::USER_REG1);
        $I->fillField('_password', 'HarvestBounty!756');
        try {
            $I->click('Login');
        } catch (\Throwable $th) {
            // Frontend url not accessible in current env
            // Local dev needs frontend devcontainer to be running to access
            // CI does not have access by default
            // Doesn't really matter as we are looking for whether a non-staff user
            // Ever has access to `/admin`
        }
        $I->dontSeeCurrentUrlEquals('/admin');
    }

    public function checkManageUserPermissions(FunctionalTester $I): void
    {
        //Change permission to Analyst
        $I->loginAdmin();
        $I->changePermission(
            'ROLE_ANALYST',
            'engineering.auto@test.yielderverse.co.uk',
        );
        $I->see('Analyst');
        $I->changePermission('ROLE_ADMIN', 'engineering.auto@test.yielderverse.co.uk');
    }

    public function checkAdminCannotDowngradeSuperadmin(FunctionalTester $I): void
    {
        $I->loginAdmin('admin');
        $superadminId = $I->getUserIdByUsername('superadmin@test.yielderverse.co.uk');
        $I->amOnPage('/admin/users/' . $superadminId . '/roles');
        $I->see('Insufficient permissions');
        $I->seeCurrentUrlEquals('/admin/users/staff');
    }

    protected function userAccessControlPermissionsProvider(): \Generator
    {
        yield 'super admin' => [
            'role' => 'super',
            'rolesVisible' => [
                'Analyst',
                'Operations',
                'Tech Ops',
                'Financial Ops',
                'Admin',
                'Super Admin',
            ],
            'promoteToRole' => [
                'Analyst',
                'Operations',
                'Tech Ops',
                'Financial Ops',
                'Admin',
                'Super_Admin',
            ],
        ];
        yield 'admin' => [
            'role' => 'admin',
            'rolesVisible' => [
                'Analyst',
                'Operations',
                'Tech Ops',
                'Financial Ops',
                'Admin',
            ],
            'promoteToRole' => [
                'Analyst',
                'Operations',
                'Tech Ops',
                'Financial Ops',
                'Admin',
            ],
        ];
        yield 'finops' => [
            'role' => 'finops',
            'rolesVisible' => ['Financial Ops', 'Tech Ops', 'Operations', 'Analyst'],
            'promoteToRole' => [],
        ];
        yield 'techops' => [
            'role' => 'techops',
            'rolesVisible' => ['Financial Ops', 'Tech Ops', 'Operations', 'Analyst'],
            'promoteToRole' => [],
        ];
        yield 'operations' => [
            'role' => 'operations',
            'rolesVisible' => ['Financial Ops', 'Tech Ops', 'Operations', 'Analyst'],
            'promoteToRole' => [],
        ];
        yield 'analyst' => [
            'role' => 'analyst',
            'rolesVisible' => ['Financial Ops', 'Tech Ops', 'Operations', 'Analyst'],
            'promoteToRole' => [],
        ];
    }

    /**
     * @dataProvider userAccessControlPermissionsProvider
     */
    public function checkPermissionsUserAccessControl(
        FunctionalTester $I,
        Example $example,
    ): void {
        $I->loginAdmin($example['role']);

        // User permissions page accessible
        $I->amOnPage('/admin/users/staff');

        $I->see('User');
        $I->see('Roles');

        // Can see specific users based on access level
        foreach ($example['rolesVisible'] as $roleString) {
            $I->see($roleString, 'span.badge');
        }

        // Check promotion powers - operations and analysts have no promotion powers
        $regUser = $I->getUserIdByUsername('freya.auto@test.yielderverse.co.uk');
        if (!empty($example['promoteToRole'])) {
            foreach ($example['promoteToRole'] as $promoteToRole) {
                // promote a user upwards to the max
                $I->amOnPage('/admin/users/' . $regUser . '/roles');
                $I->selectOption(
                    'form input[type=radio]',
                    'ROLE_' . strtoupper(str_replace(' ', '_', $promoteToRole)),
                );
                $I->click('Save Changes');
                $I->seeLink('Edit User Role', '/admin/users/' . $regUser . '/roles');
                $I->amOnPage('/admin/users/' . $regUser . '/roles');
                $I->seeOptionIsSelected(
                    'form input[type=radio]',
                    'ROLE_' . strtoupper(str_replace(' ', '_', $promoteToRole)),
                );
            }
            // reset the user to normal user
            $I->amOnPage('/admin/users/' . $regUser . '/roles');
            if ($example['role'] == 'super') {
                $I->seeElement('form input[value=ROLE_SUPER_ADMIN]');
            } else {
                $I->dontSeeElement('form input[value=ROLE_SUPER_ADMIN]');
            }
            $I->selectOption('form input[type=radio]', 'ROLE_USER');
            $I->click('Save Changes');
        } else {
            $I->amOnPage('/admin/users/' . $regUser . '/roles');
            $I->seeResponseCodeIs(403);
        }
    }

    protected function viewEditUserPermissionsProvider(): \Generator
    {
        yield 'super admin' => [
            'role' => 'super',
            'viewableUsers' => [
                'regular',
                'analyst',
                'operations',
                'admin',
                'super admin',
            ],
            'editableUsers' => [
                'regular',
                'analyst',
                'operations',
                'admin',
                'super admin',
            ],
            'editStatus' => true,
            'editDoc' => true,
            'deleteDoc' => true,
        ];
        yield 'admin' => [
            'role' => 'admin',
            'viewableUsers' => [
                'regular',
                'analyst',
                'operations',
                'admin',
                'super admin',
            ],
            'editableUsers' => ['regular', 'analyst', 'operations', 'admin'],
            'editStatus' => true,
            'editDoc' => true,
            'deleteDoc' => true,
        ];
        yield 'operations' => [
            'role' => 'operations',
            'viewableUsers' => [
                'regular',
                'analyst',
                'operations',
                'admin',
                'super admin',
            ],
            'editableUsers' => ['regular', 'analyst', 'operations'],
            'editStatus' => true,
            'editDoc' => true,
            'deleteDoc' => false,
        ];
        yield 'techops' => [
            'role' => 'techops',
            'viewableUsers' => [
                'regular',
                'analyst',
                'operations',
                'admin',
                'super admin',
            ],
            'editableUsers' => [],
            'editStatus' => false,
            'editDoc' => false,
            'deleteDoc' => false,
        ];
        yield 'analyst' => [
            'role' => 'analyst',
            'viewableUsers' => [
                'regular',
                'analyst',
                'operations',
                'admin',
                'super admin',
            ],
            'editableUsers' => [],
            'editStatus' => false,
            'editDoc' => false,
            'deleteDoc' => false,
        ];
    }

    protected function nonFinOpsProvider(): \Generator
    {
        yield 'techops' => [
            'role' => 'techops',
        ];
        yield 'operations' => [
            'role' => 'operations',
        ];
        yield 'analyst' => [
            'role' => 'analyst',
        ];
    }
}
