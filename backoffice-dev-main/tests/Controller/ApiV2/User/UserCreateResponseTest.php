<?php

namespace App\Tests\Controller\ApiV2\User;

use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV2\ApiBase64Files;
use App\Tests\Controller\ApiV2\ApiResponseFields;
use Symfony\Component\HttpFoundation\Response;

class UserCreateResponseTest extends FixtureWebTestCase
{
    public function testCreateUser(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/users';
        $content = json_encode([
            'email' => 'lily.renoir@test.com',
            'firstName' => 'Lily',
            'lastName' => 'Renoir',
            'password' => 'vsqks1Zqm',
            'referralCode' => 'yielderverseApiTest',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::USER_STANDARD;
        $actualFields = array_keys($apiResponse);
        // echo PHP_EOL;
        // print_r("count of expected: " . count($expectedFields));
        // echo PHP_EOL;
        // print_r("count of actual: " . count($actualFields));
        // echo PHP_EOL;
        // print_r($expectedFields);
        // echo PHP_EOL;
        // print_r($actualFields);
        // echo PHP_EOL;
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);

        $user = $this->searchFixtures(\App\Entity\User::class, [
            'id' => $apiResponse['id'],
        ])[0];

        $this->assertEquals(true, $user->isEnabled());
        $this->assertEquals('yielderverseApiTest', $user->getReferralCode());
        // Check that no manager is set for the new user
        $this->assertNull($user->getManagedBy());
    }

    public function testCreateUserFieldsRequired(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
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

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::USER_STANDARD;
        $actualFields = array_keys($apiResponse);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    }

    public function testCreateUserDocument(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/users/1/documents';
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

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::DOCUMENT_STANDARD;
        $actualFields = array_keys($apiResponse);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);

        $this->assertEquals('proofOfId.pdf', $apiResponse['fileName']);
        $this->assertEquals('proof_of_address', $apiResponse['tag']);
        $this->assertEquals('user/1/', substr($apiResponse['url'], 0, 7));
    }
}
