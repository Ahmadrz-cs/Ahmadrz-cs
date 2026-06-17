<?php

namespace App\Tests\Controller\ApiV2\Payout;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\RequiresEnvironmentVariable('testApiV2', '1')]
class PayoutDeleteErrorTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<'payout collection'|'payout single', array{0: '/payouts'|'/payouts/1'}, mixed, void>
     */
    public static function undeletablePayoutEndpointsProvider(): \Generator
    {
        yield 'payout collection' => ['/payouts'];
        yield 'payout single' => ['/payouts/1'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('undeletablePayoutEndpointsProvider')]
    public function testDeletePayoutEndpointsNotAllowed($endpoint): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . $endpoint;

        $this->client->request('DELETE', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }
}
