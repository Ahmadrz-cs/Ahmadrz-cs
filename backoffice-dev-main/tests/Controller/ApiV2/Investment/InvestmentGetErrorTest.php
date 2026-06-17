<?php

namespace App\Tests\Controller\ApiV2\Investment;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class InvestmentGetErrorTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<'investment'|'single not exists investment', array{0: '/investment'|'/investments/-1'}, mixed, void>
     */
    public static function notExistsEndpoints(): \Generator
    {
        yield 'investment' => ['/investment'];
        yield 'single not exists investment' => ['/investments/-1'];

        // yield "not exists investment payout" => ["/investments/-1/payouts"];
        // yield "not exists investment documents" => ["/investments/-1/documents"];
        // yield "investment document not exists" => ["/investments/1/documents/-1"];
        // yield "not exists investment document not exists" => ["/investments/-1/documents/-1"];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('notExistsEndpoints')]
    public function testGetInvestmentEndpointsNotExists($endpoint): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . $endpoint;
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // public function testGetInvestmentsQueryViewInvalid(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/investments';
    //     $parameters = [
    //         'view' => 'invalid'
    //     ];
    //     $this->client->request('GET', $uri, $parameters);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
    // public function testGetInvestmentsQueryIdInvalid(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/investments';
    //     $parameters = [
    //         'id' => 'invalid'
    //     ];
    //     $this->client->request('GET', $uri, $parameters);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
    // public function testGetSingleInvestmentQueryViewInvalid(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/investments/1';
    //     $parameters = [
    //         'view' => 'invalid'
    //     ];
    //     $this->client->request('GET', $uri, $parameters);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
}
