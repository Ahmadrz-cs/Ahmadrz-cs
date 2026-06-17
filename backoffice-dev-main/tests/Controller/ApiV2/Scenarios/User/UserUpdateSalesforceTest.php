<?php

namespace App\Tests\Controller\ApiV2\Scenarios\User;

use App\Test\ExternalServiceWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserUpdateSalesforceTest extends ExternalServiceWebTestCase
{
    public function testScenarioUserUpdateEmailNotVeified(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/users';
        $content = json_encode([
            'email' => 'john.doe5@test9.com',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $userId = $apiResponse['id'];

        $uri = self::API_PATH_PREFIX_V2 . '/users/' . $userId;
        $content = json_encode([
            'firstName' => 'John',
            'lastName' => 'Dole',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $user = $this->searchFixtures(\App\Entity\User::class, ['id' => $userId])[0];

        $this->assertEmpty($user->findCustomFieldValue('salesforce_id'));
    }

    public function testScenarioUserUpdateEmailVeified(): void
    {
        $this->useSalesforceServiceMock();

        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/users';
        $content = json_encode([
            'email' => 'john.doe3@test15.com',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $userId = $apiResponse['id'];

        $uri = self::API_PATH_PREFIX_V2 . '/users/' . $userId;
        $content = json_encode([
            'firstName' => 'John',
            'lastName' => 'Dole',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $user = $this->searchFixtures(\App\Entity\User::class, ['id' => $userId])[0];

        $this->assertEmpty($user->findCustomFieldValue('salesforce_id'));

        $uri = self::API_PATH_PREFIX_V2 . '/users/' . $userId . '/email-verification';
        $this->client->request('POST', $uri, [], []);

        $user = $this->searchFixtures(\App\Entity\User::class, ['id' => $userId])[0];

        $this->assertNotEmpty($user->findCustomFieldValue('salesforce_id'));
    }
}
