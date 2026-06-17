<?php

namespace App\Tests\Controller\ApiV2\Investment;

use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV2\ApiResponseFields;
use Symfony\Component\HttpFoundation\Response;

class InvestmentGetResponseTest extends FixtureWebTestCase
{
    public function testGetInvestments(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/investments';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::INVESTMENT_STANDARD;
        $actualFields = array_keys($response['data'][0]);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    }

    public function testGetSingleInvestment(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/investments/1';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::INVESTMENT_STANDARD;
        $actualFields = array_keys($response);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    }

    public function testGetInvestmentDocuments(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $filter = $this->searchFixtures(
            \App\Entity\Investment::class,
            ['status' => 'settled'],
            true,
        );
        $sample = $this->searchFixtures(\App\Entity\InvestmentDocuments::class, [
            'investment' => $filter[0],
        ]);
        $uri = self::API_PATH_PREFIX_V2 . '/investments/' . $filter[0] . '/documents';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::DOCUMENT_STANDARD;
        $actualFields = array_keys($apiResponse['data'][0]);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
        $this->assertEquals(count($sample), count($apiResponse['data']));
        $this->assertMatchesRegularExpression(
            '~cloudfront.net~',
            $apiResponse['data'][0]['url'],
        );
    }

    public function testGetInvestmentSingleDocument(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $filter = $this->searchFixtures(
            \App\Entity\Investment::class,
            ['status' => 'settled'],
            true,
        );
        $sample = $this->searchFixtures(
            \App\Entity\InvestmentDocuments::class,
            ['investment' => $filter[0]],
            true,
        );
        $uri =
            self::API_PATH_PREFIX_V2
            . '/investments/'
            . $filter[0]
            . '/documents/'
            . $sample[0];
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::DOCUMENT_STANDARD;
        $actualFields = array_keys($apiResponse);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
        $this->assertEquals($sample[0], $apiResponse['id']);
        $this->assertMatchesRegularExpression('~cloudfront.net~', $apiResponse['url']);
    }

    public function testGetInvestmentDocumentsQueryPagination(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $filter = $this->searchFixtures(
            \App\Entity\Investment::class,
            ['status' => 'settled'],
            true,
        );
        $uri = self::API_PATH_PREFIX_V2 . '/investments/' . $filter[0] . '/documents';
        $parameters = [
            'page' => 1,
            'limit' => 1,
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = $this->searchFixtures(
            \App\Entity\InvestmentDocuments::class,
            [
                'investment' => $filter[0],
            ],
            true,
        );
        sort($expectedFields);
        $expectedFields = array_slice(
            $expectedFields,
            $parameters['limit'] * ($parameters['page'] - 1),
            $parameters['limit'],
        );
        $actualFields = array_map(function ($x) {
            return $x['id'];
        }, $apiResponse['data']);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
        $this->assertEquals(count($expectedFields), count($actualFields));
    }

    // public function testGetInvestmentPayouts(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $filter = $this->searchFixtures(
    //         \App\Entity\Investment::class,
    //         ["status" => "settled"],
    //         true
    //     );
    //     $sample = $this->searchFixtures(
    //         \App\Entity\Payout::class,
    //         ["investment" => $filter[0]]
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/investments/' . $filter[0] . '/payouts';
    //     $this->client->request('GET', $uri);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    //     $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
    //     $expectedFields = ApiResponseFields::PAYOUT_STANDARD;
    //     $actualFields = array_keys($apiResponse['data'][0]);
    //     $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    //     $this->assertEquals(count($sample), count($apiResponse['data']));
    // }
}
