<?php

namespace App\Tests\Controller\ApiV1\User;

use App\Controller\ApiV1\Response\ErrorResponse;
use App\Entity\User;
use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserPatchPermissionTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testUpdateUserOtherAsAdmin(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR,
        ])[0];
        $uri = self::API_PATH_PREFIX_V1 . "/users/{$sample->getId()}";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $changes = [
            'first_name' => 'Franklin',
            'last_name' => 'Hall',
        ];
        $content = json_encode($changes);
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
        $this->assertEquals('success', $apiResponse['outcome']);

        // Verify that the user's information has changed
        /** @var User $sample */
        $sample = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR,
        ])[0];
        $this->assertEquals($changes['first_name'], $sample->getFirstname());
        $this->assertEquals($changes['last_name'], $sample->getLastname());
    }

    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testUpdateUserOwnAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR,
        ])[0];
        $uri = self::API_PATH_PREFIX_V1 . "/users/{$sample->getId()}";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $changes = [
            'first_name' => 'Franklin',
            'last_name' => 'Hall',
        ];
        $content = json_encode($changes);
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
        $this->assertEquals('success', $apiResponse['outcome']);

        // Verify that the user's information has changed
        /** @var User $sample */
        $sample = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR,
        ])[0];
        $this->assertEquals($changes['first_name'], $sample->getFirstname());
        $this->assertEquals($changes['last_name'], $sample->getLastname());
    }

    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testUpdateUserOtherAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR_2,
        ])[0];
        $uri = self::API_PATH_PREFIX_V1 . "/users/{$sample->getId()}";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $changes = [
            'first_name' => 'Franklin',
            'last_name' => 'Hall',
        ];
        $content = json_encode($changes);
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        // Should really be given an 403 forbidden for an authorisation issue
        // i.e. the server knows who you are, but you lack the permissions
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $apiResponse['status']);
        $this->assertEquals('fail', $apiResponse['outcome']);
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_USER_UPDATE_FAILED];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );

        // Verify that the user's information has changed
        /** @var User $sample */
        $after = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR_2,
        ])[0];
        $this->assertEquals($sample->getFirstname(), $after->getFirstname());
        $this->assertEquals($sample->getLastname(), $after->getLastname());
    }
}
