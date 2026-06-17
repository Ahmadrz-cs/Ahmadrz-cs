<?php

namespace App\Tests\Functional\Ops\Maintenance;

use App\Tests\Support\FunctionalTester;

class CardCleanupCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->loginAdmin();
    }

    public function checkCardCleanupOverview(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/maintenance/card/cleanup?trackerOnly=1');
        $I->see('Maintenance Tools');
        $I->seeElement('#cleanup-tracker-info');
        $I->seeElement('#user-info');
        $I->seeLink('Reset Task Tracker', '/admin/maintenance/card/cleanup/reset');

        $I->selectOption('#card_cleanup_batchSize', '1');
        $I->checkOption('#card_cleanup_confirmation');
        $I->click('Cleanup User Card Registrations');
        $I->see('Cleanup successful');
        $I->seeCurrentUrlEquals('/admin/maintenance/card/cleanup');

        $I->click('Configure Cleanup Job');
        $I->seeCurrentUrlEquals('/admin/maintenance/jobs/card/cleanup');
    }
}
