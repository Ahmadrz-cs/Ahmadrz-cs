<?php

namespace App\Tests\Controller\ApiV2\User;

use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV2\ApiResponseFields;
use Symfony\Component\HttpFoundation\Response;

class UserGetPermissionsTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<'user single other', array{0: ''}, mixed, void>
     */
    public static function userEndpointPermissionProvider(): \Generator
    {
        yield 'user single other' => [''];

        // yield "user offerings other" => ["/offerings"];
        // yield "user investments other" => ["/investments"];
        // yield "user payouts other" => ["/payouts"];
        // yield "user documents other" => ["/documents"];
        // yield "user document single other" => ["/documents/"];
    }

    /**
     * @psalm-return \Generator<'user collection'|'user single', array{0: '/users'|'/users/1', 1: array{0: 'user:read'}}, mixed, void>
     */
    public static function userEndpointScopeProvider(): \Generator
    {
        yield 'user collection' => ['/users', ['user:read']];
        yield 'user single' => ['/users/1', ['user:read']];

        // yield "user offerings" => ["/users/1/offerings", ['user:read', 'offering:read']];
        // yield "user investments" => ["/users/1/investments", ['user:read', 'investment:read']];
        // yield "user payouts" => ["/users/1/payouts", ['user:read', 'payout:read']];
        // yield "user documents" => ["/users/1/documents", ['user:read']];
        // yield "user document single" => ["/users/1/documents/1", ['user:read']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('userEndpointScopeProvider')]
    public function testGetUserEndpointsAsAdminMissingScope(
        $endpoint,
        $requiredScopes,
    ): void {
        $scopes = array_diff($this->permittedScopes, $requiredScopes);
        $this->loginApiClientUser(self::USER_ADMIN, $scopes);
        $uri = self::API_PATH_PREFIX_V2 . $endpoint;
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGetUsersAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V2 . '/users';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGetUsersAsPublic(): void
    {
        $this->loginApiClientPublic();
        $uri = self::API_PATH_PREFIX_V2 . '/users';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('userEndpointPermissionProvider')]
    public function testGetUserEndpointsAsRegUser($endpoint): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $filter = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => 'holly.auto@test.yielderverse.co.uk'],
            true,
        );
        if ($endpoint === '/documents/') {
            $sample = $this->searchFixtures(\App\Entity\UserDocument::class, [
                'user' => $filter[0],
            ]);
            $endpoint = '/documents/' . $sample[0]->getId();
        }
        $uri = self::API_PATH_PREFIX_V2 . '/users/' . $filter[0] . $endpoint;
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('userEndpointPermissionProvider')]
    public function testGetUserEndpointsAsPublic($endpoint): void
    {
        $this->loginApiClientPublic();
        $filter = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => 'holly.auto@test.yielderverse.co.uk'],
            true,
        );
        if ($endpoint === '/documents/') {
            $sample = $this->searchFixtures(\App\Entity\UserDocument::class, [
                'user' => $filter[0],
            ]);
            $endpoint = '/documents/' . $sample[0]->getId();
        }
        $uri = self::API_PATH_PREFIX_V2 . '/users/' . $filter[0] . $endpoint;
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('userEndpointPermissionProvider')]
    public function testGetUserEndpointsOwnAsRegUser($endpoint): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $filter = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => 'ben.auto@test.yielderverse.co.uk'],
            true,
        );
        if ($endpoint === '/documents/') {
            $sample = $this->searchFixtures(\App\Entity\UserDocument::class, [
                'user' => $filter[0],
            ]);
            $endpoint = '/documents/' . $sample[0]->getId();
        }
        $uri = self::API_PATH_PREFIX_V2 . '/users/' . $filter[0] . $endpoint;
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testGetSingleUserOwnQueryViewAdminAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => 'ben.auto@test.yielderverse.co.uk'],
            true,
        );
        $uri = self::API_PATH_PREFIX_V2 . '/users/' . $sample[0];
        $parameters = [
            'view' => 'admin',
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::USER_STANDARD;
        $actualFields = array_keys($apiResponse);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    }

    public function testGetNotOwnWalletAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(\App\Entity\User::class, [
            'username' => 'jim.auto@test.yielderverse.co.uk',
        ]);
        $uri =
            self::API_PATH_PREFIX_V2
            . '/users/'
            . $sample[0]->getId()
            . '/wallets/'
            . $sample[0]->getMangoPayWalletId();
        $this->client->request('GET', $uri);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGetNotOwnWalletAsAdmin(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(\App\Entity\User::class, [
            'username' => 'jim.auto@test.yielderverse.co.uk',
        ]);
        $uri =
            self::API_PATH_PREFIX_V2
            . '/users/'
            . $sample[0]->getId()
            . '/wallets/'
            . $sample[0]->getMangoPayWalletId();
        $this->client->request('GET', $uri);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
}
