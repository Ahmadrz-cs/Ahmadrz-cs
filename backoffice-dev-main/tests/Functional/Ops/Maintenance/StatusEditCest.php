<?php

namespace App\Tests\Functional\Ops\Maintenance;

use App\Entity\Lifecycle\InvestmentLifecycle;
use App\Tests\Support\FunctionalTester;

class StatusEditCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->loginAdmin();
    }

    public function checkInvestmentStatusEdit(FunctionalTester $I): void
    {
        $sampleInvestment = $I->grabFromDatabase('investments_status', 'id', [
            'lifecycleStatus' => InvestmentLifecycle::STATE_REJECTED,
        ]);
        $I->amOnPage("/admin/investment/{$sampleInvestment}/status");
        $I->see('Status Editor');
        $I->selectOption(
            '#admin_status_lifecycleStatus',
            InvestmentLifecycle::STATE_WITHDRAWN,
        );
        $I->checkOption('#admin_status_confirmation');
        $I->click('Save Changes');
        $I->seeCurrentUrlEquals("/admin/investment/{$sampleInvestment}/status");
        $I->see('Status updated to withdrawn');
        $newStatus = $I->grabFromDatabase('investments_status', 'lifecycleStatus', [
            'id' => $sampleInvestment,
        ]);
        $I->assertEquals(InvestmentLifecycle::STATE_WITHDRAWN, $newStatus);

        // Revert changes
        $I->selectOption(
            '#admin_status_lifecycleStatus',
            InvestmentLifecycle::STATE_REJECTED,
        );
        $I->checkOption('#admin_status_confirmation');
        $I->click('Save Changes');
    }
}
