<?php

namespace App\Tests\Controller\ApiV2\Me;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class MeGetErrorTest extends FixtureWebTestCase
{
    public static function currentUserEndpointNotFoundProvider(): \Generator
    {
        yield 'v1 self' => ['/self'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('currentUserEndpointNotFoundProvider')]
    public function testGetCurrentUserEndpointsNotExists(string $endpoint): void
    {
        $this->loginApiClientUser(self::USER_VIP);
        $uri = self::API_PATH_PREFIX_V2 . $endpoint;
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
