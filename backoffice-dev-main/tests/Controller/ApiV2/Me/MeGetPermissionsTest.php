<?php

namespace App\Tests\Controller\ApiV2\Me;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class MeGetPermissionsTest extends FixtureWebTestCase
{
    public static function currentUserEndpointsProvider(): \Generator
    {
        yield 'current user' => ['/me'];

        // yield "current user's investments" => ["/me/investments"];
        // yield "current user's listings" => ["/me/offerings"];
        // yield "current user's payouts" => ["/me/payouts"];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('currentUserEndpointsProvider')]
    public function testGetCurrentUserAsPublic(string $endpoint): void
    {
        $this->loginApiClientPublic();
        $uri = self::API_PATH_PREFIX_V2 . $endpoint;
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('currentUserEndpointsProvider')]
    public function testGetCurrentUserMissingScope(string $endpoint): void
    {
        $scopes = array_diff($this->permittedScopes, ['user:read']);
        $this->loginApiClientUser(self::USER_VIP, $scopes);
        $uri = self::API_PATH_PREFIX_V2 . $endpoint;
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
