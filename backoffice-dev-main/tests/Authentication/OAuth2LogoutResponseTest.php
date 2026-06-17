<?php

namespace App\Tests\Authentication;

use App\Test\FixtureWebTestCase;

class OAuth2LogoutResponseTest extends FixtureWebTestCase
{
    public function testLogout(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $this->client->followRedirects();
        $uri = self::OAUTH2_PATH_LOGOUT;
        $this->client->request('GET', $uri, ['continue_url' => 'https://example.com']);
        $this->assertResponseIsSuccessful();
    }
}
