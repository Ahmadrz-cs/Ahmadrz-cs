<?php

namespace App\Tests\Controller\ApiV1\User;

use App\Controller\ApiV1\Response\ErrorResponse;
use App\Entity\User;
use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV1\ApiV1ResponseFields;
use Symfony\Component\HttpFoundation\Response;

class UserGetPermissionTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetUsersAsPublic(): void
    {
        // Check public users cannot use this route
        $uri = self::API_PATH_PREFIX_V1 . '/users';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetUsersAsRegUser(): void
    {
        // Check regular users cannot use this route
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V1 . '/users';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetUserRecordsOwnAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR,
        ])[0];
        $uri = self::API_PATH_PREFIX_V1 . "/users/{$sample->getId()}";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
        $this->assertEquals('success', $apiResponse['outcome']);

        $object = $apiResponse['data']['user'];
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::USER_EXTENDED,
            array_keys($object),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetUserRecordsOtherAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR_2,
        ])[0];
        $uri = self::API_PATH_PREFIX_V1 . "/users/{$sample->getId()}";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $apiResponse['status']);
        $this->assertEquals('fail', $apiResponse['outcome']);
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_INSUFFICIENT_ENTITLEMENTS_FOR_ACTION];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
    }
}
