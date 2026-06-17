<?php

namespace App\Tests\Functional\Ops\Maintenance;

use App\Tests\Support\FunctionalTester;

class UserCommsCleanupCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->loginAdmin();
    }

    public function checkUserCommsCleanupOverview(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/maintenance/user-comms');
        $I->see('Maintenance Tools');
    }

    /**
     * @group listview
     * @dataProvider filterProvider
     */
    public function checkUserCommsListViewFilters(
        FunctionalTester $I,
        \Codeception\Example $example,
    ): void {
        if (empty($example['dbquery'])) {
            $example['dbquery'] = $example['filters'];
        }
        $count = $I->grabNumRecords('users_communications', $example['dbquery']);
        $I->amOnPage(
            '/admin/maintenance/user-comms/list?'
                . http_build_query($example['filters']),
        );
        // Max 10 rows shown by default
        $I->seeNumberOfElements('#usercommslist tbody tr', min(10, $count));
        $I->see($count, '#list-meta-results');
    }

    protected function filterProvider(): array
    {
        return [
            [
                'filters' => ['id' => '1'],
            ],
            [
                'filters' => ['status' => 1],
            ],
            [
                'filters' => [
                    'subject' => 'verif',
                ],
                'dbquery' => [
                    'subject like' => '%verif%',
                ],
            ],
            [
                'filters' => [
                    'createdAt_gte' => date_format(new \DateTime('-4 months'), 'Y-m-d'),
                    'createdAt_lt' => date_format(new \DateTime('-1 months'), 'Y-m-d'),
                ],
                'dbquery' => [
                    'createdAt >=' => date_format(
                        new \DateTime('-4 months')->setTime(0, 0),
                        \DateTime::ATOM,
                    ),
                    'createdAt <' => date_format(
                        new \DateTime('-1 months')->setTime(0, 0),
                        \DateTime::ATOM,
                    ),
                ],
            ],
        ];
    }

    public function checkUserCommsCleanupDeletion(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/maintenance/user-comms');
        $subject = $I->grabTextFrom(
            'table#email-subject-count tbody tr:first-child td[data-field-name="subject"]',
        );

        // Check totals match what's in the database
        $initialTotalCount = $I->grabTextFrom('[data-field-name="total-emails-found"]');
        $totalEmailCount = $I->grabNumRecords('users_communications');
        $I->assertEquals($initialTotalCount, $totalEmailCount);
        $initialCount = $I->grabTextFrom(
            'table#email-subject-count tbody tr:first-child td[data-field-name="count"]',
        );
        // Check specific subject line count matches what's in the database
        $I->seeNumRecords($initialCount, 'users_communications', [
            'subject' => $subject,
        ]);

        // Go to search and delete form
        $I->click('Search and Delete User Comms');
        $I->seeCurrentUrlEquals('/admin/maintenance/user-comms/cleanup');

        // Search for matches and grab the amount it claims will delete
        $I->selectOption('#user_comms_delete_subject', $subject);
        $I->click('Search Comms');
        $expectedToDelete = $I->grabTextFrom('[data-field-name="matches-for-subject"]');

        // Initiate deletion
        $I->click('Delete Comms');
        $I->seeCurrentUrlEquals('/admin/maintenance/user-comms');
        $I->see("{$expectedToDelete} {$subject} user comms deleted", '.alert');

        // Check number of emails deleted matches the search claims
        $I->seeNumRecords(
            (int) $initialCount - (int) $expectedToDelete,
            'users_communications',
            [
                'subject' => $subject,
            ],
        );
        // Only the matching subject emails should be deleted, no extras
        $I->seeNumRecords(
            (int) $totalEmailCount - (int) $expectedToDelete,
            'users_communications',
        );
    }
}
