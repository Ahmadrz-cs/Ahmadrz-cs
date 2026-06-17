<?php

namespace App\Tests\Controller\ApiV2\Offering;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class OfferingGetErrorTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<string, array{0: string}, mixed, void>
     */
    public static function offeringEndpointNotFoundProvider(): \Generator
    {
        yield 'offering misspelt' => ['/offering'];
        yield 'offering single' => ['/offerings/-1'];
        // yield "offering offerings" => ["/offerings/-1/offerings"];
        // yield "offering documents" => ["/offerings/-1/documents"];
        yield 'offering exist document single' => ['/offerings/1/documents/-1'];
        yield 'offering not exist document single' => ['/offerings/-1/documents/-1'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('offeringEndpointNotFoundProvider')]
    public function testGetOfferingEndpointsNotExists($endpoint): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . $endpoint;
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // public function testGetOfferingsQueryViewInvalid(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings';
    //     $parameters = [
    //         'view' => 'invalid'
    //     ];
    //     $this->client->request('GET', $uri, $parameters);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
    // public function testGetOfferingsQueryIdInvalid(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings';
    //     $parameters = [
    //         'id' => -1,
    //     ];
    //     $this->client->request('GET', $uri, $parameters);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
    // public function testGetSingleOfferingQueryViewInvalid(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings/1';
    //     $parameters = [
    //         'view' => 'invalid'
    //     ];
    //     $this->client->request('GET', $uri, $parameters);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
}
