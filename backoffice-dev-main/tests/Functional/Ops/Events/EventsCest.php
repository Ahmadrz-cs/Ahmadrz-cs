<?php

namespace App\Tests\Functional\Ops\Events;

use App\Tests\Support\FunctionalTester;

class EventsCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->loginAdmin();
    }

    public function checkMangopayEventListElements(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/events/mangopay');

        $elements = [
            'Timestamp',
            'Date',
            'Event Type',
            'Resource Id',
        ];
        $locator = '#mangopay-events thead tr th';
        $I->loopCheckElements($elements, $locator);

        // Check they've loaded in correctly - should be at least 1 event
        $I->assertNotEmpty($I->grabMultiple('#mangopay-events tbody tr'));

        // Check that the page limit and filter works (only checking event type)
        // Ideally pick an event that the backoffice tests will usually generate
        $dateLimit = new \DateTime('-3 days');
        $eventTypeSample = 'TRANSFER_NORMAL_SUCCEEDED';
        $I->selectOption('#perPage', '5');
        $I->selectOption('#filters_EventType', $eventTypeSample);
        $I->fillField('#filters_BeforeDate', $dateLimit->format('Y-m-d H:i:s'));
        $I->click('Apply Filters');

        $I->seeNumberOfElements('#mangopay-events tbody tr', [1, 5]);
        $timestamps = $I->grabMultiple(
            '#mangopay-events tbody tr td[data-field="timestamp"]',
        );
        $eventTypes = $I->grabMultiple(
            '#mangopay-events tbody tr td[data-field="event-type"]',
        );
        foreach ($timestamps as $timestamp) {
            $I->assertLessOrEquals($dateLimit->getTimestamp(), $timestamp);
        }
        foreach ($eventTypes as $eventType) {
            $I->assertEquals($eventTypeSample, $eventType);
        }
    }
}
