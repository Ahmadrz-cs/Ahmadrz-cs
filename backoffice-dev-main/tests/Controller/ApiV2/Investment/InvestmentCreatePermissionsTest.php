<?php

namespace App\Tests\Controller\ApiV2\Investment;

use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV2\ApiBase64Files;
use App\Tests\Controller\ApiV2\ApiResponseFields;
use Symfony\Component\HttpFoundation\Response;

class InvestmentCreatePermissionsTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<'investment documents'|'investments', array{0: '/investments'|'/investments/1/documents', 1: array{0: 'investment:write'}}, mixed, void>
     */
    public static function investmentEndpointScopeProvider(): \Generator
    {
        yield 'investments' => ['/investments', ['investment:write']];
        yield 'investment documents' => [
            '/investments/1/documents',
            ['investment:write'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('investmentEndpointScopeProvider')]
    public function testCreateInvestmentEndpointsAsAdminMissingScope(
        $endpoint,
        $requiredScopes,
    ): void {
        $scopes = array_diff($this->permittedScopes, $requiredScopes);
        $this->loginApiClientUser(self::USER_ADMIN, $scopes);
        $uri = self::API_PATH_PREFIX_V2 . $endpoint;
        $content = json_encode([]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    // public function testCreateInvestmentAsRegUser(): void
    // {
    //     $this->loginApiClientUser(self::USER_REGULAR);
    //     $sample = $this->searchFixtures(
    //         \App\Entity\Offering::class,
    //         ["status" => 'published']
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/investments';
    //     $content = json_encode([
    //         'offeringId' => $sample[0]->getId(),
    //         'numberOfShares' => '1000'
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];

    //     $this->client->request('POST', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

    //     $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
    //     $expectedFields = ApiResponseFields::INVESTMENT_STANDARD;
    //     $actualFields = array_keys($apiResponse);
    //     $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    // }

    public function testCreateInvestmentAsPublic(): void
    {
        $this->loginApiClientPublic();
        $sample = $this->searchFixtures(\App\Entity\Offering::class, [
            'status' => 'published',
        ]);
        $uri = self::API_PATH_PREFIX_V2 . '/investments';
        $content = json_encode([
            'offeringId' => $sample[0]->getId(),
            'numberOfShares' => '1000',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCreateInvestmentDocumentAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
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
            'tag' => 'test',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCreateInvestmentDocumentAsPublic(): void
    {
        $this->loginApiClientPublic();
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
            'tag' => 'test',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCreatePrefundingInvestmentAsVip(): void
    {
        $this->loginApiClientUser(self::USER_VIP);
        $sample = $this->searchFixtures(\App\Entity\Offering::class, [
            'status' => 'published',
        ]);
        $totalShares = round(self::MIN_COMMIT / $sample[0]->getPricePerShare()) + 1;
        $retention = round($totalShares * 0.2);
        $liquidation = (int) ($totalShares - $retention);

        $uri = self::API_PATH_PREFIX_V2 . '/investments';
        $content = json_encode([
            'offeringId' => $sample[0]->getId(),
            'numberOfShares' => $liquidation,
            'type' => 'prefunding',
            'sharesToKeep' => $retention,
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('prefunding', $apiResponse['type']);
        $this->assertEquals($liquidation, $apiResponse['numberOfShares']);

        $uri = self::API_PATH_PREFIX_V2 . '/investments';
        $content = json_encode([
            'offeringId' => $sample[0]->getId(),
            'numberOfShares' => $retention,
            'prefundingId' => $apiResponse['id'],
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('normal', $apiResponse['type']);
        $this->assertEquals($retention, $apiResponse['numberOfShares']);
    }
}
