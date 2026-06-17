<?php

namespace App\Tests\Controller\ApiV1\Report;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ReportGetResponseTest extends FixtureWebTestCase
{
    public function testGetReport(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => self::USER_REGULAR],
            true,
        )[0];
        $parameters = [
            'userid' => $sample,
            'report_type' => 'test_report',
        ];
        $uri = self::API_PATH_PREFIX_V1 . '/reports/getUserReport';
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('success', $apiResponse['outcome']);
        $this->assertEquals('200', $apiResponse['status']);
    }

    public function testGetReportFieldPaymentHistory(): void
    {
        // Note that the report is not admin friendly
        // Must use yourself to get your own report
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => self::USER_REGULAR],
            true,
        )[0];
        $parameters = [
            'userid' => $sample,
            'report_type' => 'payment_history',
        ];
        $uri = self::API_PATH_PREFIX_V1 . '/reports/getUserReport';
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('success', $apiResponse['outcome']);
        $this->assertEquals('200', $apiResponse['status']);

        // check has correct data points (key and type)
        $actualFields = array_keys($apiResponse['data']);
        $expectedFields = [
            'aggregate_invested',
            'aggregate_earnings',
            'payouts_summary',
            'total_profit_share',
            'total_rental_earnings',
        ];
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);

        $assetIdKeys = array_keys($apiResponse['data']['payouts_summary']);
        $actualFields = array_keys(
            $apiResponse['data']['payouts_summary'][$assetIdKeys[0]],
        );
        $expectedFields = [
            'property_name',
            'total_invested',
            'total_payouts',
            'monthly_payouts',
            'number_of_investments',
            'term_remaining',
        ];
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);

        $this->assertIsNumeric($apiResponse['data']['aggregate_invested']);
        $this->assertIsNumeric($apiResponse['data']['aggregate_earnings']);
        $this->assertIsNumeric($apiResponse['data']['total_profit_share']);
        $this->assertIsNumeric($apiResponse['data']['total_rental_earnings']);
        $this->assertIsArray($apiResponse['data']['payouts_summary']);
    }

    public function testGetUserAccountReportExclMonths(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => self::USER_REGULAR],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/reports/account/$sample";
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $actualFields = array_keys($apiResponse);
        $expectedFields = [
            'totalInvestmentCount',
            'totalInvestmentValue',
            'totalReturn',
            'totalDividend',
            'totalCapitalAppreciation',
            'monthlyPayouts',
        ];
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
        $this->assertEmpty($apiResponse['monthlyPayouts']);
    }

    public function testGetUserAccountReportInclMonths(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => self::USER_REGULAR],
            true,
        )[0];
        $parameters = ['mode' => 'cumulative'];
        $uri = self::API_PATH_PREFIX_V1 . "/reports/account/$sample";
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $actualFields = array_keys($apiResponse);
        $expectedFields = [
            'totalInvestmentCount',
            'totalInvestmentValue',
            'totalReturn',
            'totalDividend',
            'totalCapitalAppreciation',
            'monthlyPayouts',
        ];
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
        $this->assertNotEmpty($apiResponse['monthlyPayouts']);
        $this->assertEquals(12, count($apiResponse['monthlyPayouts']));
        $monthKeys = array_keys($apiResponse['monthlyPayouts']);
        $this->assertMatchesRegularExpression(
            "/^\d{4}-(0[1-9]|1[012])$/",
            $monthKeys[0],
        );
    }

    public function testGetUserAccountReportScenarioUnitMode(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => self::USER_REGULAR],
            true,
        )[0];
        $parameters = ['mode' => 'unit'];
        $uri = self::API_PATH_PREFIX_V1 . "/reports/account/$sample";
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $actualFields = array_keys($apiResponse);
        $expectedFields = [
            'totalInvestmentCount',
            'totalInvestmentValue',
            'totalReturn',
            'totalDividend',
            'totalCapitalAppreciation',
            'monthlyPayouts',
        ];
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
        $this->assertNotEmpty($apiResponse['monthlyPayouts']);
        $this->assertEquals(12, count($apiResponse['monthlyPayouts']));
        $monthKeys = array_keys($apiResponse['monthlyPayouts']);
        $this->assertMatchesRegularExpression(
            "/^\d{4}-(0[1-9]|1[012])$/",
            $monthKeys[0],
        );
    }

    public function testGetUserAssetsReportExclMonths(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => self::USER_REGULAR],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/reports/assets/$sample";
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        // Use array_values to "reset" the keys, and get the first element
        // The original keys are the asset ids which aren't needed for this test
        $actualFields = array_keys(array_values($apiResponse)[0]);
        $expectedFields = [
            'assetInvestmentCount',
            'assetInvestmentValue',
            'assetReturn',
            'assetDividend',
            'assetCapitalAppreciation',
            'monthlyPayouts',
        ];
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
        $assetIdKeys = array_keys($apiResponse);
        $this->assertTrue(empty($apiResponse[$assetIdKeys[0]]['monthlyPayouts']));
    }

    public function testGetUserAssetsReportInclMonths(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => self::USER_REGULAR],
            true,
        )[0];
        $parameters = ['mode' => 'cumulative'];
        $uri = self::API_PATH_PREFIX_V1 . "/reports/assets/$sample";
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $actualFields = array_keys(array_values($apiResponse)[0]);
        $expectedFields = [
            'assetInvestmentCount',
            'assetInvestmentValue',
            'assetReturn',
            'assetDividend',
            'assetCapitalAppreciation',
            'monthlyPayouts',
        ];
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
        $assetIdKeys = array_keys($apiResponse);
        $this->assertTrue(!empty($apiResponse[$assetIdKeys[0]]['monthlyPayouts']));
        $this->assertEquals(12, count($apiResponse[$assetIdKeys[0]]['monthlyPayouts']));
        $monthKeys = array_keys($apiResponse[$assetIdKeys[0]]['monthlyPayouts']);
        $this->assertMatchesRegularExpression(
            "/^\d{4}-(0[1-9]|1[012])$/",
            $monthKeys[0],
        );
    }

    public function testGetUserAssetsReportScenarioUnitMode(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => self::USER_REGULAR],
            true,
        )[0];
        $parameters = ['mode' => 'unit'];
        $uri = self::API_PATH_PREFIX_V1 . "/reports/assets/$sample";
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $actualFields = array_keys(array_values($apiResponse)[0]);
        $expectedFields = [
            'assetInvestmentCount',
            'assetInvestmentValue',
            'assetReturn',
            'assetDividend',
            'assetCapitalAppreciation',
            'monthlyPayouts',
        ];
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
        $assetIdKeys = array_keys($apiResponse);
        $this->assertTrue(!empty($apiResponse[$assetIdKeys[0]]['monthlyPayouts']));
        $this->assertEquals(12, count($apiResponse[$assetIdKeys[0]]['monthlyPayouts']));
        $monthKeys = array_keys($apiResponse[$assetIdKeys[0]]['monthlyPayouts']);
        $this->assertMatchesRegularExpression(
            "/^\d{4}-(0[1-9]|1[012])$/",
            $monthKeys[0],
        );
    }
}
