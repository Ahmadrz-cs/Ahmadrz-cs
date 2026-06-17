<?php

namespace App\Tests\Functional\Ops\MonthEnd\Divestment;

use App\Entity\Enum\AssetStatus;
use App\Entity\Enum\PaymentType;
use App\Entity\Enum\TradeOrderStatus;
use App\Service\MailerService;
use App\Tests\Support\FunctionalTester;

class DivestmentPaymentRunExitCest
{
    private string $assetId = '';
    private string $newOrderId = '';
    private array $divestmentRequestIds = [];

    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
        $this->newOrderId = $I->createPaymentOrder(
            'Lodge de Lac - Cumbria',
            PaymentType::Divestment,
        );
        $this->assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Lodge de Lac - Cumbria',
        ]);

        $I->amOnPage("/admin/monthend/divestments/{$this->newOrderId}");
        // Get rid of the remaining shares
        $sharesToLiquidate = str_replace(
            ',',
            '',
            $I->grabTextFrom('[data-field-name="active-shares-in-circulation"]'),
        );

        $I->amOnPage("/admin/monthend/divestments/{$this->newOrderId}/generate");
        $I->fillField('#payment_order_generate_shares', $sharesToLiquidate);
        $I->fillField('#payment_order_generate_amount', 0);
        $I->click('Generate Payments');

        // Get the IDs of all the paymentRequests
        $this->divestmentRequestIds = $I->grabColumnFromDatabase(
            'payment_request',
            'id',
            ['paymentOrder_id' => $this->newOrderId],
        );
    }

    public function _after(FunctionalTester $I)
    {
        // Change the share-trade back to cancelled to allow reruns
        foreach ($this->divestmentRequestIds as $divestmentId) {
            $shareTradeId = $I->grabFromDatabase('payment_request', 'shareTrade_id', [
                'id' => $divestmentId,
            ]);
            $I->updateInDatabase(
                'share_trade_status_log',
                ['status' => TradeOrderStatus::Cancelled->value],
                ['shareTrade_id' => $shareTradeId],
            );
        }
        // Also update all of the asset's status logs back to active to allow reruns
        $I->updateInDatabase(
            'asset_status_log',
            ['status' => AssetStatus::Active->value],
            ['asset_id' => $this->assetId],
        );
    }

    public function testInvestmentExitAssetStatusLog(FunctionalTester $I): void
    {
        // Check most recent status logs
        $I->amOnPage("/admin/products/{$this->assetId}/status-logs");
        $I->see('Active', '[data-field-name="current-status"]');

        // Make sure we are doing an investment exit
        $I->amOnPage("/admin/monthend/divestments/{$this->newOrderId}");
        $I->see(
            PaymentType::InvestmentExit->value,
            'section#divestment-summary [data-field-name="payment-type"]',
        );

        // Approve and run the order
        $I->click('Approve Payment Order');
        $I->click('Run Payment Order');

        $I->seeCurrentUrlEquals("/admin/monthend/divestments/{$this->newOrderId}");
        $I->assertEquals('Completed', $I->grabTextFrom('[data-field-name="status"]'));

        // Check most recent status logs
        $I->amOnPage("/admin/products/{$this->assetId}/status-logs");
        $I->see(
            'Archived',
            '#status-logs-list tbody tr:nth-last-child(1) [data-field-name="status"]',
        );
        $I->see('Archived', '[data-field-name="current-status"]');
    }
}
