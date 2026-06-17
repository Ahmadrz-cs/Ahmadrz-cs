<?php

namespace App\Tests\Authentication;

use App\Test\FixtureTestCase;

class OAuth2ScopeErrorsTest extends FixtureTestCase
{
    public function testGrantInvalidScope(): void
    {
        $expectedScopes = ['asset:read', 'rock', 'paper', 'scissors'];

        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'client_credentials',
            'scope' => implode(' ', $expectedScopes),
            'client_id' => self::OAUTH2_CLIENT_DEFAULT['clientId'],
            'client_secret' => self::OAUTH2_CLIENT_DEFAULT['clientSecret'],
        ]);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue(array_key_exists('error', $response));
        $this->assertEquals('invalid_scope', $response['error']);
    }
}
