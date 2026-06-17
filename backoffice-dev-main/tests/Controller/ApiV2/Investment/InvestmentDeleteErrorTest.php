<?php

namespace App\Tests\Controller\ApiV2\Investment;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class InvestmentDeleteErrorTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<string, array{0: string}, mixed, void>
     */
    public static function undeletableInvestmentEndpointsProvider(): \Generator
    {
        yield 'investment collection' => ['/investments'];
        yield 'investment single' => ['/investments/1'];
        yield 'investment payouts' => ['/investments/1/payouts'];
        yield 'investment documents' => ['/investments/1/documents'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider(
        'undeletableInvestmentEndpointsProvider',
    )]
    public function testDeleteInvestmentEndpointsNotAllowed($endpoint): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . $endpoint;

        $this->client->request('DELETE', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function testDeleteInvestmentDocumentNotExists(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/investment/1/documents/-1';
        $this->client->request('DELETE', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
