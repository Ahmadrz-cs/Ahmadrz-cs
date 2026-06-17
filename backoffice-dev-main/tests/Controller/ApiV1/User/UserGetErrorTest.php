<?php

namespace App\Tests\Controller\ApiV1\User;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserGetErrorTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testGetUsersPaginationInvalid(): void
    {
        // Check query parameter strict requirements
        $this->loginApiClientUser(self::USER_SUPER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/users';
        $parameters = [
            'offset' => 'a',
            'limit' => 3,
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testGetUsersCriteriaInvalid(): void
    {
        // Check query parameter strict requirements
        $this->loginApiClientUser(self::USER_SUPER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/users';
        $parameters = [
            'id' => implode('.', ['a', 8, 16, 22]),
            'limit' => 3,
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testGetUsersSortInvalid(): void
    {
        // Check sort parameter strict requirements
        $this->loginApiClientUser(self::USER_SUPER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/users';
        $parameters = [
            'sort' => implode(',', ['-id', '%name']),
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
