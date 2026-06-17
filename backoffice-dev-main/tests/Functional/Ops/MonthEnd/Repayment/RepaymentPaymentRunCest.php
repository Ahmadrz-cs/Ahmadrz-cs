<?php

namespace App\Tests\Functional\Ops\MonthEnd\Repayment;

use App\Entity\Enum\PaymentType;
use App\Tests\Support\FunctionalTester;

class RepaymentPaymentRunCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function testRunOrder(FunctionalTester $I): void
    {
        $sharesToRepay = 6;
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Lodge de Lac - Cumbria',
        ]);

        // Grab current repayment progress
        $I->amOnPage("/admin/monthend/{$assetId}/repayments");
        $dashboardTotal = str_replace(
            ',',
            '',
            $I->grabTextFrom(
                '#repayment-progress [data-field-name="original-shares-to-repay"]',
            ),
        );
        $dashboardRepaid = str_replace(
            ',',
            '',
            $I->grabTextFrom(
                '#repayment-progress [data-field-name="shares-already-repaid"]',
            ),
        );
        $dashboardRemaining = str_replace(
            ',',
            '',
            $I->grabTextFrom(
                '#repayment-progress [data-field-name="shares-still-to-repay"]',
            ),
        );

        $newOrderId = $I->createPaymentOrder(
            'Lodge de Lac - Cumbria',
            PaymentType::Repayment,
        );
        $I->amOnPage("/admin/monthend/repayments/{$newOrderId}/generate");
        $I->fillField('#payment_order_generate_shares', $sharesToRepay);
        $I->click('Generate Payments');
        $I->click('Approve Payment Order');

        // Check monthend summary before
        $I->amOnPage('/admin/monthend/repayments');
        $startCirculating = str_replace(
            ',',
            '',
            $I->grabTextFrom(
                '#prefunder-repayment-list tr[data-asset-id="'
                . $assetId
                . '"] [data-field="shares-circulating"]',
            ),
        );
        $startRemaining = str_replace(
            ',',
            '',
            $I->grabTextFrom(
                '#prefunder-repayment-list tr[data-asset-id="'
                . $assetId
                . '"] [data-field="shares-remaining"]',
            ),
        );
        $startAvailable = str_replace(
            ',',
            '',
            $I->grabTextFrom(
                '#prefunder-repayment-list tr[data-asset-id="'
                . $assetId
                . '"] [data-field="shares-available"]',
            ),
        );

        // Check payments summary at the start
        $I->amOnPage("/admin/monthend/repayments/{$newOrderId}");
        $sharePrice = explode(
            '£',
            $I->grabTextFrom('[data-field-name="share-price"]'),
        )[1];
        $totalToPay = round($sharePrice * $sharesToRepay, 2);
        $prefunders = $I->grabTextFrom('[data-field-name="prefunders"]');
        $firstPaymentAmount = $I->grabTextFrom(
            '#payments-list tbody tr:first-child [data-field="amount"]',
        );
        $firstPaymentShares = str_replace(
            ',',
            '',
            $I->grabTextFrom(
                '#payments-list tbody tr:first-child [data-field="current-repayment"]',
            ),
        );
        $I->assertEquals(
            "0.00/{$totalToPay}",
            $I->grabTextFrom('[data-field-name="total-paid-(£)"]'),
        );
        $I->assertEquals(
            "0/{$prefunders}",
            $I->grabTextFrom('[data-field-name="payments-completed"]'),
        );
        $I->assertEquals(
            '-',
            $I->grabTextFrom('[data-field-name="most-recently-completed"]'),
        );

        // Check payment guidelines
        $I->see(
            "{$prefunders}/{$prefunders}",
            'section#payment-guidelines [data-field-name="prefunders-being-paid"]',
        );
        $I->see(
            "0/{$sharesToRepay}",
            'section#payment-guidelines [data-field-name="shares-in-current-order-paid"]',
        );
        $originalSharesToRepay = str_replace(
            ',',
            '',
            $I->grabTextFrom(
                '#repayment-progress [data-field-name="original-shares-to-repay"]',
            ),
        );
        $sharesAlreadyRepaid = str_replace(
            ',',
            '',
            $I->grabTextFrom(
                '#repayment-progress [data-field-name="shares-already-repaid"]',
            ),
        );
        $sharesRemainingInOrder = str_replace(
            ',',
            '',
            $I->grabTextFrom(
                '#repayment-progress [data-field-name="shares-remaining-in-current-order"]',
            ),
        );
        $sharesStillToRepay = str_replace(
            ',',
            '',
            $I->grabTextFrom(
                '#repayment-progress [data-field-name="shares-still-to-repay"]',
            ),
        );
        $I->assertEquals($sharesToRepay, $sharesRemainingInOrder);
        $I->assertEquals(
            $originalSharesToRepay,
            $sharesAlreadyRepaid + $sharesRemainingInOrder + $sharesStillToRepay,
        );
        // Check the progress bar is showing the right values
        $I->assertEqualsWithDelta(
            (100 * $sharesAlreadyRepaid) / $originalSharesToRepay,
            $I->grabAttributeFrom(
                '.progress [aria-label="percentage-already-paid"]',
                'aria-valuenow',
            ),
            0.1,
        );
        $I->assertEqualsWithDelta(
            (100 * $sharesRemainingInOrder) / $originalSharesToRepay,
            $I->grabAttributeFrom(
                '.progress [aria-label="percentage-left-in-order"]',
                'aria-valuenow',
            ),
            0.1,
        );
        $I->assertEqualsWithDelta(
            (100 * $sharesStillToRepay) / $originalSharesToRepay,
            $I->grabAttributeFrom(
                '.progress [aria-label="percentage-still-to-repay"]',
                'aria-valuenow',
            ),
            0.1,
        );

        // Check payments summary after paying a single payment (the first one)
        $I->click('Pay Single', '#payments-list tbody tr:first-child');
        $I->assertEquals('In-progress', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals(
            "{$firstPaymentAmount}/{$totalToPay}",
            $I->grabTextFrom('[data-field-name="total-paid-(£)"]'),
        );
        $I->assertEquals(
            "1/{$prefunders}",
            $I->grabTextFrom('[data-field-name="payments-completed"]'),
        );
        $I->assertStringContainsString(
            date('Y-m-d'),
            $I->grabTextFrom('[data-field-name="most-recently-completed"]'),
        );

        $I->see(
            "{$prefunders}/{$prefunders}",
            'section#payment-guidelines [data-field-name="prefunders-being-paid"]',
        );
        $I->see(
            "{$firstPaymentShares}/{$sharesToRepay}",
            'section#payment-guidelines [data-field-name="shares-in-current-order-paid"]',
        );
        $originalSharesToRepay2 = str_replace(
            ',',
            '',
            $I->grabTextFrom(
                '#repayment-progress [data-field-name="original-shares-to-repay"]',
            ),
        );
        $sharesAlreadyRepaid2 = str_replace(
            ',',
            '',
            $I->grabTextFrom(
                '#repayment-progress [data-field-name="shares-already-repaid"]',
            ),
        );
        $sharesRemainingInOrder2 = str_replace(
            ',',
            '',
            $I->grabTextFrom(
                '#repayment-progress [data-field-name="shares-remaining-in-current-order"]',
            ),
        );
        $sharesStillToRepay2 = str_replace(
            ',',
            '',
            $I->grabTextFrom(
                '#repayment-progress [data-field-name="shares-still-to-repay"]',
            ),
        );
        $I->assertEquals($originalSharesToRepay, $originalSharesToRepay2);
        $I->assertEquals(
            $sharesAlreadyRepaid2,
            $sharesAlreadyRepaid + $firstPaymentShares,
        );
        $I->assertEquals(
            $sharesRemainingInOrder2,
            $sharesRemainingInOrder - $firstPaymentShares,
        );
        $I->assertEquals(
            $originalSharesToRepay,
            $sharesAlreadyRepaid2 + $sharesRemainingInOrder2 + $sharesStillToRepay2,
        );
        // Check the progress bar is showing the right values
        $I->assertEqualsWithDelta(
            (100 * $sharesAlreadyRepaid2) / $originalSharesToRepay2,
            $I->grabAttributeFrom(
                '.progress [aria-label="percentage-already-paid"]',
                'aria-valuenow',
            ),
            0.001,
        );
        $I->assertEqualsWithDelta(
            (100 * $sharesRemainingInOrder2) / $originalSharesToRepay2,
            $I->grabAttributeFrom(
                '.progress [aria-label="percentage-left-in-order"]',
                'aria-valuenow',
            ),
            0.001,
        );
        $I->assertEqualsWithDelta(
            (100 * $sharesStillToRepay2) / $originalSharesToRepay2,
            $I->grabAttributeFrom(
                '.progress [aria-label="percentage-still-to-repay"]',
                'aria-valuenow',
            ),
            0.001,
        );

        // Run the rest of the payment order
        $I->click('Run Payment Order');
        $I->seeCurrentUrlEquals("/admin/monthend/repayments/{$newOrderId}");
        $I->assertEquals('Completed', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals(
            "{$totalToPay}/{$totalToPay}",
            $I->grabTextFrom('[data-field-name="total-paid-(£)"]'),
        );
        $I->assertEquals(
            "{$prefunders}/{$prefunders}",
            $I->grabTextFrom('[data-field-name="payments-completed"]'),
        );

        $I->see(
            "{$prefunders}/{$prefunders}",
            'section#payment-guidelines [data-field-name="prefunders-being-paid"]',
        );
        $I->see(
            "{$sharesToRepay}/{$sharesToRepay}",
            'section#payment-guidelines [data-field-name="shares-in-current-order-paid"]',
        );
        $originalSharesToRepay2 = str_replace(
            ',',
            '',
            $I->grabTextFrom(
                '#repayment-progress [data-field-name="original-shares-to-repay"]',
            ),
        );
        $sharesAlreadyRepaid2 = str_replace(
            ',',
            '',
            $I->grabTextFrom(
                '#repayment-progress [data-field-name="shares-already-repaid"]',
            ),
        );
        $sharesRemainingInOrder2 = str_replace(
            ',',
            '',
            $I->grabTextFrom(
                '#repayment-progress [data-field-name="shares-remaining-in-current-order"]',
            ),
        );
        $sharesStillToRepay2 = str_replace(
            ',',
            '',
            $I->grabTextFrom(
                '#repayment-progress [data-field-name="shares-still-to-repay"]',
            ),
        );
        $I->assertEquals($originalSharesToRepay, $originalSharesToRepay2);
        $I->assertEquals($sharesAlreadyRepaid2, $sharesAlreadyRepaid + $sharesToRepay);
        $I->assertEquals(
            $sharesRemainingInOrder2,
            $sharesRemainingInOrder - $sharesToRepay,
        );
        $I->assertEquals(
            $originalSharesToRepay,
            $sharesAlreadyRepaid2 + $sharesRemainingInOrder2 + $sharesStillToRepay2,
        );
        // Check the progress bar is showing the right values
        $I->assertEqualsWithDelta(
            (100 * $sharesAlreadyRepaid2) / $originalSharesToRepay2,
            $I->grabAttributeFrom(
                '.progress [aria-label="percentage-already-paid"]',
                'aria-valuenow',
            ),
            0.001,
        );
        $I->assertEqualsWithDelta(
            (100 * $sharesRemainingInOrder2) / $originalSharesToRepay2,
            $I->grabAttributeFrom(
                '.progress [aria-label="percentage-left-in-order"]',
                'aria-valuenow',
            ),
            0.001,
        );
        $I->assertEqualsWithDelta(
            (100 * $sharesStillToRepay2) / $originalSharesToRepay2,
            $I->grabAttributeFrom(
                '.progress [aria-label="percentage-still-to-repay"]',
                'aria-valuenow',
            ),
            0.001,
        );

        // Should see a prompt to return to the monthend checklist
        $I->seeLink('Back to Monthend Checklist', "/admin/monthend/{$assetId}");

        // Check the repayment dashboard progress bar is updated
        $I->amOnPage("/admin/monthend/{$assetId}/repayments");
        $dashboardTotal2 = str_replace(
            ',',
            '',
            $I->grabTextFrom(
                '#repayment-progress [data-field-name="original-shares-to-repay"]',
            ),
        );
        $dashboardRepaid2 = str_replace(
            ',',
            '',
            $I->grabTextFrom(
                '#repayment-progress [data-field-name="shares-already-repaid"]',
            ),
        );
        $dashboardRemaining2 = str_replace(
            ',',
            '',
            $I->grabTextFrom(
                '#repayment-progress [data-field-name="shares-still-to-repay"]',
            ),
        );
        $I->assertEquals($dashboardTotal, $dashboardTotal2);
        $I->assertEquals($dashboardRepaid2, $dashboardRepaid + $sharesToRepay);
        $I->assertEquals($dashboardTotal, $dashboardRepaid2 + $dashboardRemaining2);

        // Check monthend summary after
        $I->amOnPage('/admin/monthend/repayments');
        $endCirculating = str_replace(
            ',',
            '',
            $I->grabTextFrom(
                '#prefunder-repayment-list tr[data-asset-id="'
                . $assetId
                . '"] [data-field="shares-circulating"]',
            ),
        );
        $endRemaining = str_replace(
            ',',
            '',
            $I->grabTextFrom(
                '#prefunder-repayment-list tr[data-asset-id="'
                . $assetId
                . '"] [data-field="shares-remaining"]',
            ),
        );
        $endAvailable = str_replace(
            ',',
            '',
            $I->grabTextFrom(
                '#prefunder-repayment-list tr[data-asset-id="'
                . $assetId
                . '"] [data-field="shares-available"]',
            ),
        );
        $I->assertEquals($startCirculating, $endCirculating + $sharesToRepay);
        $I->assertEquals($startRemaining, $endRemaining + $sharesToRepay);
        $I->assertEquals($startAvailable, $endAvailable + $sharesToRepay);

        // Typically the shares repaid won't be enough to complete the prefunding sell order
        // So it should still be active
        // This may eventually break if you rerun too much
        $I->amOnPage("/admin/monthend/repayments/{$newOrderId}");
        $sellorderId = $I->grabTextFrom(
            '#payments-list tr:first-child [data-field="sellOrderId"] a',
        );
        $I->amOnPage("/admin/trade-orders/{$sellorderId}");
        $I->see('Active', '#trade-order-info [data-field-name="status"]');
    }
}
