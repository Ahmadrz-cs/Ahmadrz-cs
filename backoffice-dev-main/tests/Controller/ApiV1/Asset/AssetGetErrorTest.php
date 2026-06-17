<?php

namespace App\Tests\Controller\ApiV1\Asset;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AssetGetErrorTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testGetAssetsPaginationInvalid(): void
    {
        $this->loginApiClientUser(self::USER_SUPER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/assets';
        $parameters = [
            'offset' => 'a',
            'limit' => 3,
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testGetAssetsCriteriaInvalid(): void
    {
        $this->loginApiClientUser(self::USER_SUPER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/assets';
        $parameters = [
            'id' => implode('.', ['a', 8, 16, 22]),
            'limit' => 3,
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('error
Check sort parameter strict requirements')]
    public function testGetAssetsSortInvalid(): void
    {
        $this->loginApiClientUser(self::USER_SUPER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/assets';
        $parameters = [
            'sort' => implode(',', ['-id', '%name']),
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
