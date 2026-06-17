<?php

namespace App\Tests\Controller\ApiV2\User;

use App\Entity\User;
use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV2\ApiBase64Files;
use App\Tests\Controller\ApiV2\ApiResponseFields;
use Symfony\Component\HttpFoundation\Response;

class UserCreatePermissionsTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<'user creation'|'user document creation', array{0: '/users'|'/users/1/documents', 1: array{0: 'user:write'}}, mixed, void>
     */
    public static function userEndpointScopeProvider(): \Generator
    {
        yield 'user creation' => ['/users', ['user:write']];
        yield 'user document creation' => ['/users/1/documents', ['user:write']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('userEndpointScopeProvider')]
    public function testCreateUserEndpointsAsAdminMissingScope(
        $endpoint,
        $requiredScopes,
    ): void {
        $scopes = array_diff($this->permittedScopes, $requiredScopes);
        $this->loginApiClientUser(self::USER_ADMIN, $scopes);
        $uri = self::API_PATH_PREFIX_V2 . $endpoint;
        $content = json_encode([
            'email' => 'lily.renoir@test.com',
            'firstName' => 'Lily',
            'lastName' => 'Renoir',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    // public function testCreateUserAsRegUser(): void
    // {
    //     $this->loginApiClientUser(self::USER_REGULAR);
    //     $uri = self::API_PATH_PREFIX_V2 . '/users';
    //     $content = json_encode([
    //         'email' => 'lily.renoir@test.com',
    //         'firstName' => 'Lily',
    //         'lastName' => 'Renoir'
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    // }

    public function testCreateUserAsPublic(): void
    {
        $this->loginApiClientPublic();
        $uri = self::API_PATH_PREFIX_V2 . '/users';
        $content = json_encode([
            'email' => 'lily.renoir@test.com',
            'firstName' => 'Lily',
            'lastName' => 'Renoir',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    // public function testCreateUserDocumentOtherAsRegUser(): void
    // {
    //     $this->loginApiClientUser(self::USER_REGULAR);
    //     $sample = $this->searchFixtures(
    //         \App\Entity\User::class,
    //         ["username" => "holly.auto@test.yielderverse.co.uk"],
    //         true
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/users/' . $sample[0] . '/documents';
    //     $content = json_encode([
    //         'fileName' => 'proofOfId.pdf',
    //         'documentContent' => "",
    //         'tag' => 'proof_of_identity'
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    // }

    // public function testCreateUserDocumentAsPublic(): void
    // {
    //     $this->loginApiClientPublic();
    //     $sample = $this->searchFixtures(
    //         \App\Entity\User::class,
    //         ["username" => "holly.auto@test.yielderverse.co.uk"],
    //         true
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/users/' . $sample[0] . '/documents';
    //     $content = json_encode([
    //         'fileName' => 'proofOfId.pdf',
    //         'documentContent' => "",
    //         'tag' => 'proof_of_identity'
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    // }

    public function testCreateUserDocumentOwnAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => 'ben.auto@test.yielderverse.co.uk'],
            true,
        );
        $uri = self::API_PATH_PREFIX_V2 . '/users/' . $sample[0] . '/documents';
        $content = json_encode([
            'fileName' => 'proofOfId.pdf',
            'documentContent' => ApiBase64Files::TEST_PDF,
            'tag' => 'proof_of_address',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function testCreateUserBankwirePayinAsPublic(): void
    {
        $this->loginApiClientPublic();
        $sample = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => 'ben.auto@test.yielderverse.co.uk'],
            true,
        );
        $uri = self::API_PATH_PREFIX_V2 . '/users/' . $sample[0] . '/payin';
        $content = json_encode([
            'amount' => 100,
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCreateUserBankwirePayinOwnAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => 'ben.auto@test.yielderverse.co.uk'],
            true,
        );
        $uri = self::API_PATH_PREFIX_V2 . '/users/' . $sample[0] . '/payin';
        $content = json_encode([
            'amount' => 100,
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function testCreateUserBankwirePayinOtherAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => 'holly.auto@test.yielderverse.co.uk'],
            true,
        );
        $uri = self::API_PATH_PREFIX_V2 . '/users/' . $sample[0] . '/payin';
        $content = json_encode([
            'amount' => 100,
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCreateUserFieldsManagedByAsVendor(): void
    {
        $scopes = array_diff($this->permittedScopes, ['asset:write', 'payout:write']);
        $this->loginApiClientUser(
            self::USER_VENDOR,
            $scopes,
            self::OAUTH2_CLIENT_VENDOR,
        );
        $uri = self::API_PATH_PREFIX_V2 . '/users';
        $content = json_encode([
            'email' => 'lily.renoir@test.com',
            'firstName' => 'Lily',
            'lastName' => 'Renoir',
            'password' => 'vsqks1Zqm',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::USER_STANDARD;
        $actualFields = array_keys($apiResponse);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);

        /** @var User $newUser */
        $newUser = $this->entityManager
            ->getRepository(User::class)
            ->find($apiResponse['id']);

        /** @var User $manager */
        $manager = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_VENDOR]);

        $this->assertEquals(true, $newUser->isEnabled());
        // Check that the vendor is set as the manager for the new user
        // print_r($newUser->getUsername());
        $this->assertNotNull($newUser->getManagedBy());
        $this->assertEquals($manager->getId(), $newUser->getManagedBy()->getId());
        $this->assertEquals('vendaland', $newUser->getReferralCode());
    }
}
