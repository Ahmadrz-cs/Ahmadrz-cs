<?php

namespace App\Tests\Controller\ApiV2\Investment;

use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV2\ApiBase64Files;
use App\Tests\Controller\ApiV2\ApiResponseFields;
use Symfony\Component\HttpFoundation\Response;

class InvestmentCreateResponseTest extends FixtureWebTestCase
{
    public function testCreateInvestment(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(\App\Entity\Offering::class, [
            'status' => 'published',
        ]);
        $uri = self::API_PATH_PREFIX_V2 . '/investments';
        $content = json_encode([
            'offeringId' => $sample[0]->getId(),
            'numberOfShares' =>
                round(self::MIN_COMMIT / $sample[0]->getPricePerShare()) + 1,
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::INVESTMENT_STANDARD;
        $actualFields = array_keys($apiResponse);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);

        // verify default behaviour for type field
        $this->assertEquals('normal', $apiResponse['type']);
    }

    public function testCreateInvestmentFieldsAll(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(\App\Entity\Offering::class, [
            'status' => 'published',
        ]);
        $filter = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => 'ben.auto@test.yielderverse.co.uk'],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V2 . '/investments';
        $content = json_encode([
            'offeringId' => $sample[0]->getId(),
            'numberOfShares' =>
                round(self::MIN_COMMIT / $sample[0]->getPricePerShare()) + 1,
            'type' => 'prefunding',
            'userId' => $filter,
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::INVESTMENT_STANDARD;
        $actualFields = array_keys($apiResponse);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);

        // verify optional fields can be set
        $this->assertEquals('prefunding', $apiResponse['type']);
        $this->assertEquals($filter, $apiResponse['userId']);
    }

    public function testCreateInvestmentDocument(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(\App\Entity\Investment::class, [
            'status' => 'settled',
        ]);
        $uri =
            self::API_PATH_PREFIX_V2
            . '/investments/'
            . $sample[0]->getId()
            . '/documents';
        $content = json_encode([
            'type' => 'image/jpg',
            'fileName' => 'jpgTest.jpg',
            'documentContent' => ApiBase64Files::TEST_JPG,
            'tag' => 'share_certificate',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::DOCUMENT_STANDARD;
        $actualFields = array_keys($apiResponse);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    }
}
