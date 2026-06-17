<?php

namespace App\Test;

use App\Test\FixtureTestCase;

abstract class FixtureWebTestCase extends FixtureTestCase
{
    protected const API_PATH_PREFIX_V1 = '/v1/yielders';
    protected const API_PATH_PREFIX_V2 = '/v2/yielders';
    protected const OAUTH2_PATH_TOKEN = '/oauth2/token';
    protected const OAUTH2_PATH_AUTHORIZE = '/oauth2/authorize';
    protected const OAUTH2_PATH_LOGOUT = '/oauth2/logout';

    protected array $permittedScopes = [];

    protected function setUp(): void
    {
        parent::setUp();
        $scopes = static::getContainer()->getParameter('oauth2.scopes');
        if (!is_array($scopes)) {
            $this->fail('No oauth2 scopes defined in parameters');
        }
        $this->permittedScopes = $scopes;
    }

    protected function loginApiClientPublic(
        ?array $scopes = null,
        array $clientCredentials = self::OAUTH2_CLIENT_DEFAULT,
    ): void {
        $scopes = $scopes ?? $this->permittedScopes;
        $this->client->request('POST', self::OAUTH2_PATH_TOKEN, [
            'grant_type' => 'client_credentials',
            'scope' => implode(' ', $scopes),
            'client_id' => $clientCredentials['clientId'],
            'client_secret' => $clientCredentials['clientSecret'],
        ]);
        $response = $this->client->getResponse()->getContent();
        if (!is_string($response)) {
            $this->fail('Could not create authenticated client for test');
        }
        $response = json_decode($response, true);
        $this->client->setServerParameter('HTTP_Authorization', sprintf(
            'Bearer %s',
            $response['access_token'],
        ));
    }

    protected function loginApiClientUser(
        string $userIdentifer,
        ?array $scopes = null,
        array $clientCredentials = self::OAUTH2_CLIENT_DEFAULT,
    ): void {
        $scopes = $scopes ?? $this->permittedScopes;
        $this->sendLoginRequest(
            $userIdentifer,
            self::USER_PASSWORD_STANDARD,
            $scopes ?? $this->permittedScopes,
            $clientCredentials,
        );
        $response = $this->client->getResponse()->getContent();
        if (!is_string($response)) {
            $this->fail('Could not create authenticated client for test');
        }
        $response = json_decode($response, true);
        $this->client->setServerParameter('HTTP_Authorization', sprintf(
            'Bearer %s',
            $response['access_token'],
        ));
    }

    protected function loginWebClient(string $userIdentifer): void
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $em = static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class);

        $user = $em->getRepository(\App\Entity\User::class)->findOneBy([
            'username' => $userIdentifer,
        ]);
        if (!$user instanceof \App\Entity\User) {
            $this->fail('Could not find user: ' . $userIdentifer);
        }

        // https://symfony.com/doc/current/testing.html#logging-in-users-authentication
        $this->client->loginUser($user);
    }

    protected function sendLoginRequest(
        string $userIdentifer,
        string $password = self::USER_PASSWORD_STANDARD,
        ?array $scopes = null,
        array $clientCredentials = self::OAUTH2_CLIENT_DEFAULT,
    ): void {
        $scopes = $scopes ?? $this->permittedScopes;
        $this->client->request('POST', self::OAUTH2_PATH_TOKEN, [
            'grant_type' => 'password',
            'scope' => implode(' ', $scopes),
            'client_id' => $clientCredentials['clientId'],
            'client_secret' => $clientCredentials['clientSecret'],
            'username' => $userIdentifer,
            'password' => $password,
        ]);
    }
}
