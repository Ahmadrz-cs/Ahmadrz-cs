<?php

namespace App\Tests\Controller\ApiV2\Scenarios\Kyc;

use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV2\ApiBase64Files;
use Symfony\Component\HttpFoundation\Response;

class KycCreateErrorTest extends FixtureWebTestCase
{
    public function testScenarioKycTriggerMissingNationality(): void
    {
        //create new user without nationality
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/users';
        $content = json_encode([
            'email' => 'john.Ross@test.com',
            'firstName' => 'John',
            'lastName' => 'Ross',
            'password' => 'vsqks1Zqm',
            'dateOfBirth' => '01-01-1985',
            'address' => [
                'address1' => 'Number 20',
                'address2' => 'Block A',
                'address3' => '1 Quiet Lane',
                'region' => 'Camden',
                'city' => 'London',
                'postCode' => 'NW1 0JA',
                'country' => 'United Kingdom',
            ],
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $userId = $apiResponse['id'];

        //add a proof of identity document

        $uri = self::API_PATH_PREFIX_V2 . '/users/' . $userId . '/documents';
        $content = json_encode([
            'fileName' => 'proofOfId.pdf',
            'documentContent' => ApiBase64Files::TEST_PDF,
            'tag' => 'proof_of_identity',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testScenarioKycTriggerMissingBirthdate(): void
    {
        //create new user without birtdate
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/users';
        $content = json_encode([
            'email' => 'john.r@test.com',
            'firstName' => 'John',
            'lastName' => 'Ross',
            'password' => 'vsqks1Zqm',
            'nationality' => 'GB',
            'address' => [
                'address1' => 'Number 20',
                'address2' => 'Block A',
                'address3' => '1 Quiet Lane',
                'region' => 'Camden',
                'city' => 'London',
                'postCode' => 'NW1 0JA',
                'country' => 'United Kingdom',
            ],
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $userId = $apiResponse['id'];

        //add a proof of identity document

        $uri = self::API_PATH_PREFIX_V2 . '/users/' . $userId . '/documents';
        $content = json_encode([
            'fileName' => 'proofOfId.pdf',
            'documentContent' => ApiBase64Files::TEST_PDF,
            'tag' => 'proof_of_identity',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testScenarioKycTriggerMissingAddress(): void
    {
        //create new user without address
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/users';
        $content = json_encode([
            'email' => 'john.r@test.com',
            'firstName' => 'John',
            'lastName' => 'Ross',
            'password' => 'vsqks1Zqm',
            'nationality' => 'GB',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $userId = $apiResponse['id'];

        //add a proof of identity document

        $uri = self::API_PATH_PREFIX_V2 . '/users/' . $userId . '/documents';
        $content = json_encode([
            'fileName' => 'proofOfId.pdf',
            'documentContent' => ApiBase64Files::TEST_PDF,
            'tag' => 'proof_of_identity',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
