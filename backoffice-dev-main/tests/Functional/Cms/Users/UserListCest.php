<?php

namespace App\Tests\Functional\Cms\Users;

use App\Entity\Lifecycle\UserLifecycle;
use App\Tests\Support\FunctionalTester;

class UserListCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    /**
     * @group listview
     */
    public function checkMakeVipAction(FunctionalTester $I)
    {
        $userId = $I->getUserIdByUsername($I::USER_REG2);

        $I->amOnPage('/admin/users/' . $userId . '/edit');
        $vipStatusBefore = $I->grabAttributeFrom(
            '#user_isVIP option[selected]',
            'value',
        );

        $I->amOnPage('/admin/users?username=' . mb_substr($I::USER_REG2, 0, 5));
        $I->click('a[href="/admin/users/' . $userId . '/user_vip"]');
        $I->seeCurrentUrlEquals('/admin/users');

        //check the update happened
        $I->amOnPage('/admin/users/' . $userId . '/edit');
        $vipStatusAfter = $I->grabAttributeFrom(
            '#user_isVIP option[selected]',
            'value',
        );
        $I->assertNotEquals($vipStatusBefore, $vipStatusAfter);

        if (!$vipStatusBefore) {
            $I->vipConfirmationEmail();
        }
    }

    /**
     * @group listview
     */
    public function checkToggleManualKycVerification(FunctionalTester $I)
    {
        $userId = $I->getUserIdByUsername($I::USER_REG2);
        $I->amOnPage("/admin/users?id={$userId}");
        $currentKyc = (bool) $I->grabAttributeFrom(
            "#users-list tr[data-object-id='{$userId}'] td[data-field='kyc-status']",
            'data-kyc-status',
        );
        $expectedKycAfterToggle = $currentKyc ? 'Failed' : 'Verified';

        $I->seeLink(
            'Toggle Manual KYC Verification',
            "/admin/users/{$userId}/toggle-kyc-verified",
        );
        $I->click(
            'Toggle Manual KYC Verification',
            "#users-list tr[data-object-id='{$userId}']",
        );
        $I->seeCurrentUrlEquals("/admin/users?id={$userId}");
        $I->see("User successfully updated to kyc {$expectedKycAfterToggle}");
        $I->see(
            $expectedKycAfterToggle,
            "#users-list tr[data-object-id='{$userId}'] td[data-field='kyc-status']",
        );

        $expectedKycAfterToggle = $currentKyc ? 'Verified' : 'Failed';

        $I->amOnPage("/admin/users/{$userId}/toggle-kyc-verified");
        $I->seeCurrentUrlEquals("/admin/users?id={$userId}");
        $I->see("User successfully updated to kyc {$expectedKycAfterToggle}");
        $I->see(
            $expectedKycAfterToggle,
            "#users-list tr[data-object-id='{$userId}'] td[data-field='kyc-status']",
        );
    }

    /**
     * @group listview
     */
    public function checkToggleCompanyApproved(FunctionalTester $I)
    {
        $I->amOnPage('/admin/users?username=' . mb_substr($I::USER_REG2, 0, 5));
        $userId = $I->getUserIdByUsername($I::USER_REG2);

        $I->amOnPage('/admin/users/' . $userId . '/toggle-company-approved');
        $I->seeCurrentUrlEquals('/admin/users');
        $I->see('is now an approved');
        $I->amOnPage('/admin/users?username=' . mb_substr($I::USER_REG2, 0, 5));
        $I->see('Company Approved', '.badge');

        $I->amOnPage('/admin/users/' . $userId . '/toggle-company-approved');
        $I->seeCurrentUrlEquals('/admin/users');
        $I->see('is no longer an approved');
        $I->amOnPage('/admin/users?username=' . mb_substr($I::USER_REG2, 0, 5));
        $I->dontSee('Company Approved', '.badge');
    }

    /**
     * @group listview
     */
    public function checkBlockUnblockAction(FunctionalTester $I)
    {
        $userId = $I->getUserIdByUsername($I::USER_REG3);

        //block
        $I->amOnPage('/admin/users/' . $userId . '/user_block');
        $I->seeResponseCodeIs(200);
        //unblock
        $I->amOnPage('/admin/users/' . $userId . '/user_block');
        $I->seeResponseCodeIs(200);
    }

    /**
     * @group listview
     */
    public function checkMarkApprovedAction(FunctionalTester $I)
    {
        $userId = $I->searchDatabaseByStatus('users', 'email_verified');
        //approve
        $I->amOnPage('/admin/users/' . $userId . '/user_approve');
        $I->seeResponseCodeIs(200);

        //remove approval
        $I->amOnPage('/admin/users/' . $userId . '/user_approve');
        $I->seeResponseCodeIs(200);
    }

    /**
     * @group listview
     */
    public function checkMarkRegistrationAction(FunctionalTester $I)
    {
        $userId = $I->searchDatabaseByStatus('users', 'email_verified');
        //complete
        $I->amOnPage('/admin/users/' . $userId . '/user_registercomplete');
        $I->seeResponseCodeIs(200);

        //mark incomplete
        $I->amOnPage('/admin/users/' . $userId . '/user_registercomplete');
        $I->seeResponseCodeIs(200);
    }

    /**
     * @group listview
     */
    public function checkFatca(FunctionalTester $I)
    {
        $I->amOnPage('/admin/users?username=' . mb_substr($I::USER_REG3, 0, 5));
        $I->see('FATCA', 'span.badge');

        $userId = $I->getUserIdByUsername($I::USER_REG3);
        $I->amOnPage('/admin/users/' . $userId . '/edit');
        $I->seeElement('input#user_customFields_0_fieldKey');
        $I->seeElement('input#user_customFields_0_fieldValue');
    }

    /**
     * @group listview
     */
    public function checkListViewElements(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/users');
        $I->seeLink('Go to KYC Hub', '/admin/kyc');

        $elements = [
            'Id',
            'Username',
            'Email',
            'Join Date',
            'Last Login',
            'User Type',
            'Status',
            'Actions',
        ];
        // check table headers
        $I->loopCheckElements($elements, 'thead th');

        $filterLabels = [
            'Username',
            'Contact Email',
            'Name',
            'Phone number',
            'Referral',
            'Is Vip',
            'Onboarding Step',
            'Has Top Yielder Application',
            'Gender',
            'Retail or Corporate',
            'Company name',
            'Has investments',
            'Has managed users',
            'Has KYC Profile',
            'Has KYC Manual Verification',
            'Is KYC Profile Verified',
            'CreatedAt Start',
            'CreatedAt End',
            'Items Per Page',
            'Order by',
            'Order Direction',
        ];
        // check table filters
        $I->loopCheckElements($filterLabels, 'form label');
        $I->loopCheckElements(['Status', 'Legacy Status'], 'form legend');
        // $I->see('Show/Hide Status', 'form button');

        // Check mouse over text on link buttons
        $I->seeElement(['css' => 'tbody a.btn[title="Edit User"]']);
        $I->seeElement(['css' => 'tbody a.btn[title="User Investments"]']);
        $I->seeElement(['css' => 'tbody a.btn[title="View User"]']);

        $I->amOnPage('/admin/users');
        $I->seeNumberOfElements('#users-list tbody tr', 10);
        $I->selectOption('form select[name=perPage]', '5');
        $I->click('Apply Filters');
        $I->seeNumberOfElements('#users-list tbody tr', 5);

        // check max page bracketing (to deal with filter changing)
        $I->amOnPage('/admin/users?page=1000');
        // Sends you to last page
        $I->seeElement(['css' => '.pagination li:last-child.disabled']);
    }

    /**
     * @group listview
     * @dataProvider filterProvider
     */
    public function checkListViewFilters(
        FunctionalTester $I,
        \Codeception\Example $example,
    ): void {
        if (empty($example['dbquery'])) {
            $example['dbquery'] = $example['filters'];
        }
        // special case for `wordsOfOwn` which is tied to the user investor relation
        // Note that `wordsOfOwn` cannot be combined with any other native user filters
        if (in_array('wordsOfOwn', array_keys($example['filters']))) {
            $count = $I->grabNumRecords('user_investors', $example['dbquery']);
        } else {
            $count = $I->grabNumRecords('users', $example['dbquery']);
        }
        $I->amOnPage('/admin/users?' . http_build_query($example['filters']));
        // Max 10 rows shown by default
        $I->seeNumberOfElements('#users-list tbody tr', min(10, $count));
        $I->see($count, '#list-meta-results');
    }

    /**
     * @group listview
     */
    public function checkListViewFiltersStatus(FunctionalTester $I): void
    {
        // status filter separate as it uses an array rather than single values
        $query = [
            'lifecycleStatus' => [
                UserLifecycle::STATE_EMAIL_NOT_VERIFIED,
                UserLifecycle::STATE_EMAIL_VERIFIED,
            ],
        ];
        $I->amOnPage('/admin/users?' . http_build_query($query));
        foreach ($query['lifecycleStatus'] as $status) {
            $I->see(
                preg_replace("/[\_]/", ' ', $status),
                '#users-list tbody tr .badge',
            );
        }
        $omittedStatuses = [
            UserLifecycle::STATE_APPROVED,
            UserLifecycle::STATE_REGISTRATION_COMPLETE,
            UserLifecycle::STATE_BLOCKED,
        ];
        foreach ($omittedStatuses as $status) {
            $I->dontSee(
                preg_replace("/[\_]/", ' ', $status),
                '#users-list tbody tr .badge',
            );
        }
    }

    /**
     * @group listview
     */
    public function checkUserPresetFilters(FunctionalTester $I)
    {
        $I->amOnPage('/admin/users');
        $I->seeLink('Top Yielders', '/admin/users?isVIP=1');
        $I->seeLink(
            'Not Fully Onboarded',
            '/admin/users?lifecycleStatus%5B0%5D=email_not_verified&lifecycleStatus%5B1%5D=email_verified',
        );
        // $I->seeLink(
        //     'Onboarded but not yet invested',
        //     '/admin/users?lifecycleStatus%5B0%5D=approved&hasInvestments=0',
        // );
        $I->seeLink(
            'Pending Manual KYC Review',
            '/admin/users?'
                . http_build_query([
                    'ob_step' => 5,
                    'hasKycProfile' => 1,
                    'hasVerifiedBy' => 0,
                    'lifecycleStatus' => [
                        UserLifecycle::STATE_EMAIL_VERIFIED,
                        UserLifecycle::STATE_REGISTRATION_COMPLETE,
                        UserLifecycle::STATE_APPROVED,
                    ],
                ]),
        );
        $I->seeLink(
            'Missing KYC Profile',
            '/admin/users?'
                . http_build_query([
                    'ob_step' => 5,
                    'hasKycProfile' => 0,
                    'lifecycleStatus' => [
                        UserLifecycle::STATE_EMAIL_VERIFIED,
                        UserLifecycle::STATE_REGISTRATION_COMPLETE,
                        UserLifecycle::STATE_APPROVED,
                    ],
                ]),
        );
        $I->seeLink('Is Corporate Investor', '/admin/users?corporateInvestor=1');
    }

    protected function filterProvider(): array
    {
        return [
            [
                'filters' => ['id' => '1'],
            ],
            [
                'filters' => ['isVIP' => 1],
                'dbquery' => null,
            ],
            [
                'filters' => ['wordsOfOwn' => 1],
                'dbquery' => ['wordsOfOwn !=' => null],
            ],
            [
                'filters' => ['phoneNumber' => '257887555'],
                'dbquery' => ['phone2 like' => '%257887555%'],
            ],
            [
                'filters' => [
                    'gender' => 'FEMALE',
                    'isVIP' => 1,
                ],
                'dbquery' => [
                    'gender' => 'FEMALE',
                    'isVIP' => 1,
                ],
            ],
            [
                'filters' => [
                    'isVIP' => 1,
                    'username' => 'y',
                ],
                'dbquery' => [
                    'isVIP' => 1,
                    'username like' => '%y%',
                ],
            ],
            [
                'filters' => [
                    'createdAt_gte' => date_format(new \DateTime('-4 days'), 'Y-m-d'),
                    'createdAt_lt' => date_format(new \DateTime('-1 days'), 'Y-m-d'),
                ],
                'dbquery' => [
                    'createdAt >=' => date_format(
                        new \DateTime('-4 days')->setTime(0, 0),
                        \DateTime::ATOM,
                    ),
                    'createdAt <' => date_format(
                        new \DateTime('-1 days')->setTime(0, 0),
                        \DateTime::ATOM,
                    ),
                ],
            ],
        ];
    }
}
