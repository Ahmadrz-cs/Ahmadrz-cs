<?php

namespace App\Tests\Functional\Ops\Maintenance;

use App\Tests\Support\FunctionalTester;

class ActivityLogCleanupCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->loginAdmin();
    }

    public function checkUserCommsCleanupOverview(FunctionalTester $I): void
    {
        // Default time range is 3 months or older
        $fewMonthsAgo = new \DateTime('-4 months');
        // Insert some entries
        foreach (range(1, 4) as $iteration) {
            $I->haveInDatabase('ext_log_entries', [
                'action' => 'create',
                'logged_at' => $fewMonthsAgo->format('Y-m-d H:i:s'),
                'object_class' => 'test old',
                'version' => $iteration,
            ]);
        }
        foreach (range(1, 2) as $iteration) {
            $I->haveInDatabase('ext_log_entries', [
                'action' => 'create',
                'logged_at' => new \DateTime()->format('Y-m-d H:i:s'),
                'object_class' => 'test now',
                'version' => $iteration,
            ]);
        }
        $I->amOnPage('/admin/maintenance/activity-logs/cleanup');
        $logsToCleanup = $I->grabTextFrom('[data-field-name="logs-found"]');
        // Should be 4 older ones we created earlier
        $I->assertEquals(4, $logsToCleanup);
        $I->click('Delete Logs');
        $I->seeCurrentUrlEquals('/admin/maintenance/activity-logs/cleanup');
        $I->see("{$logsToCleanup} activity logs deleted");
        $I->assertEquals(0, $I->grabTextFrom('[data-field-name="logs-found"]'));
    }
}
