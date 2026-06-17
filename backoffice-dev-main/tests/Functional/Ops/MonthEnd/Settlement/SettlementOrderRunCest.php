<?php

namespace App\Tests\Functional\Ops\MonthEnd\Settlement;

use App\Entity\Enum\TradeStatus;
use App\Service\MailerService;
use App\Service\MonthEndService;
use App\Tests\Support\FunctionalTester;

class SettlementOrderRunCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function testRunOrder(FunctionalTester $I): void
    {
        $newOrderId = $I->createSettlementOrder('Kolness by the Moor - Okehampton');
        $I->amOnPage("/admin/monthend/settlements/{$newOrderId}/edit");
        $nextMonth = new \DateTime('first day of this month');
        $I->fillField(
            '#monthend_order_edit_scheduledFor',
            $nextMonth->format('Y-m-01'),
        );
        $I->click('Save Changes');
        $I->amOnPage("/admin/monthend/settlements/{$newOrderId}/generate");
        $shareTradeId = $I->grabTextFrom(
            "#share-trades-list tbody tr:first-child [data-field='id'] a",
        );
        $I->click('Generate for Trade', '#share-trades-list tbody tr:first-child');

        $I->amOnPage("/admin/monthend/settlements/{$newOrderId}/generate-stamp-duty");
        $I->click('Generate All Transfers');
        $I->seeCurrentUrlEquals("/admin/monthend/settlements/{$newOrderId}");

        // Lower the value of the settlements to reduce need to rebalance wallets
        $transfersCount = count($I->grabMultiple('#transfers-list tbody tr'));
        $I->click('Edit', '#transfers-list tbody tr:nth-child(1)');
        $I->fillField('#asset_transfer_request_amount', '0.04');
        $I->assertStringContainsString(
            MonthEndService::DESCRIPTION_PRESETS['settlement'],
            $I->grabValueFrom('#asset_transfer_request_description'),
        );
        $I->click('Save Changes');
        if (2 == $transfersCount) {
            $I->click('Edit', '#transfers-list tbody tr:nth-child(2)');
            $I->fillField('#asset_transfer_request_amount', '0.02');
            // $I->seeInField('#asset_transfer_request_description', MonthEndService::DESCRIPTION_PRESETS['stamp duty']);
            $I->assertStringContainsString(
                MonthEndService::DESCRIPTION_PRESETS['stamp duty'],
                $I->grabValueFrom('#asset_transfer_request_description'),
            );
            $I->click('Save Changes');
        }
        $I->click('Approve Transfer Order');

        if (1 == $transfersCount) {
            $I->assertEquals(
                '0.00/0.04',
                $I->grabTextFrom('[data-field-name="total-transferred-(£)"]'),
            );
            $I->assertEquals(
                '0/1',
                $I->grabTextFrom('[data-field-name="transfers-completed"]'),
            );
            $I->assertEquals(
                '-',
                $I->grabTextFrom('[data-field-name="most-recently-completed"]'),
            );

            $I->click('Run Transfer Order');
            $I->seeCurrentUrlEquals("/admin/monthend/settlements/{$newOrderId}");
            $I->assertEquals(
                'Completed',
                $I->grabTextFrom('[data-field-name="status"]'),
            );
            $I->assertEquals(
                '0.04/0.04',
                $I->grabTextFrom('[data-field-name="total-transferred-(£)"]'),
            );
            $I->assertEquals(
                '1/1',
                $I->grabTextFrom('[data-field-name="transfers-completed"]'),
            );
        } else {
            // Check transfers summary at the start
            $I->assertEquals(
                '0.00/0.06',
                $I->grabTextFrom('[data-field-name="total-transferred-(£)"]'),
            );
            $I->assertEquals(
                '0/2',
                $I->grabTextFrom('[data-field-name="transfers-completed"]'),
            );
            $I->assertEquals(
                '-',
                $I->grabTextFrom('[data-field-name="most-recently-completed"]'),
            );

            // Check transfers summary after paying a single transfer (the first one)
            $I->click('Transfer Single', '#transfers-list tbody tr:first-child');
            $I->assertEquals(
                'In-progress',
                $I->grabTextFrom('[data-field-name="status"]'),
            );
            $I->assertEquals(
                '0.04/0.06',
                $I->grabTextFrom('[data-field-name="total-transferred-(£)"]'),
            );
            $I->assertEquals(
                '1/2',
                $I->grabTextFrom('[data-field-name="transfers-completed"]'),
            );
            $I->assertStringContainsString(
                date('Y-m-d'),
                $I->grabTextFrom('[data-field-name="most-recently-completed"]'),
            );

            // Run the rest of the transfer order
            $I->click('Run Transfer Order');
            $I->seeCurrentUrlEquals("/admin/monthend/settlements/{$newOrderId}");
            $I->assertEquals(
                'Completed',
                $I->grabTextFrom('[data-field-name="status"]'),
            );
            $I->assertEquals(
                '0.06/0.06',
                $I->grabTextFrom('[data-field-name="total-transferred-(£)"]'),
            );
            $I->assertEquals(
                '2/2',
                $I->grabTextFrom('[data-field-name="transfers-completed"]'),
            );
        }
        // Should see a prompt to return to the monthend checklist
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Kolness by the Moor - Okehampton',
        ]);
        $I->seeLink('Back to Monthend Checklist', "/admin/monthend/{$assetId}");

        // Check the share-trade has been settled (updated status)
        $I->amOnPage("/admin/share-trades/{$shareTradeId}");
        $I->see(
            TradeStatus::Settled->value,
            '#share-trade-info [data-field-name="status"]',
        );

        // Return to the settlement order
        $I->amOnPage("/admin/monthend/settlements/{$newOrderId}");

        // Email notifications should have send options
        $mailcatcher = $I->getMailcatcherClient();
        $mailcatcher->delete('/messages');
        $I->click('Manage Email Notifications');
        $I->seeCurrentUrlEquals(
            "/admin/monthend/settlements/{$newOrderId}/notifications",
        );
        $sendLinks = $I->grabMultiple(
            '#transfers-notification-list tbody tr td:last-child a',
        );
        $paymentRows = $I->grabMultiple('#transfers-notification-list tbody tr');
        // Make sure that all the links say the same thing - i.e. "Send"
        $I->assertCount(1, array_unique($sendLinks));
        $I->assertEquals('Send', trim(array_unique($sendLinks)[0]));
        $I->assertEquals(count($paymentRows), count($sendLinks));

        // Check emails being sent
        $recipientEmails = $I->grabMultiple(
            '#transfers-notification-list tbody tr [data-field="userEmail"]',
        );
        $expectedSubjectLine = 'Your investment has been settled';
        // Send single email
        $I->click('Send', '#transfers-notification-list tbody tr:first-child');
        $I->see('Settlement notification email successfully sent');
        $emailMetadata = json_decode(
            (string) $mailcatcher->get('/messages/1.json')->getBody(),
            true,
        );
        $I->assertEquals($expectedSubjectLine, $emailMetadata['subject']);
        $I->assertEquals("<{$recipientEmails[0]}>", $emailMetadata['recipients'][0]);
        $I->assertCount(1, json_decode(
            (string) $mailcatcher->get('/messages')->getBody(),
            true,
        ));

        // Send any remaining
        $I->click('Send All Notifications');
        $I->see('All remaining email notifications successfully sent');
        $allMessages = json_decode(
            (string) $mailcatcher->get('/messages')->getBody(),
            true,
        );
        $I->assertCount(count($paymentRows), $allMessages);
        foreach ($allMessages as $index => $meta) {
            $I->assertEquals($expectedSubjectLine, $meta['subject']);
            $I->assertEquals("<{$recipientEmails[$index]}>", $meta['recipients'][0]);
        }

        // Attempting to resend all will do nothing
        $mailcatcher->delete('/messages');
        $I->click('Send All Notifications');
        $allMessages = json_decode(
            (string) $mailcatcher->get('/messages')->getBody(),
            true,
        );
        $I->assertEmpty($allMessages);

        // Resend an email
        $I->click('Resend', '#transfers-notification-list tbody tr:first-child');
        $I->see('Settlement notification email successfully sent');
        $emailMetadata = json_decode(
            (string) $mailcatcher->get('/messages/1.json')->getBody(),
            true,
        );
        $I->assertEquals($expectedSubjectLine, $emailMetadata['subject']);
        $I->assertEquals("<{$recipientEmails[0]}>", $emailMetadata['recipients'][0]);
        $I->assertCount(1, json_decode(
            (string) $mailcatcher->get('/messages')->getBody(),
            true,
        ));

        // Change the share-trade back to approved to allow reruns
        // Note that this will set ALL status logs for that share trade back to unsettled
        $I->updateInDatabase(
            'share_trade_status_log',
            ['status' => TradeStatus::Unsettled->value],
            ['shareTrade_id' => $shareTradeId],
        );
    }
}
