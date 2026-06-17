<?php

namespace App\Tests\Controller\Admin;

use App\Service\ExportService;
use App\Test\FixtureWebTestCase;
use App\Test\Util\ExportTestUtil;

class ExportControllerExportTest extends FixtureWebTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testReportBuilderCreatedAtFilter(): void
    {
        $this->loginWebClient(self::USER_SUPER_ADMIN);

        // Collate values to use for testing
        $samples = $this->searchFixtures(\App\Entity\Asset::class);
        $createdAtStart = $samples[0]->getCreatedAt()->setTime(0, 0);
        $createdAtEnd = new \DateTime($createdAtStart->format('Y-m-d'))->modify(
            '+1 day',
        );
        $expectedResults = 0;
        foreach ($samples as $asset) {
            if (
                $asset->getCreatedAt() >= $createdAtStart
                && $asset->getCreatedAt() < $createdAtEnd
            ) {
                $expectedResults += 1;
            }
        }

        $crawler = $this->client->request(
            'GET',
            '/admin/export/builder/assets?clear=1',
        );
        $buttonCrawlerNode = $crawler->selectButton('Export Report');
        $form = $buttonCrawlerNode->form();

        // Select the columns we want
        // We're using a workaround for multi-select checkboxes where you need the index
        // We derive the index from the available fields for that export
        // Which is the same underlying system used to generate the form
        /** @var ExportService $exportService */
        $exportService = static::getContainer()->get(ExportService::class);
        $availableFields = $exportService->getFieldNames(ExportService::REPORT_ASSET);
        $chosenFields = ['id', 'name', 'companyNumber', 'createdAt'];
        foreach ($chosenFields as $choice) {
            $fieldIndex = array_search($choice, $availableFields);
            if (false !== $fieldIndex) {
                $form['export_report_customiser[reportFields][' . $fieldIndex . ']'] =
                    $choice;
            }
        }

        // Fill in the filter fields
        $form['export_report_customiser[createdAt_gte]'] = $createdAtStart->format(
            'Y-m-d',
        );
        $form['export_report_customiser[createdAt_lt]'] = $createdAtEnd->format(
            'Y-m-d',
        );

        // Trigger export and parse the contents into an array so we can easily check values
        $exportedData = ExportTestUtil::downloadCsvToArray(
            $this->client,
            '/admin/export/builder/Assets?clear=1',
            $form,
        );
        $this->assertResponseIsSuccessful();

        /**
         * - Check column headers limited to only the chosenFields
         * - Check the number of results match what we expect (checked earlier from fixtures)
         * - Check that each result actually matches the filters
         *   - The createdAt date is between the start and end points we chose
         */
        $columnHeaders = array_shift($exportedData);
        $this->assertEqualsCanonicalizing($chosenFields, $columnHeaders);
        $this->assertCount($expectedResults, $exportedData);

        $createdAtColumnIndex = array_search('createdAt', $columnHeaders);
        foreach ($exportedData as $row) {
            $currentCreatedAt = new \DateTime($row[$createdAtColumnIndex]);
            $this->assertGreaterThanOrEqual($createdAtStart, $currentCreatedAt);
            $this->assertLessThan($createdAtEnd, $currentCreatedAt);
        }
    }

    public function testReportBuilderRelationalIdFilters(): void
    {
        $this->loginWebClient(self::USER_SUPER_ADMIN);

        // Collate values to use for testing
        $assetFilter = $this->searchFixtures(
            \App\Entity\Asset::class,
            [
                'name' => 'Lodge de Lac - Cumbria',
            ],
            true,
        );
        $userFilter = $this->searchFixtures(
            \App\Entity\User::class,
            [
                'username' => self::USER_VIP,
            ],
            true,
        );
        $samples = $this->searchFixtures(\App\Entity\Payout::class, [
            'asset' => $assetFilter,
            'creditedUser' => $userFilter,
        ]);

        $crawler = $this->client->request('GET', '/admin/export/builder/payouts');
        $form = $crawler->selectButton('Export Report')->form();

        // Set filters in the form
        $form['export_report_customiser[assetId]'] = $assetFilter[0];
        $form['export_report_customiser[creditedUserId]'] = $userFilter[0];

        // Trigger export and parse into an array
        $exportedData = ExportTestUtil::downloadCsvToArray(
            $this->client,
            '/admin/export/builder/payouts?clear=1',
            $form,
        );
        $this->assertResponseIsSuccessful();

        /**
         * - Check the number of results match what we expect (checked earlier from fixtures)
         * - Check that each result actually matches the filters
         *   - They all have the same asset id
         *   - They all have the same credited user id
         */
        $columnHeaders = array_shift($exportedData);
        $this->assertCount(count($samples), $exportedData);

        $assetIdColumnIndex = array_search('assetId', $columnHeaders);
        $creditedUserIdColumnIndex = array_search('creditedUserId', $columnHeaders);
        foreach ($exportedData as $row) {
            $this->assertEquals($assetFilter[0], $row[$assetIdColumnIndex]);
            $this->assertEquals($userFilter[0], $row[$creditedUserIdColumnIndex]);
        }
    }
}
