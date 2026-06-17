<?php

namespace App\Tests\Functional\Ops\Maintenance;

use App\Tests\Support\FunctionalTester;

class OAuth2ArtifactCleanupCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->loginAdmin();
    }

    public function checkUserCommsCleanupOverview(FunctionalTester $I): void
    {
        // This test will mutate the database
        // Thus cannot be rerun without reloading the fixture
        $I->amOnPage('/admin/maintenance/oauth2/cleanup');
        $cleanupCounts = $I->grabMultiple('[data-field-name="expiredCount"]');
        $I->checkOption('#form_confirmation');
        $I->click('Clear Expired');
        $I->seeCurrentUrlEquals('/admin/maintenance/oauth2/cleanup');
        $I->see('Expired OAuth2 artifacts cleared');
        $expiredCounts = $I->grabMultiple('[data-field-name="expiredCount"]');
        $I->assertNotEquals($cleanupCounts, $expiredCounts);
        foreach ($expiredCounts as $count) {
            $I->assertEquals('0', $count);
        }
    }
}
