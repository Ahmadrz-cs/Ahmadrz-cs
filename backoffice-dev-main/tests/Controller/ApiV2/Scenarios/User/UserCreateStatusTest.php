<?php

namespace App\Tests\Controller\ApiV2\Scenarios\User;

use App\Entity\Lifecycle\UserLifecycle;
use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserCreateStatusTest extends FixtureWebTestCase
{
    public function testScenarioUserEmaiUnverified(): void
    {
        //create new user
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/users';
        $content = json_encode([
            'email' => 'john.doe1@test2.com',
            'firstName' => 'John',
            'lastName' => 'doe',
            'password' => 'vsqks1Zqm',
            'dateOfBirth' => '01-01-1980',
            'nationality' => 'GB',
            'address' => [
                'address1' => 'Number 20',
                'address2' => 'Block A',
                'address3' => '1 Quiet Lane',
                'region' => 'Camden',
                'city' => 'London',
                'postCode' => 'NW1 0JA',
                'country' => 'GB',
            ],
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $userId = $apiResponse['id'];

        //get user object
        $user = $this->searchFixtures(\App\Entity\User::class, ['id' => $userId])[0];

        $this->assertEquals(
            UserLifecycle::STATE_EMAIL_NOT_VERIFIED,
            $user->getLifecycleStatus(),
        );
    }

    public function testScenarioUserEmailVerified(): void
    {
        //create new user
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/users';
        $content = json_encode([
            'email' => 'john.doe2@test3.com',
            'firstName' => 'John',
            'lastName' => 'doe',
            'password' => 'vsqks1Zqm',
            'dateOfBirth' => '01-01-1980',
            'nationality' => 'GB',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $userId = $apiResponse['id'];

        //verify email address
        $uri = self::API_PATH_PREFIX_V2 . '/users/' . $userId . '/email-verification';
        $this->client->request('POST', $uri, [], []);

        //get user object
        $user = $this->searchFixtures(\App\Entity\User::class, ['id' => $userId])[0];

        $this->assertEquals(
            UserLifecycle::STATE_EMAIL_VERIFIED,
            $user->getLifecycleStatus(),
        );
    }
}
