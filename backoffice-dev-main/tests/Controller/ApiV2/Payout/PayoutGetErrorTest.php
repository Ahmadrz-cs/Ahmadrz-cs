<?php

namespace App\Tests\Controller\ApiV2\Payout;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\RequiresEnvironmentVariable('testApiV2', '1')]
class PayoutGetErrorTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<'payout misspelt', array{0: '/payout'}, mixed, void>
     */
    public static function payoutEndpointNotFoundProvider(): \Generator
    {
        yield 'payout misspelt' => ['/payout'];

        // yield "payout single" => ["/payouts/-1"];
        // yield "payout single" => ["/payout/1"];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('payoutEndpointNotFoundProvider')]
    public function testGetPayoutEndpointsNotExists($endpoint): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . $endpoint;
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // public function testGetPayoutsQueryIdInvalid(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/payouts';
    //     $parameters = [
    //         'id' => -1,
    //     ];
    //     $this->client->request('GET', $uri, $parameters);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
}
