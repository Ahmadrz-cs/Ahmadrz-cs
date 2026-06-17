<?php

namespace App\Tests\Controller\ApiV2\User;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserGetErrorTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<string, array{0: string, 1?: false}, mixed, void>
     */
    public static function userEndpointNotFoundProvider(): \Generator
    {
        yield 'user misspelt' => ['/user'];
        yield 'user single' => ['/users/-1'];
        yield 'user not found' => [
            '/users/-1/wallets/wlt_m_01HW3FBRBZF8ZMEF8WHPRA21NZ',
        ];
        yield 'wallet not exists' => ['/users/1/wallets/99999'];

        // yield "user offerings" => ["/users/-1/offerings"];
        // yield "user investments" => ["/users/-1/investments"];
        // yield "user payouts" => ["/users/-1/payouts"];
        // yield "user documents" => ["/users/-1/documents"];
        // yield "user document single" => ["/users/-1/documents/-1"];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('userEndpointNotFoundProvider')]
    public function testGetUserEndpointsNotExists($endpoint): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . $endpoint;
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
