<?php

namespace App\Tests\Controller\Admin;

use App\Entity\BaseEntity;
use App\Test\FixtureWebTestCase;
use App\Test\Util\ExportTestUtil;

class AssetControllerExportTest extends FixtureWebTestCase
{
    public function testExportAssetList(): void
    {
        $this->loginWebClient(self::USER_SUPER_ADMIN);
        $query = ['visibility' => BaseEntity::VISIBILITY_VIP];

        /**
         * Extract the number of results expected based on the list view
         * - Traverse DOM and get text
         *   - https://symfony.com/doc/current/components/dom_crawler.html#accessing-node-values
         * - Extract the number from the text
         *   - E.g. get the 5 from "5 Results"
         */
        $crawler = $this->client->request(
            'GET',
            '/admin/asset?' . http_build_query($query),
        );
        $resultsCountText = $crawler->filter('#list-meta-results')->text();
        $expected = (int) explode(' ', $resultsCountText)[0];

        $exportPath = '/admin/asset/export?' . http_build_query($query);
        $exportedData = ExportTestUtil::downloadCsvToArray($this->client, $exportPath);
        $this->assertResponseIsSuccessful();

        // Trim the header row out
        if (count($exportedData)) {
            unset($exportedData[array_key_first($exportedData)]);
        }
        $this->assertCount($expected, $exportedData);
    }
}
