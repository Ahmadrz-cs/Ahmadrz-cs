<?php

namespace App\Tests\Controller\ApiV2\User;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserUpdatePermissionsTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<'user update', array{0: '/users/1', 1: array{0: 'user:write'}}, mixed, void>
     */
    public static function userEndpointScopeProvider(): \Generator
    {
        yield 'user update' => ['/users/1', ['user:write']];

        // yield "user document update" => ["/users/1/documents/1", ['user:write']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('userEndpointScopeProvider')]
    public function testUpdateUserEndpointsAsAdminMissingScope(
        $endpoint,
        $requiredScopes,
    ): void {
        $scopes = array_diff($this->permittedScopes, $requiredScopes);
        $this->loginApiClientUser(self::USER_ADMIN, $scopes);
        $uri = self::API_PATH_PREFIX_V2 . $endpoint;
        $content = json_encode([]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUpdateUserOtherAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => 'holly.auto@test.yielderverse.co.uk'],
            true,
        );
        $uri = self::API_PATH_PREFIX_V2 . '/users/' . $sample[0];
        $content = json_encode([]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    // public function testUpdateUserOtherAsPublic(): void
    // {
    //     $this->loginApiClientPublic();
    //     $sample = $this->searchFixtures(
    //         \App\Entity\User::class,
    //         [],
    //         true
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/users/' . $sample[0];
    //     $content = json_encode([]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    // }

    public function testUpdateUserOwnAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => 'ben.auto@test.yielderverse.co.uk'],
            true,
        );
        $uri = self::API_PATH_PREFIX_V2 . '/users/' . $sample[0];
        $fieldset = [
            'email' => 'bene.spaghet@test.com',
            'firstName' => 'Bene',
            'lastName' => 'Spaghet',
        ];
        $content = json_encode($fieldset);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        foreach ($fieldset as $key => $expected) {
            $this->assertEquals($expected, $apiResponse[$key]);
        }
    }

    // public function testUpdateUserOtherDocumentAsRegUser(): void
    // {
    //     $this->loginApiClientUser(self::USER_REGULAR);
    //     $filter = $this->searchFixtures(
    //         \App\Entity\User::class,
    //         ["username" => "holly.auto@test.yielderverse.co.uk"],
    //         true
    //     );
    //     $sample = $this->searchFixtures(
    //         \App\Entity\UserDocument::class,
    //         ["user" => $filter[0]]
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/users/' . $filter[0] . '/documents/' . $sample[0]->getId();
    //     $fieldset = [
    //         "description" => "test this, test that",
    //         "tag" => "some other tag",
    //     ];
    //     $content = json_encode($fieldset);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    // }
    // public function testUpdateUserOtherDocumentAsPublic(): void
    // {
    //     $this->loginApiClientPublic();
    //     $sample = $this->searchFixtures(\App\Entity\UserDocument::class);
    //     $uri = self::API_PATH_PREFIX_V2 . '/users/' . $sample[0]->getUser()->getId() . '/documents/' . $sample[0]->getId();
    //     $fieldset = [
    //         "description" => "test this, test that",
    //         "tag" => "some other tag",
    //     ];
    //     $content = json_encode($fieldset);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    // }
    // public function testUpdateUserOwnDocumentAsRegUser(): void
    // {
    //     $this->loginApiClientUser(self::USER_REGULAR);
    //     $filter = $this->searchFixtures(
    //         \App\Entity\User::class,
    //         ["username" => "ben.auto@test.yielderverse.co.uk"],
    //         true
    //     );
    //     $sample = $this->searchFixtures(
    //         \App\Entity\UserDocument::class,
    //         ["user" => $filter[0]]
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/users/' . $filter[0] . '/documents/' . $sample[0]->getId();
    //     $fieldset = [
    //         "description" => "test this, test that",
    //         "tag" => "some other tag",
    //     ];
    //     $content = json_encode($fieldset);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    //     $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
    //     foreach ($fieldset as $key => $expected) {
    //         $this->assertEquals($expected, $apiResponse[$key]);
    //     }
    // }
}
