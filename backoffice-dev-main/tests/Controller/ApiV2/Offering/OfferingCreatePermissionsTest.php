<?php

namespace App\Tests\Controller\ApiV2\Offering;

use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV2\ApiBase64Files;
use Symfony\Component\HttpFoundation\Response;

class OfferingCreatePermissionsTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<'offering creation'|'offering document creation', array{0: '/offerings'|'/offerings/1/documents', 1: array{0: 'offering:write'}}, mixed, void>
     */
    public function offeringEndpointScopeProvider(): \Generator
    {
        yield 'offering creation' => ['/offerings', ['offering:write']];
        yield 'offering document creation' => [
            '/offerings/1/documents',
            ['offering:write'],
        ];
    }

    // /**
    //  * @dataProvider offeringEndpointScopeProvider
    //  */
    // public function testCreateOfferingEndpointsAsAdminMissingScope($endpoint, $requiredScopes): void
    // {
    //     $scopes = array_diff($this->permittedScopes, $requiredScopes);
    //     $this->loginApiClientUser(self::USER_ADMIN, $scopes);
    //     $sample = $this->searchFixtures(
    //         \App\Entity\Asset::class,
    //         ["status" => 'published']
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . $endpoint;
    //     $content = json_encode([
    //         'name' => 'testCreateOffering',
    //         'assetId' => $sample[0]->getId(),
    //         'numberOfShares' => '1000'
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];

    //     $this->client->request('POST', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    // }

    public function testCreateOfferingAsPublic(): void
    {
        $this->loginApiClientPublic();
        $sample = $this->searchFixtures(\App\Entity\Asset::class, [
            'status' => 'published',
        ]);
        $uri = self::API_PATH_PREFIX_V2 . '/offerings';
        $content = json_encode([
            'name' => 'testCreateOffering',
            'assetId' => $sample[0]->getId(),
            'numberOfShares' => '1000',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCreateOfferingAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(\App\Entity\Asset::class, [
            'status' => 'published',
        ]);
        $uri = self::API_PATH_PREFIX_V2 . '/offerings';
        $content = json_encode([
            'name' => 'testCreateOffering',
            'assetId' => $sample[0]->getId(),
            'numberOfShares' => '1000',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    // public function testCreateOfferingDocumentAsPublic(): void
    // {
    //     $this->loginApiClientPublic();
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings/1/documents';
    //     $content = json_encode([
    //         'type' => 'image/jpg',
    //         'fileName' => 'jpgTest.jpg',
    //         'documentContent' => ApiBase64Files::TEST_JPG,
    //         'tag' => 'test'
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    // }
    // public function testCreateOfferingDocumentAsRegUser(): void
    // {
    //     $this->loginApiClientUser(self::USER_REGULAR);
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings/1/documents';
    //     $content = json_encode([
    //         'type' => 'image/jpg',
    //         'fileName' => 'jpgTest.jpg',
    //         'documentContent' => ApiBase64Files::TEST_JPG,
    //         'tag' => 'test'
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    // }
    // public function testCreateOfferingRelistedAsPublic(): void
    // {
    //     $this->loginApiClientPublic();
    //     $sample = $this->searchFixtures(
    //         \App\Entity\Asset::class,
    //         ["status" => 'published']
    //     );
    //     $filter = $this->searchFixtures(
    //         \App\Entity\Investment::class,
    //         ["status" => "settled"]
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings';
    //     $content = json_encode([
    //         'name' => 'testCreateOffering',
    //         'assetId' => $sample[0]->getId(),
    //         'isSecondaryMarket' => true,
    //         'numberOfShares' => '1000',
    //         'investmentId' => $filter[0]->getId(),
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    // }
    // public function testCreateOfferingRelistedAsRegUser(): void
    // {
    //     $this->loginApiClientUser(self::USER_REGULAR);
    //     $sample = $this->searchFixtures(
    //         \App\Entity\Asset::class,
    //         ["status" => 'published']
    //     );
    //     $filter = $this->searchFixtures(
    //         \App\Entity\Investment::class,
    //         ["status" => "settled"]
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings';
    //     $content = json_encode([
    //         'name' => 'testCreateOffering',
    //         'assetId' => $sample[0]->getId(),
    //         'isSecondaryMarket' => true,
    //         'numberOfShares' => '1000',
    //         'investmentId' => $filter[0]->getId(),
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    // }
}
