<?php

namespace App\Tests\Functional\Cms\Asset;

use App\Entity\BaseEntity;
use App\Entity\Enum\AssetStatus;
use App\Entity\Lifecycle\AssetLifecycle;
use App\Tests\Support\FunctionalTester;

class AssetListCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    /**
     * @group listview
     */
    public function checkLifeCycle(FunctionalTester $I)
    {
        $I->createBasicAsset('lifecycle test asset');

        $assetID = $I->grabTextFrom('tbody tr:nth-child(1)  td:nth-child(1)');
        $stages = [
            'draft' => '/draftsubmit',
            'submitted' => '/submitapprove',
            'approved' => '/approvepublish',
            'published' => '/publisharchive',
            'archived' => null,
        ];
        $I->checkLifecycle($stages, $assetID, 'asset');
    }

    /**
     * @group listview
     * @dataProvider visibilityProvider
     */
    public function checkVisibilityToggle(
        FunctionalTester $I,
        \Codeception\Example $example,
    ) {
        $I->amOnPage('/admin/asset');

        $id = $I->grabTextFrom('tbody tr:nth-child(1) [data-field="id"]');
        $I->amOnPage('/admin/asset/' . $id . '/visibility/' . $example['visibility']);
        $I->see($example['text'], 'tbody tr:nth-child(1) [data-field="visibility"]');
    }

    /**
     * @return array
     */
    protected function visibilityProvider()
    {
        yield ['visibility' => '1', 'text' => 'Admin Only'];
        yield ['visibility' => '2', 'text' => 'VIP Only'];
        yield ['visibility' => '0', 'text' => 'Everyone'];
    }

    /**
     * @group listview
     */
    public function checkListViewElements(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/asset');
        $I->seeLink('Add Asset', '/admin/asset/add');

        $elements = [
            'Id',
            'Name',
            'SPV',
            'Asset Type',
            'Created',
            'Featured',
            'Buying',
            'Selling',
            'Relisting Fee Type',
            'Status',
            'Actions',
        ];
        // check table headers
        $I->loopCheckElements($elements, 'thead th');

        $filterLabels = [
            'Asset Id',
            'Asset Name',
            'SPV id',
            'Asset type',
            'Visibility',
            'Featured',
            'Buy market',
            'Sell market',
        ];
        // check table filters
        $I->loopCheckElements($filterLabels, 'form label');
        $I->loopCheckElements(['Status', 'Legacy Status'], 'form legend');
        // $I->see('Show/Hide Status', 'form button');

        $I->amOnPage('/admin/asset');
        $I->seeNumberOfElements('#assets-list tbody tr', 10);
        $I->selectOption('form select[name=perPage]', '5');
        $I->click('Apply Filters');
        $I->seeNumberOfElements('#assets-list tbody tr', 5);

        // check max page bracketing (to deal with filter changing)
        $I->amOnPage('/admin/asset?page=1000');
        // Sends you to last page
        $I->seeElement(['css' => '.pagination li:last-child.disabled']);

        // Check view related offerings link exists
        $sampleId = $I->getAssetId('FIRST');
        $I->seeLink('', '/admin/offering?assetId=' . $sampleId);
        $I->seeLink('Share Price Suggester', '/admin/utilities/asset-share-price');
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
        $expected = $I->grabNumRecords('assets', $example['dbquery']);
        $I->amOnPage('/admin/asset?' . http_build_query($example['filters']));
        $resultsCountText = $I->grabTextFrom('#list-meta-results');
        $actual = (int) explode(' ', $resultsCountText)[0];
        $I->assertEquals($expected, $actual);
    }

    // /**
    //  * @group listview
    //  */
    // public function checkListViewFiltersStatus(FunctionalTester $I): void
    // {
    //     // status filter separate as it uses an array rather than single values
    //     $query = [
    //         'lifecycleStatus' => [
    //             AssetLifecycle::STATE_DRAFT,
    //             AssetLifecycle::STATE_SUBMITTED,
    //             AssetLifecycle::STATE_APPROVED,
    //         ],
    //     ];
    //     $I->amOnPage('/admin/asset?' . http_build_query($query));
    //     foreach ($query['lifecycleStatus'] as $status) {
    //         $I->see($status, '#assets-list tbody tr .badge');
    //     }
    //     $omittedStatuses = [
    //         AssetLifecycle::STATE_PUBLISHED,
    //         AssetLifecycle::STATE_REJECTED,
    //         AssetLifecycle::STATE_RESTRICTED,
    //         AssetLifecycle::STATE_CANCELLED,
    //         AssetLifecycle::STATE_ARCHIVED,
    //     ];
    //     foreach ($omittedStatuses as $status) {
    //         $I->dontSee($status, '#assets-list tbody tr .badge');
    //     }
    // }

    /**
     * @group listview
     */
    public function checkListViewFiltersStatusLog(FunctionalTester $I): void
    {
        $query = [
            'status' => [
                AssetStatus::Acquiring,
                AssetStatus::Cancelled,
            ],
        ];
        $I->amOnPage('/admin/asset?' . http_build_query($query));
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

    /**
     * @group listview
     */
    public function checkAssetPresetFilters(FunctionalTester $I)
    {
        $I->amOnPage('/admin/asset');
        $I->seeLink('Featured', '/admin/asset?featured=1');
        $I->seeLink('Residential', '/admin/asset?assetType=Residential');
        $I->seeLink('Commercial', '/admin/asset?assetType=Commercial');
        $I->seeLink('Prelaunch (Draft)', '/admin/asset?status%5B0%5D=draft');
        $I->seeLink('Prefunding (Acquiring)', '/admin/asset?status%5B0%5D=acquiring');
        $I->seeLink(
            'Retail (Active and Closing)',
            '/admin/asset?status%5B0%5D=active&status%5B1%5D=closing',
        );
        $I->seeLink('Retail (Active only)', '/admin/asset?status%5B0%5D=active');
        $I->seeLink('Retail (Closing only)', '/admin/asset?status%5B0%5D=closing');
        $I->seeLink('Completed (Archived)', '/admin/asset?status%5B0%5D=archived');
    }

    protected function filterProvider(): array
    {
        return [
            [
                'filters' => ['id' => '1'],
            ],
            [
                'filters' => ['sellRestricted' => '1'],
            ],
            [
                'filters' => ['assetType' => 'Commercial'],
                'dbquery' => null,
            ],
            [
                'filters' => [
                    'visibility' => 2,
                    'assetType' => 'Residential',
                ],
                'dbquery' => null,
            ],
            [
                'filters' => ['name' => 'cam'],
                'dbquery' => ['name like' => '%cam%'],
            ],
            [
                'filters' => ['companyNumber' => 3],
                'dbquery' => ['companyNumber like' => '%3%'],
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

    // /**
    //  * @group listview
    //  */
    // public function checkListViewExport(FunctionalTester $I): void
    // {
    //     $query = [
    //         'visibility' => BaseEntity::VISIBILITY_VIP,
    //     ];
    //     $I->amOnPage('/admin/asset/export?' . http_build_query($query));
    //     $I->seeResponseCodeIsSuccessful();
    // }
    // /**
    //  * @group listview
    //  */
    // public function checkListViewFieldCustomisation(FunctionalTester $I): void
    // {
    //     $I->amOnPage('/admin/asset/list-custom');
    //     $I->see('Customise columns');
    //     $I->seeElement('form#field-customise');
    // }
}
