<?php

namespace App\Tests\Authentication;

use App\Test\FixtureTestCase;

class OAuth2GrantErrorsTest extends FixtureTestCase
{
    public function testGrantInvalidClientId(): void
    {
        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'client_credentials',
            'scope' => 'asset:read',
            'client_id' => substr(self::OAUTH2_CLIENT_DEFAULT['clientId'], 1, 1),
            'client_secret' => self::OAUTH2_CLIENT_DEFAULT['clientSecret'],
        ]);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->checkAuthenticationError('invalid_client', $response);
    }

    public function testGrantInvalidClientSecret(): void
    {
        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'client_credentials',
            'scope' => 'asset:read',
            'client_id' => self::OAUTH2_CLIENT_DEFAULT['clientId'],
            'client_secret' => substr(
                self::OAUTH2_CLIENT_DEFAULT['clientSecret'],
                1,
                1,
            ),
        ]);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->checkAuthenticationError('invalid_client', $response);
    }

    public function testGrantInvalidPassword(): void
    {
        $username = 'superadmin@test.yielderverse.co.uk';
        $password = 'HarvestBounty!756';

        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'password',
            'scope' => 'asset:read',
            'client_id' => self::OAUTH2_CLIENT_DEFAULT['clientId'],
            'client_secret' => self::OAUTH2_CLIENT_DEFAULT['clientSecret'],
            'username' => $username,
            'password' => substr($password, 1, 1),
        ]);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->checkAuthenticationError('invalid_grant', $response);
    }

    public function testGrantInvalidUsername(): void
    {
        $username = 'cookies@ndcream.co.uk';
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
        $this->checkAuthenticationError('invalid_grant', $response);
    }

    protected function checkAuthenticationError(
        string $errorMessage,
        array $responseBody,
    ): void {
        $this->assertTrue(array_key_exists('error', $responseBody));
        $this->assertEquals($errorMessage, $responseBody['error']);
    }
}
