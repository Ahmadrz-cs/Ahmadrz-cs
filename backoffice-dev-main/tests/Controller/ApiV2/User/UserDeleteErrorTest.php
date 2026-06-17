<?php

namespace App\Tests\Controller\ApiV2\User;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserDeleteErrorTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<string, array{0: string}, mixed, void>
     */
    public static function undeletableUserEndpointsProvider(): \Generator
    {
        yield 'user collection' => ['/users'];
        yield 'user single' => ['/users/1'];
        yield 'user documents' => ['/users/1/documents'];
        yield 'user offerings' => ['/users/1/offerings'];
        yield 'user investments' => ['/users/1/investments'];
        yield 'user payouts' => ['/users/1/payouts'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('undeletableUserEndpointsProvider')]
    public function testDeleteUserEndpointsNotAllowed($endpoint): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . $endpoint;

        $this->client->request('DELETE', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function testDeleteUserDocumentNotExists(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/user/1/documents/-1';
        $this->client->request('DELETE', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
