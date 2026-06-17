<?php

namespace App\Tests\Authentication;

use App\Test\FixtureTestCase;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;

class OAuth2ScopeResponseTest extends FixtureTestCase
{
    public function testGrantSingleScope(): void
    {
        $expectedScopes = ['asset:read'];

        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'client_credentials',
            'scope' => implode(' ', $expectedScopes),
            'client_id' => self::OAUTH2_CLIENT_DEFAULT['clientId'],
            'client_secret' => self::OAUTH2_CLIENT_DEFAULT['clientSecret'],
        ]);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $accessToken = $response['access_token'];
        /** @var \Lcobucci\JWT\Token\Plain $token */
        $token = new Parser(new JoseEncoder())->parse((string) $accessToken);
        $actualScopes = $token->claims()->get('scopes');

        $this->assertEqualsCanonicalizing($expectedScopes, $actualScopes);
    }

    public function testGrantMultipleScopes(): void
    {
        $expectedScopes = [
            'asset:read',
            'asset:write',
            'offering:read',
            'offering:write',
        ];

        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'client_credentials',
            'scope' => implode(' ', $expectedScopes),
            'client_id' => self::OAUTH2_CLIENT_DEFAULT['clientId'],
            'client_secret' => self::OAUTH2_CLIENT_DEFAULT['clientSecret'],
        ]);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $accessToken = $response['access_token'];
        /** @var \Lcobucci\JWT\Token\Plain $token */
        $token = new Parser(new JoseEncoder())->parse((string) $accessToken);
        $actualScopes = $token->claims()->get('scopes');

        $this->assertEqualsCanonicalizing($expectedScopes, $actualScopes);
    }
}
