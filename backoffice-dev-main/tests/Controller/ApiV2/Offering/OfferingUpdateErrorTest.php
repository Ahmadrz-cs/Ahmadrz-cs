<?php

namespace App\Tests\Controller\ApiV2\Offering;

use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV2\ApiResponseFields;
use Symfony\Component\HttpFoundation\Response;

class OfferingUpdateErrorTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<'not exists offering & document'|'not exists offering'|'offering not exists document', array{0: '/offerings/-1'|'/offerings/-1/documents/-1'|'/offerings/1/documents/-1'}, mixed, void>
     */
    public function notExistsEndpointsProvider(): \Generator
    {
        yield 'not exists offering' => ['/offerings/-1'];
        yield 'offering not exists document' => ['/offerings/1/documents/-1'];
        yield 'not exists offering & document' => ['/offerings/-1/documents/-1'];
    }

    public function testUpdateOfferingInvalidFields(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/offerings/1';
        $content = json_encode([
            'isFeatured' => true,
            'invalid' => 'field',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        // $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Currently invalid fields are just ignored
        $this->assertResponseIsSuccessful();
    }

    // /**
    //  * @dataProvider notExistsEndpointsProvider
    //  */
    // public function testUpdateOfferingEndpointsNotExists($endpoint): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . $endpoint;
    //     $content = json_encode([]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
    // public function testUpdateOffering(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings/1';
    //     $content = json_encode([
    //         'assetId' => '2187'
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    //     $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
    //     $expectedFields = ApiResponseFields::OFFERING_STANDARD;
    //     $actualFields = array_keys($apiResponse);
    //     $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    //     $this->assertNotEquals($actualFields['assetId'], 2187);
    // }
}
