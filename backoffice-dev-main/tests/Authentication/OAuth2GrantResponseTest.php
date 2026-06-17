<?php

namespace App\Tests\Authentication;

use App\Test\FixtureTestCase;

class OAuth2GrantResponseTest extends FixtureTestCase
{
    public function testClientCredentialsGrant(): void
    {
        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'client_credentials',
            'scope' => 'asset:read',
            'client_id' => self::OAUTH2_CLIENT_DEFAULT['clientId'],
            'client_secret' => self::OAUTH2_CLIENT_DEFAULT['clientSecret'],
        ]);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        // var_dump($this->client->getResponse());
        $this->checkAuthResponseFields('client_credentials', $response);
    }

    public function testPasswordGrant(): void
    {
        $username = 'superadmin@test.yielderverse.co.uk';
        $password = 'HarvestBounty!756';

        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'password',
            'scope' => 'asset:read',
            'client_id' => self::OAUTH2_CLIENT_DEFAULT['clientId'],
            'client_secret' => self::OAUTH2_CLIENT_DEFAULT['clientSecret'],
            'username' => $username,
            'password' => $password,
        ]);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->checkAuthResponseFields('password', $response);
    }

    public function testRefreshGrant(): void
    {
        /**
         * Use password grant to get the initial set of tokens, including refresh token
         * Then make second auth request with the refresh_token grant
         */
        $username = 'superadmin@test.yielderverse.co.uk';
        $password = 'HarvestBounty!756';

        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'password',
            'scope' => 'asset:read',
            'client_id' => self::OAUTH2_CLIENT_DEFAULT['clientId'],
            'client_secret' => self::OAUTH2_CLIENT_DEFAULT['clientSecret'],
            'username' => $username,
            'password' => $password,
        ]);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $refreshToken = $response['refresh_token'];

        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'refresh_token',
            'scope' => 'asset:read',
            'client_id' => self::OAUTH2_CLIENT_DEFAULT['clientId'],
            'client_secret' => self::OAUTH2_CLIENT_DEFAULT['clientSecret'],
            'refresh_token' => $refreshToken,
        ]);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->checkAuthResponseFields('refresh_token', $response);
    }

    protected function checkAuthResponseFields(
        string $grantType,
        ?array $responseBody,
    ): void {
        $expectedFields = [
            'token_type',
            'expires_in',
            'access_token',
        ];
        if ($grantType != 'client_credentials') {
            $expectedFields[] = 'refresh_token';
        }

        $this->assertEqualsCanonicalizing($expectedFields, array_keys($responseBody));
    }
}
