<?php

namespace App\Tests\Functional\Ops\MonthEnd\Divestment;

use App\Entity\Enum\PaymentType;
use App\Service\MailerService;
use App\Tests\Support\FunctionalTester;

class DivestmentPaymentRunCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    /**
     * @group emails
     */
    public function testRunOrder(FunctionalTester $I): void
    {
        $newOrderId = $I->createPaymentOrder(
            'Lodge de Lac - Cumbria',
            PaymentType::Divestment,
        );

        $I->amOnPage("/admin/monthend/divestments/{$newOrderId}");
        $sharePriceIncrease = 0.03;
        $sharesCirculating = str_replace(
            ',',
            '',
            $I->grabTextFrom('[data-field-name="active-shares-in-circulation"]'),
        );

        $sharesToLiquidate = $sharesCirculating;
        $sharePrice = explode(
            '£',
            $I->grabTextFrom('[data-field-name="share-price"]'),
        )[1];
        $totalToPay = round(
            ($sharePrice + $sharePriceIncrease) * $sharesToLiquidate,
            2,
        );
        $liquidationPrice = $totalToPay / $sharesToLiquidate;
        $capitalDifference = round($totalToPay - ($sharesToLiquidate * $sharePrice), 2);

        $I->amOnPage("/admin/monthend/divestments/{$newOrderId}/generate");
        $I->fillField('#payment_order_generate_shares', $sharesToLiquidate);
        $I->fillField('#payment_order_generate_amount', $totalToPay);
        $I->click('Generate Payments');

        // Check divestment summary
        // Note the payment type has been auto changed to Investment exit when doing a full divestment
        $I->see(
            PaymentType::InvestmentExit->value,
            'section#divestment-summary [data-field-name="payment-type"]',
        );
        $I->see(
            number_format($sharesToLiquidate),
            'section#divestment-summary [data-field-name="shares-being-liquidated"]',
        );
        $I->see(
            number_format($totalToPay, 2),
            'section#divestment-summary [data-field-name="total-to-pay-shareholders"]',
        );

        $I->see(
            round($liquidationPrice, 5),
            'section#divestment-summary [data-field-name="divestment-share-price"]',
        );
        $I->see(
            "+£{$sharePriceIncrease}",
            'section#divestment-summary [data-field-name="divestment-share-price"]',
        );

        $I->see(
            number_format($capitalDifference, 2),
            'section#divestment-summary [data-field-name="capital-gain-or-loss"]',
        );
        $I->see(
            '+'
            . round((100 * $capitalDifference) / ($sharesToLiquidate * $sharePrice), 5)
            . '%',
            'section#divestment-summary [data-field-name="capital-gain-or-loss"]',
        );

        // Reduce the divestment to avoid draining the wallets
        $sharesToLiquidate = 24;
        $totalToPay = 1.84;
        $I->amOnPage("/admin/monthend/divestments/{$newOrderId}/generate");
        $I->fillField('#payment_order_generate_shares', $sharesToLiquidate);
        $I->fillField('#payment_order_generate_amount', $totalToPay);
        $I->click('Generate Payments');

        $I->see(
            PaymentType::Divestment->value,
            'section#divestment-summary [data-field-name="payment-type"]',
        );
        $I->see(
            $sharesToLiquidate,
            'section#divestment-summary [data-field-name="shares-being-liquidated"]',
        );
        $I->see(
            $totalToPay,
            'section#divestment-summary [data-field-name="total-to-pay-shareholders"]',
        );

        $I->click('Approve Payment Order');

        // Check payments summary at the start
        $shareholders = $I->grabTextFrom('[data-field-name="current-shareholders"]');
        $emptyPayments = count($I->grabMultiple(
            '//table[@id="payments-list"]//*[@data-field="shareholding"][text()="0"]',
        ));
        $paymentsExpected = $shareholders - $emptyPayments;
        $firstPaymentAmount = $I->grabTextFrom(
            '#payments-list tbody tr:first-child [data-field="amount"]',
        );
        $I->assertEquals(
            "0.00/{$totalToPay}",
            $I->grabTextFrom('[data-field-name="total-paid-(£)"]'),
        );
        $I->assertEquals(
            "0/{$shareholders}",
            $I->grabTextFrom('[data-field-name="payments-completed"]'),
        );
        $I->assertEquals(
            '-',
            $I->grabTextFrom('[data-field-name="most-recently-completed"]'),
        );
        // There shouldn't be a BuyBack order just yet, that's created when you start paying back
        $I->dontSeeLink('View Buy Back Order');

        // Check payments summary after paying a single payment (the first one)
        $I->click('Pay Single', '#payments-list tbody tr:first-child');
        $I->assertEquals('In-progress', $I->grabTextFrom('[data-field-name="status"]'));
        $firstSharesDivested = $I->grabTextFrom(
            '#payments-list tbody tr:first-child [data-field="shareholding"]',
        );
        $sharesCirculating1 = str_replace(
            ',',
            '',
            $I->grabTextFrom('[data-field-name="active-shares-in-circulation"]'),
        );
        $I->assertEquals(
            $sharesCirculating - $firstSharesDivested,
            $sharesCirculating1,
        );
        $I->assertEquals(
            "{$firstPaymentAmount}/{$totalToPay}",
            $I->grabTextFrom('[data-field-name="total-paid-(£)"]'),
        );
        $I->assertEquals(
            "1/{$shareholders}",
            $I->grabTextFrom('[data-field-name="payments-completed"]'),
        );
        $I->assertStringContainsString(
            date('Y-m-d'),
            $I->grabTextFrom('[data-field-name="most-recently-completed"]'),
        );
        $I->seeLink('View Buy Back Order');
        // Note the link opens in a new tab which makes testing a bit awkward
        $buybackorderUrl = $I->grabAttributeFrom(
            '#divestment-summary header a',
            'href',
        );
        // Check BuyBack order to see it is currently active and the share trade for the one we just paid
        $I->amOnPage($buybackorderUrl);
        $I->see('Active', '#trade-order-info [data-field-name="status"]');
        $I->see('1', '#aggregate-stats [data-field-name="total-count"]');
        $I->see(
            $firstSharesDivested,
            '#aggregate-stats [data-field-name="total-shares"]',
        );
        $I->see(
            $firstPaymentAmount,
            '#aggregate-stats [data-field-name="total-trade-value"]',
        );
        $I->seeNumberOfElements('#share-trades tbody tr', 1);
        $I->see(
            $firstSharesDivested,
            '#share-trades tbody tr:first-child [data-field="quantity"]',
        );
        $I->see(
            $firstPaymentAmount,
            '#share-trades tbody tr:first-child [data-field="tradeValue"]',
        );

        // Run the rest of the payment order
        $I->amOnPage("/admin/monthend/divestments/{$newOrderId}");
        $I->click('Run Payment Order');
        $I->seeCurrentUrlEquals("/admin/monthend/divestments/{$newOrderId}");
        $I->assertEquals('Completed', $I->grabTextFrom('[data-field-name="status"]'));
        $I->assertEquals(
            "{$totalToPay}/{$totalToPay}",
            $I->grabTextFrom('[data-field-name="total-paid-(£)"]'),
        );
        $I->assertEquals(
            "{$shareholders}/{$shareholders}",
            $I->grabTextFrom('[data-field-name="payments-completed"]'),
        );
        $sharesCirculatingEnd = str_replace(
            ',',
            '',
            $I->grabTextFrom('[data-field-name="active-shares-in-circulation"]'),
        );
        $I->assertEquals(
            $sharesCirculating - $sharesToLiquidate,
            $sharesCirculatingEnd,
        );

        // Should see a prompt to return to the monthend checklist
        $assetId = $I->grabFromDatabase('assets', 'id', [
            'name' => 'Lodge de Lac - Cumbria',
        ]);
        $I->seeLink('Back to Monthend Checklist', "/admin/monthend/{$assetId}");

        // Email notifications should have send options
        $mailcatcher = $I->getMailcatcherClient();
        $mailcatcher->delete('/messages');
        $I->click('Manage Email Notifications');
        $I->seeCurrentUrlEquals("/admin/monthend/payments/{$newOrderId}/notifications");
        $sendLinks = $I->grabMultiple(
            '#payments-notification-list tbody tr td:first-child a',
        );
        $paymentRows = $I->grabMultiple('#payments-notification-list tbody tr');
        // Make sure that all the links say the same thing - i.e. "Send"
        $I->assertEquals(['Send'], array_unique($sendLinks));
        $I->assertEquals(count($paymentRows), count($sendLinks));

        // Check emails being sent
        $recipientEmails = $I->grabMultiple(
            '#payments-notification-list tbody tr [data-field="payeeEmail"]',
        );
        $expectedSubjectLine = $I->grabFromDatabase('mails', 'subject', [
            'slug' => MailerService::TYPE_DIVESTMENT_PAYMENT,
        ]);
        // Send single email
        $I->click('Send', '#payments-notification-list tbody tr:first-child');
        $I->see('Payment notification email successfully sent');
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
        $I->assertCount((int) $paymentsExpected, $allMessages);
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
        $I->click('Resend', '#payments-notification-list tbody tr:first-child');
        $I->see('Payment notification email successfully sent');
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

        // Check most recent status logs - partial divestment won't transition the asset
        $I->amOnPage("/admin/products/{$assetId}/status-logs");
        $I->dontSee('Closing', '[data-field-name="current-status"]');

        // Check BuyBack order to see
        $I->amOnPage($buybackorderUrl);
        $I->see(
            $sharesToLiquidate,
            '#trade-order-info [data-field-name="share-quantity"]',
        );
        $I->see(
            $totalToPay,
            '#trade-order-info [data-field-name="derived-trade-value"]',
        );
        $I->see('Completed', '#trade-order-info [data-field-name="status"]');
        $I->see(
            'Lodge de Lac - Cumbria',
            '#trade-order-info [data-field-name="asset"]',
        );
        $I->see($paymentsExpected, '#aggregate-stats [data-field-name="total-count"]');
        $I->see(
            $sharesToLiquidate,
            '#aggregate-stats [data-field-name="total-shares"]',
        );
        $I->see($totalToPay, '#aggregate-stats [data-field-name="total-trade-value"]');
        $I->seeNumberOfElements('#share-trades tbody tr', (int) $paymentsExpected);
    }
}
