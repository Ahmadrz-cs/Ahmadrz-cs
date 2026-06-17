<?php

namespace App\Tests\Functional\Ops\MonthEnd\Repayment;

use App\Entity\Enum\TransferOrderPreset;
use App\Entity\Enum\TransferType;
use App\Tests\Support\FunctionalTester;

class RepaymentTransferCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function checkCreateRepaymentTransfer(FunctionalTester $I): void
    {
        // Lodge de Lac has surplus shares for repayment
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Lodge de Lac - Cumbria',
        ]);

        // Quick create
        $I->amOnPage("/admin/monthend/repayments/transfer/{$assetId}/create");
        $transferOrders = $I->grabColumnFromDatabase('transfer_order', 'id', [
            'description' => TransferOrderPreset::PrefunderRepaymentTransfer->value,
        ]);
        sort($transferOrders);
        $newOrderId = array_pop($transferOrders);

        $transferType = $I->grabFromDatabase('transfer_order', 'transferType', [
            'id' => $newOrderId,
        ]);
        $I->assertEquals(TransferType::PaymentAllocation->value, $transferType);
        $I->seeCurrentUrlEquals(
            "/admin/monthend/repayments/transfer/{$newOrderId}/generate",
        );
        $I->seeLink('Back', "/admin/transfer-orders/{$newOrderId}/manage");
        $I->seeLink('Cancel', "/admin/transfer-orders/{$newOrderId}/manage");

        // Check guideline information is available
        $I->seeElement('section#guidelines [data-field-name="asset"]');
        $I->seeElement(
            'section#guidelines [data-field-name="settlement-wallet-balance"]',
        );
        $I->seeElement(
            'section#guidelines [data-field-name="distribution-wallet-balance"]',
        );
        $I->seeElement('section#guidelines [data-field-name="shares-issued"]');
        $I->seeElement(
            'section#guidelines [data-field-name="active-shares-in-circulation"]',
        );
        $I->seeElement('section#guidelines [data-field-name="surplus-shares-sold"]');
        $I->seeElement('section#guidelines [data-field-name="surplus-value-sold"]');
        $I->seeElement(
            'section#guidelines [data-field-name="shares-recently-settled"]',
        );
        $I->seeElement('section#guidelines [data-field-name="value-recently-settled"]');

        // Wallet fields should be disabled and have values preset
        $settlementWalletId = $I->grabFromDatabase('assets', 'additional_wallet', [
            'id' => $assetId,
        ]);
        $distributionWalletId = $I->grabFromDatabase('assets', 'distributionWalletId', [
            'id' => $assetId,
        ]);
        $I->seeInField(
            '#asset_transfer_request_debitWalletId[disabled]',
            $settlementWalletId,
        );
        $I->seeInField(
            '#asset_transfer_request_creditWalletId[disabled]',
            $distributionWalletId,
        );
        $I->fillField('#asset_transfer_request_amount', '0.04');
        $I->click('Generate Transfers');
        $I->seeCurrentUrlEquals("/admin/transfer-orders/{$newOrderId}/manage");

        // If mismatch between surplus shares and recently settled, see warning about this
        // Emulate this by changing the monthend
        $I->amOnPage("/admin/transfer-orders/{$newOrderId}/edit");
        $I->fillField(
            '#transfer_order_scheduledFor',
            new \DateTime('-2 months')->format('Y-m-d'),
        );
        $I->click('Save Changes');

        // Trying to generate again will result in an overwrite warning
        $I->click('Generate Prefunder Repayment Transfer');
        $I->seeCurrentUrlEquals(
            "/admin/monthend/repayments/transfer/{$newOrderId}/generate",
        );
        $I->seeElement('section#overwrite-warning');
        $I->seeElement('section#share-tracking-warning');
    }
}
