<?php

namespace App\Tests\Functional\Cms\Offering;

use App\Entity\Lifecycle\OfferingLifecycle;
use App\Tests\Support\FunctionalTester;

class OfferingListCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    /**
     * @group listview
     * @dataProvider visibilityProvider
     */
    public function checkVisibilityToggle(
        FunctionalTester $I,
        \Codeception\Example $example,
    ) {
        $I->amOnPage('/admin/offering');

        $id = $I->grabTextFrom('tbody tr:nth-child(1)  td:nth-child(1)');
        $I->amOnPage(
            '/admin/offering/' . $id . '/visibility/' . $example['visibility'],
        );
        if ($example['badge']) {
            $I->see($example['badge'], 'tbody tr:nth-child(1) span');
        }
    }

    /**
     * @return array
     */
    protected function visibilityProvider()
    {
        yield ['visibility' => '1', 'badge' => 'admin'];
        yield ['visibility' => '2', 'badge' => 'vip'];
        yield ['visibility' => '0', 'badge' => false];
    }

    public function checkModeSwitches(FunctionalTester $I)
    {
        $I->amOnPage('/admin/offering/1/retail-mode');
        $I->amOnPage('/admin/offering/1/prefunding-mode');
        $I->see('set to prefunding type');
        $I->assertEquals('prefunding', $I->grabFromDatabase(
            'offerings',
            'offeringType',
            ['id' => 1],
        ));
        $I->assertEquals('2', $I->grabFromDatabase('offerings', 'visibility', [
            'id' => 1,
        ]));
        // $I->amOnPage('/admin/offering/1/edit');
        // $I->seeOptionIsSelected('input[name="offering[offeringType]"]', 'prefunding');
        // $I->seeOptionIsSelected('select[name="offering[visibility]"]', 'Vip');

        $I->amOnPage('/admin/offering/1/retail-mode');
        $I->see('set to retail type');
        $I->assertEquals('retail', $I->grabFromDatabase('offerings', 'offeringType', [
            'id' => 1,
        ]));
        $I->assertEquals('0', $I->grabFromDatabase('offerings', 'visibility', [
            'id' => 1,
        ]));
        // $I->amOnPage('/admin/offering/1/edit');
        // $I->seeOptionIsSelected('input[name="offering[offeringType]"]', 'retail');
        // $I->seeOptionIsSelected('select[name="offering[visibility]"]', 'Auto (Default)');

        // return offering 1 to prefunding mode
        $I->amOnPage('/admin/offering/1/prefunding-mode');
    }

    public function checkFeaturedToggle(FunctionalTester $I)
    {
        $I->amOnPage('/admin/offering');
        $I->see('Toggle Featured');

        $startingState = (bool) $I->grabFromDatabase('offerings', 'isFeatured', [
            'id' => 1,
        ]);
        $endState = !$startingState;
        $I->amOnPage('/admin/offering/1/feature-offering');
        $I->assertEquals((int) $endState, $I->grabFromDatabase(
            'offerings',
            'isFeatured',
            ['id' => 1],
        ));
        $I->seeElement('tbody tr:nth-child(1) i.fa-star');

        $I->amOnPage('/admin/offering/1/feature-offering');
        $I->assertEquals((int) $startingState, $I->grabFromDatabase(
            'offerings',
            'isFeatured',
            ['id' => 1],
        ));
    }

    /**
     * @group listview
     */
    public function checkListViewElements(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/offering');
        $I->seeLink('Add Offering', '/admin/offering/add');

        $elements = [
            '#',
            'Name',
            'type',
            'Created',
            'Asset Share Price',
            'Raised/Target',
            'Investment ID',
            'Visibility',
            'Status',
            'Actions',
        ];
        // check table headers
        $I->loopCheckElements($elements, 'thead th');

        $filterLabels = [
            'Offering Name',
            'Asset id',
            'Retail or Prefunding',
            'Visibility',
            'Featured',
            'First party or Relisted',
            'Primary or Secondary',
            'Status',
            'CreatedAt Start',
            'CreatedAt End',
            'Items Per Page',
            'Order by',
            'Order Direction',
        ];
        // check table filters
        $I->loopCheckElements($filterLabels, 'form label');
        $I->see('Show/Hide Status', 'form button');

        $I->amOnPage('/admin/offering');
        $I->seeNumberOfElements('#offeringlist tbody tr', 10);
        $I->selectOption('form select[name=perPage]', '5');
        $I->click('Apply Filters');
        $I->seeNumberOfElements('#offeringlist tbody tr', 5);

        // check max page bracketing (to deal with filter changing)
        $I->amOnPage('/admin/offering?page=1000');
        // Sends you to last page
        $I->seeElement(['css' => '.pagination li:last-child.disabled']);

        // Check view related investment link exists
        $sampleId = $I->searchDatabaseByStatus('offerings', 'published');
        $I->seeLink('', '/admin/investment?offeringId=' . $sampleId);
    }

    /**
     * @group listview
     * @dataProvider filterProvider
     */
    public function checkListViewFilters(
        FunctionalTester $I,
        \Codeception\Example $example,
    ): void {
        if (empty($example['dbquery'])) {
            $example['dbquery'] = $example['filters'];
        }
        $count = $I->grabNumRecords('offerings', $example['dbquery']);
        $I->amOnPage(
            '/admin/offering?' . http_build_query($example['filters']) . '&perPage=50',
        );
        $I->seeNumberOfElements('#offeringlist tbody tr', min(50, $count));
        $I->see($count, '#list-meta-results');
    }

    /**
     * @group listview
     */
    public function checkListViewFiltersStatus(FunctionalTester $I): void
    {
        // status filter separate as it uses an array rather than single values
        $query = [
            'lifecycleStatus' => [
                OfferingLifecycle::STATE_DRAFT,
                OfferingLifecycle::STATE_SUBMITTED,
                OfferingLifecycle::STATE_APPROVED,
            ],
        ];
        $I->amOnPage('/admin/offering?' . http_build_query($query));
        foreach ($query['lifecycleStatus'] as $status) {
            $I->see($status, '#offeringlist tbody tr .badge');
        }
        $omittedStatuses = [
            OfferingLifecycle::STATE_PUBLISHED,
            OfferingLifecycle::STATE_REJECTED,
            OfferingLifecycle::STATE_RESTRICTED,
            OfferingLifecycle::STATE_CANCELLED,
            OfferingLifecycle::STATE_CLOSED,
        ];
        foreach ($omittedStatuses as $status) {
            $I->dontSee($status, '#offeringlist tbody tr .badge');
        }
    }

    /**
     * @group listview
     */
    public function checkOfferingPresetFilters(FunctionalTester $I)
    {
        $I->amOnPage('/admin/offering');
        $I->seeLink('All', '/admin/offering?isSecondaryMrkt=');
        $I->seeLink(
            'Any Prefunding',
            '/admin/offering?isSecondaryMrkt=1&offeringType=prefunding',
        );
        $I->seeLink('Any Featured', '/admin/offering?isFeatured=1');
        $I->seeLink(
            'Published First Party',
            '/admin/offering?isSecondaryMrkt=1&sell_investment=0&lifecycleStatus%5B0%5D=published',
        );
        $I->seeLink(
            'Relistings awaiting publication',
            '/admin/offering?isSecondaryMrkt=1&sell_investment=1&lifecycleStatus%5B0%5D=draft&lifecycleStatus%5B1%5D=submitted&lifecycleStatus%5B2%5D=approved',
        );
        $I->seeLink(
            'Published Relistings',
            '/admin/offering?isSecondaryMrkt=1&sell_investment=1&lifecycleStatus%5B0%5D=published',
        );
        $I->seeLink(
            'Inactive',
            '/admin/offering?lifecycleStatus%5B0%5D=cancelled&lifecycleStatus%5B1%5D=closed&lifecycleStatus%5B2%5D=rejected',
        );
    }

    protected function filterProvider(): array
    {
        return [
            [
                'filters' => ['id' => '1'],
            ],
            [
                'filters' => ['visibility' => 2],
                'dbquery' => null,
            ],
            [
                'filters' => [
                    'visibility' => 2,
                    'offeringType' => 'prefunding',
                ],
                'dbquery' => null,
            ],
            [
                'filters' => [
                    'isFeatured' => 1,
                    'name' => 'cam',
                ],
                'dbquery' => [
                    'isFeatured' => 1,
                    'name like' => '%cam%',
                ],
            ],
            [
                'filters' => ['createdBy' => 'ben'],
                'dbquery' => ['createdBy like' => '%ben%'],
            ],
            [
                'filters' => [
                    'assetId' => 3,
                ],
                'dbquery' => [
                    'asset_id' => 3,
                ],
            ],
            [
                'filters' => [
                    'createdAt_gte' => date_format(new \DateTime('-4 days'), 'Y-m-d'),
                    'createdAt_lt' => date_format(new \DateTime('-1 days'), 'Y-m-d'),
                ],
                'dbquery' => [
                    'createdAt >=' => date_format(
                        new \DateTime('-4 days')->setTime(0, 0),
                        \DateTime::ATOM,
                    ),
                    'createdAt <' => date_format(
                        new \DateTime('-1 days')->setTime(0, 0),
                        \DateTime::ATOM,
                    ),
                ],
            ],
        ];
    }
}
