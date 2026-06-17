<?php

namespace App\Tests\Authentication;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class OAuth2LogoutErrorTest extends FixtureWebTestCase
{
    public function testLogoutNoContinueUrl(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $this->client->followRedirects();
        $uri = self::OAUTH2_PATH_LOGOUT;
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testLogoutInvalidUrl(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $this->client->followRedirects();
        $uri = self::OAUTH2_PATH_LOGOUT;
        $this->client->request('GET', $uri, ['continue_url' => 'abc123']);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
