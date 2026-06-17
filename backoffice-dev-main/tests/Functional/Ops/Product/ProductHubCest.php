<?php

namespace App\Tests\Functional\Ops\Product;

use App\Entity\Enum\AssetStatus;
use App\Tests\Support\FunctionalTester;

class ProductHubCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    /**
     * @group listview
     */
    public function checkListViewElements(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/products');

        $elements = [
            'Actions',
            'Id',
            'Name',
            'SPV Id',
            'Funding Goal',
            'Net Projected Income',
            'Net Projected Yield',
            'Status',
            'Mode',
            'Visibility',
            'Featured',
            'Buying',
            'Selling',
        ];
        // check table headers
        $I->loopCheckElements($elements, 'thead th');

        $I->seeLink('Create New Product', '/admin/products/create');
        $I->seeLink('View Transaction Reports', '/admin/reports/mangopay/sets');

        // Check filter presets have correct urls
        $I->seeLink('Featured', '/admin/products?featured=1');
        $I->seeLink('Residential', '/admin/products?assetType=Residential');
        $I->seeLink('Commercial', '/admin/products?assetType=Commercial');
        $I->seeLink('Prelaunch (Draft)', '/admin/products?status%5B0%5D=draft');
        $I->seeLink(
            'Prefunding (Acquiring)',
            '/admin/products?status%5B0%5D=acquiring',
        );
        $I->seeLink(
            'Retail (Active and Closing)',
            '/admin/products?status%5B0%5D=active&status%5B1%5D=closing',
        );
        $I->seeLink('Retail (Active only)', '/admin/products?status%5B0%5D=active');
        $I->seeLink('Retail (Closing only)', '/admin/products?status%5B0%5D=closing');
        $I->seeLink('Completed (Archived)', '/admin/products?status%5B0%5D=archived');

        // $assetIds = $I->grabColumnFromDatabase('offerings', 'asset_id');
        $assetIds = $I->grabColumnFromDatabase('assets', 'id');
        rsort($assetIds);
        $I->seeLink('View Product', "/admin/products/{$assetIds[0]}");
    }

    /**
     * @group listview
     * @dataProvider filterProvider
     */
    public function checkListViewAssetFilters(
        FunctionalTester $I,
        \Codeception\Example $example,
    ): void {
        if (empty($example['dbquery'])) {
            $example['dbquery'] = $example['filters'];
        }
        $expected = $I->grabNumRecords('assets', $example['dbquery']);
        $I->amOnPage('/admin/products?' . http_build_query($example['filters']));
        $resultsCountText = $I->grabTextFrom('#list-meta-results');
        $actual = (int) explode(' ', $resultsCountText)[0];
        $I->assertEquals($expected, $actual);
    }

    protected function filterProvider(): array
    {
        return [
            [
                'filters' => ['name' => 'ever'],
                'dbquery' => ['name like' => '%ever%'],
            ],
        ];
    }

    public function checkListViewFiltersStatusLog(FunctionalTester $I): void
    {
        $query = [
            'status' => [
                AssetStatus::Draft,
            ],
        ];
        $I->amOnPage('/admin/products?' . http_build_query($query));
        foreach ($query['status'] as $status) {
            $I->see(
                ucfirst($status->value),
                '#assets-list tbody tr [data-field="status-log"]',
            );
        }
        $omittedStatuses = array_udiff(
            AssetStatus::cases(),
            $query['status'],
            fn(AssetStatus $a, AssetStatus $b): int => $a->value <=> $b->value,
        );
        foreach ($omittedStatuses as $status) {
            $I->dontSee(
                ucfirst($status->value),
                '#assets-list tbody tr [data-field="status-log"]',
            );
        }
    }

    public function checkMonthendHubSections(FunctionalTester $I): void
    {
        $navLinks = [
            'Dashboard' => '/admin/products',
            'Review Listings' => '/admin/products/review/listings',
        ];
        $I->amOnPage('/admin/products');
        foreach ($navLinks as $text => $path) {
            $I->seeLink($text, $path);
        }
        $I->see('Dashboard', '#hub-nav .nav-link.active');

        // Review Listings section
        // $I->click('Review Listings');
        // $I->seeCurrentUrlEquals('/admin/products/review/listings');
        // foreach ($navLinks as $text => $path) {
        //     $I->seeLink($text, $path);
        // }
        // $I->see('Review Listings', '#hub-nav .nav-link.active');
        // $I->seeElement('section#listings-summary');
        // $I->seeElement('section#listings-composition');
        // $I->seeElement('table#listings-breakdown');
        // $I->seeElement('table#listings-composition-graphical');
        // // Sanity check the filtering options work and remove some results
        // $originalCount = count($I->grabMultiple('#listings-breakdown tbody tr'));
        // $I->checkOption('#listings-summary form #hideNoInvestors');
        // $I->click('Apply Filters', '#listings-summary form');
        // $countWithFilter1 = count($I->grabMultiple('#listings-breakdown tbody tr'));
        // $I->assertLessThan($originalCount, $countWithFilter1);
        // // Repeat for other options
        // $I->checkOption('#listings-summary form #hideNoListings');
        // $I->click('Apply Filters', '#listings-summary form');
        // $countWithFilter2 = count($I->grabMultiple('#listings-breakdown tbody tr'));
        // $I->assertLessThan($countWithFilter1, $countWithFilter2);
        // $I->checkOption('#listings-summary form #hideNoAvailable');
        // $I->click('Apply Filters', '#listings-summary form');
        // $countWithFilter3 = count($I->grabMultiple('#listings-breakdown tbody tr'));
        // $I->assertLessThan($countWithFilter2, $countWithFilter3);
    }
}
