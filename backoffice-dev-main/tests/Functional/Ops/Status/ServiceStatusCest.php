<?php

namespace App\Tests\Functional\Ops\Status;

use App\Tests\Support\FunctionalTester;

final class ServiceStatusCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->loginAdmin();
    }

    public function checkServiceStatusIndex(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/status');
        $I->see('Service Integration Statuses');
        $I->see('Mangopay API Rate Limits');

        $I->seeLink('Check Mangopay Service', '/admin/status/mangopay');
        $I->seeLink('Check Contego Service', '/admin/status/contego');
        $I->seeLink('Check Salesforce Service', '/admin/status/salesforce');
        $I->seeLink('Check Mailchimp Service', '/admin/status/mailchimp');
        $I->seeLink('Check Document Storage Service', '/admin/status/docstore');

        $elements = [
            'Service',
            'Status',
            'Last Checked',
            'Time Period',
            'Calls Made',
            'Calls Remaining',
            'Next Reset',
        ];
        $I->loopCheckElements($elements, 'table th');

        // Sanity check service status checks - Mangopay will be a separate test
        // Does not matter if the check is successful, we are testing for how the check is handled
        // Should always handle errors and return to status dashboard
        $I->amOnPage('/admin/status/contego');
        $I->seeCurrentUrlEquals('/admin/status');
        $I->amOnPage('/admin/status/salesforce');
        $I->seeCurrentUrlEquals('/admin/status');
        $I->amOnPage('/admin/status/mailchimp');
        $I->seeCurrentUrlEquals('/admin/status');
        $I->amOnPage('/admin/status/docstore');
        $I->seeCurrentUrlEquals('/admin/status');
    }

    public function checkMangopayStatus(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/status/mangopay');
        $I->seeCurrentUrlEquals('/admin/status');

        // Handle issue where rate limit headers are missing (due to issue on Mangopay's end)
        // Verify that the status is successful
        $actual = $I->grabTextFrom(
            'table#service-status tbody tr:first-child td:nth-child(2)',
        );
        $I->assertNotEquals('N/A', $actual);
        // Then check if rate limits exist
        // If it does, then check that there are exactly 4 rate limit periods given
        $rateLimitTableRows = $I->grabMultiple('table#mangopay-rate-limits tbody tr');
        if (count($rateLimitTableRows) > 0) {
            $I->seeNumberOfElements('table#mangopay-rate-limits tbody tr', [4, 6]);
        } else {
            echo
                PHP_EOL
                    . PHP_EOL
                    . 'Mangopay connection working but rate limit headers missing. Possible issue on Mangopay end.'
                    . PHP_EOL
                    . PHP_EOL
            ;
        }
    }
}
